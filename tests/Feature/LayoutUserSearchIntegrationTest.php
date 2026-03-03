<?php

declare(strict_types=1);

test('app layout includes user search command palette', function (): void {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('Search games', false);
    $response->assertSee('Type to search games', false);
    $response->assertSee('role="search"', false);
});

test('app layout search trigger has keyboard shortcut in aria-label', function (): void {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('Search games (⌘K)', false);
});
