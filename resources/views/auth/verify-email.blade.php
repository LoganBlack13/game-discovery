<x-layouts.app title="Verify email">
    <div class="mx-auto flex min-h-[calc(100vh-3.5rem)] max-w-md flex-col justify-center px-4 py-12 sm:px-6 lg:px-8">
        <div class="space-y-8">
            <h1 class="text-2xl font-semibold tracking-tight">Verify your email</h1>
            @if (session('status') === 'verification-link-sent')
                <p class="text-sm text-green-600 dark:text-green-400">
                    A new verification link has been sent to your email address.
                </p>
            @else
                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    Thanks for signing up. Before getting started, please verify your email address by clicking the link we sent you.
                </p>
            @endif
            <form action="{{ url('/email/verification-notification') }}" method="POST" class="inline">
                @csrf
                <button
                    type="submit"
                    class="rounded-lg bg-zinc-900 px-4 py-2 font-medium text-white dark:bg-zinc-100 dark:text-zinc-900"
                >
                    Resend verification email
                </button>
            </form>
            <form action="{{ url('/logout') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="ml-2 text-sm text-zinc-600 underline hover:no-underline dark:text-zinc-400">
                    Log out
                </button>
            </form>
        </div>
    </div>
</x-layouts.app>
