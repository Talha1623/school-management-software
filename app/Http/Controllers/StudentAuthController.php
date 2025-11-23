<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class StudentAuthController extends Controller
{
    /**
     * Show the student login form.
     */
    public function showLoginForm(): View|RedirectResponse
    {
        // If already logged in, redirect to dashboard
        if (Auth::guard('student')->check()) {
            return redirect()->route('student.dashboard');
        }
        
        return view('student.login');
    }

    /**
     * Handle student login.
     * Student Code = Username
     * B-Form Number = Password
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'student_code' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // Trim and normalize student_code
        $studentCode = trim($credentials['student_code']);
        $password = trim($credentials['password']);

        // Find student by student_code (exact match)
        $student = Student::where('student_code', $studentCode)->first();

        if (!$student) {
            return back()->withErrors([
                'student_code' => 'The provided credentials do not match our records.',
            ])->onlyInput('student_code');
        }

        // Check if password is set
        if (empty($student->password)) {
            // If password not set but B-Form Number exists, set it now
            if (!empty($student->b_form_number)) {
                $student->password = $student->b_form_number;
                $student->save();
            } else {
                return back()->withErrors([
                    'student_code' => 'Password not set. Please contact administrator.',
                ])->onlyInput('student_code');
            }
        }

        // Check password (B-Form Number)
        if (!Hash::check($password, $student->password)) {
            return back()->withErrors([
                'student_code' => 'The provided credentials do not match our records.',
            ])->onlyInput('student_code');
        }

        // Check if student has login access
        if (!$student->hasLoginAccess()) {
            return back()->withErrors([
                'student_code' => 'You do not have login access. Please contact administrator.',
            ])->onlyInput('student_code');
        }

        // Login the student
        Auth::guard('student')->login($student, $request->filled('remember'));

        $request->session()->regenerate();

        return redirect()->intended(route('student.dashboard'));
    }

    /**
     * Handle student logout.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('student')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('student.login');
    }

    /**
     * Show student dashboard.
     */
    public function dashboard(): View
    {
        $student = Auth::guard('student')->user();
        
        return view('student.dashboard', compact('student'));
    }
}

