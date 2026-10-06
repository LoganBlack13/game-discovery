<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Support\Str;

enum CreditDiscipline: string
{
    case Direction = 'direction';
    case Production = 'production';
    case Design = 'design';
    case Writing = 'writing';
    case Programming = 'programming';
    case Art = 'art';
    case Animation = 'animation';
    case Audio = 'audio';
    case VoiceActing = 'voice_acting';
    case Other = 'other';

    /**
     * Infer the discipline of a free-form credit role (e.g. "Lead Programmer", "Composer").
     */
    public static function fromRole(string $role): self
    {
        $normalized = Str::lower($role);

        $keywords = [
            'voice' => self::VoiceActing,
            'actor' => self::VoiceActing,
            'actress' => self::VoiceActing,
            'animat' => self::Animation,
            'composer' => self::Audio,
            'music' => self::Audio,
            'sound' => self::Audio,
            'audio' => self::Audio,
            'writer' => self::Writing,
            'writing' => self::Writing,
            'narrative' => self::Writing,
            'script' => self::Writing,
            'story' => self::Writing,
            'program' => self::Programming,
            'engineer' => self::Programming,
            'developer' => self::Programming,
            'artist' => self::Art,
            'art ' => self::Art,
            'illustrat' => self::Art,
            'visual' => self::Art,
            'design' => self::Design,
            'producer' => self::Production,
            'production' => self::Production,
            'director' => self::Direction,
            'direction' => self::Direction,
            'creator' => self::Direction,
        ];

        foreach ($keywords as $keyword => $discipline) {
            if (str_contains($normalized.' ', $keyword)) {
                return $discipline;
            }
        }

        return self::Other;
    }

    public function label(): string
    {
        return match ($this) {
            self::Direction => 'Direction',
            self::Production => 'Production',
            self::Design => 'Game design',
            self::Writing => 'Writing',
            self::Programming => 'Programming',
            self::Art => 'Art',
            self::Animation => 'Animation',
            self::Audio => 'Music & audio',
            self::VoiceActing => 'Voice acting',
            self::Other => 'Other',
        };
    }
}
