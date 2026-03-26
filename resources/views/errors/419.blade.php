<x-layouts.app title="Page expired">
    <div class="flex min-h-[60vh] flex-col items-center justify-center px-4 py-16 text-center">
        <p class="font-display text-8xl font-bold text-base-content/20">419</p>
        <h1 class="mt-4 font-display text-2xl font-semibold text-base-content">Page expired</h1>
        <p class="mt-3 max-w-sm text-base-content/70">
            Your session has expired. Please refresh the page and try again.
        </p>
        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            <a href="{{ url()->current() }}" class="btn btn-primary rounded-full">Refresh the page</a>
            <a href="{{ url('/') }}" class="btn btn-ghost rounded-full">Go home</a>
        </div>
    </div>
</x-layouts.app>
