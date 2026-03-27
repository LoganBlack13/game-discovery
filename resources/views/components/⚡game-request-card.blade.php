<?php

use App\Http\Requests\StoreGameRequestRequest;
use App\Models\GameRequest;
use App\Models\GameRequestVote;
use App\Services\GameRequestNormalizer;
use Livewire\Component;

new class extends Component
{
    public string $title = '';

    public ?string $feedback = null;

    public bool $success = false;

    public function submit(): void
    {
        abort_unless(auth()->check(), 403);

        $this->validate(StoreGameRequestRequest::livewireRules());

        $normalized = GameRequestNormalizer::normalize($this->title);
        $request = GameRequest::query()->firstOrCreate(
            ['normalized_title' => $normalized],
            ['display_title' => $this->title, 'request_count' => 0]
        );
        $request->update(['display_title' => $this->title]);

        GameRequestVote::query()->firstOrCreate(
            [
                'game_request_id' => $request->id,
                'user_id' => auth()->id(),
            ]
        );

        $request->update(['request_count' => $request->votes()->count()]);

        $this->feedback = 'Thanks! Your request has been recorded.';
        $this->success = true;
        $this->title = '';
    }
};
?>

<div class="card card-compact sm:card-normal bg-base-200/60 border border-base-300 shadow-sm">
    <div class="card-body gap-4">
        <p class="text-sm text-base-content/80">
            Don't find what you want? Request a game to add to the database.
        </p>
        <form wire:submit="submit" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="flex-1">
                <label for="game-request-title" class="label label-text font-medium">Game title</label>
                <input
                    id="game-request-title"
                    type="text"
                    wire:model="title"
                    placeholder="e.g. Elden Ring"
                    class="input input-bordered w-full rounded-box border-base-300 bg-base-100"
                    maxlength="255"
                />
                @error('title')
                    <p class="mt-1 text-sm text-error">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="btn btn-primary rounded-btn shrink-0">
                Request game
            </button>
        </form>
        @if ($feedback)
            <p class="text-sm {{ $success ? 'text-success' : 'text-error' }}">
                {{ $feedback }}
            </p>
        @endif
    </div>
</div>
