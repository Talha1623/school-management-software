<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentAttendance;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class ParentAttendanceController extends Controller
{
    /**
     * Get Class Attendance List
     * Returns list of all students in the same class/section with their attendance status for a specific date
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function classAttendance(Request $request): JsonResponse
    {
        try {
            $parent = $request->user();
            
            if (!$parent) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'data' => null,
                    'token' => null,
                ], 404);
            }

            // Optional filter: specific student_id (must belong to this parent)
            if ($request->filled('student_id')) {
                $studentId = (int) $request->student_id;
                
                // Verify the student belongs to this parent
                $student = $parent->students()->find($studentId);
                
                if (!$student) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You are not allowed to view this student\'s attendance.',
                        'data' => null,
                        'token' => null,
                    ], 403);
                }
                
                // If student_id is provided, date is also required
                if (!$request->filled('date')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Date is required when student_id is provided',
                        'data' => null,
                        'token' => null,
                    ], 400);
                }
            }

            // Validate required parameters
            if (!$request->filled('date')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Date is required',
                    'data' => null,
                    'token' => null,
                ], 400);
            }

            $date = $request->date;

            // Validate date format
            try {
                $attendanceDate = Carbon::parse($date);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid date format. Please use Y-m-d format (e.g., 2024-01-15)',
                    'data' => null,
                    'token' => null,
                ], 400);
            }

            // If student_id is provided, return that student's attendance with monthly summary
            if ($request->filled('student_id')) {
                $studentId = (int) $request->student_id;
                
                // Get the specific student
                $targetStudent = $parent->students()->find($studentId);
                
                if (!$targetStudent) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Student not found or does not belong to this parent',
                        'data' => null,
                        'token' => null,
                    ], 404);
                }

                if (!$targetStudent->campus || !$targetStudent->class || !$targetStudent->section) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Student information incomplete. Cannot fetch attendance.',
                        'data' => null,
                        'token' => null,
                    ], 400);
                }

                // Get month and year from the provided date
                $month = $attendanceDate->month;
                $year = $attendanceDate->year;
                
                // Get start and end dates of the month
                $monthStart = Carbon::create($year, $month, 1)->startOfDay();
                $monthEnd = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

                // Get all attendance records for this student for the entire month
                $monthlyAttendances = StudentAttendance::where('student_id', $studentId)
                    ->whereBetween('attendance_date', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])
                    ->orderBy('attendance_date', 'asc')
                    ->get();

                // Calculate monthly totals
                $totalPresent = $monthlyAttendances->where('status', 'Present')->count();
                $totalAbsent = $monthlyAttendances->where('status', 'Absent')->count();
                $totalLeave = $monthlyAttendances->where('status', 'Leave')->count();
                
                // Calculate total late (check if status is "Late" or remarks contain "late")
                $totalLate = $monthlyAttendances->filter(function($att) {
                    return strtolower($att->status) === 'late' 
                        || (isset($att->remarks) && stripos($att->remarks, 'late') !== false);
                })->count();
                
                // Calculate total hours (for now 0, can be enhanced if time tracking is added)
                // Assuming 6 hours per day for present days (can be configured)
                $hoursPerDay = 6; // Default school hours per day
                $totalHours = $totalPresent * $hoursPerDay;

                // Get attendance for the specific date
                $attendance = StudentAttendance::where('student_id', $studentId)
                    ->whereDate('attendance_date', $attendanceDate->format('Y-m-d'))
                    ->first();

                // Format date-wise attendance
                $attendanceByDate = $monthlyAttendances->map(function($att) {
                    return [
                        'date' => Carbon::parse($att->attendance_date)->format('Y-m-d'),
                        'date_formatted' => Carbon::parse($att->attendance_date)->format('d M Y'),
                        'status' => $att->status,
                        'remarks' => $att->remarks,
                    ];
                })->values();

                // Get all dates in the month and fill missing dates with 'N/A'
                $allDatesInMonth = [];
                $currentDate = $monthStart->copy();
                while ($currentDate->lte($monthEnd)) {
                    $dateStr = $currentDate->format('Y-m-d');
                    $existingAttendance = $monthlyAttendances->first(function($att) use ($dateStr) {
                        return Carbon::parse($att->attendance_date)->format('Y-m-d') === $dateStr;
                    });
                    
                    $allDatesInMonth[] = [
                        'date' => $dateStr,
                        'date_formatted' => $currentDate->format('d M Y'),
                        'status' => $existingAttendance ? $existingAttendance->status : 'N/A',
                        'remarks' => $existingAttendance ? $existingAttendance->remarks : null,
                    ];
                    
                    $currentDate->addDay();
                }

                $studentsData = [[
                    'id' => $targetStudent->id,
                    'student_name' => $targetStudent->student_name,
                    'student_code' => $targetStudent->student_code,
                    'class' => $targetStudent->class,
                    'section' => $targetStudent->section,
                    'campus' => $targetStudent->campus,
                    'status' => $attendance ? $attendance->status : 'N/A',
                    'remarks' => $attendance ? $attendance->remarks : null,
                ]];

                // Return response with monthly summary and date-wise attendance
                return response()->json([
                    'success' => true,
                    'message' => 'Class attendance retrieved successfully',
                    'data' => [
                        'date' => $attendanceDate->format('Y-m-d'),
                        'date_formatted' => $attendanceDate->format('d M Y'),
                        'month' => $month,
                        'year' => $year,
                        'month_formatted' => $attendanceDate->format('F Y'),
                        'class' => $targetStudent->class,
                        'section' => $targetStudent->section,
                        'campus' => $targetStudent->campus,
                        'monthly_summary' => [
                            'total_present' => $totalPresent,
                            'total_absent' => $totalAbsent,
                            'total_leave' => $totalLeave,
                            'total_late' => $totalLate,
                            'total_hours' => $totalHours,
                        ],
                        'students' => $studentsData,
                        'attendance_by_date' => $allDatesInMonth,
                    ],
                    'token' => $request->user()->currentAccessToken()->token ?? null,
                ], 200);
            } else {
                // Get first student from parent's children (similar to student API using authenticated student)
                $parentStudents = $parent->students()->get();
                
                if ($parentStudents->isEmpty()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'No students connected to this parent.',
                        'data' => [
                            'date' => $attendanceDate->format('Y-m-d'),
                            'date_formatted' => $attendanceDate->format('d M Y'),
                            'class' => null,
                            'section' => null,
                            'campus' => null,
                            'students' => [],
                        ],
                        'token' => $request->user()->currentAccessToken()->token ?? null,
                    ], 200);
                }

                // Use first student's class information (similar to student API)
                $targetStudent = $parentStudents->first();

                if (!$targetStudent->campus || !$targetStudent->class || !$targetStudent->section) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Student information incomplete. Cannot fetch attendance.',
                        'data' => null,
                        'token' => null,
                    ], 400);
                }

                // Get all students in the same class and section
                $classStudents = Student::whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($targetStudent->campus))])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($targetStudent->class))])
                    ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($targetStudent->section))])
                    ->orderBy('student_code', 'asc')
                    ->orderBy('student_name', 'asc')
                    ->get();

                // Get attendance records for this date
                $studentIds = $classStudents->pluck('id');
                $attendances = StudentAttendance::whereIn('student_id', $studentIds)
                    ->whereDate('attendance_date', $attendanceDate->format('Y-m-d'))
                    ->get()
                    ->keyBy('student_id');

                // Format students data with attendance status
                $studentsData = $classStudents->map(function($classStudent) use ($attendances) {
                    $attendance = $attendances->get($classStudent->id);
                    
                    return [
                        'id' => $classStudent->id,
                        'student_name' => $classStudent->student_name,
                        'student_code' => $classStudent->student_code,
                        'class' => $classStudent->class,
                        'section' => $classStudent->section,
                        'campus' => $classStudent->campus,
                        'status' => $attendance ? $attendance->status : 'N/A',
                        'remarks' => $attendance ? $attendance->remarks : null,
                    ];
                })->values();
            }

            return response()->json([
                'success' => true,
                'message' => 'Class attendance retrieved successfully',
                'data' => [
                    'date' => $attendanceDate->format('Y-m-d'),
                    'date_formatted' => $attendanceDate->format('d M Y'),
                    'class' => $targetStudent->class,
                    'section' => $targetStudent->section,
                    'campus' => $targetStudent->campus,
                    'students' => $studentsData,
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving attendance: ' . $e->getMessage(),
                'data' => null,
                'token' => null,
            ], 200);
        }
    }
}

