<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\InterruptionReason;
use App\Enums\TrackedGameStatus;
use App\Models\Game;
use App\Models\JournalEntry;
use App\Models\TrackedGame;
use App\Models\TrackedGameStatusChange;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class PersonalTrackingService
{
    /**
     * Start tracking a game. New entries begin in the "Watching" state with a first history entry.
     */
    public function track(User $user, Game $game): TrackedGame
    {
        $entry = TrackedGame::query()->firstOrCreate([
            'user_id' => $user->id,
            'game_id' => $game->id,
        ]);

        if ($entry->wasRecentlyCreated) {
            $this->changeStatus($entry, TrackedGameStatus::Watching);
        }

        return $entry;
    }

    /**
     * Move a tracked game to a new personal status and keep a timestamped history entry.
     * The reason and comment are only kept for interruptions (pause / drop) and are always optional.
     */
    public function changeStatus(
        TrackedGame $entry,
        ?TrackedGameStatus $status,
        ?InterruptionReason $reason = null,
        ?string $comment = null,
    ): ?TrackedGameStatusChange {
        $previous = $entry->status;

        if ($previous === $status) {
            return null;
        }

        $isInterruption = $status?->isInterruption() ?? false;
        $comment = $comment !== null && mb_trim($comment) !== '' ? mb_trim($comment) : null;

        return DB::transaction(function () use ($entry, $previous, $status, $isInterruption, $reason, $comment): TrackedGameStatusChange {
            $now = now();

            $entry->status = $status;
            $entry->last_activity_at = $now;

            if ($status === TrackedGameStatus::Playing) {
                $entry->started_at ??= $now;
                $entry->is_up_next = false;
                $entry->backlog_position = null;
            }

            if ($status?->isFinished() ?? false) {
                $entry->finished_at = $now;
                $entry->is_up_next = false;
            } elseif ($previous?->isFinished() ?? false) {
                $entry->finished_at = null;
            }

            $entry->save();

            return $entry->statusChanges()->create([
                'from_status' => $previous,
                'to_status' => $status,
                'reason' => $isInterruption ? $reason : null,
                'comment' => $isInterruption ? $comment : null,
                'occurred_at' => $now,
            ]);
        });
    }

    /**
     * First Companion session of a game (fiche A2, R-06): the game becomes tracked and, unless it is finished,
     * moves to "Playing" through the regular status history so the user can revert it.
     */
    public function recordCompanionSession(User $user, Game $game, CarbonInterface $activityAt): TrackedGame
    {
        $entry = $this->track($user, $game);

        if (in_array($entry->status, [null, TrackedGameStatus::Watching, TrackedGameStatus::ToPlay, TrackedGameStatus::Paused], true)) {
            $this->changeStatus($entry, TrackedGameStatus::Playing);
        }

        $this->touchActivity($user, $game, $activityAt);

        return $entry;
    }

    /**
     * Moves the last activity of a tracked game forward, never backward.
     */
    public function touchActivity(User $user, Game $game, CarbonInterface $activityAt): void
    {
        TrackedGame::query()
            ->where('user_id', $user->id)
            ->where('game_id', $game->id)
            ->where(function (Builder $query) use ($activityAt): void {
                $query->whereNull('last_activity_at')->orWhere('last_activity_at', '<', $activityAt);
            })
            ->update(['last_activity_at' => $activityAt]);
    }

    public function addJournalEntry(TrackedGame $entry, string $body, bool $isResumeGoal = false): JournalEntry
    {
        $now = now();

        $entry->forceFill(['last_activity_at' => $now])->save();

        return $entry->journalEntries()->create([
            'body' => mb_trim($body),
            'is_resume_goal' => $isResumeGoal,
            'written_at' => $now,
        ]);
    }

    /**
     * Swap a "To Play" game with its neighbour in the user's manual backlog order (within the same "play next" group).
     */
    public function moveInBacklog(TrackedGame $entry, int $direction): void
    {
        if ($entry->status !== TrackedGameStatus::ToPlay || $direction === 0) {
            return;
        }

        DB::transaction(function () use ($entry, $direction): void {
            $backlog = TrackedGame::query()
                ->where('user_id', $entry->user_id)
                ->withStatus(TrackedGameStatus::ToPlay)
                ->where('is_up_next', $entry->is_up_next)
                ->inBacklogOrder()
                ->get(['id', 'is_up_next', 'backlog_position', 'priority', 'created_at']);

            $ids = $backlog->pluck('id')->values()->all();
            $index = array_search($entry->id, $ids, true);
            $target = $index === false ? false : $index + ($direction > 0 ? 1 : -1);

            if ($index === false || $target < 0 || $target >= count($ids)) {
                return;
            }

            [$ids[$index], $ids[$target]] = [$ids[$target], $ids[$index]];

            foreach ($ids as $position => $id) {
                TrackedGame::query()->whereKey($id)->update(['backlog_position' => $position + 1]);
            }
        });
    }
}
