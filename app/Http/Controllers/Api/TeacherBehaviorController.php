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
            // Get Classes
            $classes = ClassModel::whereNotNull('class_name')
                ->distinct()
                ->pluck('class_name')
                ->sort()
                ->values();
            
            if ($classes->isEmpty()) {
                $classesFromSubjects = Subject::whereNotNull('class')
                    ->distinct()
                    ->pluck('class')
                    ->sort();
                $classes = $classesFromSubjects->isEmpty() 
                    ? collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th'])
                    : $classesFromSubjects;
            }
            
            // Get Sections (filtered by class if provided)
            $sections = collect();
            if ($request->filled('class')) {
                $sections = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($request->class))])
                    ->whereNotNull('name')
                    ->distinct()
                    ->pluck('name')
                    ->sort()
                    ->values();
                
                if ($sections->isEmpty()) {
                    $sections = Subject::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($request->class))])
                        ->whereNotNull('section')
                        ->distinct()
                        ->pluck('section')
                        ->sort()
                        ->values();
                }
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

            $sections = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                ->whereNotNull('name')
                ->distinct()
                ->pluck('name')
                ->sort()
                ->values();
            
            if ($sections->isEmpty()) {
                $sections = Subject::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                    ->whereNotNull('section')
                    ->distinct()
                    ->pluck('section')
                    ->sort()
                    ->values();
            }

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
            $query = BehaviorRecord::query();

            // Filter by student_id
            if ($request->filled('student_id')) {
                $query->where('student_id', $request->student_id);
            }

            // Filter by class
            if ($request->filled('class')) {
                $query->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($request->class))]);
            }

            // Filter by section
            if ($request->filled('section')) {
                $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($request->section))]);
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
            if (!$teacher || strtolower(trim($teacher->designation ?? '')) !== 'teacher') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can mark self-attendance.',
                ], 403);
            }

            $validated = $request->validate([
                'attendance_date' => ['required', 'date'],
                'status' => ['required', 'string'],
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
            
            if (!empty($validated['start_time'])) {
                $startTime = $validated['start_time'];
                // If format is H:i, convert to H:i:s
                if (preg_match('/^\d{2}:\d{2}$/', $startTime)) {
                    $startTime .= ':00';
                }
            }
            
            if (!empty($validated['end_time'])) {
                $endTime = $validated['end_time'];
                // If format is H:i, convert to H:i:s
                if (preg_match('/^\d{2}:\d{2}$/', $endTime)) {
                    $endTime .= ':00';
                }
            }

            // Create or update attendance
            $attendance = StaffAttendance::updateOrCreate(
                [
                    'staff_id' => $teacher->id,
                    'attendance_date' => $validated['attendance_date'],
                ],
                [
                    'status' => $validated['status'],
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'campus' => $teacher->campus,
                    'designation' => $teacher->designation,
                    'remarks' => $validated['remarks'] ?? null,
                ]
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
                        'start_time' => $attendance->start_time ? date('H:i:s', strtotime($attendance->start_time)) : null,
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
            if (!$teacher || strtolower(trim($teacher->designation ?? '')) !== 'teacher') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can check self-attendance.',
                ], 403);
            }

            // Get date from request or use today
            $date = $request->get('date', now()->format('Y-m-d'));

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
            if (!$teacher || strtolower(trim($teacher->designation ?? '')) !== 'teacher') {
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
}

