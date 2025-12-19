<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentAttendance;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class StudentAttendanceController extends Controller
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
            $student = $request->user();
            
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'data' => null,
                    'token' => null,
                ], 404);
            }

            // Optional filter: specific student_id (must be the authenticated student)
            if ($request->filled('student_id')) {
                $studentId = (int) $request->student_id;
                
                // Verify the student_id belongs to authenticated student
                if ($studentId !== $student->id) {
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

            // Use authenticated student's information
            if (!$student->campus || !$student->class || !$student->section) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student information incomplete. Cannot fetch attendance.',
                    'data' => null,
                    'token' => null,
                ], 400);
            }

            // If student_id is provided, return only that student's attendance
            $targetStudent = $student; // Default to authenticated student
            if ($request->filled('student_id')) {
                $targetStudentId = (int) $request->student_id;
                
                // Get the specific student
                $targetStudent = Student::find($targetStudentId);
                
                if (!$targetStudent) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Student not found',
                        'data' => null,
                        'token' => null,
                    ], 404);
                }

                // Get attendance record for this specific student and date
                $attendance = StudentAttendance::where('student_id', $targetStudentId)
                    ->whereDate('attendance_date', $attendanceDate->format('Y-m-d'))
                    ->first();

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
            } else {
                // Get all students in the same class and section
                $classStudents = Student::whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($student->campus))])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($student->class))])
                    ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($student->section))])
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

