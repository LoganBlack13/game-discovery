<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class DashboardController
{
    public function __invoke(Request $request): View
    {
        $totalGames = Game::query()->count();
        $recentGamesCount = Game::query()
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        return view('admin.dashboard', [
            'totalGames' => $totalGames,
            'recentGamesCount' => $recentGamesCount,
        ]);
    }
}
