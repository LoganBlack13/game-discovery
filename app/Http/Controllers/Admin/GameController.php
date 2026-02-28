<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Game;
use Illuminate\View\View;

final class GameController
{
    public function index(): View
    {
        $games = Game::query()->latest()->paginate(20);

        return view('admin.games.index', [
            'games' => $games,
        ]);
    }
}
