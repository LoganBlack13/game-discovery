{{--
    Dashboard design: Refined editorial — clear section hierarchy, Syne for headings, DM Sans body,
    primary accent for CTAs, staggered fade-in per section. Memorable element: next-release hero
    with countdown and gradient overlay.
--}}
<x-layouts.app title="Dashboard">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <h1 class="font-display text-2xl font-bold tracking-tight text-base-content sm:text-3xl">My tracked games</h1>
        <div class="mt-8 lg:grid lg:grid-cols-[1fr_18rem] lg:gap-8">
            <div class="space-y-10">
                <livewire:dashboard-game-list />
            </div>
            <aside class="mt-10 lg:mt-0 lg:sticky lg:top-24" aria-label="Latest news">
                <livewire:dashboard-news-sidebar />
            </aside>
        </div>
    </div>
</x-layouts.app>
