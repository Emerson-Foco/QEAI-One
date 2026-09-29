<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Support\OrgAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $organization = $request->route('organization');
        $user = auth()->user();

        if (! $organization instanceof Organization || $user === null) {
            abort(403);
        }

        if (! OrgAccess::isMember($user, $organization)) {
            abort(403);
        }

        return $next($request);
    }
}
