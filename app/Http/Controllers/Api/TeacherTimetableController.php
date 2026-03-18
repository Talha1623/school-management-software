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

            // Get unique classes, sections, and subjects from teacher's assigned subjects
            $teacherClasses = $teacherSubjects->pluck('class')->unique()->filter()->map(function($class) {
                return trim($class);
            })->filter()->toArray();
            
            $teacherSections = $teacherSubjects->pluck('section')->unique()->filter()->map(function($section) {
                return trim($section);
            })->filter()->toArray();
            
            // Get assigned subject names (case-insensitive matching)
            $teacherSubjectNames = $teacherSubjects->pluck('subject_name')->unique()->filter()->map(function($subject) {
                return trim($subject);
            })->filter()->toArray();

            // Get static subjects list
            $staticSubjects = $this->getStaticSubjects();
            
            // Filter by teacher's assigned subjects AND static subjects
            // Show timetables for assigned subjects OR static subjects (not all subjects for assigned classes/sections)
            if (!empty($teacherSubjectNames) || !empty($staticSubjects)) {
                $query->where(function($q) use ($teacherSubjectNames, $staticSubjects) {
                    // Include assigned subjects
                    if (!empty($teacherSubjectNames)) {
                        foreach ($teacherSubjectNames as $subjectName) {
                            $q->orWhereRaw('LOWER(TRIM(subject)) = ?', [strtolower(trim($subjectName))]);
                        }
                    }
                    
                    // Include static subjects (Assembly, Lunch Break, etc.)
                    if (!empty($staticSubjects)) {
                        foreach ($staticSubjects as $staticSubject) {
                            $q->orWhereRaw('TRIM(subject) = ?', [trim($staticSubject)]);
                        }
                    }
                });
                
                // Also filter by assigned classes and sections to ensure proper matching
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
                }
            } else {
                // Fallback: If no assigned subjects and no static subjects, return empty
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
                'campus' => ['nullable', 'string'],
                'month' => ['nullable', 'integer', 'min:1', 'max:12'],
                'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
                'date' => ['nullable', 'string'],
            ]);

            // Get teacher's assigned subjects for this class and section
            // Note: Campus filter is NOT applied here to get all assigned subjects
            // Campus filter will be applied in timetable query
            $teacherName = strtolower(trim($teacher->name ?? ''));
            $classNameForQuery = strtolower(trim($validated['class']));
            
            $teacherSubjectsQuery = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [$teacherName])
                ->whereRaw('LOWER(TRIM(class)) = ?', [$classNameForQuery]);
            
            // Filter by section if provided
            if (!empty($validated['section'])) {
                $sectionNameForQuery = strtolower(trim($validated['section']));
                $teacherSubjectsQuery->whereRaw('LOWER(TRIM(section)) = ?', [$sectionNameForQuery]);
            }
            
            $teacherSubjects = $teacherSubjectsQuery->get();
            
            // Get assigned subject names (case-insensitive matching)
            $teacherSubjectNames = $teacherSubjects->pluck('subject_name')->unique()->filter()->map(function($subject) {
                return trim($subject);
            })->filter()->toArray();
            
            // Also handle class name variations (One vs 1, etc.)
            // If no subjects found, try class name variations
            if (empty($teacherSubjectNames)) {
                $classVariations = [];
                $classInput = strtolower(trim($validated['class']));
                
                // Map common class name variations
                $classMap = [
                    'one' => ['1', 'one'],
                    'two' => ['2', 'two'],
                    'three' => ['3', 'three'],
                    'four' => ['4', 'four'],
                    'five' => ['5', 'five'],
                    'six' => ['6', 'six'],
                    'seven' => ['7', 'seven'],
                    'eight' => ['8', 'eight'],
                    'nine' => ['9', 'nine'],
                    'ten' => ['10', 'ten'],
                ];
                
                // Get variations for the input class
                if (isset($classMap[$classInput])) {
                    $classVariations = $classMap[$classInput];
                } elseif (is_numeric($classInput)) {
                    // If numeric, try word form
                    $numberToWord = [
                        '1' => 'one', '2' => 'two', '3' => 'three', '4' => 'four', '5' => 'five',
                        '6' => 'six', '7' => 'seven', '8' => 'eight', '9' => 'nine', '10' => 'ten'
                    ];
                    if (isset($numberToWord[$classInput])) {
                        $classVariations = [$classInput, $numberToWord[$classInput]];
                    }
                }
                
                // Try each variation
                foreach ($classVariations as $variation) {
                    if ($variation === $classInput) continue; // Already tried
                    
                    $teacherSubjectsQuery2 = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [$teacherName])
                        ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($variation))]);
                    if (!empty($validated['section'])) {
                        $teacherSubjectsQuery2->whereRaw('LOWER(TRIM(section)) = ?', [$sectionNameForQuery]);
                    }
                    $teacherSubjects2 = $teacherSubjectsQuery2->get();
                    $foundSubjects = $teacherSubjects2->pluck('subject_name')->unique()->filter()->map(function($subject) {
                        return trim($subject);
                    })->filter()->toArray();
                    
                    if (!empty($foundSubjects)) {
                        $teacherSubjectNames = $foundSubjects;
                        break;
                    }
                }
            }

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
            
            // Filter by campus if provided - case insensitive (MANDATORY if provided)
            // If campus is provided, match exact campus OR null campus (for backward compatibility)
            if (!empty($validated['campus'])) {
                $campusName = strtolower(trim($validated['campus']));
                $query->where(function($q) use ($campusName) {
                    $q->whereRaw('LOWER(TRIM(campus)) = ?', [$campusName])
                      ->orWhereNull('campus'); // Also include entries with null campus for backward compatibility
                });
            }
            
            // Get static subjects list
            $staticSubjects = $this->getStaticSubjects();
            
            // Filter by teacher's assigned subjects AND static subjects
            // Show timetables for assigned subjects OR static subjects (not all subjects for this class/section)
            // Static subjects should be included regardless of teacher assignment (they are class-wide activities)
            if (!empty($teacherSubjectNames) || !empty($staticSubjects)) {
                $query->where(function($q) use ($teacherSubjectNames, $staticSubjects) {
                    // Include assigned subjects
                    if (!empty($teacherSubjectNames)) {
                        foreach ($teacherSubjectNames as $subjectName) {
                            $subjectNameLower = strtolower(trim($subjectName));
                            // Exact match
                            $q->orWhereRaw('LOWER(TRIM(subject)) = ?', [$subjectNameLower]);
                            
                            // Handle common subject name variations
                            $subjectVariations = [
                                'computer science' => ['computer science', 'computer', 'cs', 'comp science'],
                                'english' => ['english', 'eng'],
                                'mathematics' => ['mathematics', 'maths', 'math'],
                                'urdu' => ['urdu'],
                                'science' => ['science', 'sci'],
                                'islamiat' => ['islamiat', 'islamic studies', 'islamic'],
                                'social studies' => ['social studies', 'social', 'sst'],
                            ];
                            
                            if (isset($subjectVariations[$subjectNameLower])) {
                                foreach ($subjectVariations[$subjectNameLower] as $variant) {
                                    $q->orWhereRaw('LOWER(TRIM(subject)) = ?', [$variant]);
                                }
                            }
                        }
                    }
                    
                    // Include static subjects (Assembly, Lunch Break, etc.)
                    // These are class-wide activities and should always be shown
                    if (!empty($staticSubjects)) {
                        foreach ($staticSubjects as $staticSubject) {
                            $q->orWhereRaw('TRIM(subject) = ?', [trim($staticSubject)]);
                        }
                    }
                });
            } else {
                // If teacher has no assigned subjects and no static subjects, return empty
                $query->whereRaw('1 = 0');
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
                'campus' => $validated['campus'] ?? null,
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
                // Get filtered timetables (with all filters applied) for debug
                $filteredTimetables = Timetable::query()
                    ->whereRaw('LOWER(TRIM(class)) = ?', [$className]);
                
                if (!empty($validated['section'])) {
                    $filteredTimetables->whereRaw('LOWER(TRIM(section)) = ?', [$sectionName]);
                }
                
                if (!empty($validated['campus'])) {
                    $filteredTimetables->where(function($q) use ($campusName) {
                        $q->whereRaw('LOWER(TRIM(campus)) = ?', [$campusName])
                          ->orWhereNull('campus');
                    });
                }
                
                if (!empty($teacherSubjectNames)) {
                    $filteredTimetables->where(function($q) use ($teacherSubjectNames) {
                        foreach ($teacherSubjectNames as $subjectName) {
                            $subjectNameLower = strtolower(trim($subjectName));
                            $q->orWhereRaw('LOWER(TRIM(subject)) = ?', [$subjectNameLower]);
                            
                            // Handle variations
                            $subjectVariations = [
                                'mathematics' => ['mathematics', 'maths', 'math'],
                                'computer science' => ['computer science', 'computer', 'cs', 'comp science'],
                                'english' => ['english', 'eng'],
                            ];
                            
                            if (isset($subjectVariations[$subjectNameLower])) {
                                foreach ($subjectVariations[$subjectNameLower] as $variant) {
                                    $q->orWhereRaw('LOWER(TRIM(subject)) = ?', [$variant]);
                                }
                            }
                        }
                    });
                }
                
                $filteredTimetablesResult = $filteredTimetables->get();
                
                $responseData['debug'] = [
                    'query_class' => $className,
                    'query_section' => $validated['section'] ?? null,
                    'query_campus' => $validated['campus'] ?? null,
                    'teacher_name' => $teacher->name ?? null,
                    'assigned_subjects_count' => count($teacherSubjectNames),
                    'assigned_subject_names' => $teacherSubjectNames,
                    'actual_campus_in_db' => $actualCampus,
                    'total_found' => $timetables->count(),
                    'total_for_class_only' => $allClassTimetables->count(),
                    'available_sections' => $allClassTimetables->pluck('section')->unique()->values()->toArray(),
                    'available_campuses' => $allClassTimetables->whereNotNull('campus')->pluck('campus')->unique()->values()->toArray(),
                    'available_subjects_in_timetable' => $filteredTimetablesResult->pluck('subject')->unique()->values()->toArray(), // Only assigned subjects
                    'all_subjects_in_class' => $allClassTimetables->pluck('subject')->unique()->values()->toArray(), // All subjects for reference
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

