<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreGameRequestRequest extends FormRequest
{
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

    public function authorize(): bool // @codeCoverageIgnore
    {
        return $this->user() !== null; // @codeCoverageIgnore
    } // @codeCoverageIgnore

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array // @codeCoverageIgnore
    {
        return [ // @codeCoverageIgnore
            'title' => ['required', 'string', 'max:255'], // @codeCoverageIgnore
        ]; // @codeCoverageIgnore
    } // @codeCoverageIgnore
}
