<?php

namespace App\Http\Middleware;

use App\Models\ParentAccount;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sanctum may authenticate /api/parent/* as the web-session user before the Bearer token is applied.
 * If the client sends Authorization: Bearer {parent token}, force that ParentAccount identity.
 */
class ParentApiPreferBearerToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $plain = $request->bearerToken();
        if ($plain === null || $plain === '') {
            return $next($request);
        }

        $accessToken = PersonalAccessToken::findToken($plain);
        if (!$accessToken || !($accessToken->tokenable instanceof ParentAccount)) {
            return $next($request);
        }

        /** @var ParentAccount $parent */
        $parent = $accessToken->tokenable;
        $parent->withAccessToken($accessToken);
        Auth::guard('sanctum')->setUser($parent);

        return $next($request);
    }
}
