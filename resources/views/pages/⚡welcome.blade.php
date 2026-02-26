<?php

use Livewire\Component;
use Livewire\Attributes\Title;

new #[Title('Discover your next game')] class extends Component
{
    // Placeholder game data for discovery strip
    public function getDiscoveryGames(): array
    {
        return [
            ['title' => 'Neon Drift', 'genre' => 'Racing'],
            ['title' => 'Void Protocol', 'genre' => 'Strategy'],
            ['title' => 'Crimson Echo', 'genre' => 'RPG'],
            ['title' => 'Static Pulse', 'genre' => 'Rhythm'],
            ['title' => 'Havenfall', 'genre' => 'Adventure'],
        ];
    }
};
?>

<div class="relative min-h-[calc(100vh-3.5rem)] overflow-hidden">
    {{-- Background: gradient mesh + grain --}}
    <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_80%_60%_at_50%_-20%,oklch(0.72_0.15_195_/_.15),transparent),radial-gradient(ellipse_60%_50%_at_80%_50%,oklch(0.45_0.12_270_/_.12),transparent),radial-gradient(ellipse_50%_40%_at_20%_80%,oklch(0.5_0.1_195_/_.1),transparent)] dark:bg-[radial-gradient(ellipse_80%_60%_at_50%_-20%,oklch(0.72_0.15_195_/_.2),transparent),radial-gradient(ellipse_60%_50%_at_80%_50%,oklch(0.35_0.1_270_/_.15),transparent),radial-gradient(ellipse_50%_40%_at_20%_80%,oklch(0.4_0.08_195_/_.12),transparent)]" aria-hidden="true"></div>
    <div class="pointer-events-none absolute inset-0 opacity-[0.025] dark:opacity-[0.04]" style="background-image: url('data:image/svg+xml,%3Csvg viewBox=%220 0 256 256%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cfilter id=%22noise%22%3E%3CfeTurbulence type=%22fractalNoise%22 baseFrequency=%220.9%22 numOctaves=%224%22 stitchTiles=%22stitch%22/%3E%3C/filter%3E%3Crect width=%22100%25%22 height=%22100%25%22 filter=%22url(%23noise)%22/%3E%3C/svg%3E');" aria-hidden="true"></div>

    <div class="relative mx-auto max-w-7xl px-4 pt-16 sm:px-6 sm:pt-24 lg:px-8 lg:pt-32">
        {{-- Hero: asymmetric layout, headline left, CTA right on large screens --}}
        <div class="grid gap-12 lg:grid-cols-[1fr_auto] lg:items-end lg:gap-16">
            <div class="space-y-8">
                <h1 class="font-display text-4xl font-extrabold tracking-tight text-zinc-900 dark:text-white sm:text-5xl md:text-6xl lg:text-7xl opacity-0 welcome-animate welcome-animate-delay-1 [text-shadow:0_0_80px_oklch(0.72_0.15_195_/_.4)] dark:[text-shadow:0_0_80px_oklch(0.72_0.15_195_/_.5)]">
                    Discover your next game
                </h1>
                <p class="max-w-xl font-sans text-lg text-zinc-600 dark:text-zinc-400 sm:text-xl opacity-0 welcome-animate welcome-animate-delay-2">
                    Curated picks, hidden gems, and trending titles. One place to find what you’ll play next.
                </p>
                <div class="flex flex-wrap gap-4 opacity-0 welcome-animate welcome-animate-delay-3">
                    <flux:button href="#" variant="primary" color="cyan" size="base" class="focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-zinc-950 transition-transform hover:scale-[1.02] active:scale-[0.98]">
                        Explore games
                    </flux:button>
                    <flux:button href="#" variant="ghost" size="base" class="text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400 dark:focus-visible:ring-zinc-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-zinc-950">
                        See what’s trending
                    </flux:button>
                </div>
            </div>
        </div>

        {{-- Discovery strip: horizontal cards --}}
        <section class="mt-20 sm:mt-28 lg:mt-36" aria-label="Discover games">
            <p class="font-display text-sm font-semibold uppercase tracking-widest text-cyan-600 dark:text-cyan-400 opacity-0 welcome-animate welcome-animate-delay-4 mb-6">
                Trending now
            </p>
            <div class="flex gap-4 overflow-x-auto pb-4 -mx-4 px-4 sm:mx-0 sm:px-0 sm:grid sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 sm:overflow-visible">
                @foreach($this->getDiscoveryGames() as $index => $game)
                    <flux:card
                        class="shrink-0 w-[280px] sm:w-auto transition-transform hover:scale-[1.02] focus-within:ring-2 focus-within:ring-cyan-500 focus-within:ring-offset-2 dark:focus-within:ring-offset-zinc-950 opacity-0 welcome-animate welcome-animate-delay-{{ min(5 + $index, 9) }} border-zinc-200/80 dark:border-white/10 bg-white/90 dark:bg-white/5 backdrop-blur-sm"
                        size="sm"
                    >
                        <a href="#" class="block focus:outline-none">
                            <div class="aspect-[3/4] rounded-lg bg-gradient-to-br from-zinc-200 to-zinc-300 dark:from-zinc-700 dark:to-zinc-800 mb-3 flex items-center justify-center">
                                <span class="font-display text-2xl font-bold text-zinc-400 dark:text-zinc-500">{{ substr($game['title'], 0, 1) }}</span>
                            </div>
                            <h3 class="font-display font-semibold text-zinc-900 dark:text-white">{{ $game['title'] }}</h3>
                            <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">{{ $game['genre'] }}</p>
                        </a>
                    </flux:card>
                @endforeach
            </div>
        </section>
    </div>
</div>
