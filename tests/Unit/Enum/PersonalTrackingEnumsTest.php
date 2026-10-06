<?php

declare(strict_types=1);

use App\Enums\BacklogPriority;
use App\Enums\CreditDiscipline;
use App\Enums\InterruptionReason;
use App\Enums\TrackedGameStatus;

test('every personal tracking enum case has a non empty label', function (BackedEnum $case): void {
    expect($case->label())->toBeString()->not->toBeEmpty();
})->with(fn (): array => [
    ...TrackedGameStatus::cases(),
    ...InterruptionReason::cases(),
    ...BacklogPriority::cases(),
    ...CreditDiscipline::cases(),
]);

test('every tracked game status has a badge class', function (TrackedGameStatus $status): void {
    expect($status->badgeClass())->toStartWith('badge-');
})->with(TrackedGameStatus::cases());

test('only completed, mastered and dropped statuses finish a game', function (): void {
    $finished = array_values(array_filter(TrackedGameStatus::cases(), fn (TrackedGameStatus $status): bool => $status->isFinished()));

    expect($finished)->toBe([TrackedGameStatus::Completed, TrackedGameStatus::Mastered, TrackedGameStatus::Dropped]);
});

test('only paused and dropped statuses are interruptions', function (): void {
    $interruptions = array_values(array_filter(TrackedGameStatus::cases(), fn (TrackedGameStatus $status): bool => $status->isInterruption()));

    expect($interruptions)->toBe([TrackedGameStatus::Paused, TrackedGameStatus::Dropped]);
});
