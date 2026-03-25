<x-layouts.app title="Log in">
    <div class="mx-auto flex min-h-[calc(100vh-3.5rem)] max-w-md flex-col justify-center px-4 py-12 sm:px-6 lg:px-8">
        <div class="space-y-8">
            <h1 class="text-2xl font-semibold tracking-tight">Log in</h1>
            <form action="{{ url('/login') }}" method="POST" class="flex flex-col gap-6">
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
                <div class="flex flex-col gap-2">
                    <label for="password" class="text-sm font-medium">Password</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        class="input input-bordered w-full"
                    />
                    @error('password')
                        <p class="text-sm text-error">{{ $message }}</p>
                    @enderror
                </div>
                <label class="flex cursor-pointer items-center gap-2">
                    <input type="checkbox" name="remember" class="checkbox checkbox-sm" />
                    <span class="text-sm">Remember me</span>
                </label>
                <button type="submit" class="btn btn-primary w-full">
                    Log in
                </button>
            </form>
            <p class="text-sm text-base-content/70">
                <a href="{{ url('/forgot-password') }}" class="link link-hover">Forgot your password?</a>
            </p>
            <p class="text-sm text-base-content/70">
                Don't have an account? <a href="{{ url('/register') }}" class="link link-hover">Register</a>
            </p>
        </div>
    </div>
</x-layouts.app>
