<?php

declare(strict_types=1);

test('welcome page loads and shows hero content', function (): void {
    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertSee('Discover your next game');
    $response->assertSee('Explore games');
    $response->assertSee('Trending now');
});
