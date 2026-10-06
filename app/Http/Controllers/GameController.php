<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\CreditDiscipline;
use App\Models\Game;
use App\Models\GameCredit;
use App\Models\Person;
use App\Models\User;
use App\Services\PersonalTrackingService;
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
            'credits' => fn (HasMany $q) => $q->with('person')->orderBy('role'),
        ]);

        $authUser = auth()->user();
        $isTracked = $authUser instanceof User
            && $authUser->trackedGames()->where('game_id', $game->id)->exists();

        $creditsByDiscipline = $this->groupCreditsByDiscipline($game->credits);

        return view('games.show', [
            'game' => $game,
            'isTracked' => $isTracked,
            'creditsByDiscipline' => $creditsByDiscipline,
        ]);
    }

    public function track(Request $request, Game $game, PersonalTrackingService $tracking): JsonResponse|RedirectResponse
    {
        $this->authorize('track', $game);

        $user = $request->user();
        assert($user instanceof User);
        $tracking->track($user, $game);

        if ($request->expectsJson()) {
            return response()->json(['tracked' => true]);
        }

        return back()->with('status', 'game-tracked');
    }

    public function untrack(Request $request, Game $game): JsonResponse|RedirectResponse
    {
        $this->authorize('untrack', $game);

        $user = $request->user();
        assert($user instanceof User);
        $user->trackedGames()->detach($game->id);

        if ($request->expectsJson()) {
            return response()->json(['tracked' => false]);
        }

        return back()->with('status', 'game-untracked');
    }

    /**
     * Group credits by discipline (enum order), then by person, keeping every role of a person.
     *
     * @param  iterable<GameCredit>  $credits
     * @return array<string, list<array{person: Person, roles: list<string>}>>
     */
    private function groupCreditsByDiscipline(iterable $credits): array
    {
        /** @var array<string, array<int, array{person: Person, roles: list<string>}>> $rows */
        $rows = [];
        foreach ($credits as $credit) {
            $discipline = $credit->discipline->value;
            $rows[$discipline][$credit->person_id] ??= ['person' => $credit->person, 'roles' => []];
            if (! in_array($credit->role, $rows[$discipline][$credit->person_id]['roles'], true)) {
                $rows[$discipline][$credit->person_id]['roles'][] = $credit->role;
            }
        }

        $grouped = [];
        foreach (CreditDiscipline::cases() as $discipline) {
            if (! isset($rows[$discipline->value])) {
                continue;
            }

            $people = array_values($rows[$discipline->value]);
            usort($people, fn (array $a, array $b): int => strcasecmp($a['person']->name, $b['person']->name));
            $grouped[$discipline->value] = $people;
        }

        return $grouped;
    }
}
