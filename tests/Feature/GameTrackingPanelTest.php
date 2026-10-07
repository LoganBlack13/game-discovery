<?php

declare(strict_types=1);

use App\Enums\BacklogPriority;
use App\Enums\InterruptionReason;
use App\Enums\TrackedGameStatus;
use App\Models\Game;
use App\Models\JournalEntry;
use App\Models\TrackedGame;
use App\Models\TrackedGameStatusChange;
use App\Models\User;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

function trackingPanelFor(TrackedGame $entry): Testable
{
    return Livewire::actingAs($entry->user)->test('game-tracking-panel', ['game' => $entry->game]);
}

test('game page shows the tracking panel only for a tracked game', function (): void {
    $user = User::factory()->create();
    $tracked = TrackedGame::factory()->playing()->for($user)->create();
    $untracked = Game::factory()->create();

    $this->actingAs($user)->get(route('games.show', $tracked->game))
        ->assertOk()
        ->assertSee('My tracking', false)
        ->assertSee('Playing', false);

    $this->actingAs($user)->get(route('games.show', $untracked))
        ->assertOk()
        ->assertDontSee('My tracking', false);
});

test('guests never see the tracking panel', function (): void {
    $entry = TrackedGame::factory()->playing()->create();

    $this->get(route('games.show', $entry->game))->assertOk()->assertDontSee('My tracking', false);
});

test('selecting a regular status applies it immediately', function (): void {
    $entry = TrackedGame::factory()->toPlay()->create();

    trackingPanelFor($entry)
        ->call('selectStatus', TrackedGameStatus::Playing->value)
        ->assertSet('pendingStatus', null)
        ->assertSee('Status updated to Playing.');

    expect($entry->fresh()->status)->toBe(TrackedGameStatus::Playing)
        ->and($entry->fresh()->started_at)->not->toBeNull();
});

test('selecting an unknown status does nothing', function (): void {
    $entry = TrackedGame::factory()->toPlay()->create();

    trackingPanelFor($entry)->call('selectStatus', 'nope');

    expect($entry->fresh()->status)->toBe(TrackedGameStatus::ToPlay);
});

test('pausing asks for an optional reason before applying', function (): void {
    $entry = TrackedGame::factory()->playing()->create();

    trackingPanelFor($entry)
        ->call('selectStatus', TrackedGameStatus::Paused->value)
        ->assertSet('pendingStatus', TrackedGameStatus::Paused->value)
        ->assertSee('Lack of time')
        ->set('reason', InterruptionReason::WaitingForUpdate->value)
        ->set('comment', 'Waiting for the performance patch')
        ->call('confirmInterruption')
        ->assertSet('pendingStatus', null)
        ->assertSee('Why it was paused')
        ->assertSee('Waiting for the performance patch');

    $change = $entry->statusChanges()->first();
    expect($entry->fresh()->status)->toBe(TrackedGameStatus::Paused)
        ->and($change->reason)->toBe(InterruptionReason::WaitingForUpdate)
        ->and($change->comment)->toBe('Waiting for the performance patch');
});

test('dropping without a reason is allowed and opens the optional review', function (): void {
    $entry = TrackedGame::factory()->playing()->create();

    trackingPanelFor($entry)
        ->call('selectStatus', TrackedGameStatus::Dropped->value)
        ->call('confirmInterruption')
        ->assertHasNoErrors()
        ->assertSet('showReviewForm', true);

    expect($entry->fresh()->status)->toBe(TrackedGameStatus::Dropped)
        ->and($entry->statusChanges()->first()->reason)->toBeNull();
});

test('an invalid reason is rejected', function (): void {
    $entry = TrackedGame::factory()->playing()->create();

    trackingPanelFor($entry)
        ->call('selectStatus', TrackedGameStatus::Paused->value)
        ->set('reason', 'bored')
        ->call('confirmInterruption')
        ->assertHasErrors('reason');

    expect($entry->fresh()->status)->toBe(TrackedGameStatus::Playing);
});

test('cancelling an interruption keeps the current status', function (): void {
    $entry = TrackedGame::factory()->playing()->create();

    trackingPanelFor($entry)
        ->call('selectStatus', TrackedGameStatus::Paused->value)
        ->call('cancelInterruption')
        ->assertSet('pendingStatus', null);

    expect($entry->fresh()->status)->toBe(TrackedGameStatus::Playing);
});

test('the interruption comment can be edited later', function (): void {
    $entry = TrackedGame::factory()->status(TrackedGameStatus::Paused)->create();
    $change = TrackedGameStatusChange::factory()->paused(InterruptionReason::Difficulty, 'Stuck on the boss')->for($entry)->create();

    trackingPanelFor($entry)
        ->call('editComment', $change->id)
        ->assertSet('editingComment', 'Stuck on the boss')
        ->set('editingComment', 'Stuck on the boss, try a ranged build')
        ->call('saveComment')
        ->assertSet('editingChangeId', null);

    expect($change->fresh()->comment)->toBe('Stuck on the boss, try a ranged build')
        ->and($change->fresh()->reason)->toBe(InterruptionReason::Difficulty);
});

test('another user status change cannot be edited', function (): void {
    $entry = TrackedGame::factory()->playing()->create();
    $foreignChange = TrackedGameStatusChange::factory()->paused()->create();

    trackingPanelFor($entry)
        ->call('editComment', $foreignChange->id)
        ->assertNotFound();
});

test('completing a game prompts an optional review that can be skipped', function (): void {
    $entry = TrackedGame::factory()->playing()->create();

    trackingPanelFor($entry)
        ->call('selectStatus', TrackedGameStatus::Completed->value)
        ->assertSet('showReviewForm', true)
        ->call('skipReview')
        ->assertSet('showReviewForm', false)
        ->assertSee('No review yet');

    expect($entry->fresh()->status)->toBe(TrackedGameStatus::Completed)
        ->and($entry->fresh()->hasReview())->toBeFalse();
});

test('a closing review is saved and can be edited afterwards', function (): void {
    $entry = TrackedGame::factory()->completed()->create();

    trackingPanelFor($entry)
        ->call('openReviewForm')
        ->set('rating', 9)
        ->set('playtimeHours', 42)
        ->set('wouldRecommend', 'yes')
        ->set('review', 'A masterpiece.')
        ->call('saveReview')
        ->assertHasNoErrors()
        ->assertSee('9/10')
        ->assertSee('A masterpiece.');

    trackingPanelFor($entry->fresh())
        ->assertSet('rating', 9)
        ->set('wouldRecommend', '')
        ->set('review', '')
        ->call('saveReview');

    $entry->refresh();
    expect($entry->rating)->toBe(9)
        ->and($entry->playtime_hours)->toBe(42)
        ->and($entry->would_recommend)->toBeNull()
        ->and($entry->review)->toBeNull();
});

test('a dropped game keeps its reason alongside its review', function (): void {
    $entry = TrackedGame::factory()->status(TrackedGameStatus::Dropped)->create(['finished_at' => now()]);
    TrackedGameStatusChange::factory()->for($entry)->create([
        'to_status' => TrackedGameStatus::Dropped,
        'reason' => InterruptionReason::RepetitiveGameplay,
    ]);

    trackingPanelFor($entry)
        ->set('rating', 4)
        ->set('review', 'Started strong.')
        ->call('saveReview')
        ->assertSee('Repetitive gameplay')
        ->assertSee('Started strong.');
});

test('review values are validated', function (): void {
    $entry = TrackedGame::factory()->completed()->create();

    trackingPanelFor($entry)
        ->set('rating', 11)
        ->set('playtimeHours', -1)
        ->set('wouldRecommend', 'maybe')
        ->call('saveReview')
        ->assertHasErrors(['rating', 'playtimeHours', 'wouldRecommend']);
});

test('progress, platform and notes are saved', function (): void {
    $entry = TrackedGame::factory()->playing()->create(['last_activity_at' => now()->subWeek()]);

    trackingPanelFor($entry)
        ->set('platform', 'PC')
        ->set('progress', 'Chapter 4')
        ->set('progressPercent', 55)
        ->set('notes', 'Use the fire sword')
        ->call('saveDetails')
        ->assertHasNoErrors();

    $entry->refresh();
    expect($entry->platform)->toBe('PC')
        ->and($entry->progress)->toBe('Chapter 4')
        ->and($entry->progress_percent)->toBe(55)
        ->and($entry->notes)->toBe('Use the fire sword')
        ->and($entry->last_activity_at->toDateTimeString())->toBe(now()->toDateTimeString());
});

test('progress percent cannot exceed 100', function (): void {
    $entry = TrackedGame::factory()->playing()->create();

    trackingPanelFor($entry)
        ->set('progressPercent', 120)
        ->call('saveDetails')
        ->assertHasErrors('progressPercent');
});

test('backlog priority and play next can be set from the game page', function (): void {
    $entry = TrackedGame::factory()->toPlay()->create();

    trackingPanelFor($entry)
        ->set('priority', BacklogPriority::High->value)
        ->set('isUpNext', true)
        ->call('saveBacklog')
        ->assertHasNoErrors();

    expect($entry->fresh()->priority)->toBe(BacklogPriority::High)
        ->and($entry->fresh()->is_up_next)->toBeTrue()
        ->and($entry->game->fresh()->title)->toBe($entry->game->title);
});

test('journal entries are added and listed with the resume goal', function (): void {
    $entry = TrackedGame::factory()->playing()->create();

    trackingPanelFor($entry)
        ->set('journalBody', 'Reached the castle')
        ->call('addJournalEntry')
        ->set('journalBody', 'Explore the north tower')
        ->set('journalIsResumeGoal', true)
        ->call('addJournalEntry')
        ->assertSet('journalBody', '')
        ->assertSee('Reached the castle')
        ->assertSee('Next time:')
        ->assertSee('Explore the north tower');

    expect($entry->journalEntries()->count())->toBe(2);
});

test('an empty journal entry is rejected', function (): void {
    $entry = TrackedGame::factory()->playing()->create();

    trackingPanelFor($entry)
        ->set('journalBody', '')
        ->call('addJournalEntry')
        ->assertHasErrors('journalBody');
});

test('a resume goal can be marked as done and stays in the journal', function (): void {
    $entry = TrackedGame::factory()->playing()->create();
    $goal = JournalEntry::factory()->resumeGoal()->for($entry)->create(['body' => 'Find the key']);

    trackingPanelFor($entry)
        ->call('completeResumeGoal', $goal->id)
        ->assertDontSee('Next time:')
        ->assertSee('Find the key');

    expect($goal->fresh()->completed_at)->not->toBeNull();
});

test('another user journal entry cannot be completed', function (): void {
    $entry = TrackedGame::factory()->playing()->create();
    $foreignGoal = JournalEntry::factory()->resumeGoal()->create();

    trackingPanelFor($entry)
        ->call('completeResumeGoal', $foreignGoal->id)
        ->assertNotFound();

    expect($foreignGoal->fresh()->completed_at)->toBeNull();
});

test('the history lists status changes in reverse chronological order', function (): void {
    $entry = TrackedGame::factory()->toPlay()->create();

    $component = trackingPanelFor($entry)->call('selectStatus', TrackedGameStatus::Playing->value);
    $this->travel(1)->day();
    $component->call('selectStatus', TrackedGameStatus::Completed->value)
        ->assertSeeInOrder(['History', 'Completed', 'To Play']);
});
