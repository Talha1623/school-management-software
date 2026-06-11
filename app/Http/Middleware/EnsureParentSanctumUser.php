<?php

namespace App\Http\Middleware;

use App\Models\ParentAccount;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Parent API routes expect a Personal Access Token minted via POST /api/parent/login
 * (tokenable_type = ParentAccount). Session/cookies from other guards (web User) or
 * student/teachers tokens authenticate but must not impersonate parents.
 */
class EnsureParentSanctumUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof ParentAccount) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'This endpoint requires a parent account token. Log in via POST /api/parent/login and send Authorization: Bearer {token}. Student or staff tokens will not work on /api/parent/*.',
            'token' => null,
        ], 403);
    }
}
