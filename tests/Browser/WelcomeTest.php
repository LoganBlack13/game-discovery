<?php

declare(strict_types=1);

it('has welcome page', function (): void {
    $page = visit('/');

    $page->assertSee('Track your games')
        ->assertSee('Start tracking your games');
});
