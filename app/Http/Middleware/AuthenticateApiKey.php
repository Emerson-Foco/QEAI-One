<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Api-Key') ?: $request->bearerToken();

        if (! is_string($token) || $token === '') {
            return response()->json(['error' => ['code' => 'unauthorized', 'message' => 'Chave de API ausente.']], 401);
        }

        $key = ApiKey::with('organization')->where('token_hash', hash('sha256', $token))->first();
        if ($key === null || ! $key->isActive()) {
            return response()->json(['error' => ['code' => 'unauthorized', 'message' => 'Chave de API inválida.']], 401);
        }

        $bucket = 'api-lead-' . $key->id;
        if (RateLimiter::tooManyAttempts($bucket, 120)) {
            return response()->json(['error' => ['code' => 'rate_limited', 'message' => 'Limite de requisições excedido.']], 429);
        }
        RateLimiter::hit($bucket, 60);

        $key->forceFill(['last_used_at' => now()])->save();

        $request->attributes->set('api_organization', $key->organization);
        $request->attributes->set('api_key', $key);

        return $next($request);
    }
}
