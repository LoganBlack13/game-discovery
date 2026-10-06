<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\CreditDiscipline;
use App\Models\Game;
use App\Models\Person;
use App\Services\RawgGameDataProvider;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class SyncGameCreditsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $gameId) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(RawgGameDataProvider $rawg): void
    {
        $game = Game::query()->find($this->gameId);
        if ($game === null) {
            return;
        }

        if ($game->external_source !== 'rawg' || $game->external_id === null || $game->external_id === '') {
            $game->forceFill(['credits_synced_at' => now()])->save();

            return;
        }

        try {
            $members = $rawg->getDevelopmentTeam($game->external_id);
        } catch (InvalidArgumentException $e) {
            $this->fail($e);

            return;
        }

        DB::transaction(function () use ($game, $members): void {
            $game->credits()->delete();

            foreach ($members as $member) {
                $person = $this->upsertPerson($member);

                foreach ($member['roles'] as $role) {
                    $game->credits()->firstOrCreate(
                        ['person_id' => $person->id, 'role' => $role],
                        ['discipline' => CreditDiscipline::fromRole($role)],
                    );
                }
            }

            $game->forceFill(['credits_synced_at' => now()])->save();
        });
    }

    /**
     * Persons are identified by their source id (never by name) so homonyms stay distinct.
     *
     * @param  array{external_id: string, name: string, slug: string, image: string|null, roles: list<string>}  $member
     */
    private function upsertPerson(array $member): Person
    {
        $person = Person::query()->firstOrNew([
            'external_source' => 'rawg',
            'external_id' => $member['external_id'],
        ]);

        $person->name = $member['name'];
        $person->image = $member['image'];

        if (! $person->exists) {
            $slug = Str::slug($member['slug']) ?: 'person';
            $person->slug = Person::query()->where('slug', $slug)->exists()
                ? $slug.'-'.$member['external_id']
                : $slug;
        }

        $person->save();

        return $person;
    }
}
