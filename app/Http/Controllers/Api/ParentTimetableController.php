<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Timetable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class ParentTimetableController extends Controller
{
    /**
     * Get Timetable for Student's Class
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
            $parent = $request->user();
            
            if (!$parent) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'token' => null,
                ], 404);
            }

            $studentId = (int) $studentId;

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

            // Verify student belongs to this parent
            // First check via parent_account_id relationship
            $student = $parent->students()->find($studentId);
            
            // If not found via parent_account_id, check via father_id_card match
            if (!$student) {
                $student = Student::find($studentId);
                
                if ($student && $parent->id_card_number) {
                    // Normalize both ID cards for comparison
                    $parentIdCard = str_replace(['-', ' ', '_', '.'], '', strtolower(trim($parent->id_card_number)));
                    $studentFatherIdCard = str_replace(['-', ' ', '_', '.'], '', strtolower(trim($student->father_id_card ?? '')));
                    
                    // Check if they match
                    if ($parentIdCard !== $studentFatherIdCard || empty($studentFatherIdCard)) {
                        $student = null; // Not a match
                    }
                } else {
                    $student = null;
                }
            }
            
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student not found or does not belong to this parent',
                    'token' => null,
                ], 404);
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

            // Build query for timetables - similar to teacher timetable logic
            $query = Timetable::query();

            // Filter by student's class and section (less restrictive - show all timetables for this class/section)
            // This ensures timetables added by super admin also show up
            $studentClass = trim($student->class);
            $studentSection = trim($student->section);

            // Normalize class names for better matching (remove spaces, convert to lowercase)
            // This handles cases like "Fifty One" vs "fifty" or "FiftyOne" vs "fifty one"
            $normalizedStudentClass = str_replace(' ', '', strtolower(trim($studentClass)));
            $studentClassLower = strtolower(trim($studentClass));

            // Extract primary word from class name for flexible matching
            // "Fifty One" -> "fifty", "fifty" -> "fifty"
            $classWords = array_filter(explode(' ', $studentClassLower), function($word) {
                return strlen(trim($word)) > 0;
            });
            $primaryClassWord = !empty($classWords) ? trim(reset($classWords)) : $studentClassLower;

            // Filter by class and section combination (like teacher timetable)
            // Use flexible matching to handle class name variations like "Fifty One" vs "fifty"
            if ($studentClass && $studentSection) {
                $query->where(function($q) use ($studentClassLower, $normalizedStudentClass, $primaryClassWord, $studentSection) {
                    // Exact match (case-insensitive)
                    $q->where(function($exactQuery) use ($studentClassLower, $studentSection) {
                        $exactQuery->whereRaw('LOWER(TRIM(class)) = ?', [$studentClassLower])
                                   ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($studentSection)]);
                    })
                    // Normalized match (handles "Fifty One" vs "fiftyone" variations)
                    ->orWhere(function($normalizedQuery) use ($normalizedStudentClass, $studentSection) {
                        $normalizedQuery->whereRaw('LOWER(REPLACE(class, " ", "")) = ?', [$normalizedStudentClass])
                                        ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($studentSection)]);
                    })
                    // Match by primary word - if student class is "Fifty One", match timetables with "fifty"
                    ->orWhere(function($wordQuery) use ($primaryClassWord, $studentSection) {
                        $wordQuery->whereRaw('LOWER(TRIM(class)) = ?', [$primaryClassWord])
                                  ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($studentSection)]);
                    })
                    // Also check if normalized timetable class contains primary word
                    ->orWhere(function($flexibleQuery) use ($primaryClassWord, $studentSection) {
                        $flexibleQuery->whereRaw('LOWER(REPLACE(class, " ", "")) LIKE ?', ['%' . $primaryClassWord . '%'])
                                      ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($studentSection)]);
                    })
                    // Reverse: check if student class contains timetable class
                    ->orWhere(function($reverseQuery) use ($primaryClassWord, $studentSection) {
                        $reverseQuery->whereRaw('LOWER(TRIM(class)) LIKE ?', ['%' . $primaryClassWord . '%'])
                                     ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($studentSection)]);
                    });
                });
            } elseif ($studentClass) {
                // If only class is available, filter by class only (with flexible matching)
                $query->where(function($q) use ($studentClassLower, $normalizedStudentClass, $primaryClassWord) {
                    $q->whereRaw('LOWER(TRIM(class)) = ?', [$studentClassLower])
                      ->orWhereRaw('LOWER(REPLACE(class, " ", "")) = ?', [$normalizedStudentClass])
                      ->orWhereRaw('LOWER(TRIM(class)) = ?', [$primaryClassWord])
                      ->orWhereRaw('LOWER(REPLACE(class, " ", "")) LIKE ?', ['%' . $primaryClassWord . '%'])
                      ->orWhereRaw('LOWER(TRIM(class)) LIKE ?', ['%' . $primaryClassWord . '%']);
                });
            } elseif ($studentSection) {
                // If only section is available, filter by section only
                $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($studentSection)]);
            }

            // Don't filter by campus - show all timetables for class/section regardless of campus
            // This ensures timetables added by super admin show up even if campus doesn't match

            // Filter by day name for the specific date
            $query->whereRaw('LOWER(TRIM(day)) = ?', [strtolower($dayName)]);
            
            // Order by day and time
            $timetables = $query->orderByRaw("
                CASE day
                    WHEN 'Monday' THEN 1
                    WHEN 'Tuesday' THEN 2
                    WHEN 'Wednesday' THEN 3
                    WHEN 'Thursday' THEN 4
                    WHEN 'Friday' THEN 5
                    WHEN 'Saturday' THEN 6
                    WHEN 'Sunday' THEN 7
                    ELSE 8
                END
            ")
            ->orderBy('starting_time', 'asc')
            ->get();

            // Format timetable data
            $timetableData = $timetables->map(function($timetable) use ($timetableDate) {
                return [
                    'id' => $timetable->id,
                    'campus' => $timetable->campus ?? null,
                    'class' => $timetable->class,
                    'section' => $timetable->section,
                    'subject' => $timetable->subject,
                    'day' => $timetable->day,
                    'starting_time' => $timetable->starting_time,
                    'ending_time' => $timetable->ending_time,
                    'starting_time_formatted' => Carbon::parse($timetable->starting_time)->format('h:i A'),
                    'ending_time_formatted' => Carbon::parse($timetable->ending_time)->format('h:i A'),
                ];
            });

            // Group by day for better organization
            $timetableByDay = [];
            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            
            // Get all timetables for this class/section (not just for the specific day)
            $allTimetablesQuery = Timetable::query();
            
            // Filter by class and section combination (with flexible matching)
            if ($studentClass && $studentSection) {
                $allTimetablesQuery->where(function($q) use ($studentClassLower, $normalizedStudentClass, $primaryClassWord, $studentSection) {
                    // Exact match (case-insensitive)
                    $q->where(function($exactQuery) use ($studentClassLower, $studentSection) {
                        $exactQuery->whereRaw('LOWER(TRIM(class)) = ?', [$studentClassLower])
                                   ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($studentSection)]);
                    })
                    // Normalized match (handles "Fifty One" vs "fiftyone" variations)
                    ->orWhere(function($normalizedQuery) use ($normalizedStudentClass, $studentSection) {
                        $normalizedQuery->whereRaw('LOWER(REPLACE(class, " ", "")) = ?', [$normalizedStudentClass])
                                        ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($studentSection)]);
                    })
                    // Match by primary word - if student class is "Fifty One", match timetables with "fifty"
                    ->orWhere(function($wordQuery) use ($primaryClassWord, $studentSection) {
                        $wordQuery->whereRaw('LOWER(TRIM(class)) = ?', [$primaryClassWord])
                                  ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($studentSection)]);
                    })
                    // Also check if normalized timetable class contains primary word
                    ->orWhere(function($flexibleQuery) use ($primaryClassWord, $studentSection) {
                        $flexibleQuery->whereRaw('LOWER(REPLACE(class, " ", "")) LIKE ?', ['%' . $primaryClassWord . '%'])
                                      ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($studentSection)]);
                    })
                    // Reverse: check if student class contains timetable class
                    ->orWhere(function($reverseQuery) use ($primaryClassWord, $studentSection) {
                        $reverseQuery->whereRaw('LOWER(TRIM(class)) LIKE ?', ['%' . $primaryClassWord . '%'])
                                     ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($studentSection)]);
                    });
                });
            } elseif ($studentClass) {
                $allTimetablesQuery->where(function($q) use ($studentClassLower, $normalizedStudentClass, $primaryClassWord) {
                    $q->whereRaw('LOWER(TRIM(class)) = ?', [$studentClassLower])
                      ->orWhereRaw('LOWER(REPLACE(class, " ", "")) = ?', [$normalizedStudentClass])
                      ->orWhereRaw('LOWER(TRIM(class)) = ?', [$primaryClassWord])
                      ->orWhereRaw('LOWER(REPLACE(class, " ", "")) LIKE ?', ['%' . $primaryClassWord . '%'])
                      ->orWhereRaw('LOWER(TRIM(class)) LIKE ?', ['%' . $primaryClassWord . '%']);
                });
            } elseif ($studentSection) {
                $allTimetablesQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($studentSection)]);
            }
            
            // Don't filter by campus - show all timetables for class/section regardless of campus
            
            $allTimetables = $allTimetablesQuery->orderByRaw("
                CASE day
                    WHEN 'Monday' THEN 1
                    WHEN 'Tuesday' THEN 2
                    WHEN 'Wednesday' THEN 3
                    WHEN 'Thursday' THEN 4
                    WHEN 'Friday' THEN 5
                    WHEN 'Saturday' THEN 6
                    WHEN 'Sunday' THEN 7
                    ELSE 8
                END
            ")
            ->orderBy('starting_time', 'asc')
            ->get();

            // Group all timetables by day
            foreach ($days as $day) {
                $dayTimetables = $allTimetables->filter(function($timetable) use ($day) {
                    return strtolower(trim($timetable->day)) === strtolower($day);
                })->map(function($timetable) {
                    return [
                        'id' => $timetable->id,
                        'campus' => $timetable->campus ?? null,
                        'class' => $timetable->class,
                        'section' => $timetable->section,
                        'subject' => $timetable->subject,
                        'day' => $timetable->day,
                        'starting_time' => $timetable->starting_time,
                        'ending_time' => $timetable->ending_time,
                        'starting_time_formatted' => Carbon::parse($timetable->starting_time)->format('h:i A'),
                        'ending_time_formatted' => Carbon::parse($timetable->ending_time)->format('h:i A'),
                    ];
                })->values();
                
                if ($dayTimetables->isNotEmpty()) {
                    $timetableByDay[$day] = $dayTimetables->toArray();
                }
            }

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
                    'timetable' => $timetableData->values()->toArray(),
                    'timetable_by_day' => $timetableByDay,
                    'total_periods' => $timetables->count(),
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

