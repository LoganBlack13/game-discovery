<?php

declare(strict_types=1);

use App\Services\GameRequestNormalizer;

uses()->group('unit');

test('normalize trims and lowercases and collapses spaces', function (): void {
    expect(GameRequestNormalizer::normalize('  Elden   Ring  '))->toBe('elden ring');
});

test('normalize handles single word', function (): void {
    expect(GameRequestNormalizer::normalize('Hades'))->toBe('hades');
});

test('normalize handles already normalized title', function (): void {
    expect(GameRequestNormalizer::normalize('elden ring'))->toBe('elden ring');
});
