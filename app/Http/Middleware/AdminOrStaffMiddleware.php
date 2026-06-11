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
                $routeName = $request->route()?->getName();
                if (!$this->staffMayAccessWebRoute($routeName)) {
                    if ($request->expectsJson()
                        || $request->ajax()
                        || $request->header('X-Requested-With') === 'XMLHttpRequest'
                        || str_contains((string) $request->header('Accept', ''), 'application/json')) {
                        return response()->json([
                            'message' => 'This page is not available for your account.',
                            'subjects' => [],
                            'uploadable_subjects' => [],
                            'sections' => [],
                            'tests' => [],
                        ], 403);
                    }

                    return redirect()
                        ->route('staff.dashboard')
                        ->with('error', 'This page is not available for your account.');
                }

                return $next($request);
            }
        }

        // If neither admin nor staff is logged in, redirect to admin login
        return redirect()->route('admin.login')->with('error', 'Please login to access this page.');
    }

    /**
     * Routes staff may hit when using shared AdminOrStaff-protected URLs (attendance, student list,
     * behaviour recording, locale).
     * Staff dashboard and chat use the staff guard only and do not pass through this check.
     */
    private function staffMayAccessWebRoute(?string $routeName): bool
    {
        if ($routeName === null || $routeName === '') {
            return true;
        }

        if (str_starts_with($routeName, 'attendance.student')) {
            return true;
        }

        if (str_starts_with($routeName, 'student-list')) {
            return true;
        }

        if ($routeName === 'attendance.barcode.scan') {
            return true;
        }

        if (str_starts_with($routeName, 'student-behavior.recording')) {
            return true;
        }

        if (str_starts_with($routeName, 'test.marks-entry')
            || str_starts_with($routeName, 'exam.marks-entry')
            || str_starts_with($routeName, 'test.list')
            || str_starts_with($routeName, 'test.teacher-remarks')
            || str_starts_with($routeName, 'exam.teacher-remarks')) {
            return true;
        }

        return $routeName === 'language.switch';
    }
}

