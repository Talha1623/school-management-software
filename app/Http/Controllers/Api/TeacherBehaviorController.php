<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BehaviorRecord;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class TeacherBehaviorController extends Controller
{
    /**
     * Get Filter Options (Classes, Sections, Types)
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
                    'message' => 'Access denied. Only teachers can access behavior recording.',
                    'token' => null,
                ], 403);
            }

            // Get classes from teacher's assigned subjects
            $classes = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                ->whereNotNull('class')
                ->distinct()
                ->pluck('class')
                ->sort()
                ->values();
            
            // If no classes assigned, return empty
            if ($classes->isEmpty()) {
                $classes = collect();
            }
            
            // Get Sections (filtered by class if provided) - from teacher's assigned subjects
            $sections = collect();
            if ($request->filled('class')) {
                $class = trim($request->class);
                
                // Get sections from teacher's assigned subjects for this class
                $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($class)])
                    ->get();
                
                $sections = $assignedSubjects->pluck('section')
                    ->unique()
                    ->filter()
                    ->sort()
                    ->values();
            }
            
            // Behavior Types
            $types = ['daily behavior' => 'Daily Behavior'];
            
            return response()->json([
                'success' => true,
                'message' => 'Filter options retrieved successfully',
                'data' => [
                    'classes' => $classes->values(),
                    'sections' => $sections->values(),
                    'types' => $types,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving filter options',
                'data' => [
                    'classes' => [],
                    'sections' => [],
                    'types' => [],
                ],
            ], 200);
        }
    }

    /**
     * Get Sections by Class
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getSections(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();

            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access behavior recording.',
                    'token' => null,
                ], 403);
            }

            $class = $request->get('class');
            
            if (!$class) {
                return response()->json([
                    'success' => true,
                    'message' => 'Sections retrieved successfully',
                    'data' => [
                        'sections' => [],
                    ],
                ], 200);
            }

            // Get sections from teacher's assigned subjects for this class
            $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                ->get();
            
            $sections = $assignedSubjects->pluck('section')
                ->unique()
                ->filter()
                ->sort()
                ->values();

            return response()->json([
                'success' => true,
                'message' => 'Sections retrieved successfully',
                'data' => [
                    'sections' => $sections->values(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving sections',
                'data' => [
                    'sections' => [],
                ],
            ], 200);
        }
    }

    /**
     * Get Students List for Behavior Recording
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getStudents(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();

            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access behavior recording.',
                    'token' => null,
                ], 403);
            }

            $request->validate([
                'class' => ['required', 'string'],
                'section' => ['nullable', 'string'],
                'date' => ['required', 'date'],
                'type' => ['nullable', 'string'],
            ]);

            $filterClass = $request->class;
            $filterSection = $request->section;
            $filterDate = $request->date;
            $filterType = $request->type ?? 'daily behavior';

            // Validate that class is in teacher's assigned classes
            $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                ->get();
            
            $assignedClasses = $assignedSubjects->pluck('class')
                ->unique()
                ->filter()
                ->values();
            
            $assignedSections = $assignedSubjects->where(function($subject) use ($filterClass) {
                return strtolower(trim($subject->class ?? '')) === strtolower(trim($filterClass));
            })->pluck('section')
                ->unique()
                ->filter()
                ->values();
            
            // Check if class is assigned to teacher
            if (!$assignedClasses->contains(function($c) use ($filterClass) {
                return strtolower(trim($c)) === strtolower(trim($filterClass));
            })) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. This class is not assigned to you.',
                    'token' => null,
                ], 403);
            }
            
            // Check if section is assigned to teacher (if section is provided)
            if ($filterSection && !$assignedSections->contains(function($s) use ($filterSection) {
                return strtolower(trim($s)) === strtolower(trim($filterSection));
            })) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. This section is not assigned to you.',
                    'token' => null,
                ], 403);
            }

            // Get students based on filters - only those connected to parents
            $studentsQuery = Student::query();
            
            // Only get students who have parent connection (parent_account_id or father_name)
            $studentsQuery->where(function($query) {
                $query->whereNotNull('parent_account_id')
                      ->orWhere(function($q) {
                          $q->whereNotNull('father_name')
                            ->where('father_name', '!=', '');
                      });
            });
            
            $studentsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
            
            if ($filterSection) {
                $studentsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))]);
            }
            
            $students = $studentsQuery->orderBy('student_code', 'asc')
                ->orderBy('student_name', 'asc')
                ->get();
            
            // Get campus from first student or use default
            $campusName = 'Main Campus';
            if ($students->count() > 0) {
                $campusName = $students->first()->campus ?? 'Main Campus';
            }

            // Get existing behavior records for the date (case-insensitive type matching)
            $studentIds = $students->pluck('id')->toArray();
            
            $behaviorRecords = BehaviorRecord::whereRaw('LOWER(TRIM(type)) = ?', [strtolower(trim($filterType))])
                ->whereDate('date', $filterDate)
                ->whereIn('student_id', $studentIds)
                ->get()
                ->keyBy('student_id');

            // Format students data with behavior points
            $studentsData = $students->map(function($student) use ($behaviorRecords, $filterType, $filterClass, $filterSection, $campusName, $filterDate) {
                $behaviorRecord = $behaviorRecords->get($student->id);
                
                return [
                    'id' => $student->id,
                    'student_code' => $student->student_code,
                    'student_name' => $student->student_name,
                    'parent_name' => $student->father_name ?? 'N/A',
                    'class' => $student->class,
                    'section' => $student->section,
                    'campus' => $student->campus ?? $campusName,
                    'current_points' => $behaviorRecord ? $behaviorRecord->points : null,
                    'behavior_record_id' => $behaviorRecord ? $behaviorRecord->id : null,
                ];
            });
            
            return response()->json([
                'success' => true,
                'message' => 'Students list retrieved successfully',
                'data' => [
                    'students' => $studentsData,
                    'campus' => $campusName,
                    'class' => $filterClass,
                    'section' => $filterSection,
                    'date' => $filterDate,
                    'type' => $filterType,
                ],
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving students list',
                'data' => [
                    'students' => [],
                ],
            ], 200);
        }
    }

    /**
     * Save Behavior Record (Single or Bulk)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function saveRecord(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();

            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can save behavior records.',
                    'token' => null,
                ], 403);
            }

            // Get teacher's assigned subjects
            $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                ->get();
            
            $assignedClasses = $assignedSubjects->pluck('class')
                ->unique()
                ->filter()
                ->values();

            // Check if it's a single record or array of records
            $records = $request->has('student_id') 
                ? [$request->all()] 
                : $request->input('records', []);

            if (empty($records)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No records provided',
                ], 422);
            }

            $savedCount = 0;
            $updatedCount = 0;
            $errors = [];
            $savedStudentIds = [];
            $firstRecord = null;

            foreach ($records as $index => $recordData) {
                try {
                    $validated = validator($recordData, [
                        'student_id' => ['required', 'exists:students,id'],
                        'type' => ['required', 'string'],
                        'points' => ['required', 'integer'],
                        'class' => ['required', 'string'],
                        'section' => ['nullable', 'string'],
                        'campus' => ['required', 'string'],
                        'date' => ['required', 'date'],
                    ])->validate();

                    // Validate that class is in teacher's assigned classes
                    $class = trim($validated['class']);
                    if (!$assignedClasses->contains(function($c) use ($class) {
                        return strtolower(trim($c)) === strtolower(trim($class));
                    })) {
                        $errors[] = [
                            'index' => $index,
                            'student_id' => $validated['student_id'],
                            'message' => 'Access denied. This class is not assigned to you.',
                        ];
                        continue;
                    }

                    // Store first record for getting updated students list
                    if ($firstRecord === null) {
                        $firstRecord = $validated;
                    }

                    // Get student details
                    $student = Student::findOrFail($validated['student_id']);

                    // Normalize section - convert empty string to null
                    $section = !empty($validated['section']) ? $validated['section'] : null;

                    // Check if record already exists for this student, type, and date (case-insensitive)
                    $existingRecord = BehaviorRecord::where('student_id', $validated['student_id'])
                        ->whereRaw('LOWER(TRIM(type)) = ?', [strtolower(trim($validated['type']))])
                        ->whereDate('date', $validated['date'])
                        ->first();

                    if ($existingRecord) {
                        // Update existing record
                        $existingRecord->update([
                            'points' => $validated['points'],
                            'section' => $section,
                            'description' => $validated['points'] > 0 ? '+' . $validated['points'] . ' Points' : $validated['points'] . ' Points',
                        ]);
                        $updatedCount++;
                    } else {
                        // Create new record
                        BehaviorRecord::create([
                            'student_id' => $validated['student_id'],
                            'student_name' => $student->student_name,
                            'type' => $validated['type'],
                            'points' => $validated['points'],
                            'class' => $validated['class'],
                            'section' => $section,
                            'campus' => $validated['campus'],
                            'date' => $validated['date'],
                            'description' => $validated['points'] > 0 ? '+' . $validated['points'] . ' Points' : $validated['points'] . ' Points',
                            'recorded_by' => $request->user()->name ?? $request->user()->email ?? 'Teacher API',
                        ]);
                        $savedCount++;
                    }

                    $savedStudentIds[] = $validated['student_id'];
                } catch (\Illuminate\Validation\ValidationException $e) {
                    $errors[] = [
                        'index' => $index,
                        'student_id' => $recordData['student_id'] ?? null,
                        'errors' => $e->errors(),
                    ];
                } catch (\Exception $e) {
                    $errors[] = [
                        'index' => $index,
                        'student_id' => $recordData['student_id'] ?? null,
                        'message' => $e->getMessage(),
                    ];
                }
            }

            // Get updated students data if we have saved records
            $updatedStudents = [];
            if (!empty($savedStudentIds) && $firstRecord) {
                $students = Student::whereIn('id', $savedStudentIds)->get();
                $behaviorRecords = BehaviorRecord::whereRaw('LOWER(TRIM(type)) = ?', [strtolower(trim($firstRecord['type']))])
                    ->whereDate('date', $firstRecord['date'])
                    ->whereIn('student_id', $savedStudentIds)
                    ->get()
                    ->keyBy('student_id');

                $updatedStudents = $students->map(function($student) use ($behaviorRecords) {
                    $behaviorRecord = $behaviorRecords->get($student->id);
                    return [
                        'id' => $student->id,
                        'student_code' => $student->student_code,
                        'student_name' => $student->student_name,
                        'current_points' => $behaviorRecord ? $behaviorRecord->points : null,
                        'behavior_record_id' => $behaviorRecord ? $behaviorRecord->id : null,
                    ];
                })->values();
            }

            $message = '';
            if ($savedCount > 0 && $updatedCount > 0) {
                $message = "{$savedCount} record(s) saved and {$updatedCount} record(s) updated successfully";
            } elseif ($savedCount > 0) {
                $message = "{$savedCount} record(s) saved successfully";
            } elseif ($updatedCount > 0) {
                $message = "{$updatedCount} record(s) updated successfully";
            }

            return response()->json([
                'success' => true,
                'message' => $message ?: 'Behavior records processed',
                'data' => [
                    'saved' => $savedCount,
                    'updated' => $updatedCount,
                    'errors' => $errors,
                    'updated_students' => $updatedStudents,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while saving behavior records: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Behavior Records (History)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getRecords(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();

            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access behavior records.',
                    'token' => null,
                ], 403);
            }

            // Get teacher's assigned subjects
            $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                ->get();
            
            // Get assigned classes and sections
            $assignedClasses = $assignedSubjects->pluck('class')
                ->unique()
                ->filter()
                ->values();
            
            $assignedSections = $assignedSubjects->pluck('section')
                ->unique()
                ->filter()
                ->values();

            $query = BehaviorRecord::query();

            // Filter by teacher's assigned classes ONLY
            if ($assignedClasses->isNotEmpty()) {
                $query->where(function($q) use ($assignedClasses) {
                    foreach ($assignedClasses as $class) {
                        $q->orWhereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
                    }
                });
            } else {
                // If no classes assigned, return empty result
                $query->whereRaw('1 = 0');
            }

            // Filter by student_id
            if ($request->filled('student_id')) {
                $query->where('student_id', $request->student_id);
            }

            // Filter by class - validate it's in assigned classes
            if ($request->filled('class')) {
                $class = trim($request->class);
                if ($assignedClasses->contains(function($c) use ($class) {
                    return strtolower(trim($c)) === strtolower(trim($class));
                })) {
                    $query->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($class)]);
                }
            }

            // Filter by section - validate it's in assigned sections
            if ($request->filled('section')) {
                $section = trim($request->section);
                if ($assignedSections->contains(function($s) use ($section) {
                    return strtolower(trim($s)) === strtolower(trim($section));
                })) {
                    $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($section)]);
                }
            }

            // Filter by date
            if ($request->filled('date')) {
                $query->whereDate('date', $request->date);
            }

            // Filter by date range
            if ($request->filled('date_from')) {
                $query->whereDate('date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('date', '<=', $request->date_to);
            }

            // Filter by type
            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }

            // Pagination
            $perPage = $request->get('per_page', 10);
            $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;

            $records = $query->orderBy('date', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            // Format records data
            $recordsData = $records->map(function($record) {
                return [
                    'id' => $record->id,
                    'student_id' => $record->student_id,
                    'student_name' => $record->student_name,
                    'type' => $record->type,
                    'points' => $record->points,
                    'class' => $record->class,
                    'section' => $record->section,
                    'campus' => $record->campus,
                    'date' => $record->date->format('Y-m-d'),
                    'description' => $record->description,
                    'recorded_by' => $record->recorded_by,
                    'created_at' => $record->created_at->format('Y-m-d H:i:s'),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Behavior records retrieved successfully',
                'data' => [
                    'records' => $recordsData,
                    'pagination' => [
                        'current_page' => $records->currentPage(),
                        'last_page' => $records->lastPage(),
                        'per_page' => $records->perPage(),
                        'total' => $records->total(),
                        'from' => $records->firstItem(),
                        'to' => $records->lastItem(),
                    ],
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving behavior records',
                'data' => [
                    'records' => [],
                ],
            ], 200);
        }
    }

    /**
     * Mark Teacher Self Attendance
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function markSelfAttendance(Request $request): JsonResponse
    {
        try {
            // Get logged-in teacher
            $teacher = $request->user();
            
            // Validate that user is a teacher
            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can mark self-attendance.',
                ], 403);
            }

            // Support both GET (query params) and POST (body) requests
            $validated = $request->validate([
                'attendance_date' => ['required', 'date'],
                'status' => ['required', 'string'],
                'class' => ['nullable', 'string'],
                'section' => ['nullable', 'string'],
                'start_time' => ['nullable', 'string', 'regex:/^([0-1][0-9]|2[0-3]):[0-5][0-9](:([0-5][0-9]))?$/'],
                'end_time' => ['nullable', 'string', 'regex:/^([0-1][0-9]|2[0-3]):[0-5][0-9](:([0-5][0-9]))?$/'],
                'remarks' => ['nullable', 'string', 'max:500'],
            ]);
            
            // Custom validation: end_time should be after start_time if both are provided
            if (!empty($validated['start_time']) && !empty($validated['end_time'])) {
                $startTime = strtotime($validated['start_time']);
                $endTime = strtotime($validated['end_time']);
                if ($endTime <= $startTime) {
                    return response()->json([
                        'success' => false,
                        'message' => 'End time must be after start time',
                    ], 422);
                }
            }

            // Normalize status (case-insensitive)
            $status = trim($validated['status']);
            $statusLower = strtolower($status);
            
            // Map common variations to standard status
            $statusMap = [
                'present' => 'Present',
                'absent' => 'Absent',
                'holiday' => 'Holiday',
                'sunday' => 'Sunday',
                'leave' => 'Leave',
                'n/a' => 'N/A',
                'na' => 'N/A',
            ];
            
            if (!isset($statusMap[$statusLower])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid status. Allowed values: Present, Absent, Holiday, Sunday, Leave, N/A',
                ], 422);
            }
            
            $validated['status'] = $statusMap[$statusLower];

            // Normalize time format (accept H:i or H:i:s)
            $startTime = null;
            $endTime = null;
            
            // Handle start_time - trim and check if not empty
            if (!empty($validated['start_time']) && trim($validated['start_time']) !== '') {
                $startTime = trim($validated['start_time']);
                // If format is H:i, convert to H:i:s
                if (preg_match('/^\d{2}:\d{2}$/', $startTime)) {
                    $startTime .= ':00';
                }
            }
            
            // Handle end_time - trim and check if not empty
            if (!empty($validated['end_time']) && trim($validated['end_time']) !== '') {
                $endTime = trim($validated['end_time']);
                // If format is H:i, convert to H:i:s
                if (preg_match('/^\d{2}:\d{2}$/', $endTime)) {
                    $endTime .= ':00';
                }
            }

            // Check if there's existing attendance for this date
            $existingAttendance = StaffAttendance::where('staff_id', $teacher->id)
                ->whereDate('attendance_date', $validated['attendance_date'])
                ->first();

            // Check if start_time was explicitly provided in request (even if empty string)
            $startTimeKeyExists = $request->has('start_time');

            // Validation: Check if trying to checkout without checkin
            // If end_time is provided but start_time is empty or not provided
            if (!empty($endTime) && empty($startTime)) {
                // If start_time key exists in request but is empty/null, return error (don't use existing)
                if ($startTimeKeyExists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Check-in is required first. Please check-in before checking out.',
                    ], 422);
                }
                
                // If start_time was not provided at all, check existing attendance
                if (!$startTimeKeyExists) {
                    // If no existing attendance or existing attendance doesn't have start_time
                    if (!$existingAttendance || empty($existingAttendance->start_time)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Check-in is required first. Please check-in before checking out.',
                        ], 422);
                    }
                    
                    // Use existing start_time if available
                    $startTime = $existingAttendance->start_time;
                }
            }

            // Prepare data for update/create
            $attendanceData = [
                'status' => $validated['status'],
                'campus' => $teacher->campus,
                'designation' => $teacher->designation,
                'class' => !empty($validated['class']) ? trim($validated['class']) : ($existingAttendance ? $existingAttendance->class : null),
                'section' => !empty($validated['section']) ? trim($validated['section']) : ($existingAttendance ? $existingAttendance->section : null),
                'remarks' => $validated['remarks'] ?? ($existingAttendance ? $existingAttendance->remarks : null),
            ];

            // Handle start_time: use provided value, or keep existing, or set null
            if (!empty($startTime)) {
                $attendanceData['start_time'] = $startTime;
            } else if ($existingAttendance && !empty($existingAttendance->start_time)) {
                // Preserve existing start_time if not being updated
                $attendanceData['start_time'] = $existingAttendance->start_time;
            } else {
                $attendanceData['start_time'] = null;
            }

            // Handle end_time: use provided value, or keep existing, or set null
            if (!empty($endTime)) {
                $attendanceData['end_time'] = $endTime;
            } else if ($existingAttendance && !empty($existingAttendance->end_time)) {
                // Preserve existing end_time if not being updated
                $attendanceData['end_time'] = $existingAttendance->end_time;
            } else {
                $attendanceData['end_time'] = null;
            }

            // Create or update attendance
            $attendance = StaffAttendance::updateOrCreate(
                [
                    'staff_id' => $teacher->id,
                    'attendance_date' => $validated['attendance_date'],
                ],
                $attendanceData
            );

            return response()->json([
                'success' => true,
                'message' => 'Self-attendance marked successfully',
                'data' => [
                    'attendance' => [
                        'id' => $attendance->id,
                        'staff_id' => $attendance->staff_id,
                        'name' => $teacher->name,
                        'emp_id' => $teacher->emp_id,
                        'designation' => $teacher->designation,
                        'attendance_date' => $attendance->attendance_date->format('Y-m-d'),
                        'status' => $attendance->status,
                        'class' => $attendance->class,
                        'section' => $attendance->section,
                        'end_time' => $attendance->end_time ? date('H:i:s', strtotime($attendance->end_time)) : null,
                        'campus' => $attendance->campus,
                        'remarks' => $attendance->remarks,
                    ],
                ],
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while marking self-attendance: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check Today's Self Attendance Status
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function checkSelfAttendance(Request $request): JsonResponse
    {
        try {
            // Get logged-in teacher
            $teacher = $request->user();
            
            // Validate that user is a teacher
            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can check self-attendance.',
                    'token' => null,
                ], 403);
            }

            // Support both GET (query params) and POST (body) requests
            // Get date from request or use today
            $date = $request->get('date', $request->input('date', now()->format('Y-m-d')));

            // Check if attendance exists for this date
            $attendance = StaffAttendance::where('staff_id', $teacher->id)
                ->whereDate('attendance_date', $date)
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Attendance status retrieved successfully',
                'data' => [
                    'teacher' => [
                        'id' => $teacher->id,
                        'name' => $teacher->name,
                        'emp_id' => $teacher->emp_id,
                        'designation' => $teacher->designation,
                        'campus' => $teacher->campus,
                    ],
                    'date' => $date,
                    'is_marked' => $attendance !== null,
                    'attendance' => $attendance ? [
                        'id' => $attendance->id,
                        'status' => $attendance->status,
                        'class' => $attendance->class,
                        'section' => $attendance->section,
                        'start_time' => $attendance->start_time ? date('H:i:s', strtotime($attendance->start_time)) : null,
                        'end_time' => $attendance->end_time ? date('H:i:s', strtotime($attendance->end_time)) : null,
                        'remarks' => $attendance->remarks,
                        'marked_at' => $attendance->created_at->format('Y-m-d H:i:s'),
                    ] : null,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while checking attendance: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }

    /**
     * Check-In API
     * Teacher check-in karta hai (start_time set karta hai)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function checkIn(Request $request): JsonResponse
    {
        try {
            // Get logged-in teacher
            $teacher = $request->user();
            
            // Validate that user is a teacher
            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can check-in.',
                ], 403);
            }

            // Support both GET (query params) and POST (body) requests
            $validated = $request->validate([
                'attendance_date' => ['required', 'date'],
                'start_time' => ['required', 'string', 'regex:/^([0-1][0-9]|2[0-3]):[0-5][0-9](:([0-5][0-9]))?$/'],
                'status' => ['nullable', 'string'],
                'class' => ['nullable', 'string'],
                'section' => ['nullable', 'string'],
                'remarks' => ['nullable', 'string', 'max:500'],
            ]);

            // Normalize start_time format (accept H:i or H:i:s)
            $startTime = trim($validated['start_time']);
            if (preg_match('/^\d{2}:\d{2}$/', $startTime)) {
                $startTime .= ':00';
            }

            // Normalize status if provided
            $status = 'Present'; // Default status
            if (!empty($validated['status'])) {
                $statusInput = trim($validated['status']);
                $statusLower = strtolower($statusInput);
                
                $statusMap = [
                    'present' => 'Present',
                    'absent' => 'Absent',
                    'holiday' => 'Holiday',
                    'sunday' => 'Sunday',
                    'leave' => 'Leave',
                    'n/a' => 'N/A',
                    'na' => 'N/A',
                ];
                
                if (isset($statusMap[$statusLower])) {
                    $status = $statusMap[$statusLower];
                }
            }

            // Check if there's existing attendance for this date
            $existingAttendance = StaffAttendance::where('staff_id', $teacher->id)
                ->whereDate('attendance_date', $validated['attendance_date'])
                ->first();

            // If already checked in today, return error
            if ($existingAttendance && !empty($existingAttendance->start_time)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already checked in today. Check-in time: ' . date('H:i:s', strtotime($existingAttendance->start_time)),
                ], 422);
            }

            // Prepare data for check-in
            $attendanceData = [
                'status' => $status,
                'campus' => $teacher->campus,
                'designation' => $teacher->designation,
                'start_time' => $startTime,
                'class' => !empty($validated['class']) ? trim($validated['class']) : ($existingAttendance ? $existingAttendance->class : null),
                'section' => !empty($validated['section']) ? trim($validated['section']) : ($existingAttendance ? $existingAttendance->section : null),
                'remarks' => $validated['remarks'] ?? ($existingAttendance ? $existingAttendance->remarks : null),
            ];

            // Preserve existing end_time if attendance already exists
            if ($existingAttendance && !empty($existingAttendance->end_time)) {
                $attendanceData['end_time'] = $existingAttendance->end_time;
            }

            // Create or update attendance (only start_time)
            $attendance = StaffAttendance::updateOrCreate(
                [
                    'staff_id' => $teacher->id,
                    'attendance_date' => $validated['attendance_date'],
                ],
                $attendanceData
            );

            return response()->json([
                'success' => true,
                'message' => 'Check-in successful',
                'data' => [
                    'attendance' => [
                        'id' => $attendance->id,
                        'staff_id' => $attendance->staff_id,
                        'name' => $teacher->name,
                        'emp_id' => $teacher->emp_id,
                        'designation' => $teacher->designation,
                        'attendance_date' => $attendance->attendance_date->format('Y-m-d'),
                        'status' => $attendance->status,
                        'class' => $attendance->class,
                        'section' => $attendance->section,
                        'start_time' => date('H:i:s', strtotime($attendance->start_time)),
                        'end_time' => $attendance->end_time ? date('H:i:s', strtotime($attendance->end_time)) : null,
                        'campus' => $attendance->campus,
                        'remarks' => $attendance->remarks,
                    ],
                ],
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while checking in: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check-Out API
     * Teacher check-out karta hai (end_time set karta hai)
     * Check-in pehle hona chahiye
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function checkOut(Request $request): JsonResponse
    {
        try {
            // Get logged-in teacher
            $teacher = $request->user();
            
            // Validate that user is a teacher
            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can check-out.',
                ], 403);
            }

            // Support both GET (query params) and POST (body) requests
            $validated = $request->validate([
                'attendance_date' => ['required', 'date'],
                'end_time' => ['required', 'string', 'regex:/^([0-1][0-9]|2[0-3]):[0-5][0-9](:([0-5][0-9]))?$/'],
                'remarks' => ['nullable', 'string', 'max:500'],
            ]);

            // Normalize end_time format (accept H:i or H:i:s)
            $endTime = trim($validated['end_time']);
            if (preg_match('/^\d{2}:\d{2}$/', $endTime)) {
                $endTime .= ':00';
            }

            // Check if there's existing attendance for this date
            $existingAttendance = StaffAttendance::where('staff_id', $teacher->id)
                ->whereDate('attendance_date', $validated['attendance_date'])
                ->first();

            // Validation: Check-in pehle hona chahiye
            if (!$existingAttendance || empty($existingAttendance->start_time)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Check-in is required first. Please check-in before checking out.',
                ], 422);
            }

            // Validation: End time should be after start time
            $startTime = strtotime($existingAttendance->start_time);
            $endTimeStamp = strtotime($endTime);
            if ($endTimeStamp <= $startTime) {
                return response()->json([
                    'success' => false,
                    'message' => 'Check-out time must be after check-in time. Check-in time: ' . date('H:i:s', $startTime),
                ], 422);
            }

            // If already checked out today, return error
            if (!empty($existingAttendance->end_time)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already checked out today. Check-out time: ' . date('H:i:s', strtotime($existingAttendance->end_time)),
                ], 422);
            }

            // Update attendance with end_time
            $existingAttendance->update([
                'end_time' => $endTime,
                'remarks' => $validated['remarks'] ?? $existingAttendance->remarks,
            ]);

            // Calculate total hours and minutes
            $startTimeStamp = strtotime($existingAttendance->start_time);
            $endTimeStamp = strtotime($existingAttendance->end_time);
            $totalSeconds = $endTimeStamp - $startTimeStamp;
            $totalHours = floor($totalSeconds / 3600);
            $totalMinutes = floor(($totalSeconds % 3600) / 60);
            $totalSecondsRemaining = $totalSeconds % 60;

            return response()->json([
                'success' => true,
                'message' => 'Check-out successful',
                'data' => [
                    'attendance' => [
                        'id' => $existingAttendance->id,
                        'staff_id' => $existingAttendance->staff_id,
                        'name' => $teacher->name,
                        'emp_id' => $teacher->emp_id,
                        'designation' => $teacher->designation,
                        'attendance_date' => $existingAttendance->attendance_date->format('Y-m-d'),
                        'status' => $existingAttendance->status,
                        'class' => $existingAttendance->class,
                        'section' => $existingAttendance->section,
                        'end_time' => date('H:i:s', strtotime($existingAttendance->end_time)),
                        'campus' => $existingAttendance->campus,
                        'remarks' => $existingAttendance->remarks,
                        'total_hours' => $totalHours,
                        'total_minutes' => $totalMinutes,
                        'total_time' => sprintf('%d hours %d minutes', $totalHours, $totalMinutes),
                    ],
                ],
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while checking out: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Teacher Self Attendance History
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getSelfAttendanceHistory(Request $request): JsonResponse
    {
        try {
            // Get logged-in teacher
            $teacher = $request->user();
            
            // Validate that user is a teacher
            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can view self-attendance history.',
                ], 403);
            }

            $query = StaffAttendance::where('staff_id', $teacher->id);

            // Filter by date range
            if ($request->filled('start_date')) {
                $query->whereDate('attendance_date', '>=', $request->start_date);
            }

            if ($request->filled('end_date')) {
                $query->whereDate('attendance_date', '<=', $request->end_date);
            }

            // Filter by status
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Pagination
            $perPage = $request->get('per_page', 30);
            $perPage = in_array($perPage, [10, 25, 30, 50, 100]) ? $perPage : 30;

            $attendances = $query->orderBy('attendance_date', 'desc')->paginate($perPage);

            // Calculate statistics
            $totalDays = $attendances->total();
            $presentDays = StaffAttendance::where('staff_id', $teacher->id)
                ->where('status', 'Present')
                ->when($request->filled('start_date'), function($q) use ($request) {
                    $q->whereDate('attendance_date', '>=', $request->start_date);
                })
                ->when($request->filled('end_date'), function($q) use ($request) {
                    $q->whereDate('attendance_date', '<=', $request->end_date);
                })
                ->count();

            $absentDays = StaffAttendance::where('staff_id', $teacher->id)
                ->where('status', 'Absent')
                ->when($request->filled('start_date'), function($q) use ($request) {
                    $q->whereDate('attendance_date', '>=', $request->start_date);
                })
                ->when($request->filled('end_date'), function($q) use ($request) {
                    $q->whereDate('attendance_date', '<=', $request->end_date);
                })
                ->count();

            // Format attendance data
            $attendancesData = $attendances->map(function($attendance) {
                return [
                    'id' => $attendance->id,
                    'attendance_date' => $attendance->attendance_date->format('Y-m-d'),
                    'status' => $attendance->status,
                    'class' => $attendance->class,
                    'section' => $attendance->section,
                    'start_time' => $attendance->start_time ? date('H:i:s', strtotime($attendance->start_time)) : null,
                    'end_time' => $attendance->end_time ? date('H:i:s', strtotime($attendance->end_time)) : null,
                    'campus' => $attendance->campus,
                    'remarks' => $attendance->remarks,
                    'marked_at' => $attendance->created_at->format('Y-m-d H:i:s'),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Self-attendance history retrieved successfully',
                'data' => [
                    'teacher' => [
                        'id' => $teacher->id,
                        'name' => $teacher->name,
                        'emp_id' => $teacher->emp_id,
                        'designation' => $teacher->designation,
                        'campus' => $teacher->campus,
                    ],
                    'statistics' => [
                        'total_days' => $totalDays,
                        'present_days' => $presentDays,
                        'absent_days' => $absentDays,
                        'attendance_percentage' => $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 2) : 0,
                    ],
                    'attendances' => $attendancesData,
                    'pagination' => [
                        'current_page' => $attendances->currentPage(),
                        'last_page' => $attendances->lastPage(),
                        'per_page' => $attendances->perPage(),
                        'total' => $attendances->total(),
                        'from' => $attendances->firstItem(),
                        'to' => $attendances->lastItem(),
                    ],
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving attendance history: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Teacher Attendance Report
     * Comprehensive attendance report with statistics, monthly summaries, and detailed records
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getAttendanceReport(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();
            
            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access attendance report.',
                    'token' => null,
                ], 403);
            }

            // Get date range filters
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            $month = $request->get('month'); // 1-12
            $year = $request->get('year'); // e.g., 2025
            
            // Build query
            $query = StaffAttendance::where('staff_id', $teacher->id);
            
            // If month and year provided, filter by that month
            if ($month && $year) {
                $month = (int)$month;
                $year = (int)$year;
                if ($month >= 1 && $month <= 12 && $year >= 2000 && $year <= 2100) {
                    $query->whereYear('attendance_date', $year)
                          ->whereMonth('attendance_date', $month);
                }
            }
            
            // If start_date and end_date provided, use date range
            if ($startDate) {
                $query->whereDate('attendance_date', '>=', $startDate);
            }
            if ($endDate) {
                $query->whereDate('attendance_date', '<=', $endDate);
            }
            
            // Filter by status if provided
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            
            // Get all attendances for statistics
            $allAttendances = $query->orderBy('attendance_date', 'desc')->get();
            
            // Calculate statistics
            $totalDays = $allAttendances->count();
            $presentDays = $allAttendances->where('status', 'Present')->count();
            $absentDays = $allAttendances->where('status', 'Absent')->count();
            $leaveDays = $allAttendances->where('status', 'Leave')->count();
            $holidayDays = $allAttendances->where('status', 'Holiday')->count();
            $sundayDays = $allAttendances->where('status', 'Sunday')->count();
            
            // Calculate attendance percentage (excluding holidays and sundays)
            $workingDays = $totalDays - $holidayDays - $sundayDays;
            $attendancePercentage = $workingDays > 0 ? round(($presentDays / $workingDays) * 100, 2) : 0;
            
            // Get late arrivals count (if start_time is after a certain time, e.g., 9:00 AM)
            $lateArrivals = $allAttendances->filter(function($attendance) {
                if ($attendance->status === 'Present' && $attendance->start_time) {
                    $startTime = strtotime($attendance->start_time);
                    $expectedTime = strtotime('09:00:00'); // 9 AM
                    return $startTime > $expectedTime;
                }
                return false;
            })->count();
            
            // Monthly summary (if month/year not specified, show last 12 months)
            $monthlySummary = [];
            if ($month && $year) {
                // Single month summary
                $monthAttendances = $allAttendances;
                $monthlySummary[] = [
                    'month' => $month,
                    'year' => $year,
                    'month_name' => date('F Y', mktime(0, 0, 0, $month, 1, $year)),
                    'total_days' => $monthAttendances->count(),
                    'present' => $monthAttendances->where('status', 'Present')->count(),
                    'absent' => $monthAttendances->where('status', 'Absent')->count(),
                    'leave' => $monthAttendances->where('status', 'Leave')->count(),
                    'holiday' => $monthAttendances->where('status', 'Holiday')->count(),
                    'sunday' => $monthAttendances->where('status', 'Sunday')->count(),
                ];
            } else {
                // Last 12 months summary
                $currentDate = Carbon::now();
                for ($i = 11; $i >= 0; $i--) {
                    $date = $currentDate->copy()->subMonths($i);
                    $monthNum = $date->month;
                    $yearNum = $date->year;
                    
                    $monthAttendances = StaffAttendance::where('staff_id', $teacher->id)
                        ->whereYear('attendance_date', $yearNum)
                        ->whereMonth('attendance_date', $monthNum)
                        ->get();
                    
                    $monthlySummary[] = [
                        'month' => $monthNum,
                        'year' => $yearNum,
                        'month_name' => $date->format('F Y'),
                        'total_days' => $monthAttendances->count(),
                        'present' => $monthAttendances->where('status', 'Present')->count(),
                        'absent' => $monthAttendances->where('status', 'Absent')->count(),
                        'leave' => $monthAttendances->where('status', 'Leave')->count(),
                        'holiday' => $monthAttendances->where('status', 'Holiday')->count(),
                        'sunday' => $monthAttendances->where('status', 'Sunday')->count(),
                    ];
                }
            }
            
            // Pagination for detailed records
            $perPage = $request->get('per_page', 30);
            $perPage = in_array($perPage, [10, 25, 30, 50, 100]) ? $perPage : 30;
            
            $paginatedAttendances = $query->orderBy('attendance_date', 'desc')->paginate($perPage);
            
            // Format attendance data
            $attendancesData = $paginatedAttendances->map(function($attendance) {
                return [
                    'id' => $attendance->id,
                    'attendance_date' => $attendance->attendance_date ? $attendance->attendance_date->format('Y-m-d') : null,
                    'date_formatted' => $attendance->attendance_date ? $attendance->attendance_date->format('d M Y') : null,
                    'day_name' => $attendance->attendance_date ? $attendance->attendance_date->format('l') : null,
                    'status' => $attendance->status,
                    'start_time' => $attendance->start_time,
                    'start_time_formatted' => $attendance->start_time ? date('h:i A', strtotime($attendance->start_time)) : null,
                    'end_time' => $attendance->end_time,
                    'end_time_formatted' => $attendance->end_time ? date('h:i A', strtotime($attendance->end_time)) : null,
                    'total_hours' => $this->calculateTotalHours($attendance->start_time, $attendance->end_time),
                    'campus' => $attendance->campus,
                    'remarks' => $attendance->remarks,
                    'created_at' => $attendance->created_at ? $attendance->created_at->format('Y-m-d H:i:s') : null,
                ];
            });
            
            return response()->json([
                'success' => true,
                'message' => 'Attendance report retrieved successfully',
                'data' => [
                    'teacher' => [
                        'id' => $teacher->id,
                        'name' => $teacher->name,
                        'emp_id' => $teacher->emp_id,
                        'designation' => $teacher->designation,
                        'campus' => $teacher->campus,
                    ],
                    'statistics' => [
                        'total_days' => $totalDays,
                        'present_days' => $presentDays,
                        'absent_days' => $absentDays,
                        'leave_days' => $leaveDays,
                        'holiday_days' => $holidayDays,
                        'sunday_days' => $sundayDays,
                        'working_days' => $workingDays,
                        'attendance_percentage' => $attendancePercentage,
                        'late_arrivals' => $lateArrivals,
                    ],
                    'monthly_summary' => $monthlySummary,
                    'attendances' => $attendancesData,
                    'pagination' => [
                        'current_page' => $paginatedAttendances->currentPage(),
                        'last_page' => $paginatedAttendances->lastPage(),
                        'per_page' => $paginatedAttendances->perPage(),
                        'total' => $paginatedAttendances->total(),
                        'from' => $paginatedAttendances->firstItem(),
                        'to' => $paginatedAttendances->lastItem(),
                    ],
                    'filters' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'month' => $month,
                        'year' => $year,
                        'status' => $request->get('status'),
                    ],
                ],
                'token' => $request->bearerToken(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving attendance report: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }
    
    /**
     * Calculate total hours between start and end time
     */
    private function calculateTotalHours($startTime, $endTime)
    {
        if (!$startTime || !$endTime) {
            return null;
        }
        
        try {
            $start = strtotime($startTime);
            $end = strtotime($endTime);
            
            if ($start === false || $end === false) {
                return null;
            }
            
            $diff = $end - $start;
            $hours = round($diff / 3600, 2);
            
            return [
                'hours' => $hours,
                'formatted' => $this->formatHours($hours),
            ];
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Format hours to readable string
     */
    private function formatHours($hours)
    {
        if ($hours < 1) {
            $minutes = round($hours * 60);
            return "{$minutes} minutes";
        }
        
        $wholeHours = floor($hours);
        $minutes = round(($hours - $wholeHours) * 60);
        
        if ($minutes > 0) {
            return "{$wholeHours} hours {$minutes} minutes";
        }
        
        return "{$wholeHours} hours";
    }
}

