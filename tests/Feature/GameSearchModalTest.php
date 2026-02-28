<?php

declare(strict_types=1);

test('search modal and trigger are present when layout is loaded', function (): void {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('Search games', false);
    $response->assertSee('data-flux-modal-trigger', false);
    $response->assertSee('game-search', false);
});

test('search trigger documents keyboard shortcut in UI', function (): void {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('Search games (⌘K)', false);
});
