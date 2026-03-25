<x-layouts.app title="Forgot password">
    <div class="mx-auto flex min-h-[calc(100vh-3.5rem)] max-w-md flex-col justify-center px-4 py-12 sm:px-6 lg:px-8">
        <div class="space-y-8">
            <h1 class="text-2xl font-semibold tracking-tight">Forgot password</h1>
            @if (session('status'))
                <p class="text-sm text-success">{{ session('status') }}</p>
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
                        class="input input-bordered w-full"
                    />
                    @error('email')
                        <p class="text-sm text-error">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="btn btn-primary w-full">
                    Email password reset link
                </button>
            </form>
            <p class="text-sm text-base-content/70">
                <a href="{{ url('/login') }}" class="link link-hover">Back to log in</a>
            </p>
        </div>
    </div>
</x-layouts.app>
