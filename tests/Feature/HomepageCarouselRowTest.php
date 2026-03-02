<?php

declare(strict_types=1);

use function Pest\Laravel\get;

it('renders homepage carousels with card rows', function () {
    $response = get('/');

    $response->assertOk();
    $response->assertSee('Coming soon', escape: false);
    $response->assertSee('Trending now', escape: false);
    $response->assertSee('Recently released', escape: false);
});
