<?php

declare(strict_types=1);

namespace App\Http\Requests\Companion;

use Illuminate\Foundation\Http\FormRequest;

final class StartCompanionPairingRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:100'],
            'platform' => ['required', 'string', 'max:50'],
            'app_version' => ['nullable', 'string', 'max:30'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'label.required' => 'The device needs a name.',
            'platform.required' => 'The device platform is required.',
        ];
    }
}
