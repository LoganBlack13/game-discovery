<x-layouts.admin title="Manage games">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Manage games</h1>
        <p class="mt-2 text-zinc-600 dark:text-zinc-400">
            Search, filter, and manage games in the database.
        </p>
        <div class="mt-6">
            <livewire:admin.games-list />
        </div>
    </div>
</x-layouts.admin>
