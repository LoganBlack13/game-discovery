<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class NotificationsController
{
    public function index(Request $request): View
    {
        $user = $request->user();
        assert($user instanceof User);
        $notifications = $user->notifications()->latest()->paginate(20);
        $user->unreadNotifications()->update(['read_at' => now()]);

        return view('notifications.index', ['notifications' => $notifications]);
    }

    public function destroy(Request $request, string $id): RedirectResponse
    {
        $user = $request->user();
        assert($user instanceof User);
        $user->notifications()->findOrFail($id)->delete();

        return back();
    }
}
