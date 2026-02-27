<x-layouts.app title="Recovery codes">
    <div class="mx-auto max-w-2xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="space-y-6">
            <h1 class="text-2xl font-semibold tracking-tight">Recovery codes</h1>
            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                Store these recovery codes in a safe place. Each code can only be used once.
            </p>
            @if (count($recoveryCodes) > 0)
                <ul class="grid list-none gap-2 font-mono text-sm">
                    @foreach ($recoveryCodes as $code)
                        <li class="rounded bg-zinc-100 px-3 py-2 dark:bg-zinc-800">{{ $code }}</li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-zinc-500 dark:text-zinc-400">No recovery codes available.</p>
            @endif
            <p>
                <a href="{{ route('profile.edit') }}" class="text-sm underline hover:no-underline">Back to profile</a>
            </p>
        </div>
    </div>
</x-layouts.app>
