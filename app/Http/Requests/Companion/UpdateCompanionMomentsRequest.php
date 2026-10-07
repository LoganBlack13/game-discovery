<?php

declare(strict_types=1);

namespace App\Http\Requests\Companion;

use App\Models\SavedMoment;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Rules for the moment settings and captions edited from Livewire (fiche A6).
 */
final class UpdateCompanionMomentsRequest extends FormRequest
{
    /** One to four modifiers, then a function key, a letter, a digit or a navigation key — what `RegisterHotKey` accepts. */
    public const string HOTKEY_PATTERN = '/^(?:(?:Ctrl|Alt|Shift|Win)\+){1,4}(?:F(?:[1-9]|1\d|2[0-4])|[A-Z0-9]|PrintScreen|Pause|Insert|Delete|Home|End|PageUp|PageDown)$/';

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function hotkeyRules(): array
    {
        return [
            'momentHotkey' => ['required', 'string', 'max:40', 'regex:'.self::HOTKEY_PATTERN],
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function captionRules(): array
    {
        return [
            'caption' => ['nullable', 'string', 'max:'.SavedMoment::CAPTION_MAX_LENGTH],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function livewireMessages(): array
    {
        return [
            'momentHotkey.required' => 'Choose a shortcut.',
            'momentHotkey.regex' => 'Use one or more of Ctrl, Alt, Shift, Win, then a key, e.g. Ctrl+Shift+F9.',
            'caption.max' => 'A caption cannot be longer than 280 characters.',
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array // @codeCoverageIgnore
    {
        return [...self::hotkeyRules(), ...self::captionRules()]; // @codeCoverageIgnore
    } // @codeCoverageIgnore

    /**
     * @return array<string, string>
     */
    public function messages(): array // @codeCoverageIgnore
    {
        return self::livewireMessages(); // @codeCoverageIgnore
    } // @codeCoverageIgnore
}
