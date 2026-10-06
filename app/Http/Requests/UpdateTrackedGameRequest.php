<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\BacklogPriority;
use App\Enums\InterruptionReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateTrackedGameRequest extends FormRequest
{
    /**
     * Progress, platform and personal notes (Livewire).
     *
     * @return array<string, array<int, mixed>>
     */
    public static function detailsRules(): array
    {
        return [
            'platform' => ['nullable', 'string', 'max:100'],
            'progress' => ['nullable', 'string', 'max:255'],
            'progressPercent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Optional reason and comment attached to a pause or a drop (Livewire).
     *
     * @return array<string, array<int, mixed>>
     */
    public static function interruptionRules(): array
    {
        return [
            'reason' => ['nullable', Rule::enum(InterruptionReason::class)],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Personal backlog priority (Livewire).
     *
     * @return array<string, array<int, mixed>>
     */
    public static function backlogRules(): array
    {
        return [
            'priority' => ['nullable', Rule::enum(BacklogPriority::class)],
            'isUpNext' => ['boolean'],
        ];
    }

    /**
     * Optional closing review for a finished or dropped game (Livewire).
     *
     * @return array<string, array<int, mixed>>
     */
    public static function reviewRules(): array
    {
        return [
            'rating' => ['nullable', 'integer', 'min:1', 'max:10'],
            'wouldRecommend' => ['nullable', 'in:yes,no'],
            'playtimeHours' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'review' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * Journal entry (session note or resume goal) (Livewire).
     *
     * @return array<string, array<int, mixed>>
     */
    public static function journalRules(): array
    {
        return [
            'journalBody' => ['required', 'string', 'max:1000'],
            'journalIsResumeGoal' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function livewireMessages(): array
    {
        return [
            'journalBody.required' => 'Write a short note before saving it.',
            'progressPercent.max' => 'Progress cannot exceed 100%.',
            'rating.min' => 'Rating must be between 1 and 10.',
            'rating.max' => 'Rating must be between 1 and 10.',
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
        return self::detailsRules(); // @codeCoverageIgnore
    } // @codeCoverageIgnore
}
