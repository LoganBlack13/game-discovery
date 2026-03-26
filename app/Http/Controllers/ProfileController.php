<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

final class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('profile.edit', [
            'user' => auth()->user(),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        assert($user instanceof User);
        $validated = $request->validated();

        if ($request->hasFile('photo')) {
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }

            $path = $request->file('photo')->store('profile-photos', 'public');
            $validated['profile_photo_path'] = $path;
        } elseif ($request->boolean('remove_photo') && $user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
            $validated['profile_photo_path'] = null;
        }

        $oldEmail = $user->email;
        $user->forceFill(collect($validated)->only([
            'name',
            'username',
            'email',
            'profile_photo_path',
        ])->all())->save();

        if (isset($validated['email']) && $validated['email'] !== $oldEmail) {
            $user->forceFill(['email_verified_at' => null])->save();
            $user->sendEmailVerificationNotification();
        }

        return back()->with('status', 'profile-updated');
    }

    public function recoveryCodes(Request $request): View
    {
        $user = $request->user();
        assert($user instanceof User);
        $codes = $user->two_factor_secret && $user->two_factor_recovery_codes
            ? $user->recoveryCodes()
            : [];

        return view('profile.recovery-codes', ['recoveryCodes' => $codes]);
    }
}
