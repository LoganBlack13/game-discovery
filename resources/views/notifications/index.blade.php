<x-layouts.app title="Notifications">
    <div class="mx-auto max-w-2xl px-4 py-12 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-semibold tracking-tight">Notifications</h1>

        <div class="mt-6 flex flex-col gap-3">
            @forelse ($notifications as $notification)
                @php
                    $data = $notification->data;
                    $isReleased = str_ends_with($notification->type, 'GameReleasedNotification');
                @endphp
                <div class="card card-border bg-base-100 {{ $notification->read_at ? 'opacity-60' : '' }}">
                    <div class="card-body flex-row items-start justify-between gap-4 p-4">
                        <div class="flex flex-col gap-1">
                            <p class="font-medium">
                                @if ($isReleased)
                                    <span class="badge badge-success badge-sm mr-1">Released</span>
                                @else
                                    <span class="badge badge-warning badge-sm mr-1">Date changed</span>
                                @endif
                                <a href="{{ route('games.show', $data['game_slug']) }}" class="hover:underline">
                                    {{ $data['game_title'] }}
                                </a>
                            </p>
                            <p class="text-sm text-base-content/60">
                                @if ($isReleased)
                                    Now available{{ isset($data['release_date']) ? ' · '.\Carbon\Carbon::parse($data['release_date'])->format('M j, Y') : '' }}
                                @else
                                    Release date changed from {{ \Carbon\Carbon::parse($data['old_release_date'])->format('M j, Y') }} to {{ \Carbon\Carbon::parse($data['new_release_date'])->format('M j, Y') }}
                                @endif
                                · {{ $notification->created_at->diffForHumans() }}
                            </p>
                        </div>
                        <form action="{{ route('notifications.destroy', $notification->id) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-ghost btn-xs" aria-label="Dismiss">
                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="text-base-content/50">No notifications yet.</p>
            @endforelse
        </div>

        @if ($notifications->hasPages())
            <div class="mt-8">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</x-layouts.app>
