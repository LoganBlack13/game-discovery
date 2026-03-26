<x-layouts.app title="Profile">
    <div class="mx-auto max-w-2xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="space-y-8">
            <h1 class="text-2xl font-semibold tracking-tight">Profile</h1>

            @if (session('status') === 'profile-updated')
                <div role="alert" class="alert alert-success">
                    <span>Profile saved.</span>
                </div>
            @endif

            <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-6">
                @csrf
                @method('PATCH')

                <div class="flex items-center gap-6">
                    @if ($user->getAttribute('profile_photo_path'))
                        <img
                            src="{{ asset('storage/'.$user->profile_photo_path) }}"
                            alt=""
                            class="size-20 rounded-full object-cover"
                        />
                    @else
                        <div class="flex size-20 items-center justify-center rounded-full bg-base-300 text-2xl font-medium text-base-content/50">
                            {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                        </div>
                    @endif

                    <div class="flex flex-col gap-2">
                        <label class="form-control">
                            <div class="label">
                                <span class="label-text font-medium">Photo</span>
                            </div>
                            <input
                                id="photo"
                                type="file"
                                name="photo"
                                accept="image/*"
                                class="file-input file-input-bordered file-input-sm w-full max-w-xs"
                            />
                        </label>
                        @error('photo')
                            <p class="text-sm text-error">{{ $message }}</p>
                        @enderror
                        @if ($user->getAttribute('profile_photo_path'))
                            <label class="flex cursor-pointer items-center gap-2">
                                <input type="checkbox" name="remove_photo" value="1" class="checkbox checkbox-sm" />
                                <span class="label-text text-sm">Remove current photo</span>
                            </label>
                        @endif
                    </div>
                </div>

                <label class="form-control">
                    <div class="label">
                        <span class="label-text font-medium">Name</span>
                    </div>
                    <input
                        id="name"
                        type="text"
                        name="name"
                        value="{{ old('name', $user->name) }}"
                        required
                        class="input input-bordered"
                    />
                    @error('name')
                        <div class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </div>
                    @enderror
                </label>

                <label class="form-control">
                    <div class="label">
                        <span class="label-text font-medium">Username</span>
                    </div>
                    <input
                        id="username"
                        type="text"
                        name="username"
                        value="{{ old('username', $user->username) }}"
                        required
                        class="input input-bordered"
                    />
                    @error('username')
                        <div class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </div>
                    @enderror
                </label>

                <label class="form-control">
                    <div class="label">
                        <span class="label-text font-medium">Email</span>
                    </div>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email', $user->email) }}"
                        required
                        class="input input-bordered"
                    />
                    @error('email')
                        <div class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </div>
                    @enderror
                </label>

                <div>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>

            <div class="divider"></div>

            <section>
                <h2 class="text-lg font-medium">Two-factor authentication</h2>
                @if ($user->hasPendingTwoFactorConfirmation())
                    <p class="mt-2 text-sm text-base-content/60">
                        Scan the QR code with your authenticator app, then enter a code below to confirm.
                    </p>
                    <p class="mt-2">
                        <a href="{{ route('two-factor.qr-code') }}" target="_blank" rel="noopener" class="link link-primary text-sm">Show QR code</a>
                    </p>
                    <form action="{{ url('/user/confirmed-two-factor-authentication') }}" method="POST" class="mt-4 flex flex-col gap-3">
                        @csrf
                        <label class="form-control w-full max-w-xs">
                            <div class="label">
                                <span class="label-text font-medium">Confirmation code</span>
                            </div>
                            <input
                                id="code"
                                type="text"
                                name="code"
                                inputmode="numeric"
                                class="input input-bordered"
                            />
                            @error('code', 'confirmTwoFactorAuthentication')
                                <div class="label">
                                    <span class="label-text-alt text-error">{{ $message }}</span>
                                </div>
                            @enderror
                        </label>
                        <div>
                            <button type="submit" class="btn btn-primary btn-sm">Confirm</button>
                        </div>
                    </form>
                @elseif ($user->hasEnabledTwoFactorAuthentication())
                    <p class="mt-2 text-sm text-success">Two-factor authentication is enabled.</p>
                    <p class="mt-2">
                        <a href="{{ route('profile.recovery-codes') }}" class="link link-primary text-sm">View recovery codes</a>
                    </p>
                    <form action="{{ url('/user/two-factor-authentication') }}" method="POST" class="mt-4 inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-error btn-sm btn-outline">Disable two-factor authentication</button>
                    </form>
                @else
                    <p class="mt-2 text-sm text-base-content/60">Add an extra layer of security by enabling two-factor authentication.</p>
                    <form action="{{ url('/user/two-factor-authentication') }}" method="POST" class="mt-4 inline">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm">Enable</button>
                    </form>
                @endif
            </section>
        </div>
    </div>
</x-layouts.app>
