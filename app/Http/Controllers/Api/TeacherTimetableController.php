<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Timetable;
use App\Models\Subject;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Campus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TeacherTimetableController extends Controller
{
    /**
     * Get Filter Options (Campuses, Classes, Sections, Subjects)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getFilterOptions(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();

            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access timetable.',
                    'token' => null,
                ], 403);
            }

            // Get teacher's assigned subjects
            $teacherSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                ->whereNotNull('class')
                ->get();

            // Get campuses from teacher's assigned subjects
            $campuses = $teacherSubjects->whereNotNull('campus')
                ->pluck('campus')
                ->unique()
                ->sort()
                ->values();

            // If no campuses from subjects, get from Campus model
            if ($campuses->isEmpty()) {
                $campuses = Campus::orderBy('campus_name', 'asc')
                    ->pluck('campus_name')
                    ->values();
            }

            // Get classes from teacher's assigned subjects
            $classes = $teacherSubjects->whereNotNull('class')
                ->pluck('class')
                ->unique()
                ->sort()
                ->values();

            // If no classes from subjects, get from ClassModel
            if ($classes->isEmpty()) {
                $classes = ClassModel::orderBy('class_name', 'asc')
                    ->pluck('class_name')
                    ->values();
            }

            // Get subjects from teacher's assigned subjects
            $subjects = $teacherSubjects->whereNotNull('subject_name')
                ->pluck('subject_name')
                ->unique()
                ->sort()
                ->values();

            // Days of the week
            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

            return response()->json([
                'success' => true,
                'message' => 'Filter options retrieved successfully.',
                'data' => [
                    'campuses' => $campuses,
                    'classes' => $classes,
                    'subjects' => $subjects,
                    'days' => $days,
                ],
                'token' => $request->bearerToken(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving filter options.',
                'error' => config('app.debug') ? $e->getMessage() : null,
                'token' => $request->bearerToken(),
            ], 500);
        }
    }

    /**
     * Get Sections by Class
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getSectionsByClass(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();

            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access timetable.',
                    'token' => null,
                ], 403);
            }

            $validated = $request->validate([
                'class' => ['required', 'string'],
            ]);

            // Get teacher's assigned subjects for this class
            $teacherSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($validated['class']))])
                ->whereNotNull('section')
                ->get();

            // Get sections from teacher's assigned subjects
            $sections = $teacherSubjects->pluck('section')
                ->unique()
                ->sort()
                ->values();

            // If no sections from subjects, get from Section model
            if ($sections->isEmpty()) {
                $sections = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($validated['class']))])
                    ->whereNotNull('name')
                    ->distinct()
                    ->orderBy('name', 'asc')
                    ->pluck('name')
                    ->sort()
                    ->values();
            }

            return response()->json([
                'success' => true,
                'message' => 'Sections retrieved successfully.',
                'data' => [
                    'sections' => $sections,
                ],
                'token' => $request->bearerToken(),
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
                'token' => $request->bearerToken(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving sections.',
                'error' => config('app.debug') ? $e->getMessage() : null,
                'token' => $request->bearerToken(),
            ], 500);
        }
    }

    /**
     * Get Timetable
     * Returns timetable filtered by teacher's assigned subjects
     * 
     * @param Request $request
     * @param int|null $day
     * @param int|null $month
     * @param int|null $year
     * @return JsonResponse
     */
    public function getTimetable(Request $request, ?int $day = null, ?int $month = null, ?int $year = null): JsonResponse
    {
        try {
            $teacher = $request->user();

            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access timetable.',
                    'token' => null,
                ], 403);
            }

            // Use URL parameters if provided, otherwise use query parameters
            if ($day !== null && $month !== null && $year !== null) {
                $request->merge([
                    'day' => $day,
                    'month' => $month,
                    'year' => $year,
                ]);
            }

            // Get teacher's assigned subjects
            $teacherSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                ->whereNotNull('class')
                ->get();

            // If teacher has no assigned subjects, return empty
            if ($teacherSubjects->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No timetable found. You have no assigned subjects.',
                    'data' => [
                        'timetable' => [],
                        'timetable_by_day' => [],
                    ],
                    'token' => $request->bearerToken(),
                ], 200);
            }

            // Build query for timetables
            $query = Timetable::query();

            // Get unique classes and sections from teacher's assigned subjects
            $teacherClasses = $teacherSubjects->pluck('class')->unique()->filter()->map(function($class) {
                return trim($class);
            })->filter()->toArray();
            
            $teacherSections = $teacherSubjects->pluck('section')->unique()->filter()->map(function($section) {
                return trim($section);
            })->filter()->toArray();

            // Filter by teacher's assigned classes and sections (less restrictive)
            // Show ALL timetables for assigned classes/sections, regardless of subject
            // This ensures timetables added by super admin also show up
            if (!empty($teacherClasses) || !empty($teacherSections)) {
                $query->where(function($q) use ($teacherClasses, $teacherSections) {
                    // Match by class AND section combination (preferred)
                    if (!empty($teacherClasses) && !empty($teacherSections)) {
                        foreach ($teacherClasses as $class) {
                            foreach ($teacherSections as $section) {
                                $q->orWhere(function($csQuery) use ($class, $section) {
                                    $csQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                                           ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($section))]);
                                });
                            }
                        }
                    }
                    
                    // Also match by class only (if sections are empty)
                    if (!empty($teacherClasses) && empty($teacherSections)) {
                        foreach ($teacherClasses as $class) {
                            $q->orWhereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
                        }
                    }
                    
                    // Also match by section only (if classes are empty)
                    if (empty($teacherClasses) && !empty($teacherSections)) {
                        foreach ($teacherSections as $section) {
                            $q->orWhereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($section))]);
                        }
                    }
                });
            } else {
                // Fallback: If no classes/sections, return empty
                $query->whereRaw('1 = 0');
            }

            // Apply additional filters if provided
            if ($request->filled('campus')) {
                $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($request->campus))]);
            }

            if ($request->filled('class')) {
                $query->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($request->class))]);
            }

            if ($request->filled('section')) {
                $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($request->section))]);
            }

            if ($request->filled('subject')) {
                $query->whereRaw('LOWER(TRIM(subject)) = ?', [strtolower(trim($request->subject))]);
            }

            // Get day, month and year for date calculation
            $dayForDate = null;
            $monthForDate = null;
            $yearForDate = null;
            if ($request->filled('day') && $request->filled('month') && $request->filled('year')) {
                $dayForDate = (int)$request->day;
                $monthForDate = (int)$request->month;
                $yearForDate = (int)$request->year;
            } elseif ($request->filled('month') && $request->filled('year')) {
                $monthForDate = (int)$request->month;
                $yearForDate = (int)$request->year;
            }
            
            // Only filter by day name if it's a valid day name (not a numeric day)
            // Valid day names: Monday, Tuesday, Wednesday, Thursday, Friday, Saturday, Sunday
            $validDayNames = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            if ($request->filled('day')) {
                $dayValue = strtolower(trim($request->day));
                // Only apply day filter if it's a day name, not a numeric value
                if (in_array($dayValue, $validDayNames)) {
                    $query->whereRaw('LOWER(TRIM(day)) = ?', [$dayValue]);
                }
                // If it's a numeric value (date day), don't filter by day name
            }

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
            ->orderBy('starting_time')
            ->get();

            // Format timetable data
            $timetableData = $timetables->map(function($timetable) use ($dayForDate, $monthForDate, $yearForDate) {
                $item = [
                    'id' => $timetable->id,
                    'campus' => $timetable->campus ?? null,
                    'class' => $timetable->class,
                    'section' => $timetable->section,
                    'subject' => $timetable->subject,
                    'day' => $timetable->day,
                    'starting_time' => $timetable->starting_time,
                    'ending_time' => $timetable->ending_time,
                    'starting_time_formatted' => date('h:i A', strtotime($timetable->starting_time)),
                    'ending_time_formatted' => date('h:i A', strtotime($timetable->ending_time)),
                ];

                // If exact date (day/month/year) is provided, use it directly
                if ($dayForDate !== null && $monthForDate !== null && $yearForDate !== null) {
                    // Validate the date exists
                    if (checkdate($monthForDate, $dayForDate, $yearForDate)) {
                        $calculatedDate = date('Y-m-d', mktime(0, 0, 0, $monthForDate, $dayForDate, $yearForDate));
                        $item['date'] = $calculatedDate;
                        $item['date_formatted'] = date('d M Y', mktime(0, 0, 0, $monthForDate, $dayForDate, $yearForDate));
                        $item['day_number'] = $dayForDate;
                    }
                } elseif ($monthForDate !== null && $yearForDate !== null) {
                    // If only month and year provided, calculate date from day name
                    $dayName = ucfirst(strtolower(trim($timetable->day)));
                    $dayNumber = ['Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3, 'Thursday' => 4, 'Friday' => 5, 'Saturday' => 6, 'Sunday' => 7];
                    
                    if (isset($dayNumber[$dayName]) && $monthForDate >= 1 && $monthForDate <= 12 && $yearForDate >= 2000 && $yearForDate <= 2100) {
                        // Find the first occurrence of the day in the given month/year
                        $firstDayOfMonth = date('N', mktime(0, 0, 0, $monthForDate, 1, $yearForDate)); // 1=Monday, 7=Sunday
                        $targetDay = $dayNumber[$dayName];
                        $firstOccurrence = 1 + (($targetDay - $firstDayOfMonth + 7) % 7);
                        
                        // Validate the date exists
                        if (checkdate($monthForDate, $firstOccurrence, $yearForDate)) {
                            $calculatedDate = date('Y-m-d', mktime(0, 0, 0, $monthForDate, $firstOccurrence, $yearForDate));
                            $item['date'] = $calculatedDate;
                            $item['date_formatted'] = date('d M Y', mktime(0, 0, 0, $monthForDate, $firstOccurrence, $yearForDate));
                            $item['day_number'] = (int)date('j', strtotime($calculatedDate)); // 'j' gives day without leading zero (1-31)
                        }
                    }
                }

                return $item;
            });

            // Group by day
            $timetableByDay = [];
            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            
            foreach ($days as $day) {
                $dayTimetables = $timetableData->filter(function($item) use ($day) {
                    return strtolower($item['day']) === strtolower($day);
                })->values();
                
                if ($dayTimetables->isNotEmpty()) {
                    $timetableByDay[$day] = $dayTimetables->toArray();
                }
            }

            $responseData = [
                'teacher' => [
                    'name' => $teacher->name,
                    'emp_id' => $teacher->emp_id ?? null,
                ],
                'timetable' => $timetableData->values()->toArray(),
                'timetable_by_day' => $timetableByDay,
                'total_periods' => $timetables->count(),
            ];

            // Add day, month and year info if provided
            if ($dayForDate !== null && $monthForDate !== null && $yearForDate !== null) {
                $responseData['day'] = $dayForDate;
                $responseData['month'] = $monthForDate;
                $responseData['year'] = $yearForDate;
            } elseif ($monthForDate !== null && $yearForDate !== null) {
                $responseData['month'] = $monthForDate;
                $responseData['year'] = $yearForDate;
            }

            return response()->json([
                'success' => true,
                'message' => 'Timetable retrieved successfully.',
                'data' => $responseData,
                'token' => $request->bearerToken(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving timetable.',
                'error' => config('app.debug') ? $e->getMessage() : null,
                'token' => $request->bearerToken(),
            ], 500);
        }
    }

    /**
     * Get Timetable by Class
     * Returns timetable list for a specific class, section, and campus
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getTimetableByClass(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();

            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access timetable.',
                    'token' => null,
                ], 403);
            }

            // Validate required parameters
            $validated = $request->validate([
                'class' => ['required', 'string'],
                'section' => ['nullable', 'string'],
                'month' => ['nullable', 'integer', 'min:1', 'max:12'],
                'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
                'date' => ['nullable', 'string'],
            ]);

            // Build query for timetables
            $query = Timetable::query();

            // Filter by class (required) - case insensitive
            $className = strtolower(trim($validated['class']));
            $query->whereRaw('LOWER(TRIM(class)) = ?', [$className]);

            // Filter by section if provided - case insensitive
            if (!empty($validated['section'])) {
                $sectionName = strtolower(trim($validated['section']));
                $query->whereRaw('LOWER(TRIM(section)) = ?', [$sectionName]);
            }

            // Debug: Check if any data exists for this class (without section filter)
            $debugQuery = Timetable::query();
            $debugQuery->whereRaw('LOWER(TRIM(class)) = ?', [$className]);
            $allClassTimetables = $debugQuery->get();

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
            ->orderBy('starting_time')
            ->get();

            // Get day, month and year for date calculation
            $dayForDate = null;
            $monthForDate = null;
            $yearForDate = null;
            
            // If date parameter is provided, parse it first
            if ($request->filled('date')) {
                try {
                    $dateValue = $request->date;
                    // Try to parse the date in various formats
                    // Try YYYY-MM-DD format
                    if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $dateValue, $matches)) {
                        $yearForDate = (int)$matches[1];
                        $monthForDate = (int)$matches[2];
                        $dayForDate = (int)$matches[3];
                    }
                    // Try DD-MM-YYYY format
                    elseif (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})$/', $dateValue, $matches)) {
                        $dayForDate = (int)$matches[1];
                        $monthForDate = (int)$matches[2];
                        $yearForDate = (int)$matches[3];
                    }
                    // Try other formats using strtotime
                    else {
                        $timestamp = strtotime($dateValue);
                        if ($timestamp !== false) {
                            $dayForDate = (int)date('j', $timestamp);
                            $monthForDate = (int)date('n', $timestamp);
                            $yearForDate = (int)date('Y', $timestamp);
                        }
                    }
                    
                    // Validate the parsed date
                    if ($dayForDate && $monthForDate && $yearForDate) {
                        if (!checkdate($monthForDate, $dayForDate, $yearForDate)) {
                            $dayForDate = null;
                            $monthForDate = null;
                            $yearForDate = null;
                        }
                    }
                } catch (\Exception $e) {
                    // If date parsing fails, ignore it and use day/month/year separately
                }
            }
            
            // If date parameter not provided or parsing failed, use month/year separately
            if ($dayForDate === null && $monthForDate === null && $yearForDate === null) {
                if ($request->filled('month') && $request->filled('year')) {
                    $monthForDate = (int)$request->month;
                    $yearForDate = (int)$request->year;
                }
            }

            // Format timetable data
            $timetableData = $timetables->map(function($timetable) use ($dayForDate, $monthForDate, $yearForDate) {
                $item = [
                    'id' => $timetable->id,
                    'campus' => $timetable->campus ?? null,
                    'class' => $timetable->class,
                    'section' => $timetable->section,
                    'subject' => $timetable->subject,
                    'day' => $timetable->day,
                    'starting_time' => $timetable->starting_time,
                    'ending_time' => $timetable->ending_time,
                    'starting_time_formatted' => date('h:i A', strtotime($timetable->starting_time)),
                    'ending_time_formatted' => date('h:i A', strtotime($timetable->ending_time)),
                ];

                // If exact date (day/month/year) is provided, use it directly
                if ($dayForDate !== null && $monthForDate !== null && $yearForDate !== null) {
                    // Validate the date exists
                    if (checkdate($monthForDate, $dayForDate, $yearForDate)) {
                        $calculatedDate = date('Y-m-d', mktime(0, 0, 0, $monthForDate, $dayForDate, $yearForDate));
                        $item['date'] = $calculatedDate;
                        $item['date_formatted'] = date('d M Y', mktime(0, 0, 0, $monthForDate, $dayForDate, $yearForDate));
                        $item['day_number'] = $dayForDate;
                    }
                } elseif ($monthForDate !== null && $yearForDate !== null) {
                    // If only month and year provided, calculate date from day name
                    $dayName = ucfirst(strtolower(trim($timetable->day)));
                    $dayNumber = ['Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3, 'Thursday' => 4, 'Friday' => 5, 'Saturday' => 6, 'Sunday' => 7];
                    
                    if (isset($dayNumber[$dayName]) && $monthForDate >= 1 && $monthForDate <= 12 && $yearForDate >= 2000 && $yearForDate <= 2100) {
                        // Find the first occurrence of the day in the given month/year
                        $firstDayOfMonth = date('N', mktime(0, 0, 0, $monthForDate, 1, $yearForDate)); // 1=Monday, 7=Sunday
                        $targetDay = $dayNumber[$dayName];
                        $firstOccurrence = 1 + (($targetDay - $firstDayOfMonth + 7) % 7);
                        
                        // Validate the date exists
                        if (checkdate($monthForDate, $firstOccurrence, $yearForDate)) {
                            $calculatedDate = date('Y-m-d', mktime(0, 0, 0, $monthForDate, $firstOccurrence, $yearForDate));
                            $item['date'] = $calculatedDate;
                            $item['date_formatted'] = date('d M Y', mktime(0, 0, 0, $monthForDate, $firstOccurrence, $yearForDate));
                            $item['day_number'] = (int)date('j', strtotime($calculatedDate)); // 'j' gives day without leading zero (1-31)
                        }
                    }
                }

                return $item;
            });

            // Group by day
            $timetableByDay = [];
            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            
            foreach ($days as $day) {
                $dayTimetables = $timetableData->filter(function($item) use ($day) {
                    return strtolower($item['day']) === strtolower($day);
                })->values();
                
                if ($dayTimetables->isNotEmpty()) {
                    $timetableByDay[$day] = $dayTimetables->toArray();
                }
            }

            // Get actual campus from first timetable entry if available (for debugging)
            $actualCampus = null;
            if ($timetables->isNotEmpty()) {
                $actualCampus = $timetables->first()->campus;
            }

            $responseData = [
                'class' => $validated['class'],
                'section' => $validated['section'] ?? null,
                'timetable' => $timetableData->values()->toArray(),
                'timetable_by_day' => $timetableByDay,
                'total_periods' => $timetables->count(),
            ];

            // Add day, month and year info if provided
            if ($dayForDate !== null && $monthForDate !== null && $yearForDate !== null) {
                $responseData['day'] = $dayForDate;
                $responseData['month'] = $monthForDate;
                $responseData['year'] = $yearForDate;
            } elseif ($monthForDate !== null && $yearForDate !== null) {
                $responseData['month'] = $monthForDate;
                $responseData['year'] = $yearForDate;
            }

            // Add debug info in development mode
            if (config('app.debug')) {
                $responseData['debug'] = [
                    'query_class' => $className,
                    'query_section' => $validated['section'] ?? null,
                    'actual_campus_in_db' => $actualCampus,
                    'total_found' => $timetables->count(),
                    'total_for_class_only' => $allClassTimetables->count(),
                    'available_sections' => $allClassTimetables->pluck('section')->unique()->values()->toArray(),
                    'available_campuses' => $allClassTimetables->whereNotNull('campus')->pluck('campus')->unique()->values()->toArray(),
                ];
            }

            return response()->json([
                'success' => true,
                'message' => $timetables->count() > 0 
                    ? 'Timetable retrieved successfully.' 
                    : 'No timetable found for the specified class/section/campus.',
                'data' => $responseData,
                'token' => $request->bearerToken(),
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
                'token' => $request->bearerToken(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving timetable.',
                'error' => config('app.debug') ? $e->getMessage() : null,
                'token' => $request->bearerToken(),
            ], 500);
        }
    }
}

