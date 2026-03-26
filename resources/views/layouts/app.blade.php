@props(['title' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ config('themes.default_dark') }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
        <script>
            window.GAME_DISCOVERY_THEMES = @json(config('themes'));
        </script>
        <script>
            (function () {
                const config = window.GAME_DISCOVERY_THEMES;
                const slugs = config.themes.map(function (t) { return t.slug; });
                const stored = localStorage.getItem('game-discovery-theme');

                function resolveThemeSlug(raw) {
                    if (raw === 'system' || !raw) {
                        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                        return prefersDark ? config.default_dark : config.default_light;
                    }

                    if (raw === 'dark') {
                        return config.default_dark;
                    }

                    if (raw === 'light') {
                        return config.default_light;
                    }

                    return slugs.indexOf(raw) !== -1 ? raw : config.default_dark;
                }

                function themeType(slug) {
                    const theme = config.themes.find(function (t) { return t.slug === slug; });
                    return theme && theme.light ? 'light' : 'dark';
                }

                const effective = resolveThemeSlug(stored);
                document.documentElement.dataset.theme = effective;
                document.documentElement.dataset.themeType = themeType(effective);
            })();
        </script>
    </head>
    <body class="antialiased font-sans bg-base-100 text-base-content">
        <div class="min-h-screen flex flex-col">
            <header class="sticky top-0 z-50 shrink-0 px-4 pt-4">
                <div class="header-bar mx-auto flex h-12 max-w-5xl items-center justify-between gap-6 rounded-full bg-base-100/10 px-6 py-0 backdrop-blur-[10px] md:justify-between">
                    <a href="{{ url('/') }}" class="flex shrink-0 items-center" aria-label="{{ config('app.name') }} home">
                        <span class="header-logo flex h-8 w-8 items-center justify-center font-display text-xl font-bold">S</span>
                    </a>
                    <nav class="hidden items-center gap-6 md:flex" aria-label="Main">
                        <a href="{{ route('games.index') }}" class="nav-pill-link rounded-full px-3.5 py-1.5 text-base font-medium text-base-content/80 transition-colors hover:text-base-content">Games</a>
                        @guest
                            <a href="{{ url('/') }}#how-it-works" class="nav-pill-link rounded-full px-3.5 py-1.5 text-base font-medium text-base-content/80 transition-colors hover:text-base-content">How it works</a>
                        @endguest
                    </nav>
                    <div class="flex items-center gap-6">
                        <button type="button" class="flex items-center gap-2 rounded-full px-3 py-2 text-base-content/80 transition-colors hover:bg-base-100/20 hover:text-base-content" aria-label="Search games (⌘K)" onclick="window.dispatchEvent(new CustomEvent('open-game-search'))">
                            <svg class="size-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                            <kbd class="spotlight-shortcut-badge pointer-events-none hidden rounded border border-base-content/20 bg-base-100/50 px-1.5 py-0.5 font-sans text-[10px] font-medium tabular-nums text-base-content/60 sm:inline-flex">⌘K</kbd>
                        </button>
                        @auth
                            <a
                                href="{{ route('notifications.index') }}"
                                class="relative flex items-center justify-center rounded-full p-2 text-base-content/70 transition-colors hover:bg-base-100/20 hover:text-base-content"
                                aria-label="Notifications"
                            >
                                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                                </svg>
                                @php($unreadCount = auth()->user()->unreadNotifications()->count())
                                @if ($unreadCount > 0)
                                    <span class="absolute right-1 top-1 flex size-2 rounded-full bg-primary"></span>
                                @endif
                            </a>
                            <div
                                class="relative hidden sm:block"
                                x-data="{ open: false }"
                                x-on:click.outside="open = false"
                            >
                                <button
                                    type="button"
                                    class="flex items-center gap-2 rounded-full bg-base-100/10 px-3 py-1.5 text-sm font-medium text-base-content/80 hover:bg-base-100/20 hover:text-base-content"
                                    :aria-expanded="open"
                                    aria-haspopup="menu"
                                    @click="open = !open"
                                >
                                    @if (auth()->user()->profile_photo_path)
                                        <img src="{{ asset('storage/'.auth()->user()->profile_photo_path) }}" alt="" class="size-6 rounded-full object-cover" />
                                    @else
                                        <span class="inline-flex size-6 items-center justify-center rounded-full bg-base-100/20 text-xs font-semibold uppercase">
                                            {{ \Illuminate\Support\Str::substr(auth()->user()->name, 0, 1) }}
                                        </span>
                                    @endif
                                    <span class="max-w-[7rem] truncate text-left">{{ auth()->user()->name }}</span>
                                    <svg class="size-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 9l6 6 6-6" />
                                    </svg>
                                </button>
                                <div
                                    class="absolute right-0 z-40 mt-2 w-48 rounded-2xl border border-base-content/10 bg-base-200/95 p-1.5 shadow-xl backdrop-blur"
                                    x-show="open"
                                    x-cloak
                                    x-transition
                                    role="menu"
                                >
                                    <div class="flex flex-col gap-0.5 text-sm">
                                        <a
                                            href="{{ route('dashboard') }}"
                                            class="rounded-xl px-2.5 py-2 text-base-content/80 hover:bg-base-300/70 hover:text-base-content"
                                            @click="open = false"
                                            role="menuitem"
                                        >
                                            Dashboard
                                        </a>
                                        @if(auth()->user()->isAdmin())
                                            <a
                                                href="{{ route('admin.dashboard') }}"
                                                class="rounded-xl px-2.5 py-2 text-base-content/80 hover:bg-base-300/70 hover:text-base-content"
                                                @click="open = false"
                                                role="menuitem"
                                            >
                                                Admin
                                            </a>
                                        @endif
                                        <a
                                            href="{{ route('profile.edit') }}"
                                            class="rounded-xl px-2.5 py-2 text-base-content/80 hover:bg-base-300/70 hover:text-base-content"
                                            @click="open = false"
                                            role="menuitem"
                                        >
                                            Profile
                                        </a>
                                        <form action="{{ url('/logout') }}" method="POST" role="none">
                                            @csrf
                                            <button
                                                type="submit"
                                                class="flex w-full items-center justify-between rounded-xl px-2.5 py-2 text-left text-base-content/80 hover:bg-base-300/70 hover:text-base-content"
                                                role="menuitem"
                                            >
                                                <span>Log out</span>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @else
                            <a href="{{ url('/login') }}" class="nav-pill-link rounded-full px-3.5 py-1.5 text-sm font-medium text-base-content/80 transition-colors hover:text-base-content">Sign in</a>
                            <a href="{{ url('/register') }}" class="btn btn-primary btn-sm rounded-full px-4 py-2 font-medium">Start tracking</a>
                        @endauth
                        <div
                            class="theme-toggle-pill flex shrink-0 items-center gap-1.5 rounded-full bg-base-100/10 px-2 py-1.5"
                            x-data="themeToggle(window.GAME_DISCOVERY_THEMES)"
                            x-init="init()"
                        >
                            <button
                                type="button"
                                class="hidden rounded-full px-2 py-1 text-xs font-medium text-base-content/70 hover:text-base-content sm:inline-flex"
                                :class="mode === 'system' ? 'bg-base-100/20 text-base-content' : ''"
                                @click="setMode('system')"
                            >
                                System
                            </button>
                            <div class="relative" x-data="{ open: false }" x-on:click.outside="open = false">
                                <button
                                    type="button"
                                    class="flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium text-base-content/80 hover:bg-base-100/15"
                                    @click="open = !open"
                                    :aria-expanded="open"
                                    aria-haspopup="listbox"
                                >
                                    <span x-text="labelForTheme(effectiveTheme)"></span>
                                    <svg class="size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 9l6 6 6-6" />
                                    </svg>
                                </button>
                                <div
                                    class="absolute right-0 z-50 mt-1 w-44 rounded-xl border border-base-content/10 bg-base-200/95 p-1 shadow-xl backdrop-blur"
                                    x-show="open"
                                    x-cloak
                                >
                                    <ul class="max-h-64 overflow-auto text-xs" role="listbox">
                                        <template x-for="theme in themes" :key="theme.slug">
                                            <li>
                                                <button
                                                    type="button"
                                                    class="flex w-full items-center justify-between gap-2 rounded-lg px-2.5 py-1.5 text-left hover:bg-base-300"
                                                    :class="theme.slug === effectiveTheme ? 'bg-base-300/80' : ''"
                                                    @click="setTheme(theme.slug); open = false"
                                                    role="option"
                                                    :aria-selected="theme.slug === effectiveTheme"
                                                >
                                                    <span x-text="theme.label"></span>
                                                    <span
                                                        class="size-3 rounded-full border border-base-content/20"
                                                        :style="`background: ${theme.swatch}`"
                                                        aria-hidden="true"
                                                    ></span>
                                                </button>
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="relative md:hidden" x-data="{ open: false }" x-on:click.outside="open = false">
                            <button type="button" class="rounded-full p-2 text-base-content/80 hover:bg-base-100/20 hover:text-base-content" aria-label="Open menu" :aria-expanded="open" @click="open = !open">
                                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                            </button>
                            <div class="fixed inset-0 top-[72px] z-40 bg-black/50 backdrop-blur-sm" x-show="open" x-cloak x-transition @click="open = false"></div>
                            <div class="fixed left-4 right-4 top-[72px] z-50 rounded-2xl border border-base-content/10 bg-base-950 p-4 shadow-xl" x-show="open" x-cloak x-transition>
                                <nav class="flex flex-col gap-1" aria-label="Main">
                                    <a href="{{ route('games.index') }}" class="rounded-full px-3.5 py-2.5 text-base font-medium text-base-content/80 hover:bg-base-100/10 hover:text-base-content" @click="open = false">Games</a>
                                    @guest
                                        <a href="{{ url('/') }}#how-it-works" class="rounded-full px-3.5 py-2.5 text-base font-medium text-base-content/80 hover:bg-base-100/10 hover:text-base-content" @click="open = false">How it works</a>
                                        <a href="{{ url('/login') }}" class="rounded-full px-3.5 py-2.5 text-base font-medium text-base-content/80 hover:bg-base-100/10 hover:text-base-content" @click="open = false">Sign in</a>
                                        <a href="{{ url('/register') }}" class="btn btn-primary btn-sm rounded-full px-4 py-2.5 text-center font-medium" @click="open = false">Start tracking</a>
                                    @endguest
                                </nav>
                                @auth
                                    <div class="mt-3 flex flex-col gap-1 border-t border-base-content/10 pt-3">
                                        <a href="{{ route('dashboard') }}" class="rounded-full px-3.5 py-2.5 text-base font-medium text-base-content/80 hover:bg-base-100/10" @click="open = false">Dashboard</a>
                                        @if(auth()->user()->isAdmin())
                                            <a href="{{ route('admin.dashboard') }}" class="rounded-full px-3.5 py-2.5 text-base font-medium text-base-content/80 hover:bg-base-100/10" @click="open = false">Admin</a>
                                        @endif
                                        <a href="{{ route('profile.edit') }}" class="rounded-full px-3.5 py-2.5 text-base font-medium text-base-content/80 hover:bg-base-100/10" @click="open = false">{{ auth()->user()->name }}</a>
                                        <form action="{{ url('/logout') }}" method="POST">@csrf<button type="submit" class="w-full rounded-full px-3.5 py-2.5 text-left text-base font-medium text-base-content/80 hover:bg-base-100/10">Log out</button></form>
                                    </div>
                                @else
                                    <div class="mt-3 border-t border-base-content/10 pt-3">
                                        <a href="{{ url('/register') }}" class="block rounded-full px-3.5 py-2.5 text-base font-medium text-base-content/80 hover:bg-base-100/10" @click="open = false">Register</a>
                                    </div>
                                @endauth
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            <main class="grow">
                {{ $slot }}
            </main>

            <footer class="mt-auto border-t border-base-content/10 bg-base-200/50">
                <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6">
                    <div class="flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
                        <div class="max-w-sm">
                            <a href="{{ url('/') }}" class="font-display text-lg font-semibold text-base-content">{{ config('app.name') }}</a>
                            <p class="mt-2 text-sm text-base-content/70">
                                Track upcoming releases, follow news for your games, and plan your backlog.
                            </p>
                        </div>
                        <nav class="flex flex-wrap gap-6 text-sm" aria-label="Footer">
                            <a href="{{ route('games.index') }}" class="link link-hover text-base-content/80">Games</a>
                            <a href="{{ url('/') }}#how-it-works" class="link link-hover text-base-content/80">How it works</a>
                            <a href="{{ url('/privacy') }}" class="link link-hover text-base-content/80">Privacy</a>
                            <a href="{{ url('/terms') }}" class="link link-hover text-base-content/80">Terms</a>
                        </nav>
                    </div>
                </div>
            </footer>
        </div>

        <div
            class="fixed inset-0 z-[100]"
            x-data="{ open: false }"
            x-show="open"
            x-cloak
            :class="{ 'spotlight-open': open }"
            x-bind:data-spotlight-open="open ? 'true' : ''"
            x-on:open-game-search.window="open = true; $nextTick(() => $dispatch('spotlight-opened'))"
            x-on:keydown.escape.window="if (open) open = false"
            x-on:close-game-search.window="open = false"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        >
            <livewire:game-search-modal />
        </div>

        <script>
            document.addEventListener('keydown', function (e) {
                if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                    var t = e.target;
                    if (t && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.isContentEditable)) {
                        return;
                    }
                    e.preventDefault();
                    window.dispatchEvent(new CustomEvent('open-game-search'));
                }
            });
        </script>

        @livewireScripts
        <script>
            function themeToggle(config) {
                return {
                    mode: 'system',
                    effectiveTheme: config.default_dark,
                    themes: config.themes,
                    defaultDark: config.default_dark,
                    defaultLight: config.default_light,
                    init() {
                        const stored = localStorage.getItem('game-discovery-theme');
                        if (stored === 'system' || !stored) {
                            this.mode = 'system';
                        } else if (stored === 'light' || stored === 'dark') {
                            this.mode = 'explicit';
                        } else if (this.themes.find((t) => t.slug === stored)) {
                            this.mode = 'explicit';
                        }

                        this.updateEffectiveFromStorage(stored);

                        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                            if (this.mode === 'system') {
                                this.updateEffectiveFromStorage('system');
                            }
                        });
                    },
                    themeType(slug) {
                        const theme = this.themes.find((t) => t.slug === slug);
                        return theme && theme.light ? 'light' : 'dark';
                    },
                    updateEffectiveFromStorage(raw) {
                        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                        let slug;

                        if (!raw || raw === 'system') {
                            slug = prefersDark ? this.defaultDark : this.defaultLight;
                        } else if (raw === 'dark') {
                            slug = this.defaultDark;
                        } else if (raw === 'light') {
                            slug = this.defaultLight;
                        } else if (this.themes.find((t) => t.slug === raw)) {
                            slug = raw;
                        } else {
                            slug = prefersDark ? this.defaultDark : this.defaultLight;
                        }

                        this.effectiveTheme = slug;
                        document.documentElement.dataset.theme = slug;
                        document.documentElement.dataset.themeType = this.themeType(slug);
                    },
                    setTheme(slug) {
                        this.mode = 'explicit';
                        this.effectiveTheme = slug;
                        localStorage.setItem('game-discovery-theme', slug);
                        document.documentElement.dataset.theme = slug;
                        document.documentElement.dataset.themeType = this.themeType(slug);
                    },
                    setMode(value) {
                        this.mode = value;
                        if (value === 'system') {
                            localStorage.setItem('game-discovery-theme', 'system');
                            this.updateEffectiveFromStorage('system');
                        }
                    },
                    labelForTheme(slug) {
                        const theme = this.themes.find((t) => t.slug === slug);
                        return theme ? theme.label : this.themes[0].label;
                    },
                };
            }
        </script>
    </body>
</html>
