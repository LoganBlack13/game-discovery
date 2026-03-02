<?php

use Livewire\Component;

new class extends Component
{
    public bool $open = false;
};
?>

<div class="relative" x-data="{ open: @entangle('open') }">
    <button
        type="button"
        @click="open = !open"
        class="text-sm text-base-content/80 underline hover:no-underline hover:text-base-content"
    >
        Log in
    </button>
    <div
        x-show="open"
        x-cloak
        @click.outside="open = false"
        class="absolute right-0 top-full z-50 mt-2 w-72 rounded-box border border-base-content/10 bg-base-100 p-4 shadow-xl"
    >
        <form action="{{ url('/login') }}" method="POST" class="flex flex-col gap-3">
            @csrf
            <div class="flex flex-col gap-1">
                <label for="auth-dropdown-email" class="text-xs font-medium text-base-content">Email</label>
                <input
                    id="auth-dropdown-email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    class="input input-bordered input-sm w-full"
                />
                @error('email')
                    <p class="text-xs text-error">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex flex-col gap-1">
                <label for="auth-dropdown-password" class="text-xs font-medium text-base-content">Password</label>
                <input
                    id="auth-dropdown-password"
                    type="password"
                    name="password"
                    required
                    class="input input-bordered input-sm w-full"
                />
                @error('password')
                    <p class="text-xs text-error">{{ $message }}</p>
                @enderror
            </div>
            <label class="flex cursor-pointer items-center gap-2 text-sm text-base-content">
                <input type="checkbox" name="remember" class="checkbox checkbox-sm" />
                Remember me
            </label>
            <button type="submit" class="btn btn-primary btn-sm w-full">
                Log in
            </button>
        </form>
        <p class="mt-2 text-xs text-base-content/70">
            <a href="{{ url('/forgot-password') }}" class="underline hover:no-underline">Forgot password?</a>
            · <a href="{{ url('/register') }}" class="underline hover:no-underline">Register</a>
        </p>
    </div>
</div>
