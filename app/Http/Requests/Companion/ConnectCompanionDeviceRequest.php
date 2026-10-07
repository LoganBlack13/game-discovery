<?php

declare(strict_types=1);

namespace App\Http\Requests\Companion;

use Illuminate\Foundation\Http\FormRequest;

final class ConnectCompanionDeviceRequest extends FormRequest
{
    /**
     * Pairing confirmation on `/companion/link` (Livewire).
     *
     * @return array<string, array<int, mixed>>
     */
    public static function connectRules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20'],
            'label' => ['required', 'string', 'max:100'],
        ];
    }

    /**
     * Renaming a connected device from the profile (Livewire).
     *
     * @return array<string, array<int, mixed>>
     */
    public static function renameRules(): array
    {
        return [
            'editingLabel' => ['required', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function livewireMessages(): array
    {
        return [
            'code.required' => 'Enter the code shown by Questlog Companion.',
            'label.required' => 'Give this device a name.',
            'editingLabel.required' => 'Give this device a name.',
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array // @codeCoverageIgnore
    {
        return self::connectRules(); // @codeCoverageIgnore
    } // @codeCoverageIgnore

    /**
     * @return array<string, string>
     */
    public function messages(): array // @codeCoverageIgnore
    {
        return self::livewireMessages(); // @codeCoverageIgnore
    } // @codeCoverageIgnore
}
