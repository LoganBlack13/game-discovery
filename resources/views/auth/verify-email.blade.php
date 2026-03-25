<x-layouts.app title="Verify email">
    <div class="mx-auto flex min-h-[calc(100vh-3.5rem)] max-w-md flex-col justify-center px-4 py-12 sm:px-6 lg:px-8">
        <div class="space-y-8">
            <h1 class="text-2xl font-semibold tracking-tight">Verify your email</h1>
            @if (session('status') === 'verification-link-sent')
                <p class="text-sm text-success">
                    A new verification link has been sent to your email address.
                </p>
            @else
                <p class="text-sm text-base-content/70">
                    Thanks for signing up. Before getting started, please verify your email address by clicking the link we sent you.
                </p>
            @endif
            <div class="flex items-center gap-4">
                <form action="{{ url('/email/verification-notification') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        Resend verification email
                    </button>
                </form>
                <form action="{{ url('/logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-ghost btn-sm">
                        Log out
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-layouts.app>
