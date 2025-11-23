<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class StudentMiddleware
{
    /**
     * Handle an incoming request.
     * Only Students with login access can access routes protected by this middleware.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard('student')->check()) {
            return redirect()->route('student.login')->with('error', 'Please login to access this page.');
        }

        $student = Auth::guard('student')->user();

        // Check if student has login access (has student_code and password)
        if (!$student->hasLoginAccess()) {
            return redirect()->route('student.login')->with('error', 'You do not have login access. Please contact administrator.');
        }

        return $next($request);
    }
}

