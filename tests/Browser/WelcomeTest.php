<?php

declare(strict_types=1);

it('has welcome page', function (): void {
    $page = visit('/');

    $page->assertSee('Discover your next game')
        ->assertSee('Explore games');
});
