<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\GameActivityType;
use App\Models\Game;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Builds a unified feed of news and game activities for the user dashboard.
 */
final class DashboardFeedService
{
    private const int MAX_ITEMS_PER_SOURCE = 500;

    /**
     * @return array<int, array{game_id: int, game: Game, type: string, type_label: string, title: string, description: string|null, url: string, occurred_at: Carbon}>
     */
    public function getFeedItems(User $user, string $filter, int $limit, int $offset): array
    {
        $trackedGameIds = $user->trackedGames()->pluck('games.id')->all();
        if ($trackedGameIds === []) {
            return [];
        }

        $items = collect();

        if ($filter === 'all' || $filter === 'news') {
            $news = \App\Models\News::query()
                ->whereIn('game_id', $trackedGameIds)
                ->with('game')
                ->orderByDesc('published_at')
                ->limit(self::MAX_ITEMS_PER_SOURCE)
                ->get();

            foreach ($news as $n) {
                $items->push([
                    'game_id' => $n->game_id,
                    'game' => $n->game,
                    'type' => 'new_article',
                    'type_label' => 'New Article',
                    'title' => $n->title,
                    'description' => null,
                    'url' => $n->url,
                    'occurred_at' => $n->published_at ?? $n->created_at,
                ]);
            }
        }

        if ($filter === 'all' || $filter === 'release') {
            $activities = \App\Models\GameActivity::query()
                ->whereIn('game_id', $trackedGameIds)
                ->with('game')
                ->orderByDesc('occurred_at')
                ->limit(self::MAX_ITEMS_PER_SOURCE)
                ->get();

            foreach ($activities as $a) {
                $items->push([
                    'game_id' => $a->game_id,
                    'game' => $a->game,
                    'type' => $a->type->value,
                    'type_label' => $this->activityTypeLabel($a->type),
                    'title' => $a->title,
                    'description' => $a->description,
                    'url' => $a->url ?? route('games.show', $a->game),
                    'occurred_at' => $a->occurred_at,
                ]);
            }
        }

        return $items
            ->sortByDesc(fn (array $row): Carbon => $row['occurred_at'])
            ->values()
            ->skip($offset)
            ->take($limit)
            ->all();
    }

    private function activityTypeLabel(GameActivityType $type): string
    {
        return match ($type) {
            GameActivityType::ReleaseDateChanged => 'Release Date Changed',
            GameActivityType::ReleaseDateAnnounced => 'Release Date Announced',
            GameActivityType::GameReleased => 'Game Released',
            GameActivityType::MajorUpdate => 'Major Update',
        };
    }
}
