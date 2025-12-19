<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Timetable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class StudentTimetableController extends Controller
{
    /**
     * Get Timetable for Student
     * Returns timetable for the student's class/section for a specific date
     * 
     * @param Request $request
     * @param int $studentId
     * @param string $date
     * @return JsonResponse
     */
    public function getTimetable(Request $request, $studentId, $date): JsonResponse
    {
        try {
            $authenticatedStudent = $request->user();
            
            if (!$authenticatedStudent) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'token' => null,
                ], 404);
            }

            // Validate date format
            try {
                $timetableDate = Carbon::parse($date);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid date format. Please use Y-m-d format (e.g., 2024-01-15)',
                    'token' => null,
                ], 400);
            }

            // Find the student
            $student = Student::find($studentId);
            
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student not found',
                    'token' => null,
                ], 404);
            }

            // Verify that the authenticated student is accessing their own timetable
            // or allow if they have permission to view other students' timetables
            if ($authenticatedStudent->id != $studentId) {
                // You can add additional permission checks here if needed
                // For now, we'll allow students to view their own timetable only
                return response()->json([
                    'success' => false,
                    'message' => 'You can only view your own timetable',
                    'token' => null,
                ], 403);
            }

            if (!$student->campus || !$student->class || !$student->section) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student information incomplete. Cannot fetch timetable.',
                    'token' => null,
                ], 400);
            }

            // Get day name from date
            $dayName = $timetableDate->format('l'); // Monday, Tuesday, etc.

            // Get timetable for this class and section
            $timetables = Timetable::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($student->class))])
                ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($student->section))])
                ->whereRaw('LOWER(TRIM(day)) = ?', [strtolower($dayName)])
                ->orderBy('starting_time', 'asc')
                ->get();

            // Format timetable data
            $timetableData = $timetables->map(function($timetable) {
                return [
                    'id' => $timetable->id,
                    'subject' => $timetable->subject,
                    'day' => $timetable->day,
                    'starting_time' => $timetable->starting_time,
                    'ending_time' => $timetable->ending_time,
                    'starting_time_formatted' => Carbon::parse($timetable->starting_time)->format('h:i A'),
                    'ending_time_formatted' => Carbon::parse($timetable->ending_time)->format('h:i A'),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Timetable retrieved successfully',
                'data' => [
                    'date' => $timetableDate->format('Y-m-d'),
                    'date_formatted' => $timetableDate->format('d M Y'),
                    'day' => $dayName,
                    'class' => $student->class,
                    'section' => $student->section,
                    'campus' => $student->campus,
                    'timetable' => $timetableData,
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving timetable: ' . $e->getMessage(),
                'token' => null,
            ], 200);
        }
    }
}

