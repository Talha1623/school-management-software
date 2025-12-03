<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\Student;
use App\Models\StudentAttendance;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class TeacherController extends Controller
{
    /**
     * Get Teacher Dashboard Stats
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function dashboard(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();

            if (!$teacher || strtolower(trim($teacher->designation ?? '')) !== 'teacher') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access dashboard.',
                    'token' => null,
                ], 403);
            }

            // Step 1: Get teacher's assigned subjects
            $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                ->get();

            // Step 2: Get unique classes from assigned subjects
            $assignedClasses = $assignedSubjects->pluck('class')
                ->unique()
                ->filter()
                ->values();

            // Step 3: Get students from assigned classes
            $studentsQuery = Student::query();

            // Filter by teacher's campus
            if ($teacher->campus) {
                $studentsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($teacher->campus))]);
            }

            // Filter by assigned classes ONLY
            if ($assignedClasses->isNotEmpty()) {
                $studentsQuery->where(function($q) use ($assignedClasses) {
                    foreach ($assignedClasses as $class) {
                        $q->orWhereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
                    }
                });
            } else {
                // If no classes assigned, return empty result
                $studentsQuery->whereRaw('1 = 0');
            }

            $allStudents = $studentsQuery->get();

            // Step 4: Calculate statistics
            $totalStudents = $allStudents->count();

            // Count boys and girls
            $boys = $allStudents->filter(function($student) {
                $gender = strtolower($student->gender ?? '');
                return $gender === 'male' || $gender === 'm';
            })->count();

            $girls = $allStudents->filter(function($student) {
                $gender = strtolower($student->gender ?? '');
                return $gender === 'female' || $gender === 'f';
            })->count();

            // Step 5: Calculate today's attendance
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

                // Count present and absent
                $presentToday = $todayAttendance->where('status', 'Present')->count();
                $absentToday = $todayAttendance->where('status', 'Absent')->count();

                // Calculate percentage
                $totalMarked = $todayAttendance->whereIn('status', ['Present', 'Absent'])->count();
                if ($totalMarked > 0) {
                    $attendancePercentage = round(($presentToday / $totalMarked) * 100, 1);
                }
            }

            // Step 6: Get latest admissions (from assigned classes only)
            $latestAdmissions = $allStudents
                ->whereNotNull('admission_date')
                ->sortByDesc('admission_date')
                ->sortByDesc('created_at')
                ->take(12)
                ->map(function($student) {
                    return [
                        'id' => $student->id,
                        'student_name' => $student->student_name,
                        'admission_date' => $student->admission_date ? Carbon::parse($student->admission_date)->format('Y-m-d') : null,
                    ];
                })
                ->values();

            return response()->json([
                'success' => true,
                'message' => 'Dashboard data retrieved successfully',
                'data' => [
                    'assigned_classes' => $assignedClasses,
                    'statistics' => [
                        'total_students' => $totalStudents,
                        'boys' => $boys,
                        'girls' => $girls,
                        'attendance_percentage' => $attendancePercentage,
                        'present_today' => $presentToday,
                        'absent_today' => $absentToday,
                    ],
                    'latest_admissions' => $latestAdmissions,
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Dashboard API Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'teacher_id' => $request->user()->id ?? null,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving dashboard data: ' . $e->getMessage(),
                'token' => null,
            ], 200);
        }
    }
}
