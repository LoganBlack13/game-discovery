@props(['title' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
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
            (function () {
                const THEMES = ['arcade-night', 'daylight-pastel', 'retro-crt', 'noir-minimal', 'cosmic-fade'];
                const stored = localStorage.getItem('game-discovery-theme');

                function resolveThemeSlug(raw) {
                    if (raw === 'system' || !raw) {
                        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                        return prefersDark ? 'arcade-night' : 'daylight-pastel';
                    }

                    if (raw === 'dark') {
                        return 'arcade-night';
                    }

                    if (raw === 'light') {
                        return 'daylight-pastel';
                    }

                    return THEMES.includes(raw) ? raw : 'arcade-night';
                }

                const effective = resolveThemeSlug(stored);
                document.documentElement.dataset.theme = effective;
            })();
        </script>
    </head>
    <body class="antialiased font-sans bg-base-100 text-base-content">
        <div class="min-h-screen flex flex-col">
            <header class="sticky top-0 z-50 shrink-0 px-4 pt-4">
                <div class="header-bar mx-auto flex h-12 max-w-5xl items-center justify-between gap-6 rounded-full bg-white/[0.06] px-6 py-0 backdrop-blur-[10px] md:justify-between">
                    <a href="{{ url('/') }}" class="flex shrink-0 items-center" aria-label="{{ config('app.name') }} home">
                        <span class="header-logo flex h-8 w-8 items-center justify-center font-display text-xl font-bold">S</span>
                    </a>
                    <nav class="hidden items-center gap-6 md:flex" aria-label="Main">
                        <a href="{{ url('/') }}#coming-soon" class="nav-pill-link rounded-full px-3.5 py-1.5 text-base font-medium text-white/[0.85] transition-colors hover:text-white">Games</a>
                        <a href="{{ url('/') }}#trending" class="nav-pill-link rounded-full px-3.5 py-1.5 text-base font-medium text-white/[0.85] transition-colors hover:text-white">Trending</a>
                        <a href="{{ url('/') }}#latest-news" class="nav-pill-link rounded-full px-3.5 py-1.5 text-base font-medium text-white/[0.85] transition-colors hover:text-white">News</a>
                        <a href="{{ url('/') }}#about" class="nav-pill-link rounded-full px-3.5 py-1.5 text-base font-medium text-white/[0.85] transition-colors hover:text-white">About</a>
                    </nav>
                    <div class="flex items-center gap-6">
                        <button type="button" class="rounded-full p-2 text-white/[0.85] transition-colors hover:bg-white/[0.08] hover:text-white" aria-label="Search games (⌘K)" @click="$dispatch('open-game-search')">
                            <svg class="size-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                        </button>
                        @auth
                            <a href="{{ route('dashboard') }}" class="hidden text-base font-medium text-white/[0.85] hover:text-white sm:inline">Dashboard</a>
                            @if(auth()->user()->isAdmin())
                                <a href="{{ route('admin.dashboard') }}" class="hidden text-base font-medium text-white/[0.85] hover:text-white sm:inline">Admin</a>
                            @endif
                            <a href="{{ route('profile.edit') }}" class="hidden text-base font-medium text-white/[0.85] hover:text-white sm:inline">{{ auth()->user()->name }}</a>
                            <form action="{{ url('/logout') }}" method="POST" class="hidden sm:inline">
                                @csrf
                                <button type="submit" class="text-base font-medium text-white/[0.85] hover:text-white">Log out</button>
                            </form>
                        @else
                            <livewire:auth-dropdown />
                            <a href="{{ url('/register') }}" class="hidden text-base font-medium text-white/[0.85] hover:text-white sm:inline">Register</a>
                        @endauth
                        <div
                            class="theme-toggle-pill flex shrink-0 items-center gap-1.5 rounded-full bg-white/[0.08] px-2 py-1.5"
                            x-data="themeToggle()"
                            x-init="init()"
                        >
                            <button
                                type="button"
                                class="hidden rounded-full px-2 py-1 text-xs font-medium text-white/[0.7] hover:text-white sm:inline-flex"
                                :class="mode === 'system' ? 'bg-white/15 text-white' : ''"
                                @click="setMode('system')"
                            >
                                System
                            </button>
                            <div class="relative" x-data="{ open: false }" x-on:click.outside="open = false">
                                <button
                                    type="button"
                                    class="flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium text-white/[0.85] hover:bg-white/[0.12]"
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
                            <button type="button" class="rounded-full p-2 text-white/[0.85] hover:bg-white/[0.08] hover:text-white" aria-label="Open menu" :aria-expanded="open" @click="open = !open">
                                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                            </button>
                            <div class="fixed inset-0 top-[72px] z-40 bg-black/50 backdrop-blur-sm" x-show="open" x-cloak x-transition @click="open = false"></div>
                            <div class="fixed left-4 right-4 top-[72px] z-50 rounded-2xl border border-white/10 bg-[#0a0f12] p-4 shadow-xl" x-show="open" x-cloak x-transition>
                                <nav class="flex flex-col gap-1" aria-label="Main">
                                    <a href="{{ url('/') }}#coming-soon" class="rounded-full px-3.5 py-2.5 text-base font-medium text-white/[0.85] hover:bg-white/[0.08] hover:text-white" @click="open = false">Games</a>
                                    <a href="{{ url('/') }}#trending" class="rounded-full px-3.5 py-2.5 text-base font-medium text-white/[0.85] hover:bg-white/[0.08] hover:text-white" @click="open = false">Trending</a>
                                    <a href="{{ url('/') }}#latest-news" class="rounded-full px-3.5 py-2.5 text-base font-medium text-white/[0.85] hover:bg-white/[0.08] hover:text-white" @click="open = false">News</a>
                                    <a href="{{ url('/') }}#about" class="rounded-full px-3.5 py-2.5 text-base font-medium text-white/[0.85] hover:bg-white/[0.08] hover:text-white" @click="open = false">About</a>
                                </nav>
                                @auth
                                    <div class="mt-3 flex flex-col gap-1 border-t border-white/10 pt-3">
                                        <a href="{{ route('dashboard') }}" class="rounded-full px-3.5 py-2.5 text-base font-medium text-white/[0.85] hover:bg-white/[0.08]" @click="open = false">Dashboard</a>
                                        @if(auth()->user()->isAdmin())
                                            <a href="{{ route('admin.dashboard') }}" class="rounded-full px-3.5 py-2.5 text-base font-medium text-white/[0.85] hover:bg-white/[0.08]" @click="open = false">Admin</a>
                                        @endif
                                        <a href="{{ route('profile.edit') }}" class="rounded-full px-3.5 py-2.5 text-base font-medium text-white/[0.85] hover:bg-white/[0.08]" @click="open = false">{{ auth()->user()->name }}</a>
                                        <form action="{{ url('/logout') }}" method="POST">@csrf<button type="submit" class="w-full rounded-full px-3.5 py-2.5 text-left text-base font-medium text-white/[0.85] hover:bg-white/[0.08]">Log out</button></form>
                                    </div>
                                @else
                                    <div class="mt-3 border-t border-white/10 pt-3">
                                        <a href="{{ url('/register') }}" class="block rounded-full px-3.5 py-2.5 text-base font-medium text-white/[0.85] hover:bg-white/[0.08]" @click="open = false">Register</a>
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

        @livewireScripts
        <script>
            function themeToggle() {
                return {
                    mode: 'system',
                    effectiveTheme: 'arcade-night',
                    themes: [
                        { slug: 'arcade-night', label: 'Arcade night', swatch: '#0f172a' },
                        { slug: 'daylight-pastel', label: 'Daylight pastel', swatch: '#e0f2f1' },
                        { slug: 'retro-crt', label: 'Retro CRT', swatch: '#0b1020' },
                        { slug: 'noir-minimal', label: 'Noir minimal', swatch: '#020617' },
                        { slug: 'cosmic-fade', label: 'Cosmic fade', swatch: '#020617' },
                    ],
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
                    updateEffectiveFromStorage(raw) {
                        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                        let slug;

                        if (!raw || raw === 'system') {
                            slug = prefersDark ? 'arcade-night' : 'daylight-pastel';
                        } else if (raw === 'dark') {
                            slug = 'arcade-night';
                        } else if (raw === 'light') {
                            slug = 'daylight-pastel';
                        } else if (this.themes.find((t) => t.slug === raw)) {
                            slug = raw;
                        } else {
                            slug = prefersDark ? 'arcade-night' : 'daylight-pastel';
                        }

                        this.effectiveTheme = slug;
                        document.documentElement.dataset.theme = slug;
                    },
                    setTheme(slug) {
                        this.mode = 'explicit';
                        this.effectiveTheme = slug;
                        localStorage.setItem('game-discovery-theme', slug);
                        document.documentElement.dataset.theme = slug;
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
                        return theme ? theme.label : 'Arcade night';
                    },
                };
            }
        </script>
    </body>
</html>
