<?php

declare(strict_types=1);

namespace App\Http\Requests\Companion;

use App\Enums\GameLauncher;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RecordMappingCandidatesRequest extends FormRequest
{
    public const int MAX_CANDIDATES = 100;

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'candidates' => ['required', 'array', 'min:1', 'max:'.self::MAX_CANDIDATES],
            'candidates.*.fingerprint' => ['required', 'string', 'max:64'],
            'candidates.*.executable_name' => ['required', 'string', 'max:255'],
            'candidates.*.path_fragment' => ['nullable', 'string', 'max:255'],
            'candidates.*.launcher' => ['nullable', Rule::enum(GameLauncher::class)],
            'candidates.*.launcher_game_id' => ['nullable', 'required_with:candidates.*.launcher', 'string', 'max:255'],
            'candidates.*.display_name' => ['nullable', 'string', 'max:255'],
            'candidates.*.product_name' => ['nullable', 'string', 'max:255'],
            'candidates.*.total_seconds' => ['required', 'integer', 'min:0'],
            'candidates.*.first_seen_at' => ['required', 'date'],
            'candidates.*.last_seen_at' => ['required', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'candidates.max' => 'Send at most 100 candidates per request.',
            'candidates.*.launcher_game_id.required_with' => 'A launcher candidate needs its launcher identifier.',
        ];
    }
}
