<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Bridges the two ways a sequel is numbered, so `Graveyard Keeper 2` finds `Graveyard Keeper II`.
 * Only whole words from 2 to 20 are converted: a lone `I` is too often a word of the title.
 */
final class TitleNumerals
{
    private const array ROMAN = [
        2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X',
        11 => 'XI', 12 => 'XII', 13 => 'XIII', 14 => 'XIV', 15 => 'XV', 16 => 'XVI', 17 => 'XVII', 18 => 'XVIII',
        19 => 'XIX', 20 => 'XX',
    ];

    public static function toArabic(string $title): string
    {
        $numbers = array_flip(self::ROMAN);

        return (string) preg_replace_callback(
            '/\b('.implode('|', self::ROMAN).')\b/iu',
            fn (array $match): string => (string) $numbers[mb_strtoupper($match[1])],
            $title,
        );
    }

    public static function toRoman(string $title): string
    {
        return (string) preg_replace_callback(
            '/\b(\d{1,2})\b/u',
            fn (array $match): string => self::ROMAN[(int) $match[1]] ?? $match[1],
            $title,
        );
    }

    /**
     * The title as typed, then with its numerals written the other way.
     *
     * @return list<string>
     */
    public static function variants(string $title): array
    {
        return array_values(array_unique([$title, self::toArabic($title), self::toRoman($title)]));
    }
}
