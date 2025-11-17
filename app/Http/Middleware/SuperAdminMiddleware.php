<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminMiddleware
{
    /**
     * Handle an incoming request.
     * Only Super Admins can access routes protected by this middleware.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login')->with('error', 'Please login to access this page.');
        }

        $admin = Auth::guard('admin')->user();

        // Check if super admin
        if (!$admin->isSuperAdmin()) {
            return redirect()->route('dashboard')->with('error', 'Access denied. Super Admin access required.');
        }

        return $next($request);
    }
}
