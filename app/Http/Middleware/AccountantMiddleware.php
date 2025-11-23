<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AccountantMiddleware
{
    /**
     * Handle an incoming request.
     * Only Accountants with web login access can access routes protected by this middleware.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard('accountant')->check()) {
            return redirect()->route('accountant.login')->with('error', 'Please login to access this page.');
        }

        $accountant = Auth::guard('accountant')->user();

        // Check if accountant has web login access
        if (!$accountant->hasWebLoginAccess()) {
            return redirect()->route('accountant.login')->with('error', 'You do not have web login access. Please contact administrator.');
        }

        return $next($request);
    }
}

