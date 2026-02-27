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
        class="text-sm underline hover:no-underline"
    >
        Log in
    </button>
    <div
        x-show="open"
        x-cloak
        @click.outside="open = false"
        class="absolute right-0 top-full z-50 mt-2 w-72 rounded-lg border border-zinc-200 bg-white p-4 shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
    >
        <form action="{{ url('/login') }}" method="POST" class="flex flex-col gap-3">
            @csrf
            <div class="flex flex-col gap-1">
                <label for="auth-dropdown-email" class="text-xs font-medium">Email</label>
                <input
                    id="auth-dropdown-email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    class="rounded border border-zinc-300 px-2 py-1.5 text-sm dark:border-zinc-600 dark:bg-zinc-800"
                />
                @error('email')
                    <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex flex-col gap-1">
                <label for="auth-dropdown-password" class="text-xs font-medium">Password</label>
                <input
                    id="auth-dropdown-password"
                    type="password"
                    name="password"
                    required
                    class="rounded border border-zinc-300 px-2 py-1.5 text-sm dark:border-zinc-600 dark:bg-zinc-800"
                />
                @error('password')
                    <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="remember" class="rounded border-zinc-300" />
                Remember me
            </label>
            <button
                type="submit"
                class="rounded bg-zinc-900 px-3 py-1.5 text-sm font-medium text-white dark:bg-zinc-100 dark:text-zinc-900"
            >
                Log in
            </button>
        </form>
        <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
            <a href="{{ url('/forgot-password') }}" class="underline hover:no-underline">Forgot password?</a>
            · <a href="{{ url('/register') }}" class="underline hover:no-underline">Register</a>
        </p>
    </div>
</div>
