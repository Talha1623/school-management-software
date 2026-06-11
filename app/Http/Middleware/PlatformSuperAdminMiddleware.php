<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PlatformSuperAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard('platform_super_admin')->check()) {
            return redirect()->route('platform-admin.login')->with('error', 'Please login to access this page.');
        }

        $admin = Auth::guard('platform_super_admin')->user();

        if (!$admin || !$admin->is_active) {
            Auth::guard('platform_super_admin')->logout();

            return redirect()->route('platform-admin.login')->with('error', 'Your account is inactive.');
        }

        return $next($request);
    }
}
