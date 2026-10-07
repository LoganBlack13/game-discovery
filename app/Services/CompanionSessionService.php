<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\GameSessionSource;
use App\Enums\SessionDetectionSource;
use App\Enums\SessionEndReason;
use App\Models\CompanionDevice;
use App\Models\Game;
use App\Models\GameExecutableMapping;
use App\Models\GameSession;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Records the session snapshots sent by Questlog Companion (fiche A2). A snapshot is the full state of a
 * session at a given time: replaying it never changes anything, and an older one never moves a session back.
 */
final readonly class CompanionSessionService
{
    public const int CLOCK_SKEW_TOLERANCE_SECONDS = 120;

    public const int DURATION_TOLERANCE_SECONDS = 60;

    public const int MAX_DURATION_HOURS = 48;

    public const int MAX_AGE_DAYS = 30;

    public const int FUTURE_TOLERANCE_MINUTES = 5;

    public const int STALE_AFTER_MINUTES = 15;

    public function __construct(private PersonalTrackingService $tracking) {}

    /**
     * @param  array{game_id: int, started_at: string, last_heartbeat_at: string, ended_at?: string|null, active_seconds: int, idle_seconds: int, end_reason?: string|null, detection_source: string, mapping_id?: int|null, client_sent_at: string}  $snapshot
     * @return array{session: GameSession, created: bool}
     */
    public function record(CompanionDevice $device, string $sessionId, array $snapshot): array
    {
        $times = $this->correctedTimes($snapshot);
        $this->assertCoherent($times, $snapshot['active_seconds'], $snapshot['idle_seconds']);

        $user = $device->user;
        assert($user instanceof User);

        return DB::transaction(function () use ($device, $user, $sessionId, $snapshot, $times): array {
            $session = GameSession::query()->lockForUpdate()->find($sessionId);
            $created = false;

            if ($session === null) {
                $game = Game::query()->find($snapshot['game_id']);
                abort_if($game === null, 404, 'Unknown game.');

                $session = GameSession::query()->createOrFirst(['id' => $sessionId], [
                    'user_id' => $user->id,
                    'game_id' => $game->id,
                    'companion_device_id' => $device->id,
                    'game_executable_mapping_id' => $this->availableMappingId($user, $snapshot['mapping_id'] ?? null),
                    'source' => GameSessionSource::QuestlogCompanion,
                    'detection_source' => SessionDetectionSource::from($snapshot['detection_source']),
                    'started_at' => $times['started_at'],
                    'last_heartbeat_at' => $times['last_heartbeat_at'],
                    'ended_at' => $times['ended_at'],
                    'active_seconds' => $snapshot['active_seconds'],
                    'idle_seconds' => $snapshot['idle_seconds'],
                    'end_reason' => $times['ended_at'] instanceof CarbonImmutable ? $snapshot['end_reason'] ?? null : null,
                ]);
                $created = $session->wasRecentlyCreated;
            }

            abort_if($session->companion_device_id !== $device->id, 403, 'This session belongs to another device.');

            if ($created) {
                $this->supersedeOpenSessions($session);
            } else {
                $this->update($session, $snapshot, $times);
            }

            $this->applyTrackingEffects($session, $user);

            return ['session' => $session, 'created' => $created];
        });
    }

    /**
     * Closes sessions left open for more than 15 minutes without heartbeat (fiche A2, F-06).
     */
    public function closeStaleSessions(): int
    {
        $closed = 0;

        GameSession::query()
            ->open()
            ->where('last_heartbeat_at', '<', now()->subMinutes(self::STALE_AFTER_MINUTES))
            ->lazyById()
            ->each(function (GameSession $session) use (&$closed): void {
                $session->closeAtLastHeartbeat(SessionEndReason::Timeout);
                $closed++;
            });

        return $closed;
    }

    /**
     * Shifts the client timestamps when its clock is off by more than two minutes (fiche A2, R-05).
     *
     * @param  array{started_at: string, last_heartbeat_at: string, ended_at?: string|null, client_sent_at: string, ...}  $snapshot
     * @return array{started_at: CarbonImmutable, last_heartbeat_at: CarbonImmutable, ended_at: CarbonImmutable|null}
     */
    private function correctedTimes(array $snapshot): array
    {
        $skew = now()->getTimestamp() - CarbonImmutable::parse($snapshot['client_sent_at'])->getTimestamp();
        if (abs($skew) <= self::CLOCK_SKEW_TOLERANCE_SECONDS) {
            $skew = 0;
        }

        $endedAt = $snapshot['ended_at'] ?? null;

        return [
            'started_at' => CarbonImmutable::parse($snapshot['started_at'])->utc()->addSeconds($skew),
            'last_heartbeat_at' => CarbonImmutable::parse($snapshot['last_heartbeat_at'])->utc()->addSeconds($skew),
            'ended_at' => $endedAt === null ? null : CarbonImmutable::parse($endedAt)->utc()->addSeconds($skew),
        ];
    }

    /**
     * @param  array{started_at: CarbonImmutable, last_heartbeat_at: CarbonImmutable, ended_at: CarbonImmutable|null}  $times
     */
    private function assertCoherent(array $times, int $activeSeconds, int $idleSeconds): void
    {
        $started = $times['started_at'];
        $end = $times['ended_at'] ?? $times['last_heartbeat_at'];
        $errors = [];

        if ($started->gt(now()->addMinutes(self::FUTURE_TOLERANCE_MINUTES))) {
            $errors['started_at'] = 'The session cannot start in the future.';
        } elseif ($started->lt(now()->subDays(self::MAX_AGE_DAYS))) {
            $errors['started_at'] = 'The session is too old to be recorded.';
        }

        if ($times['last_heartbeat_at']->lt($started) || $end->lt($started)) {
            $errors['last_heartbeat_at'] = 'The session cannot end before it starts.';
        } elseif ($started->diffInHours($end) > self::MAX_DURATION_HOURS) {
            $errors['ended_at'] = 'A session cannot last more than 48 hours.';
        } elseif ($activeSeconds + $idleSeconds > $started->diffInSeconds($end) + self::DURATION_TOLERANCE_SECONDS) {
            $errors['active_seconds'] = 'Active and idle time exceed the session duration.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Counters only grow; a session closed by its device is final; one closed by the server can still be
     * completed — or resumed after a timeout — by its device (fiche A2, R-02 and R-03).
     *
     * @param  array{active_seconds: int, idle_seconds: int, end_reason?: string|null, ...}  $snapshot
     * @param  array{started_at: CarbonImmutable, last_heartbeat_at: CarbonImmutable, ended_at: CarbonImmutable|null}  $times
     */
    private function update(GameSession $session, array $snapshot, array $times): void
    {
        if ($session->isFinal()) {
            return;
        }

        $heartbeatAdvanced = $times['last_heartbeat_at']->gt($session->last_heartbeat_at);

        $session->last_heartbeat_at = $heartbeatAdvanced ? $times['last_heartbeat_at'] : $session->last_heartbeat_at;
        $session->active_seconds = max($session->active_seconds, $snapshot['active_seconds']);
        $session->idle_seconds = max($session->idle_seconds, $snapshot['idle_seconds']);

        if ($times['ended_at'] instanceof CarbonImmutable) {
            $session->ended_at = $times['ended_at']->max($session->started_at);
            $session->end_reason = SessionEndReason::from($snapshot['end_reason'] ?? SessionEndReason::Closed->value);
        } elseif ($session->end_reason === SessionEndReason::Timeout && $heartbeatAdvanced) {
            $session->ended_at = null;
            $session->end_reason = null;
        }

        $session->save();
    }

    /**
     * A device runs one session per game at a time (fiche A2, R-01).
     */
    private function supersedeOpenSessions(GameSession $session): void
    {
        GameSession::query()
            ->open()
            ->where('companion_device_id', $session->companion_device_id)
            ->where('game_id', $session->game_id)
            ->whereKeyNot($session->getKey())
            ->get()
            ->each(fn (GameSession $previous) => $previous->closeAtLastHeartbeat(SessionEndReason::Superseded));
    }

    /**
     * The first snapshot of a session updates the personal status once; every snapshot refreshes the last activity.
     */
    private function applyTrackingEffects(GameSession $session, User $user): void
    {
        $game = $session->game;
        assert($game instanceof Game);

        if ($session->effects_applied_at === null) {
            $this->tracking->recordCompanionSession($user, $game, $session->last_heartbeat_at);
            $session->forceFill(['effects_applied_at' => now()])->save();

            return;
        }

        $this->tracking->touchActivity($user, $game, $session->last_heartbeat_at);
    }

    private function availableMappingId(User $user, ?int $mappingId): ?int
    {
        if ($mappingId === null) {
            return null;
        }

        $availableId = GameExecutableMapping::query()->availableTo($user)->whereKey($mappingId)->value('id');

        return is_numeric($availableId) ? (int) $availableId : null;
    }
}
