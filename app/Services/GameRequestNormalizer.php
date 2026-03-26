<?php

declare(strict_types=1);

namespace App\Services;

final class GameRequestNormalizer
{
    public static function normalize(string $title): string
    {
        $trimmed = mb_trim($title);
        $lower = mb_strtolower($trimmed);

        return (string) preg_replace('/\s+/', ' ', $lower);
    }
}
