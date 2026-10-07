<?php

use App\Http\Requests\Companion\ConnectCompanionDeviceRequest;
use App\Models\CompanionPairing;
use App\Models\User;
use App\Services\CompanionPairingService;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Connect Questlog Companion')] class extends Component
{
    public const int MAX_WRONG_CODES = 5;

    public const int WRONG_CODE_DECAY_SECONDS = 600;

    #[Url]
    public string $code = '';

    public string $label = '';

    public bool $connected = false;

    public function mount(CompanionPairingService $pairings): void
    {
        $this->code = CompanionPairing::formatCode($this->code);
        $this->label = $pairings->findPendingByCode($this->code)?->label ?? '';
    }

    public function connect(CompanionPairingService $pairings): void
    {
        $this->validate(ConnectCompanionDeviceRequest::connectRules(), ConnectCompanionDeviceRequest::livewireMessages());

        /** @var User $user */
        $user = auth()->user();
        $throttleKey = 'companion-link:'.$user->id;

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_WRONG_CODES)) {
            $this->addError('code', 'Too many wrong codes. Try again in a few minutes.');

            return;
        }

        $pairing = $pairings->findPendingByCode($this->code);

        if ($pairing === null || ! $pairings->approve($pairing, $user, $this->label)) {
            RateLimiter::hit($throttleKey, self::WRONG_CODE_DECAY_SECONDS);
            $this->addError('code', 'This code is invalid, expired or already used. Start pairing again from Questlog Companion.');

            return;
        }

        $this->connected = true;
    }
};
?>

<div class="mx-auto max-w-lg px-4 py-12 sm:px-6 lg:px-8">
    <header class="mb-8 space-y-1">
        <h1 class="font-display text-3xl font-semibold text-base-content">Connect Questlog Companion</h1>
        <p class="text-sm text-base-content/70">Link the Companion running on your computer to your Questlog account.</p>
    </header>

    @if ($connected)
        <div role="alert" class="alert alert-success">
            <span>Device connected. You can go back to your game, Questlog Companion is now tracking your sessions.</span>
        </div>
        <p class="mt-6 text-sm text-base-content/70">
            Manage your devices from your <a href="{{ route('profile.edit') }}" class="link link-primary">profile</a>.
        </p>
    @else
        <form wire:submit="connect" class="card border border-base-300 bg-base-100">
            <div class="card-body gap-4">
                <label class="form-control">
                    <div class="label">
                        <span class="label-text font-medium">Code shown by the Companion</span>
                    </div>
                    <input
                        id="code"
                        type="text"
                        wire:model="code"
                        autocomplete="off"
                        autocapitalize="characters"
                        placeholder="XXXX-XXXX"
                        class="input input-bordered font-mono text-lg tracking-widest uppercase"
                    />
                    @error('code')
                        <div class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </div>
                    @enderror
                </label>

                <label class="form-control">
                    <div class="label">
                        <span class="label-text font-medium">Device name</span>
                    </div>
                    <input id="label" type="text" wire:model="label" maxlength="100" class="input input-bordered" />
                    @error('label')
                        <div class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </div>
                    @enderror
                </label>

                <div class="card-actions justify-end">
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">Connect this device</button>
                </div>
            </div>
        </form>
    @endif
</div>
