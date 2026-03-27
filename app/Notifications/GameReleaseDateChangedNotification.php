<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Game;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class GameReleaseDateChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Game $game,
        public readonly CarbonInterface $oldReleaseDate,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'game_id' => $this->game->id,
            'game_title' => $this->game->title,
            'game_slug' => $this->game->slug,
            'old_release_date' => $this->oldReleaseDate->format('Y-m-d'),
            'new_release_date' => $this->game->release_date?->format('Y-m-d'),
        ];
    }
}
