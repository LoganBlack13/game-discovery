<?php

declare(strict_types=1);

namespace App\Enums;

enum InterruptionReason: string
{
    case LackOfTime = 'lack_of_time';
    case Difficulty = 'difficulty';
    case LostInterest = 'lost_interest';
    case RepetitiveGameplay = 'repetitive_gameplay';
    case TechnicalIssue = 'technical_issue';
    case WaitingForUpdate = 'waiting_for_update';
    case WantToPlaySomethingElse = 'want_to_play_something_else';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::LackOfTime => 'Lack of time',
            self::Difficulty => 'Too difficult',
            self::LostInterest => 'Lost interest',
            self::RepetitiveGameplay => 'Repetitive gameplay',
            self::TechnicalIssue => 'Technical / performance issue',
            self::WaitingForUpdate => 'Waiting for an update',
            self::WantToPlaySomethingElse => 'Want to play something else',
            self::Other => 'Other',
        };
    }
}
