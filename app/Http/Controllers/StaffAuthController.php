<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentAttendance;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

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
        $attendancePercentage = 0;
        $presentToday = 0;
        $absentToday = 0;
        
        if ($totalStudents > 0) {
            $today = date('Y-m-d');
            $studentIds = $allStudents->pluck('id');
            
            // Get today's attendance
            $todayAttendance = StudentAttendance::whereIn('student_id', $studentIds)
                ->whereDate('attendance_date', $today)
                ->get();
            
            // Count present (only 'Present' status counts as present)
            $presentToday = $todayAttendance->where('status', 'Present')->count();
            
            // Count absent (only 'Absent' status counts as absent)
            $absentToday = $todayAttendance->where('status', 'Absent')->count();
            
            // Calculate percentage based on total students who have attendance marked
            $totalMarked = $todayAttendance->whereIn('status', ['Present', 'Absent'])->count();
            if ($totalMarked > 0) {
                $attendancePercentage = round(($presentToday / $totalMarked) * 100, 1);
            }
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

    /**
     * Get attendance statistics for AJAX requests (for real-time updates).
     */
    public function getAttendanceStats(): JsonResponse
    {
        $staff = Auth::guard('staff')->user();
        
        // Get students based on staff's campus
        $studentsQuery = Student::query();
        
        if ($staff->campus) {
            $studentsQuery->where('campus', $staff->campus);
        }
        
        $allStudents = $studentsQuery->get();
        $totalStudents = $allStudents->count();
        
        $attendancePercentage = 0;
        $presentToday = 0;
        $absentToday = 0;
        
        if ($totalStudents > 0) {
            $today = date('Y-m-d');
            $studentIds = $allStudents->pluck('id');
            
            // Get today's attendance
            $todayAttendance = StudentAttendance::whereIn('student_id', $studentIds)
                ->whereDate('attendance_date', $today)
                ->get();
            
            // Count present (only 'Present' status counts as present)
            $presentToday = $todayAttendance->where('status', 'Present')->count();
            
            // Count absent (only 'Absent' status counts as absent)
            $absentToday = $todayAttendance->where('status', 'Absent')->count();
            
            // Calculate percentage based on total students who have attendance marked
            $totalMarked = $todayAttendance->whereIn('status', ['Present', 'Absent'])->count();
            if ($totalMarked > 0) {
                $attendancePercentage = round(($presentToday / $totalMarked) * 100, 1);
            }
        }
        
        return response()->json([
            'attendancePercentage' => $attendancePercentage,
            'presentToday' => $presentToday,
            'absentToday' => $absentToday,
            'totalStudents' => $totalStudents
        ]);
    }
}

