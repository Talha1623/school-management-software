<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\Subject;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

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
        
        // Step 1: Get teacher's assigned subjects (if teacher)
        $assignedSubjects = collect();
        $assignedClasses = collect();
        
        if ($staff->isTeacher()) {
            // Get teacher's assigned subjects
            $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                ->get();
            
            // Get teacher's assigned sections
            $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                ->get();
            
            // Get unique classes from both assigned subjects and sections
            $assignedClasses = $assignedSubjects->pluck('class')
                ->merge($assignedSections->pluck('class'))
                ->map(function($class) {
                    return trim($class);
                })
                ->filter(function($class) {
                    return !empty($class);
                })
                ->unique()
                ->values();
        }
        
        // Step 2: Get students based on assigned classes (if teacher) or campus (if other staff)
        $studentsQuery = Student::query();
        
        if ($staff->campus) {
            $studentsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($staff->campus))]);
        }
        
        // If teacher, only show students from assigned classes
        if ($staff->isTeacher()) {
            if ($assignedClasses->isNotEmpty()) {
                $studentsQuery->where(function($q) use ($assignedClasses) {
                    foreach ($assignedClasses as $class) {
                        $q->orWhereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
                    }
                });
            } else {
                // If teacher has no assigned classes, return empty result
                $studentsQuery->whereRaw('1 = 0');
            }
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
            $today = Carbon::today()->format('Y-m-d');
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
        
        // Get latest admissions (12 most recent students) - from assigned classes if teacher
        $latestAdmissionsQuery = Student::query();
        if ($staff->campus) {
            $latestAdmissionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($staff->campus))]);
        }
        
        // Filter by assigned classes if teacher
        if ($staff->isTeacher()) {
            if ($assignedClasses->isNotEmpty()) {
                $latestAdmissionsQuery->where(function($q) use ($assignedClasses) {
                    foreach ($assignedClasses as $class) {
                        $q->orWhereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
                    }
                });
            } else {
                // If teacher has no assigned classes, return empty result
                $latestAdmissionsQuery->whereRaw('1 = 0');
            }
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
            'latestAdmissions',
            'assignedClasses'
        ));
    }

    /**
     * Get attendance statistics for AJAX requests (for real-time updates).
     */
    public function getAttendanceStats(): JsonResponse
    {
        $staff = Auth::guard('staff')->user();
        
        // Step 1: Get teacher's assigned classes (if teacher)
        $assignedClasses = collect();
        
        if ($staff->isTeacher()) {
            // Get teacher's assigned subjects
            $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                ->get();
            
            // Get teacher's assigned sections
            $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                ->get();
            
            // Get unique classes from both assigned subjects and sections
            $assignedClasses = $assignedSubjects->pluck('class')
                ->merge($assignedSections->pluck('class'))
                ->map(function($class) {
                    return trim($class);
                })
                ->filter(function($class) {
                    return !empty($class);
                })
                ->unique()
                ->values();
        }
        
        // Step 2: Get students based on assigned classes (if teacher) or campus (if other staff)
        $studentsQuery = Student::query();
        
        if ($staff->campus) {
            $studentsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($staff->campus))]);
        }
        
        // If teacher, only show students from assigned classes
        if ($staff->isTeacher()) {
            if ($assignedClasses->isNotEmpty()) {
                $studentsQuery->where(function($q) use ($assignedClasses) {
                    foreach ($assignedClasses as $class) {
                        $q->orWhereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
                    }
                });
            } else {
                // If teacher has no assigned classes, return empty result
                $studentsQuery->whereRaw('1 = 0');
            }
        }
        
        $allStudents = $studentsQuery->get();
        $totalStudents = $allStudents->count();
        
        $attendancePercentage = 0;
        $presentToday = 0;
        $absentToday = 0;
        
        if ($totalStudents > 0) {
            $today = Carbon::today()->format('Y-m-d');
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

