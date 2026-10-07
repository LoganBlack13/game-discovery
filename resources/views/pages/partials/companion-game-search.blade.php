<div class="flex flex-col gap-2">
    <input
        type="search"
        wire:model.live.debounce.300ms="search"
        placeholder="Search a game in Questlog"
        aria-label="Search a game"
        class="input input-bordered input-sm w-full"
        autofocus
    />
    @if ($this->searchResults->isNotEmpty())
        <ul class="menu rounded-box border border-base-300 bg-base-100 p-1">
            @foreach ($this->searchResults as $result)
                <li wire:key="result-{{ $result->id }}">
                    <button type="button" wire:click="chooseGame({{ $result->id }})">{{ $result->title }}</button>
                </li>
            @endforeach
        </ul>
    @elseif (mb_strlen(mb_trim($search)) >= 2)
        <p class="text-xs text-base-content/60">No game found. <a href="{{ route('game-requests.index') }}" class="link link-primary">Request it</a>.</p>
    @endif
    <div>
        <button type="button" wire:click="cancelSearch" class="btn btn-ghost btn-xs">Cancel</button>
    </div>
</div>
