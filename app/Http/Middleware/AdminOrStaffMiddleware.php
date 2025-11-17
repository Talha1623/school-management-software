<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminOrStaffMiddleware
{
    /**
     * Handle an incoming request.
     * Allow access if user is either Admin or Staff.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if admin is logged in
        if (Auth::guard('admin')->check()) {
            return $next($request);
        }

        // Check if staff is logged in
        if (Auth::guard('staff')->check()) {
            $staff = Auth::guard('staff')->user();
            // Check if staff has dashboard access
            if ($staff->hasDashboardAccess()) {
                return $next($request);
            }
        }

        // If neither admin nor staff is logged in, redirect to admin login
        return redirect()->route('admin.login')->with('error', 'Please login to access this page.');
    }
}

