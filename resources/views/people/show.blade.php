<x-layouts.app :title="$person->name">
    <article class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="flex items-center gap-6">
            @if ($person->image)
                <img src="{{ $person->image }}" alt="" class="size-24 shrink-0 rounded-full object-cover shadow" />
            @else
                <div class="flex size-24 shrink-0 items-center justify-center rounded-full bg-base-300">
                    <span class="font-display text-3xl font-bold text-base-content/40">{{ mb_substr($person->name, 0, 1) }}</span>
                </div>
            @endif
            <div class="min-w-0">
                <p class="text-sm font-medium uppercase tracking-wide text-base-content/60">Contributor</p>
                <h1 class="font-display text-3xl font-bold tracking-tight text-base-content sm:text-4xl">{{ $person->name }}</h1>
                <p class="mt-1 text-sm text-base-content/70">{{ count($gamesWithRoles) }} {{ Str::plural('game', count($gamesWithRoles)) }} known on Questlog</p>
            </div>
        </div>

        @if ($fromGame)
            <div class="mt-8 rounded-box border border-primary/30 bg-primary/10 p-4" data-from-game>
                <p class="text-sm text-base-content/70">On <a href="{{ route('games.show', $fromGame['game']) }}" class="link link-hover font-medium text-base-content">{{ $fromGame['game']->title }}</a></p>
                <p class="mt-1 font-medium text-base-content">{{ implode(', ', array_map(fn ($credit) => $credit->role, $fromGame['credits'])) }}</p>
            </div>
        @endif

        <section class="mt-10 border-t border-base-content/10 pt-10" aria-labelledby="ludography-title">
            <h2 id="ludography-title" class="font-display text-lg font-semibold text-base-content">Ludography</h2>
            <p class="mt-1 text-sm text-base-content/60">Only games available on Questlog are listed — this ludography may be incomplete.</p>
            <ul class="mt-4 flex flex-col gap-3" role="list">
                @foreach ($gamesWithRoles as $row)
                    <li>
                        <a
                            href="{{ route('games.show', $row['game']) }}"
                            class="flex gap-4 rounded-box border border-base-content/10 p-3 transition-colors hover:bg-base-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                        >
                            @if ($row['game']->cover_image)
                                <img src="{{ $row['game']->cover_image }}" alt="" class="h-20 w-16 shrink-0 rounded-lg object-cover" />
                            @else
                                <div class="flex h-20 w-16 shrink-0 items-center justify-center rounded-lg bg-base-300">
                                    <span class="font-display text-xl font-bold text-base-content/40">{{ mb_substr($row['game']->title, 0, 1) }}</span>
                                </div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <span class="font-medium text-base-content">{{ $row['game']->title }}</span>
                                @if ($row['game']->release_date)
                                    <span class="ml-2 text-sm text-base-content/50">{{ $row['game']->release_date->format('Y') }}</span>
                                @endif
                                <div class="mt-1 flex flex-wrap gap-1.5">
                                    @foreach ($row['credits'] as $credit)
                                        <span class="badge badge-outline badge-sm">{{ $credit->role }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    </article>
</x-layouts.app>
