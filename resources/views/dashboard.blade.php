<x-layouts.app title="My tracked games">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <h1 class="font-display text-2xl font-bold tracking-tight text-zinc-900 dark:text-white sm:text-3xl">My tracked games</h1>
        <div class="mt-8 lg:grid lg:grid-cols-[1fr_18rem] lg:gap-8">
            <div>
                <livewire:dashboard-game-list />
            </div>
            <aside class="mt-10 lg:mt-0 lg:sticky lg:top-24" aria-label="Latest news">
                <livewire:dashboard-news-sidebar />
            </aside>
        </div>
    </div>
</x-layouts.app>
