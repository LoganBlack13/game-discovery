<x-layouts.app title="Register">
    <div class="mx-auto flex min-h-[calc(100vh-3.5rem)] max-w-md flex-col justify-center px-4 py-12 sm:px-6 lg:px-8">
        <div class="space-y-8">
            <h1 class="text-2xl font-semibold tracking-tight">Register</h1>
            <form action="{{ url('/register') }}" method="POST" class="flex flex-col gap-6">
                @csrf
                <div class="flex flex-col gap-2">
                    <label for="name" class="text-sm font-medium">Name</label>
                    <input
                        id="name"
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        required
                        autofocus
                        autocomplete="name"
                        class="input input-bordered w-full"
                    />
                    @error('name')
                        <p class="text-sm text-error">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex flex-col gap-2">
                    <label for="username" class="text-sm font-medium">Username</label>
                    <input
                        id="username"
                        type="text"
                        name="username"
                        value="{{ old('username') }}"
                        required
                        autocomplete="username"
                        class="input input-bordered w-full"
                    />
                    @error('username')
                        <p class="text-sm text-error">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex flex-col gap-2">
                    <label for="email" class="text-sm font-medium">Email</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
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
                        autocomplete="new-password"
                        class="input input-bordered w-full"
                    />
                    @error('password')
                        <p class="text-sm text-error">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex flex-col gap-2">
                    <label for="password_confirmation" class="text-sm font-medium">Confirm password</label>
                    <input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                        class="input input-bordered w-full"
                    />
                </div>
                <button type="submit" class="btn btn-primary w-full">
                    Register
                </button>
            </form>
            <p class="text-sm text-base-content/70">
                Already have an account? <a href="{{ url('/login') }}" class="link link-hover">Log in</a>
            </p>
        </div>
    </div>
</x-layouts.app>
