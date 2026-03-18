<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Timetable;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class StudentTimetableController extends Controller
{
    /**
     * Static timetable subjects that are not stored in Subject table.
     */
    private function getStaticSubjects(): array
    {
        return [
            '[Assembly]',
            '[Lunch Break]',
            '[Free Time]',
            '[Lab Active]',
            '[physicial/sports/activity]',
            '[singing class]',
            '[material arts class]',
            '[Library Activity]',
            '[chilligraphy class]',
            '[other fun activities]',
        ];
    }

    /**
     * Check if a subject is a static subject
     */
    private function isStaticSubject(string $subject): bool
    {
        $staticSubjects = $this->getStaticSubjects();
        return in_array($subject, $staticSubjects);
    }

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

            // Use authenticated student directly (students can only view their own timetable)
            // Ignore student_id parameter and use authenticated student's ID
            // Find student record from database using authenticated student's ID
            $student = Student::find($authenticatedStudent->id);
            
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student not found',
                    'token' => null,
                ], 404);
            }

            // Get class, section, and campus from student's record only (no query parameters)
            $selectedClass = $student->class ?? null;
            $selectedSection = $student->section ?? null;
            $selectedCampus = $student->campus ?? null;

            // Require class and section from student record
            if (!$selectedClass || !$selectedSection) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student information incomplete. Cannot fetch timetable.',
                    'token' => null,
                ], 400);
            }

            // Get day name from date
            $dayName = $timetableDate->format('l'); // Monday, Tuesday, etc.

            // Get timetable for this class and section
            // SoftDeletes will automatically exclude deleted entries (where deleted_at is not null)
            $timetables = Timetable::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($selectedClass))])
                ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($selectedSection))])
                ->whereRaw('LOWER(TRIM(day)) = ?', [strtolower($dayName)]);
            
            // Filter by campus from student's record
            if ($selectedCampus) {
                $timetables->where(function($query) use ($selectedCampus) {
                    $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($selectedCampus))])
                          ->orWhereNull('campus'); // Include entries with null campus for backward compatibility
                });
            } else {
                // If student doesn't have campus, show all timetables for this class/section regardless of campus
                // This allows students without campus to still see their timetable
            }
            
            $timetables = $timetables->orderBy('starting_time', 'asc')->get();

            // Format timetable data with teacher information
            $timetableData = $timetables->map(function($timetable) use ($selectedClass, $selectedSection) {
                $teacher = null;
                
                // Skip static subjects (like [Assembly], [Lunch Break], etc.)
                if (!$this->isStaticSubject($timetable->subject)) {
                    // Find teacher assigned to this subject for the selected class and section
                    $subjectRecord = Subject::whereRaw('LOWER(TRIM(subject_name)) = ?', [strtolower(trim($timetable->subject))])
                        ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($selectedClass))])
                        ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($selectedSection))])
                        ->whereNotNull('teacher')
                        ->first();
                    
                    if ($subjectRecord && $subjectRecord->teacher) {
                        $teacher = $subjectRecord->teacher;
                    } else {
                        // Try to find any teacher assigned to this subject (different class/section)
                        $anyTeacher = Subject::whereRaw('LOWER(TRIM(subject_name)) = ?', [strtolower(trim($timetable->subject))])
                            ->whereNotNull('teacher')
                            ->first();
                        $teacher = $anyTeacher ? $anyTeacher->teacher : null;
                    }
                }
                
                return [
                    'id' => $timetable->id,
                    'subject' => $timetable->subject,
                    'teacher' => $teacher,
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
                    'id' => $student->id,
                    'date' => $timetableDate->format('Y-m-d'),
                    'date_formatted' => $timetableDate->format('d M Y'),
                    'day' => $dayName,
                    'class' => $selectedClass,
                    'section' => $selectedSection,
                    'campus' => $selectedCampus,
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

