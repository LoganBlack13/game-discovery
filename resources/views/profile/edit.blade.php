<x-layouts.app title="Profile">
    <div class="mx-auto max-w-2xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="space-y-8">
            <h1 class="text-2xl font-semibold tracking-tight">Profile</h1>
            @if (session('status') === 'profile-updated')
                <p class="text-sm text-green-600 dark:text-green-400">Profile saved.</p>
            @endif
            <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-6">
                @csrf
                @method('PATCH')
                <div class="flex items-center gap-6">
                    @if ($user->getAttribute('profile_photo_path'))
                        <img
                            src="{{ asset('storage/'.$user->profile_photo_path) }}"
                            alt=""
                            class="h-20 w-20 rounded-full object-cover"
                        />
                    @else
                        <div class="flex h-20 w-20 items-center justify-center rounded-full bg-zinc-200 text-2xl font-medium text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400">
                            {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                        </div>
                    @endif
                    <div class="flex flex-col gap-1">
                        <label for="photo" class="text-sm font-medium">Photo</label>
                        <input
                            id="photo"
                            type="file"
                            name="photo"
                            accept="image/*"
                            class="text-sm text-zinc-600 dark:text-zinc-400"
                        />
                        @error('photo')
                            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div class="flex flex-col gap-2">
                    <label for="name" class="text-sm font-medium">Name</label>
                    <input
                        id="name"
                        type="text"
                        name="name"
                        value="{{ old('name', $user->name) }}"
                        required
                        class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-zinc-900 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                    />
                    @error('name')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex flex-col gap-2">
                    <label for="username" class="text-sm font-medium">Username</label>
                    <input
                        id="username"
                        type="text"
                        name="username"
                        value="{{ old('username', $user->username) }}"
                        required
                        class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-zinc-900 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                    />
                    @error('username')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex flex-col gap-2">
                    <label for="email" class="text-sm font-medium">Email</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email', $user->email) }}"
                        required
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
                    Save
                </button>
            </form>

            <section class="border-t border-zinc-200 pt-8 dark:border-zinc-800">
                <h2 class="text-lg font-medium">Two-factor authentication</h2>
                @if ($user->hasPendingTwoFactorConfirmation())
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                        Scan the QR code with your authenticator app, then enter a code below to confirm.
                    </p>
                    <p class="mt-2">
                        <a href="{{ route('two-factor.qr-code') }}" target="_blank" rel="noopener" class="text-sm underline hover:no-underline">Show QR code</a>
                    </p>
                    <form action="{{ url('/user/confirmed-two-factor-authentication') }}" method="POST" class="mt-4 flex flex-col gap-3">
                        @csrf
                        <div class="flex flex-col gap-1">
                            <label for="code" class="text-sm font-medium">Confirmation code</label>
                            <input
                                id="code"
                                type="text"
                                name="code"
                                inputmode="numeric"
                                class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-zinc-900 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                            />
                            @error('code', 'confirmTwoFactorAuthentication')
                                <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" class="w-fit rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white dark:bg-zinc-100 dark:text-zinc-900">Confirm</button>
                    </form>
                @elseif ($user->hasEnabledTwoFactorAuthentication())
                    <p class="mt-2 text-sm text-green-600 dark:text-green-400">Two-factor authentication is enabled.</p>
                    <p class="mt-2">
                        <a href="{{ route('profile.recovery-codes') }}" class="text-sm underline hover:no-underline">View recovery codes</a>
                    </p>
                    <form action="{{ url('/user/two-factor-authentication') }}" method="POST" class="mt-4 inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm text-red-600 underline hover:no-underline dark:text-red-400">Disable two-factor authentication</button>
                    </form>
                @else
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Add an extra layer of security by enabling two-factor authentication.</p>
                    <form action="{{ url('/user/two-factor-authentication') }}" method="POST" class="mt-4 inline">
                        @csrf
                        <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white dark:bg-zinc-100 dark:text-zinc-900">Enable</button>
                    </form>
                @endif
            </section>
        </div>
    </div>
</x-layouts.app>
