<?php

namespace App\Http\Middleware;

use App\Support\SchoolAdminAuth;
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
        // Prefer route names (works with subfolders / rewrites); path match is a fallback.
        $path = ltrim($request->path(), '/');
        $accountantFamilyFeePath = str_starts_with($path, 'accountant/family-fee-calculator');
        $onAccountantFamilyFee = $request->routeIs(
            'accountant.family-fee-calculator',
            'accountant.family-fee-calculator.*'
        ) || $accountantFamilyFeePath;

        // If an accountant session exists, keep accountant URLs inside the accountant portal.
        // A stale admin session can otherwise redirect /accountant/chat to the admin dashboard.
        if (Auth::guard('accountant')->check()) {
            $accountant = Auth::guard('accountant')->user();

            if (!$accountant->hasWebLoginAccess()) {
                return redirect()->guest(route('accountant.login'))->with('error', 'You do not have web login access. Please contact administrator.');
            }

            return $next($request);
        }

        // Super admin / ICMS admin on back-office guards -> use main panel, not accountant portal URLs.
        if (SchoolAdminAuth::isBackOfficeGuard() && str_starts_with($path, 'accountant/')) {
            if ($onAccountantFamilyFee) {
                return redirect()->route('accounting.family-fee-calculator');
            }

            if (! $request->routeIs('accountant.login', 'accountant.login.post', 'accountant.logout')) {
                return redirect()->route('dashboard');
            }
        }

        if (! Auth::guard('accountant')->check()) {
            if ($request->expectsJson()
                || $request->ajax()
                || $request->header('X-Requested-With') === 'XMLHttpRequest'
                || str_contains((string) $request->header('Accept', ''), 'application/json')) {
                return response()->json(['message' => 'Please login as accountant.', 'classes' => [], 'sections' => []], 401);
            }

            // Preserve intended URL so after login user returns here (e.g. Family Fee Calculator), not default dashboard.
            return redirect()->guest(route('accountant.login'))->with('error', 'Please login to access this page.');
        }

        return $next($request);
    }
}

