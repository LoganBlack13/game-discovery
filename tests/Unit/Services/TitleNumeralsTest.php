<?php

declare(strict_types=1);

use App\Services\TitleNumerals;

test('roman numerals become digits', function (string $title, string $expected): void {
    expect(TitleNumerals::toArabic($title))->toBe($expected);
})->with([
    ['Graveyard Keeper II', 'Graveyard Keeper 2'],
    ['final fantasy xiv online', 'final fantasy 14 online'],
    ['Civilization VI: Rise and Fall', 'Civilization 6: Rise and Fall'],
    ['I Am Bread', 'I Am Bread'],
    ['Vixen', 'Vixen'],
    ['Outer Wilds', 'Outer Wilds'],
]);

test('digits become roman numerals', function (string $title, string $expected): void {
    expect(TitleNumerals::toRoman($title))->toBe($expected);
})->with([
    ['Graveyard Keeper 2', 'Graveyard Keeper II'],
    ['Final Fantasy 14', 'Final Fantasy XIV'],
    ['Cyberpunk 2077', 'Cyberpunk 2077'],
    ['Left 4 Dead 2', 'Left IV Dead II'],
    ['Persona 25', 'Persona 25'],
    ['Portal 1', 'Portal 1'],
]);

test('the variants hold the title as typed and its numerals written the other way', function (): void {
    expect(TitleNumerals::variants('Graveyard Keeper 2'))->toBe(['Graveyard Keeper 2', 'Graveyard Keeper II'])
        ->and(TitleNumerals::variants('Hades II'))->toBe(['Hades II', 'Hades 2'])
        ->and(TitleNumerals::variants('Celeste'))->toBe(['Celeste']);
});
