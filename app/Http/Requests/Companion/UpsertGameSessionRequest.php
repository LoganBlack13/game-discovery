<?php

declare(strict_types=1);

namespace App\Http\Requests\Companion;

use App\Enums\SessionDetectionSource;
use App\Enums\SessionEndReason;
use App\Services\CompanionSessionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpsertGameSessionRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $maxSeconds = CompanionSessionService::MAX_DURATION_HOURS * 3600;

        return [
            'game_id' => ['required', 'integer', 'min:1'],
            'started_at' => ['required', 'date'],
            'last_heartbeat_at' => ['required', 'date'],
            'ended_at' => ['nullable', 'date'],
            'active_seconds' => ['required', 'integer', 'min:0', "max:{$maxSeconds}"],
            'idle_seconds' => ['required', 'integer', 'min:0', "max:{$maxSeconds}"],
            'end_reason' => ['nullable', 'required_with:ended_at', Rule::enum(SessionEndReason::class)->only(SessionEndReason::reportedByDevice())],
            'detection_source' => ['required', Rule::enum(SessionDetectionSource::class)],
            'mapping_id' => ['nullable', 'integer'],
            'client_sent_at' => ['required', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'end_reason.required_with' => 'A closed session needs an end reason.',
            'client_sent_at.required' => 'The send time is required to correct the device clock.',
        ];
    }
}
