<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreGameRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * Validation rules for use in Livewire (static).
     *
     * @return array<string, array<int, mixed>>
     */
    public static function livewireRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
        ];
    }
}
