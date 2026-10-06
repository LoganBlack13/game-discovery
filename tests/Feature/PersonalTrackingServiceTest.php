<?php

declare(strict_types=1);

use App\Enums\BacklogPriority;
use App\Enums\InterruptionReason;
use App\Enums\TrackedGameStatus;
use App\Models\Game;
use App\Models\TrackedGame;
use App\Models\User;
use App\Services\PersonalTrackingService;

beforeEach(function (): void {
    $this->service = resolve(PersonalTrackingService::class);
});

test('tracking a game starts in watching with a first history entry', function (): void {
    $user = User::factory()->create();
    $game = Game::factory()->create();

    $entry = $this->service->track($user, $game);

    expect($entry->status)->toBe(TrackedGameStatus::Watching)
        ->and($entry->statusChanges()->count())->toBe(1)
        ->and($entry->statusChanges()->first()->from_status)->toBeNull()
        ->and($entry->statusChanges()->first()->to_status)->toBe(TrackedGameStatus::Watching);
});

test('tracking an already tracked game keeps its current status and history', function (): void {
    $entry = TrackedGame::factory()->playing()->create();

    $sameEntry = $this->service->track($entry->user, $entry->game);

    expect($sameEntry->id)->toBe($entry->id)
        ->and($sameEntry->fresh()->status)->toBe(TrackedGameStatus::Playing)
        ->and($entry->statusChanges()->count())->toBe(0);
});

test('each status change is timestamped and keeps a single current status', function (): void {
    $entry = TrackedGame::factory()->toPlay()->create();

    $change = $this->service->changeStatus($entry, TrackedGameStatus::Playing);

    expect($change->from_status)->toBe(TrackedGameStatus::ToPlay)
        ->and($change->to_status)->toBe(TrackedGameStatus::Playing)
        ->and($change->occurred_at->toDateTimeString())->toBe(now()->toDateTimeString())
        ->and($entry->fresh()->status)->toBe(TrackedGameStatus::Playing)
        ->and($entry->fresh()->last_activity_at->toDateTimeString())->toBe(now()->toDateTimeString());
});

test('changing to the same status records nothing', function (): void {
    $entry = TrackedGame::factory()->playing()->create();

    expect($this->service->changeStatus($entry, TrackedGameStatus::Playing))->toBeNull()
        ->and($entry->statusChanges()->count())->toBe(0);
});

test('starting a game sets the start date once and leaves the play next list', function (): void {
    $entry = TrackedGame::factory()->toPlay()->create(['is_up_next' => true, 'backlog_position' => 2]);

    $this->service->changeStatus($entry, TrackedGameStatus::Playing);
    $firstStart = $entry->fresh()->started_at;

    $this->travel(3)->days();
    $this->service->changeStatus($entry, TrackedGameStatus::Paused);
    $this->service->changeStatus($entry, TrackedGameStatus::Playing);

    $entry->refresh();
    expect($entry->started_at->toDateTimeString())->toBe($firstStart->toDateTimeString())
        ->and($entry->is_up_next)->toBeFalse()
        ->and($entry->backlog_position)->toBeNull()
        ->and($entry->statusChanges()->count())->toBe(3);
});

test('completed and dropped keep their end date and replaying clears it', function (TrackedGameStatus $finished): void {
    $entry = TrackedGame::factory()->playing()->create();

    $this->service->changeStatus($entry, $finished);
    expect($entry->fresh()->finished_at->toDateTimeString())->toBe(now()->toDateTimeString());

    $this->service->changeStatus($entry, TrackedGameStatus::Playing);
    expect($entry->fresh()->finished_at)->toBeNull()
        ->and($entry->statusChanges()->where('to_status', $finished->value)->first()->occurred_at)->not->toBeNull();
})->with([
    'completed' => TrackedGameStatus::Completed,
    'mastered' => TrackedGameStatus::Mastered,
    'dropped' => TrackedGameStatus::Dropped,
]);

test('a pause keeps its own reason and comment', function (): void {
    $entry = TrackedGame::factory()->playing()->create();

    $change = $this->service->changeStatus($entry, TrackedGameStatus::Paused, InterruptionReason::LackOfTime, '  Busy month  ');

    expect($change->reason)->toBe(InterruptionReason::LackOfTime)
        ->and($change->comment)->toBe('Busy month');
});

test('a drop can be recorded without any reason', function (): void {
    $entry = TrackedGame::factory()->playing()->create();

    $change = $this->service->changeStatus($entry, TrackedGameStatus::Dropped);

    expect($change->reason)->toBeNull()
        ->and($change->comment)->toBeNull()
        ->and($entry->fresh()->status)->toBe(TrackedGameStatus::Dropped);
});

test('reasons are ignored for statuses that are not interruptions', function (): void {
    $entry = TrackedGame::factory()->playing()->create();

    $change = $this->service->changeStatus($entry, TrackedGameStatus::Completed, InterruptionReason::Difficulty, 'ignored');

    expect($change->reason)->toBeNull()->and($change->comment)->toBeNull();
});

test('successive pauses and a resume keep every interruption in history', function (): void {
    $entry = TrackedGame::factory()->playing()->create();

    $this->service->changeStatus($entry, TrackedGameStatus::Paused, InterruptionReason::LackOfTime);
    $this->travel(1)->day();
    $this->service->changeStatus($entry, TrackedGameStatus::Playing);
    $this->travel(1)->day();
    $this->service->changeStatus($entry, TrackedGameStatus::Paused, InterruptionReason::TechnicalIssue, 'Crash in chapter 3');
    $this->travel(1)->day();
    $this->service->changeStatus($entry, TrackedGameStatus::Playing);

    $pauses = $entry->statusChanges()->where('to_status', TrackedGameStatus::Paused->value)->get();

    expect($pauses)->toHaveCount(2)
        ->and($pauses->pluck('reason')->all())->toBe([InterruptionReason::TechnicalIssue, InterruptionReason::LackOfTime])
        ->and($entry->fresh()->latestInterruption->comment)->toBe('Crash in chapter 3');
});

test('a journal entry updates the last activity date', function (): void {
    $entry = TrackedGame::factory()->playing()->create(['last_activity_at' => now()->subWeek()]);

    $journalEntry = $this->service->addJournalEntry($entry, ' Beat the second boss ', true);

    expect($journalEntry->body)->toBe('Beat the second boss')
        ->and($journalEntry->is_resume_goal)->toBeTrue()
        ->and($entry->fresh()->last_activity_at->toDateTimeString())->toBe(now()->toDateTimeString())
        ->and($entry->fresh()->currentResumeGoal->id)->toBe($journalEntry->id);
});

test('the current resume goal is the latest unfinished one', function (): void {
    $entry = TrackedGame::factory()->playing()->create();
    $older = $this->service->addJournalEntry($entry, 'Older goal', true);
    $this->travel(1)->hour();
    $latest = $this->service->addJournalEntry($entry, 'Latest goal', true);
    $this->travel(1)->hour();
    $this->service->addJournalEntry($entry, 'Plain session note');

    expect($entry->fresh()->currentResumeGoal->id)->toBe($latest->id);

    $latest->update(['completed_at' => now()]);
    expect($entry->fresh()->currentResumeGoal->id)->toBe($older->id);
});

test('backlog order puts play next first then manual order then priority', function (): void {
    $user = User::factory()->create();
    $low = TrackedGame::factory()->toPlay()->for($user)->create(['priority' => BacklogPriority::Low]);
    $high = TrackedGame::factory()->toPlay()->for($user)->create(['priority' => BacklogPriority::High]);
    $pinned = TrackedGame::factory()->toPlay()->for($user)->create(['is_up_next' => true]);
    $none = TrackedGame::factory()->toPlay()->for($user)->create();

    $ids = TrackedGame::query()->where('user_id', $user->id)->inBacklogOrder()->pluck('id')->all();

    expect($ids)->toBe([$pinned->id, $high->id, $low->id, $none->id]);
});

test('moving a game in the backlog swaps it with its neighbour', function (): void {
    $user = User::factory()->create();
    $first = TrackedGame::factory()->toPlay()->for($user)->create();
    $this->travel(1)->minute();
    $second = TrackedGame::factory()->toPlay()->for($user)->create();
    $this->travel(1)->minute();
    $third = TrackedGame::factory()->toPlay()->for($user)->create();

    $this->service->moveInBacklog($third, -1);

    $ids = TrackedGame::query()->where('user_id', $user->id)->inBacklogOrder()->pluck('id')->all();
    expect($ids)->toBe([$first->id, $third->id, $second->id]);

    $this->service->moveInBacklog($first->fresh(), -1);
    $this->service->moveInBacklog($second->fresh(), 1);

    $ids = TrackedGame::query()->where('user_id', $user->id)->inBacklogOrder()->pluck('id')->all();
    expect($ids)->toBe([$first->id, $third->id, $second->id]);
});

test('moving ignores games that are not in the to play backlog', function (): void {
    $entry = TrackedGame::factory()->playing()->create();

    $this->service->moveInBacklog($entry, 1);

    expect($entry->fresh()->backlog_position)->toBeNull();
});

test('backlog moves never touch another user games', function (): void {
    $user = User::factory()->create();
    $mine = TrackedGame::factory()->toPlay()->for($user)->create();
    TrackedGame::factory()->toPlay()->for($user)->create();
    $someoneElse = TrackedGame::factory()->toPlay()->create();

    $this->service->moveInBacklog($mine, 1);

    expect($someoneElse->fresh()->backlog_position)->toBeNull();
});
