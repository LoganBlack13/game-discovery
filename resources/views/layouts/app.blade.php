<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
        @fluxAppearance
    </head>
    <body class="antialiased font-sans bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100">
        <div class="min-h-screen flex flex-col">
            <header class="sticky top-0 z-50 shrink-0 border-b border-zinc-200/80 dark:border-zinc-800/80 bg-white/80 dark:bg-zinc-950/80 backdrop-blur-md">
                <div class="mx-auto flex h-14 max-w-7xl items-center justify-end gap-4 px-4 sm:px-6 lg:px-8">
                    <flux:radio.group x-data variant="segmented" x-model="$flux.appearance" class="shrink-0">
                        <flux:radio value="light" icon="sun">Light</flux:radio>
                        <flux:radio value="dark" icon="moon">Dark</flux:radio>
                        <flux:radio value="system" icon="computer-desktop">System</flux:radio>
                    </flux:radio.group>
                </div>
            </header>
            <main class="grow">
                {{ $slot }}
            </main>
        </div>

        @livewireScripts
        @fluxScripts
    </body>
</html>
