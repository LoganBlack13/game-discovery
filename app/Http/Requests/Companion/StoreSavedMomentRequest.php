<?php

declare(strict_types=1);

namespace App\Http\Requests\Companion;

use App\Services\CompanionMomentService;
use Illuminate\Foundation\Http\FormRequest;

final class StoreSavedMomentRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $maxKilobytes = CompanionMomentService::MAX_IMAGE_KILOBYTES;

        return [
            'uuid' => ['required', 'uuid'],
            'session_uuid' => ['required', 'uuid'],
            'game_id' => ['required', 'integer', 'min:1'],
            'captured_at' => ['required', 'date'],
            'client_sent_at' => ['required', 'date'],
            'image' => ['required', 'file', 'mimetypes:image/jpeg,image/png', "max:{$maxKilobytes}"],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'image.mimetypes' => 'A moment must be a JPEG or PNG image.',
            'image.max' => 'A moment cannot be larger than 10 MB.',
            'client_sent_at.required' => 'The send time is required to correct the device clock.',
        ];
    }
}
