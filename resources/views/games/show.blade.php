<x-layouts.app :title="$game->title">
    <article class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-8 sm:flex-row sm:gap-12">
            {{-- Cover --}}
            @if ($game->cover_image)
                <div class="shrink-0">
                    <img
                        src="{{ $game->cover_image }}"
                        alt=""
                        class="aspect-[3/4] w-full max-w-sm rounded-xl object-cover shadow-lg sm:w-64"
                    />
                </div>
            @else
                <div class="flex aspect-[3/4] w-full max-w-sm shrink-0 items-center justify-center rounded-xl bg-base-300 sm:w-64">
                    <span class="font-display text-4xl font-bold text-base-content/40">{{ substr($game->title, 0, 1) }}</span>
                </div>
            @endif

            {{-- Meta --}}
            <div class="min-w-0 flex-1 space-y-4">
                <h1 class="font-display text-3xl font-bold tracking-tight text-base-content sm:text-4xl">{{ $game->title }}</h1>

                <dl class="space-y-1 text-sm">
                    @if ($game->developer)
                        <div class="flex gap-2">
                            <dt class="font-medium text-base-content/70">Developer</dt>
                            <dd class="text-base-content">{{ $game->developer }}</dd>
                        </div>
                    @endif
                    @if ($game->publisher)
                        <div class="flex gap-2">
                            <dt class="font-medium text-base-content/70">Publisher</dt>
                            <dd class="text-base-content">{{ $game->publisher }}</dd>
                        </div>
                    @endif
                    @if ($game->release_date)
                        <div class="flex gap-2">
                            <dt class="font-medium text-base-content/70">Release date</dt>
                            <dd class="text-base-content">{{ $game->release_date->format('F j, Y') }}</dd>
                        </div>
                    @endif
                    <div class="flex gap-2">
                        <dt class="font-medium text-base-content/70">Status</dt>
                        <dd>
                            @php
                                $statusBadge = match ($game->release_status) {
                                    \App\Enums\ReleaseStatus::Released => 'badge badge-success badge-sm',
                                    \App\Enums\ReleaseStatus::ComingSoon => 'badge badge-info badge-sm',
                                    \App\Enums\ReleaseStatus::Announced => 'badge badge-ghost badge-sm',
                                    \App\Enums\ReleaseStatus::Delayed => 'badge badge-warning badge-sm',
                                };
                            @endphp
                            <span class="{{ $statusBadge }}">{{ ucfirst(str_replace('_', ' ', $game->release_status->value)) }}</span>
                        </dd>
                    </div>
                </dl>

                @if ($game->release_date && $game->release_date->isFuture())
                    <div
                        class="rounded-xl bg-info/10 p-4"
                        data-release-iso="{{ $game->release_date->toIso8601String() }}"
                        data-countdown
                        role="timer"
                        aria-live="polite"
                    >
                        <p class="text-sm font-medium text-info-content/80">Time until release</p>
                        <p class="mt-1 font-mono text-lg tabular-nums text-base-content" data-countdown-display>—</p>
                    </div>
                @endif

                @if (count($game->genres) > 0)
                    <div class="flex flex-wrap gap-2">
                        @foreach ($game->genres as $genre)
                            <span class="badge badge-ghost">{{ $genre }}</span>
                        @endforeach
                    </div>
                @endif

                @if (count($game->platforms) > 0)
                    <div class="flex flex-wrap gap-2">
                        @foreach ($game->platforms as $platform)
                            <span class="badge badge-outline badge-sm">{{ $platform }}</span>
                        @endforeach
                    </div>
                @endif

                <div class="pt-2">
                    @guest
                        <a href="{{ route('login') }}" class="btn btn-primary btn-sm" aria-label="Log in to track this game">
                            Log in to track
                        </a>
                    @else
                        @if ($isTracked)
                            <form action="{{ route('games.untrack', $game) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline btn-sm" aria-label="Remove {{ $game->title }} from tracking">
                                    Remove from tracking
                                </button>
                            </form>
                        @else
                            <form action="{{ route('games.track', $game) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-sm" aria-label="Track {{ $game->title }}">
                                    Track game
                                </button>
                            </form>
                        @endif
                    @endguest
                </div>
            </div>
        </div>

        {{-- About --}}
        @if ($game->description)
            <div class="mt-10 border-t border-base-content/10 pt-10">
                <h2 class="font-display text-lg font-semibold text-base-content">About</h2>
                <p class="mt-3 whitespace-pre-wrap text-base-content/70">{{ $game->description }}</p>
            </div>
        @endif

        {{-- Activity timeline --}}
        @if ($game->activities->isNotEmpty())
            <section class="mt-10 border-t border-base-content/10 pt-10" aria-label="Activity">
                <h2 class="font-display text-lg font-semibold text-base-content">Activity</h2>
                <ol class="mt-4 flex flex-col gap-0" role="list">
                    @foreach ($game->activities as $activity)
                        @php
                            $badgeClass = match ($activity->type) {
                                \App\Enums\GameActivityType::ReleaseDateChanged => 'badge badge-warning badge-sm',
                                \App\Enums\GameActivityType::ReleaseDateAnnounced => 'badge badge-success badge-sm',
                                \App\Enums\GameActivityType::GameReleased => 'badge badge-success badge-sm',
                                \App\Enums\GameActivityType::MajorUpdate => 'badge badge-secondary badge-sm',
                            };
                            $dotClass = match ($activity->type) {
                                \App\Enums\GameActivityType::ReleaseDateChanged => 'bg-warning',
                                \App\Enums\GameActivityType::ReleaseDateAnnounced => 'bg-success',
                                \App\Enums\GameActivityType::GameReleased => 'bg-success',
                                \App\Enums\GameActivityType::MajorUpdate => 'bg-secondary',
                            };
                        @endphp
                        <li class="relative flex gap-4 pb-6 last:pb-0">
                            {{-- Timeline line --}}
                            <div class="relative flex flex-col items-center">
                                <span class="mt-1 size-2.5 shrink-0 rounded-full {{ $dotClass }}"></span>
                                @if (! $loop->last)
                                    <span class="mt-1 w-px grow bg-base-content/10"></span>
                                @endif
                            </div>
                            <div class="min-w-0 flex-1 pb-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="{{ $badgeClass }}">{{ $activity->title }}</span>
                                    <time
                                        datetime="{{ $activity->occurred_at->toIso8601String() }}"
                                        class="text-xs text-base-content/50"
                                    >{{ $activity->occurred_at->format('M j, Y') }}</time>
                                </div>
                                @if ($activity->description)
                                    <p class="mt-1 text-sm text-base-content/70">{{ $activity->description }}</p>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>
        @endif

        {{-- News --}}
        @if ($game->news->isNotEmpty())
            <section class="mt-10 border-t border-base-content/10 pt-10" aria-label="News">
                <h2 class="font-display text-lg font-semibold text-base-content">News</h2>
                <ul class="mt-4 flex flex-col gap-3">
                    @foreach ($game->news as $item)
                        <li>
                            <a
                                href="{{ $item->url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="flex gap-4 rounded-box border border-base-content/10 p-4 transition-colors hover:bg-base-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                            >
                                @if ($item->thumbnail)
                                    <img src="{{ $item->thumbnail }}" alt="" class="h-20 w-32 shrink-0 rounded-lg object-cover" />
                                @endif
                                <div class="min-w-0 flex-1">
                                    <span class="font-medium text-base-content">{{ $item->title }}</span>
                                    @if ($item->source)
                                        <span class="ml-2 text-sm text-base-content/50">{{ $item->source }}</span>
                                    @endif
                                    @if ($item->published_at)
                                        <p class="mt-0.5 text-sm text-base-content/50">{{ $item->published_at->format('M j, Y') }}</p>
                                    @endif
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </article>

    @if ($game->release_date && $game->release_date->isFuture())
        <script>
            (function () {
                const el = document.querySelector('[data-countdown]');
                const display = document.querySelector('[data-countdown-display]');
                if (!el || !display) return;
                const releaseIso = el.dataset.releaseIso;
                if (!releaseIso) return;
                function update() {
                    const now = new Date();
                    const release = new Date(releaseIso);
                    if (release <= now) {
                        display.textContent = 'Released';
                        return;
                    }
                    const d = Math.max(0, Math.floor((release - now) / 86400000));
                    const h = Math.max(0, Math.floor(((release - now) % 86400000) / 3600000));
                    const m = Math.max(0, Math.floor(((release - now) % 3600000) / 60000));
                    display.textContent = d + 'd ' + h + 'h ' + m + 'm';
                }
                update();
                setInterval(update, 60000);
            })();
        </script>
    @endif
</x-layouts.app>
