<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class GameController extends Controller
{
    use AuthorizesRequests;

    public function show(Game $game): View
    {
        $game->load([
            'news' => fn (HasMany $q) => $q->latest('published_at'),
            'activities',
        ]);

        $isTracked = auth()->check()
            && auth()->user()->trackedGames()->where('game_id', $game->id)->exists();

        return view('games.show', [
            'game' => $game,
            'isTracked' => $isTracked,
        ]);
    }

    public function track(Request $request, Game $game): JsonResponse|RedirectResponse
    {
        $this->authorize('track', $game);

        $request->user()->trackedGames()->syncWithoutDetaching([$game->id]);

        if ($request->expectsJson()) {
            return response()->json(['tracked' => true]);
        }

        return back()->with('status', 'game-tracked');
    }

    public function untrack(Request $request, Game $game): JsonResponse|RedirectResponse
    {
        $this->authorize('untrack', $game);

        $request->user()->trackedGames()->detach($game->id);

        if ($request->expectsJson()) {
            return response()->json(['tracked' => false]);
        }

        return back()->with('status', 'game-untracked');
    }
}
