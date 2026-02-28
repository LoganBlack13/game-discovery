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

        <div class="mt-6">
            <a
                href="{{ route('admin.add-game') }}"
                class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 shadow-sm hover:bg-zinc-50 dark:hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-zinc-500 focus:ring-offset-2 dark:focus:ring-offset-zinc-900"
            >
                Add game from RAWG
            </a>
        </div>
    </div>
</x-layouts.admin>
