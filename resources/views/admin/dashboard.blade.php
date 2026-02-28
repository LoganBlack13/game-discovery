<x-layouts.admin title="Admin">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Admin</h1>
        <p class="mt-2 text-zinc-600 dark:text-zinc-400">
            Administrator actions (e.g. update DB, manage content) will be gathered here.
        </p>

        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-4 py-3 shadow-sm">
                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total games</p>
                <p class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ number_format($totalGames) }}</p>
            </div>
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-4 py-3 shadow-sm">
                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Added this week</p>
                <p class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ number_format($recentGamesCount) }}</p>
            </div>
        </div>

        <section class="mt-8" aria-labelledby="latest-games-heading">
            <div class="flex items-center justify-between gap-4">
                <h2 id="latest-games-heading" class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Latest games</h2>
                <a
                    href="{{ route('admin.games.index') }}"
                    class="text-sm font-medium text-zinc-600 dark:text-zinc-400 underline hover:no-underline focus:outline-none focus:ring-2 focus:ring-zinc-500 focus:ring-offset-2 dark:focus:ring-offset-zinc-900 rounded"
                >
                    See more
                </a>
            </div>
            <div class="mt-4 overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Cover</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Title</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Release date</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Source</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse ($latestGames as $game)
                            <tr class="bg-white dark:bg-zinc-900 hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="px-4 py-3">
                                    @if ($game->cover_image)
                                        <img src="{{ $game->cover_image }}" alt="" class="h-10 w-10 shrink-0 rounded object-cover" loading="lazy">
                                    @else
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded bg-zinc-200 dark:bg-zinc-700 text-zinc-400 dark:text-zinc-500 text-xs">—</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('games.show', $game) }}" class="font-medium text-zinc-900 dark:text-zinc-100 hover:underline">{{ $game->title }}</a>
                                </td>
                                <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">{{ $game->release_date?->format('M j, Y') ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">{{ $game->external_source ? ucfirst($game->external_source) : '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-sm text-zinc-500 dark:text-zinc-400">No games yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="mt-8" id="add-game" aria-labelledby="add-game-heading">
            <h2 id="add-game-heading" class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Add game from RAWG</h2>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                Search RAWG and add a game to the database. Shown inline below.
            </p>
            <div class="mt-4">
                <livewire:admin-rawg-add-game />
            </div>
        </section>
    </div>
</x-layouts.admin>
