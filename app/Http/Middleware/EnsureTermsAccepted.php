<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTermsAccepted
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('legal.require_acceptance')) {
            return $next($request);
        }

        $user = auth()->user();
        if ($user !== null && $user->terms_accepted_at === null) {
            if ($request->routeIs('legal.accept') || $request->routeIs('legal.accept.store') || $request->routeIs('logout')) {
                return $next($request);
            }

            return redirect()->route('legal.accept');
        }

        return $next($request);
    }
}
