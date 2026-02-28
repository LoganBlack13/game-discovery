<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\View\View;

final class DashboardController extends \Illuminate\Routing\Controller
{
    public function __invoke(): View
    {
        return view('dashboard');
    }
}
