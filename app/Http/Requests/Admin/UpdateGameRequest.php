<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateGameRequest extends FormRequest
{
    /**
     * Rules for a given game (for use from Livewire).
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rulesForGame(?Game $game): array
    {
        $slugUnique = Rule::unique('games', 'slug');
        if ($game !== null) {
            $slugUnique->ignore($game->id);
        }

        return [
            'editTitle' => ['required', 'string', 'max:255'],
            'editSlug' => ['nullable', 'string', 'max:255', $slugUnique],
            'editDescription' => ['nullable', 'string'],
            'editCoverImage' => ['nullable', 'url', 'max:2048'],
            'editDeveloper' => ['nullable', 'string', 'max:255'],
            'editPublisher' => ['nullable', 'string', 'max:255'],
            'editGenres' => ['nullable', 'string', 'max:1000'],
            'editPlatforms' => ['nullable', 'string', 'max:1000'],
            'editReleaseDate' => ['nullable', 'date'],
            'editReleaseStatus' => ['required', 'string', Rule::enum(\App\Enums\ReleaseStatus::class)],
        ];
    }

    public function authorize(): bool // @codeCoverageIgnore
    {
        return $this->user() instanceof User && $this->user()->isAdmin(); // @codeCoverageIgnore
    } // @codeCoverageIgnore

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array // @codeCoverageIgnore
    {
        $game = $this->route('game'); // @codeCoverageIgnore

        return $this->rulesForGame($game instanceof Game ? $game : null); // @codeCoverageIgnore
    } // @codeCoverageIgnore
}
