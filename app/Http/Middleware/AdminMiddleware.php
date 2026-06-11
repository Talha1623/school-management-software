<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('admin')->check()) {
            if ($request->expectsJson()
                || $request->ajax()
                || $request->header('X-Requested-With') === 'XMLHttpRequest'
                || str_contains((string) $request->header('Accept', ''), 'application/json')) {
                return response()->json(['message' => 'Please login as admin.', 'classes' => [], 'sections' => []], 401);
            }

            return redirect()->route('admin.login')->with('error', 'Please login to access this page.');
        }

        // Allow both Super Admin and normal Admin to access
        return $next($request);
    }
}
