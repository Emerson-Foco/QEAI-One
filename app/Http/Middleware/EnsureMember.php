<?php

namespace App\Http\Middleware;

use App\Models\Membership;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMember
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        if ($user === null) {
            abort(403);
        }
        if ($user->is_root_admin) {
            return $next($request);
        }
        if (Membership::where('user_id', $user->id)->where('status', 'active')->exists()) {
            return $next($request);
        }

        abort(403);
    }
}
