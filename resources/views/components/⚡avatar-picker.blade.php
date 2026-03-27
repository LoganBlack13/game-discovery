<?php

use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public ?string $selectedSeed = null;

    public function mount(): void
    {
        /** @var User $user */
        $user = auth()->user();
        $this->selectedSeed = $user->avatar_seed;
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function seeds(): array
    {
        /** @var User $user */
        $user = auth()->user();
        $count = config('avatar.count', 12);
        $seeds = [];

        for ($i = 0; $i < $count; $i++) {
            $seeds[] = substr(md5($user->id.'-avatar-'.$i), 0, 10);
        }

        return $seeds;
    }

    public function select(string $seed): void
    {
        $this->selectedSeed = $seed;
    }

    public function save(): void
    {
        /** @var User $user */
        $user = auth()->user();
        $user->forceFill(['avatar_seed' => $this->selectedSeed])->save();
        $this->dispatch('avatar-saved');
    }
};
?>

<div x-data="{ open: false }" @avatar-saved.window="open = false">
    {{-- Current avatar, click to open --}}
    <button type="button" class="group relative" @click="open = true" aria-label="Change avatar">
        <img src="{{ auth()->user()->avatarUrl() }}" alt="" class="size-16 rounded-full" />
        <div class="absolute inset-0 flex items-center justify-center rounded-full bg-black/50 opacity-0 transition-opacity group-hover:opacity-100" aria-hidden="true">
            <svg class="size-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
            </svg>
        </div>
    </button>

    {{-- Modal --}}
    <div
        x-show="open"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        aria-label="Choose your avatar"
    >
        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="open = false" aria-hidden="true"></div>

        {{-- Panel --}}
        <div class="relative z-10 w-full max-w-sm rounded-2xl bg-base-100 p-6 shadow-2xl ring-1 ring-base-content/10" @click.stop>
            <h3 class="mb-4 font-semibold">Choose your avatar</h3>

            <div class="grid grid-cols-4 gap-3">
                @foreach ($this->seeds as $seed)
                    <button
                        type="button"
                        wire:click="select('{{ $seed }}')"
                        aria-label="Select avatar {{ $loop->iteration }}"
                        :aria-pressed="{{ $selectedSeed === $seed ? 'true' : 'false' }}"
                        class="rounded-full transition {{ $selectedSeed === $seed ? 'ring-2 ring-primary ring-offset-2 ring-offset-base-100' : 'opacity-70 hover:opacity-100' }}"
                    >
                        <img src="{{ auth()->user()->avatarUrl($seed) }}" alt="" class="size-14 rounded-full" />
                    </button>
                @endforeach
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <button type="button" class="btn btn-ghost btn-sm" @click="open = false">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" wire:click="save">
                    <span wire:loading.remove wire:target="save">Save</span>
                    <span wire:loading wire:target="save">…</span>
                </button>
            </div>
        </div>
    </div>
</div>
