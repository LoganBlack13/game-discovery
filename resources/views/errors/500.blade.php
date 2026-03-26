<x-layouts.app title="Server error">
    <div class="flex min-h-[60vh] flex-col items-center justify-center px-4 py-16 text-center">
        <p class="font-display text-8xl font-bold text-base-content/20">500</p>
        <h1 class="mt-4 font-display text-2xl font-semibold text-base-content">Server error</h1>
        <p class="mt-3 max-w-sm text-base-content/70">
            Something went wrong on our end. Please try again in a moment.
        </p>
        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            <a href="{{ url('/') }}" class="btn btn-primary rounded-full">Go home</a>
            <a href="{{ route('games.index') }}" class="btn btn-ghost rounded-full">Browse games</a>
        </div>
    </div>
</x-layouts.app>
