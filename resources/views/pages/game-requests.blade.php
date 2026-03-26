<x-layouts.app title="Request a game">
    <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
        <header class="mb-8 space-y-1">
            <h1 class="font-display text-3xl font-semibold text-base-content">Request a game</h1>
            <p class="text-sm text-base-content/70">Can't find a game in our catalogue? Submit a request and vote for others.</p>
        </header>

        <div class="mb-10">
            <livewire:game-request-card />
        </div>

        @if ($topRequests->isNotEmpty())
            <section>
                <h2 class="mb-4 font-display text-lg font-semibold text-base-content">Most requested</h2>
                <ul class="flex flex-col gap-2">
                    @foreach ($topRequests as $request)
                        <li class="flex items-center justify-between rounded-box border border-base-300 bg-base-200/40 px-4 py-3">
                            <span class="text-sm font-medium text-base-content">{{ $request->display_title }}</span>
                            <span class="badge badge-neutral badge-sm">{{ $request->request_count }} {{ Str::plural('vote', $request->request_count) }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</x-layouts.app>
