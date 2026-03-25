<x-layouts.app title="Two-factor authentication">
    <div class="mx-auto flex min-h-[calc(100vh-3.5rem)] max-w-md flex-col justify-center px-4 py-12 sm:px-6 lg:px-8">
        <div class="space-y-8">
            <h1 class="text-2xl font-semibold tracking-tight">Two-factor authentication</h1>
            <p class="text-sm text-base-content/70">
                Please enter the code from your authenticator app or a recovery code to continue.
            </p>
            <form action="{{ url('/two-factor-challenge') }}" method="POST" class="flex flex-col gap-6">
                @csrf
                <div class="flex flex-col gap-2">
                    <label for="code" class="text-sm font-medium">Code</label>
                    <input
                        id="code"
                        type="text"
                        name="code"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        autofocus
                        class="input input-bordered w-full"
                    />
                    @error('code')
                        <p class="text-sm text-error">{{ $message }}</p>
                    @enderror
                </div>
                <p class="text-center text-sm text-base-content/60">or</p>
                <div class="flex flex-col gap-2">
                    <label for="recovery_code" class="text-sm font-medium">Recovery code</label>
                    <input
                        id="recovery_code"
                        type="text"
                        name="recovery_code"
                        class="input input-bordered w-full"
                    />
                    @error('recovery_code')
                        <p class="text-sm text-error">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="btn btn-primary w-full">
                    Verify
                </button>
            </form>
        </div>
    </div>
</x-layouts.app>
