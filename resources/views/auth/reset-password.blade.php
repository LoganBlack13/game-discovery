<x-layouts.app title="Reset password">
    <div class="mx-auto flex min-h-[calc(100vh-3.5rem)] max-w-md flex-col justify-center px-4 py-12 sm:px-6 lg:px-8">
        <div class="space-y-8">
            <h1 class="text-2xl font-semibold tracking-tight">Reset password</h1>
            <form action="{{ url('/reset-password') }}" method="POST" class="flex flex-col gap-6">
                @csrf
                <input type="hidden" name="token" value="{{ $request->route('token') }}" />
                <div class="flex flex-col gap-2">
                    <label for="email" class="text-sm font-medium">Email</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email', $request->email) }}"
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
                    Reset password
                </button>
            </form>
        </div>
    </div>
</x-layouts.app>
