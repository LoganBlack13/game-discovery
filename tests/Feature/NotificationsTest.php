<?php

declare(strict_types=1);

use App\Enums\ReleaseStatus;
use App\Models\Game;
use App\Models\User;
use App\Notifications\GameReleaseDateChangedNotification;
use App\Notifications\GameReleasedNotification;
use App\Services\GameActivityRecorder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;

test('guest cannot access notifications page', function (): void {
    $this->get(route('notifications.index'))->assertRedirect();
});

test('authenticated user can view notifications page', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('notifications.index'))->assertSuccessful();
});

test('notifications are marked as read when page is visited', function (): void {
    $user = User::factory()->create();
    $game = Game::factory()->create();
    $user->notify(new GameReleasedNotification($game));

    expect($user->unreadNotifications()->count())->toBe(1);

    $this->actingAs($user)->get(route('notifications.index'));

    expect($user->fresh()->unreadNotifications()->count())->toBe(0);
});

test('authenticated user can dismiss a notification', function (): void {
    $user = User::factory()->create();
    $game = Game::factory()->create();
    $user->notify(new GameReleasedNotification($game));

    $notification = $user->notifications()->first();

    $this->actingAs($user)
        ->delete(route('notifications.destroy', $notification->id))
        ->assertRedirect();

    expect($user->notifications()->count())->toBe(0);
});

test('game released notification is sent to users tracking the game', function (): void {
    Notification::fake();

    $tracker = User::factory()->create();
    $other = User::factory()->create();
    $game = Game::factory()->create([
        'release_status' => ReleaseStatus::ComingSoon,
        'release_date' => now()->addMonth(),
    ]);
    $tracker->trackedGames()->attach($game);

    $game->update(['release_status' => ReleaseStatus::Released, 'release_date' => now()->subDay()]);

    $recorder = app(GameActivityRecorder::class);
    $recorder->recordReleaseChanges(
        $game->fresh()->load('trackedByUsers'),
        Carbon::parse(now()->addMonth()),
        ReleaseStatus::ComingSoon,
        wasExisting: true,
    );

    Notification::assertSentTo($tracker, GameReleasedNotification::class);
    Notification::assertNotSentTo($other, GameReleasedNotification::class);
});

test('release date changed notification is sent to users tracking the game', function (): void {
    Notification::fake();

    $tracker = User::factory()->create();
    $game = Game::factory()->create([
        'release_status' => ReleaseStatus::ComingSoon,
        'release_date' => now()->addMonths(3),
    ]);
    $tracker->trackedGames()->attach($game);

    $oldDate = $game->release_date;
    $game->update(['release_date' => now()->addMonths(6)]);

    $recorder = app(GameActivityRecorder::class);
    $recorder->recordReleaseChanges(
        $game->fresh()->load('trackedByUsers'),
        $oldDate,
        ReleaseStatus::ComingSoon,
        wasExisting: true,
    );

    Notification::assertSentTo($tracker, GameReleaseDateChangedNotification::class);
});
