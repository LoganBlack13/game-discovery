<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\GameRequest;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class GameRequestController extends Controller
{
    public function index(): View
    {
        $topRequests = GameRequest::query()
            ->pending()
            ->orderByDesc('request_count')
            ->limit(20)
            ->get();

        return view('pages.game-requests', ['topRequests' => $topRequests]);
    }
}
