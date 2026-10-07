<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\GameLauncher;
use App\Enums\MappingCandidateStatus;
use App\Enums\MappingConfidence;
use App\Models\CompanionDevice;
use App\Models\CompanionMappingCandidate;
use App\Models\Game;
use App\Models\GameExecutableMapping;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Associates the executables and installed games reported by the Companion with Questlog games (fiche A5).
 * An association is automatic only when a launcher identifier resolves to exactly one game; a title match is
 * only ever proposed to the user.
 */
final readonly class CompanionMappingService
{
    public function __construct(private IgdbGameDataProvider $igdb) {}

    /**
     * Lowercases a title and keeps only letters and digits so `ELDEN RING™` and `Elden Ring` compare equal.
     */
    public static function normalizeTitle(string $title): string
    {
        $words = preg_split('/[^\pL\pN]+/u', mb_strtolower($title), -1, PREG_SPLIT_NO_EMPTY);

        return implode(' ', $words === false ? [] : $words);
    }

    /**
     * Records the candidates sent by a device, idempotently per fingerprint, and resolves the pending ones.
     *
     * @param  list<array{fingerprint: string, executable_name: string, path_fragment?: string|null, launcher?: string|null, launcher_game_id?: string|null, display_name?: string|null, product_name?: string|null, total_seconds: int, first_seen_at: string, last_seen_at: string}>  $candidates
     * @return list<CompanionMappingCandidate>
     */
    public function record(CompanionDevice $device, array $candidates): array
    {
        $user = $device->user;
        assert($user instanceof User);

        $recorded = [];
        foreach ($candidates as $data) {
            $candidate = CompanionMappingCandidate::query()->firstOrNew([
                'user_id' => $user->id,
                'fingerprint' => $data['fingerprint'],
            ]);
            $firstSeen = CarbonImmutable::parse($data['first_seen_at']);
            $lastSeen = CarbonImmutable::parse($data['last_seen_at']);

            $candidate->fill([
                'companion_device_id' => $device->id,
                'executable_name' => $data['executable_name'],
                'path_fragment' => $data['path_fragment'] ?? null,
                'launcher' => $data['launcher'] ?? null,
                'launcher_game_id' => $data['launcher_game_id'] ?? null,
                'display_name' => $data['display_name'] ?? null,
                'product_name' => $data['product_name'] ?? null,
                'total_seconds' => max($candidate->total_seconds, $data['total_seconds']),
                'first_seen_at' => $candidate->exists ? $candidate->first_seen_at->min($firstSeen) : $firstSeen,
                'last_seen_at' => $candidate->exists ? $candidate->last_seen_at->max($lastSeen) : $lastSeen,
            ])->save();

            if ($candidate->status === MappingCandidateStatus::Pending) {
                $this->resolve($candidate, $user);
            }

            $recorded[] = $candidate;
        }

        return $recorded;
    }

    /**
     * The user confirms which game the candidate is.
     */
    public function confirm(CompanionMappingCandidate $candidate, Game $game): GameExecutableMapping
    {
        $user = User::query()->findOrFail($candidate->user_id);

        return $this->map($candidate, $user, $game, validatedByUser: true);
    }

    public function ignore(CompanionMappingCandidate $candidate): void
    {
        $candidate->forceFill(['status' => MappingCandidateStatus::Ignored])->save();
    }

    public function restore(CompanionMappingCandidate $candidate): void
    {
        $candidate->forceFill(['status' => MappingCandidateStatus::Pending])->save();
    }

    /**
     * Points a personal mapping to another game. Future sessions follow; past sessions recorded through the
     * mapping move too only when asked (fiche A5, F-05).
     */
    public function reassign(GameExecutableMapping $mapping, Game $game, bool $movePastSessions): void
    {
        DB::transaction(function () use ($mapping, $game, $movePastSessions): void {
            $mapping->forceFill(['game_id' => $game->id, 'validated_by_user' => true])->save();

            if ($movePastSessions && $mapping->user_id !== null) {
                User::query()->findOrFail($mapping->user_id)
                    ->gameSessions()
                    ->where('game_executable_mapping_id', $mapping->id)
                    ->update(['game_id' => $game->id]);
            }
        });
    }

    private function resolve(CompanionMappingCandidate $candidate, User $user): void
    {
        $existing = $this->existingPersonalMapping($candidate, $user);
        if ($existing instanceof GameExecutableMapping) {
            $this->markMapped($candidate, $existing);

            return;
        }

        if ($candidate->launcher instanceof GameLauncher && $candidate->launcher_game_id !== null) {
            $igdbId = $this->igdb->findGameIdByExternalUid($candidate->launcher, $candidate->launcher_game_id);
            $candidate->igdb_game_id = $igdbId;

            $game = $igdbId === null ? null : Game::query()
                ->where('external_source', 'igdb')
                ->where('external_id', (string) $igdbId)
                ->first();

            if ($game instanceof Game) {
                $this->map($candidate, $user, $game, validatedByUser: false);

                return;
            }
        }

        $proposal = $this->proposeByTitle($candidate);
        $candidate->forceFill([
            'proposed_game_id' => $proposal?->id,
            'proposal_confidence' => $proposal instanceof Game ? MappingConfidence::Medium : null,
        ])->save();
    }

    private function existingPersonalMapping(CompanionMappingCandidate $candidate, User $user): ?GameExecutableMapping
    {
        $key = GameExecutableMapping::keyFor($candidate->launcher, $candidate->launcher_game_id, $candidate->executable_name, $candidate->path_fragment);

        return GameExecutableMapping::query()
            ->where('user_id', $user->id)
            ->get()
            ->first(fn (GameExecutableMapping $mapping): bool => $mapping->matchKey() === $key);
    }

    /**
     * A single game whose normalized title equals the launcher or file name; never applied without the user.
     */
    private function proposeByTitle(CompanionMappingCandidate $candidate): ?Game
    {
        $name = self::normalizeTitle($candidate->display_name ?? $candidate->product_name ?? '');
        if ($name === '') {
            return null;
        }

        $matches = Game::query()
            ->searchByTitle(explode(' ', $name)[0])
            ->limit(50)
            ->get(['id', 'title'])
            ->filter(fn (Game $game): bool => self::normalizeTitle($game->title) === $name);

        return $matches->count() === 1 ? $matches->first() : null;
    }

    private function map(CompanionMappingCandidate $candidate, User $user, Game $game, bool $validatedByUser): GameExecutableMapping
    {
        return DB::transaction(function () use ($candidate, $user, $game, $validatedByUser): GameExecutableMapping {
            $mapping = GameExecutableMapping::query()->create([
                'game_id' => $game->id,
                'user_id' => $user->id,
                'executable_name' => $candidate->executable_name,
                'path_fragment' => $candidate->path_fragment,
                'launcher' => $candidate->launcher,
                'launcher_game_id' => $candidate->launcher_game_id,
                'confidence' => MappingConfidence::High,
                'validated_by_user' => $validatedByUser,
            ]);

            $this->markMapped($candidate, $mapping);

            return $mapping;
        });
    }

    private function markMapped(CompanionMappingCandidate $candidate, GameExecutableMapping $mapping): void
    {
        $candidate->forceFill([
            'status' => MappingCandidateStatus::Mapped,
            'game_executable_mapping_id' => $mapping->id,
            'proposed_game_id' => $mapping->game_id,
        ])->save();
    }
}
