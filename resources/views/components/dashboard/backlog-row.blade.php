{{-- Compact backlog row. Expects $entry (TrackedGame with game), $isFirst, $isLast; rendered inside the dashboard-backlog Livewire component. --}}
<li wire:key="backlog-{{ $entry->id }}" class="flex flex-wrap items-center gap-3 px-3 py-2">
    <div class="flex flex-col">
        <button type="button" wire:click="move({{ $entry->id }}, -1)" class="btn btn-ghost btn-xs px-1" aria-label="Move {{ $entry->game->title }} up" @disabled($isFirst)>▲</button>
        <button type="button" wire:click="move({{ $entry->id }}, 1)" class="btn btn-ghost btn-xs px-1" aria-label="Move {{ $entry->game->title }} down" @disabled($isLast)>▼</button>
    </div>
    <a href="{{ route('games.show', $entry->game) }}" class="link link-hover min-w-40 flex-1 truncate font-medium text-base-content">{{ $entry->game->title }}</a>
    @if ($entry->game->release_date && $entry->game->release_date->isFuture())
        <span class="badge badge-ghost badge-sm">Out {{ $entry->game->release_date->format('M j, Y') }}</span>
    @endif
    <label for="priority-{{ $entry->id }}" class="sr-only">Priority for {{ $entry->game->title }}</label>
    <select
        id="priority-{{ $entry->id }}"
        wire:change="setPriority({{ $entry->id }}, $event.target.value)"
        class="select select-bordered select-xs w-28"
    >
        <option value="">No priority</option>
        @foreach (\App\Enums\BacklogPriority::cases() as $backlogPriority)
            <option value="{{ $backlogPriority->value }}" @selected($entry->priority === $backlogPriority)>{{ $backlogPriority->label() }}</option>
        @endforeach
    </select>
    <button
        type="button"
        wire:click="togglePlayNext({{ $entry->id }})"
        @class(['btn btn-xs', 'btn-primary' => $entry->is_up_next, 'btn-outline' => ! $entry->is_up_next])
        aria-pressed="{{ $entry->is_up_next ? 'true' : 'false' }}"
    >{{ $entry->is_up_next ? 'Unpin' : 'Play next' }}</button>
    <button type="button" wire:click="start({{ $entry->id }})" class="btn btn-ghost btn-xs">Start</button>
</li>
