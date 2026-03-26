<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureUserIsAdmin
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->guest()) { // @codeCoverageIgnore
            return to_route('login'); // @codeCoverageIgnore
        } // @codeCoverageIgnore

        $user = auth()->user();
        assert($user instanceof User);
        abort_unless($user->isAdmin(), 403);

        return $next($request);
    }
}
