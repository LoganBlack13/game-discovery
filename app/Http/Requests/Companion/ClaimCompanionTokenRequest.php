<?php

declare(strict_types=1);

namespace App\Http\Requests\Companion;

use Illuminate\Foundation\Http\FormRequest;

final class ClaimCompanionTokenRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'pairing_secret' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pairing_secret.required' => 'The pairing secret is required.',
        ];
    }
}
