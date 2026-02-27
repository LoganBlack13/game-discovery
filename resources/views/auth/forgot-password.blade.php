<x-layouts.app title="Forgot password">
    <div class="mx-auto flex min-h-[calc(100vh-3.5rem)] max-w-md flex-col justify-center px-4 py-12 sm:px-6 lg:px-8">
        <div class="space-y-8">
            <h1 class="text-2xl font-semibold tracking-tight">Forgot password</h1>
            @if (session('status'))
                <p class="text-sm text-green-600 dark:text-green-400">{{ session('status') }}</p>
            @endif
            <form action="{{ url('/forgot-password') }}" method="POST" class="flex flex-col gap-6">
                @csrf
                <div class="flex flex-col gap-2">
                    <label for="email" class="text-sm font-medium">Email</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="email"
                        class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-zinc-900 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                    />
                    @error('email')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <button
                    type="submit"
                    class="rounded-lg bg-zinc-900 px-4 py-2 font-medium text-white dark:bg-zinc-100 dark:text-zinc-900"
                >
                    Email password reset link
                </button>
            </form>
            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                <a href="{{ url('/login') }}" class="underline hover:no-underline">Back to log in</a>
            </p>
        </div>
    </div>
</x-layouts.app>
