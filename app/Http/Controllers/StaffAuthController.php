<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class StaffAuthController extends Controller
{
    /**
     * Show the staff login form.
     */
    public function showLoginForm(): View|RedirectResponse
    {
        // If already logged in, redirect to dashboard
        if (Auth::guard('staff')->check()) {
            return redirect()->route('staff.dashboard');
        }
        
        return view('staff.login');
    }

    /**
     * Handle staff login.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Find staff by email
        $staff = Staff::where('email', $credentials['email'])->first();

        if (!$staff) {
            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

        // Check password
        if (!Hash::check($credentials['password'], $staff->password)) {
            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

        // Check if staff has dashboard access
        if (!$staff->hasDashboardAccess()) {
            return back()->withErrors([
                'email' => 'You do not have dashboard access. Please contact administrator.',
            ])->onlyInput('email');
        }

        // Login the staff
        Auth::guard('staff')->login($staff, $request->filled('remember'));

        $request->session()->regenerate();

        return redirect()->intended(route('staff.dashboard'));
    }

    /**
     * Handle staff logout.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('staff')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('staff.login');
    }

    /**
     * Show staff dashboard.
     */
    public function dashboard(): View
    {
        $staff = Auth::guard('staff')->user();
        
        // Get students based on staff's campus
        $studentsQuery = Student::query();
        
        if ($staff->campus) {
            $studentsQuery->where('campus', $staff->campus);
        }
        
        $allStudents = $studentsQuery->get();
        
        // Calculate statistics dynamically
        $totalStudents = $allStudents->count();
        
        // Count boys and girls (checking both uppercase and lowercase)
        $boys = $allStudents->filter(function($student) {
            $gender = strtolower($student->gender ?? '');
            return $gender === 'male' || $gender === 'm';
        })->count();
        
        $girls = $allStudents->filter(function($student) {
            $gender = strtolower($student->gender ?? '');
            return $gender === 'female' || $gender === 'f';
        })->count();
        
        // Calculate attendance percentage (for today)
        // This is a placeholder - you'll need to integrate with your attendance system
        // For now, we'll calculate based on a simple logic or show 0%
        $attendancePercentage = 0;
        $presentToday = 0;
        $absentToday = $totalStudents; // Default: all absent until attendance system is integrated
        
        if ($totalStudents > 0) {
            // Get today's attendance count (placeholder - replace with actual attendance logic)
            // When attendance system is implemented, query the attendance table here
            $presentToday = 0; // This should come from your attendance system
            $absentToday = $totalStudents - $presentToday;
            
            // For now, if you want to show a sample percentage, you can use:
            // $attendancePercentage = round(($presentToday / $totalStudents) * 100, 1);
            // Otherwise, it will show 0% until attendance system is integrated
        }
        
        // Get latest admissions (12 most recent students)
        $latestAdmissionsQuery = Student::query();
        if ($staff->campus) {
            $latestAdmissionsQuery->where('campus', $staff->campus);
        }
        $latestAdmissions = $latestAdmissionsQuery
            ->whereNotNull('admission_date')
            ->orderBy('admission_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(12)
            ->get(['id', 'student_name', 'admission_date']);
        
        return view('staff.dashboard', compact(
            'staff', 
            'totalStudents', 
            'boys', 
            'girls', 
            'attendancePercentage',
            'presentToday',
            'absentToday',
            'latestAdmissions'
        ));
    }
}

