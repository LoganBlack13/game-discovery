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
                const stored = localStorage.getItem('game-discovery-theme');
                const theme = stored === 'light' || stored === 'dark' || stored === 'system' ? stored : 'system';
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                const effective = theme === 'system' ? (prefersDark ? 'dark' : 'light') : theme;
                document.documentElement.dataset.theme = effective;
            })();
        </script>
    </head>
    <body class="antialiased font-sans bg-base-100 text-base-content">
        <div class="min-h-screen flex flex-col">
            {{-- Header: glass pill bar per spec (48px, rounded-full, bg white/6%, blur) --}}
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
                        <div class="theme-toggle-pill flex shrink-0 items-center gap-2 rounded-full bg-white/[0.08] p-2" x-data="themeToggle()" x-init="init()">
                            <button type="button" class="rounded-full p-1.5 transition-colors" aria-label="Light mode" :class="effectiveTheme === 'light' ? 'bg-white/20 text-white' : 'text-white/[0.7] hover:text-white'" @click="setTheme('light')">
                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 3v1.5m0 16.5V21m9-9h-1.5m-16.5 0H3m15.364 6.364-1.08-1.08M6.344 6.344 5.264 5.264m12.728 0-1.08 1.08M6.344 17.656l-1.08 1.08M21 12h-1.5M4.5 12H3m3.75-6.364-1.08-1.08" /></svg>
                            </button>
                            <button type="button" class="rounded-full p-1.5 transition-colors" aria-label="Dark mode" :class="effectiveTheme === 'dark' ? 'bg-white/20 text-white' : 'text-white/[0.7] hover:text-white'" @click="setTheme('dark')">
                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.93.53-3.746 1.436-5.392a9.72 9.72 0 0 1 15.316 5.144Z" /></svg>
                            </button>
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
                    theme: 'system',
                    effectiveTheme: 'dark',
                    init() {
                        this.theme = localStorage.getItem('game-discovery-theme') || 'system';
                        this.updateEffective();
                        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => this.updateEffective());
                    },
                    setTheme(value) {
                        this.theme = value;
                        localStorage.setItem('game-discovery-theme', value);
                        this.updateEffective();
                    },
                    updateEffective() {
                        this.effectiveTheme = this.theme === 'system'
                            ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
                            : this.theme;
                        document.documentElement.dataset.theme = this.effectiveTheme;
                    }
                };
            }
        </script>
    </body>
</html>
