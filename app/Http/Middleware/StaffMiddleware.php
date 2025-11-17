<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class StaffMiddleware
{
    /**
     * Handle an incoming request.
     * Only Staff members with dashboard access can access routes protected by this middleware.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard('staff')->check()) {
            return redirect()->route('staff.login')->with('error', 'Please login to access this page.');
        }

        $staff = Auth::guard('staff')->user();

        // Check if staff has dashboard access (has email and password)
        if (!$staff->hasDashboardAccess()) {
            return redirect()->route('staff.login')->with('error', 'You do not have dashboard access. Please contact administrator.');
        }

        return $next($request);
    }
}

