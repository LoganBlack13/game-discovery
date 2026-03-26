<?php

declare(strict_types=1);

it('shows a branded 404 page', function (): void {
    $this->get('/this-route-does-not-exist-xyz')
        ->assertNotFound()
        ->assertSee('Page not found');
});

it('shows go home and browse games links on the 404 page', function (): void {
    $response = $this->get('/this-route-does-not-exist-xyz');

    $response->assertNotFound();
    $response->assertSee(url('/'), false);
    $response->assertSee(route('games.index'), false);
});
