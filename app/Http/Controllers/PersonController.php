<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameCredit;
use App\Models\Person;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class PersonController extends Controller
{
    public function show(Request $request, Person $person): View
    {
        $person->load(['credits' => fn (HasMany $query) => $query->with('game')->orderBy('role')]);

        /** @var array<int, array{game: Game, credits: list<GameCredit>}> $rows */
        $rows = [];
        foreach ($person->credits as $credit) {
            $rows[$credit->game_id] ??= ['game' => $credit->game, 'credits' => []];
            $rows[$credit->game_id]['credits'][] = $credit;
        }

        $gamesWithRoles = array_values($rows);
        usort($gamesWithRoles, fn (array $a, array $b): int => strcmp(
            $b['game']->release_date?->format('Y-m-d') ?? '0000-00-00',
            $a['game']->release_date?->format('Y-m-d') ?? '0000-00-00',
        ));

        $fromSlug = $request->string('game')->toString();
        $fromGame = null;
        foreach ($gamesWithRoles as $row) {
            if ($fromSlug !== '' && $row['game']->slug === $fromSlug) {
                $fromGame = $row;
            }
        }

        return view('people.show', [
            'person' => $person,
            'gamesWithRoles' => $gamesWithRoles,
            'fromGame' => $fromGame,
        ]);
    }
}
