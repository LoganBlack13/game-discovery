<?php

use App\Http\Requests\Companion\ConnectCompanionDeviceRequest;
use App\Models\CompanionDevice;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public ?int $editingId = null;

    public string $editingLabel = '';

    /**
     * @return Collection<int, CompanionDevice>
     */
    #[Computed]
    public function devices(): Collection
    {
        /** @var User $user */
        $user = auth()->user();

        return $user->companionDevices()->active()->latest('last_seen_at')->get();
    }

    public function edit(int $deviceId): void
    {
        $device = $this->findDevice($deviceId);
        $this->authorize('update', $device);

        $this->editingId = $device->id;
        $this->editingLabel = $device->label;
    }

    public function cancelEdit(): void
    {
        $this->reset('editingId', 'editingLabel');
    }

    public function saveLabel(): void
    {
        if ($this->editingId === null) {
            return;
        }

        $this->validate(ConnectCompanionDeviceRequest::renameRules(), ConnectCompanionDeviceRequest::livewireMessages());

        $device = $this->findDevice($this->editingId);
        $this->authorize('update', $device);

        $device->forceFill(['label' => mb_trim($this->editingLabel)])->save();

        $this->cancelEdit();
        unset($this->devices);
    }

    public function revoke(int $deviceId): void
    {
        $device = $this->findDevice($deviceId);
        $this->authorize('delete', $device);

        $device->revoke();

        if ($this->editingId === $deviceId) {
            $this->cancelEdit();
        }
        unset($this->devices);
    }

    private function findDevice(int $deviceId): CompanionDevice
    {
        return CompanionDevice::query()->findOrFail($deviceId);
    }
};
?>

<section>
    <h2 class="text-lg font-medium">Connected devices</h2>

    @if ($this->devices->isEmpty())
        <p class="mt-2 text-sm text-base-content/60">
            No device connected. Questlog Companion is an optional desktop app that records your play sessions automatically.
        </p>
    @else
        <ul class="mt-4 flex flex-col gap-2">
            @foreach ($this->devices as $device)
                <li wire:key="device-{{ $device->id }}" class="flex flex-col gap-3 rounded-box border border-base-300 bg-base-200/40 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    @if ($editingId === $device->id)
                        <form wire:submit="saveLabel" class="flex w-full flex-col gap-2 sm:flex-row sm:items-center">
                            <input type="text" wire:model="editingLabel" maxlength="100" aria-label="Device name" class="input input-bordered input-sm grow" />
                            <div class="flex gap-2">
                                <button type="submit" class="btn btn-primary btn-sm">Save</button>
                                <button type="button" wire:click="cancelEdit" class="btn btn-ghost btn-sm">Cancel</button>
                            </div>
                            @error('editingLabel')
                                <span class="text-xs text-error">{{ $message }}</span>
                            @enderror
                        </form>
                    @else
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-base-content">{{ $device->label }}</p>
                            <p class="text-xs text-base-content/60">
                                {{ $device->platform }}@if ($device->app_version) · v{{ $device->app_version }}@endif
                                · {{ $device->last_seen_at ? 'Last seen '.$device->last_seen_at->diffForHumans() : 'Never seen' }}
                            </p>
                        </div>
                        <div class="flex shrink-0 gap-2">
                            <button type="button" wire:click="edit({{ $device->id }})" class="btn btn-ghost btn-sm">Rename</button>
                            <button
                                type="button"
                                wire:click="revoke({{ $device->id }})"
                                wire:confirm="Disconnect {{ $device->label }}? It will stop syncing until it is paired again."
                                class="btn btn-error btn-outline btn-sm"
                            >Revoke</button>
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</section>
