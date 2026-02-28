<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\View\View;

final class DashboardController
{
    public function __invoke(Request $request): View
    {
        return view('admin.dashboard');
    }
}
