<?php

declare(strict_types=1);

use App\Enums\ReleaseStatus;
use App\Models\Game;

use function Pest\Laravel\get;

it('renders homepage with upcoming releases carousel', function (): void {
    Game::factory()->create([
        'title' => 'Upcoming Carousel Game',
        'release_date' => now()->addDays(30),
        'release_status' => ReleaseStatus::ComingSoon,
    ]);

    $response = get('/');

    $response->assertOk();
    $response->assertSee('Upcoming releases', escape: false);
    $response->assertSee('Upcoming Carousel Game', escape: false);
});
