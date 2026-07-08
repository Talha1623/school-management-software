<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\StudentAttendance;
use App\Models\StudentPayment;
use App\Models\Subject;
use App\Models\Campus;
use App\Models\Staff;
use App\Models\AdminRole;
use App\Models\Message;
use App\Models\StudentNotification;
use App\Models\StudentDeviceToken;
use App\Services\FirebasePushService;
use App\Services\AutoStudentAttendanceService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class StudentAttendanceController extends Controller
{
    private function notifyAdminsAboutStaffStudentAttendance(string $status, string $attendanceDate, int $studentCount, ?Student $student = null): void
    {
        $staff = Auth::guard('staff')->user();
        if (!$staff || $studentCount <= 0) {
            return;
        }

        $target = $student
            ? sprintf('%s (%s)', $student->student_name ?? 'Student', $student->student_code ?? $student->id)
            : $studentCount . ' student(s)';

        $classSection = $student
            ? trim((string) ($student->class ?? '') . (($student->section ?? '') !== '' ? ' - ' . $student->section : ''))
            : '';

        $text = sprintf(
            '%s marked student attendance as %s for %s on %s.',
            $staff->name ?? 'Staff',
            $status,
            $target,
            Carbon::parse($attendanceDate)->format('d-m-Y')
        );

        if ($student && $student->campus) {
            $text .= ' Campus: ' . $student->campus . '.';
        }
        if ($classSection !== '') {
            $text .= ' Class: ' . $classSection . '.';
        }

        AdminRole::query()
            ->select('id')
            ->orderBy('id')
            ->get()
            ->each(function (AdminRole $admin) use ($staff, $text) {
                Message::create([
                    'from_type' => 'staff_notification',
                    'from_id' => $staff->id,
                    'to_type' => 'admin',
                    'to_id' => $admin->id,
                    'text' => $text,
                    'attachment_path' => null,
                    'attachment_type' => null,
                    'read_at' => null,
                ]);
            });
    }

    /**
     * Display student attendance page.
     */
    public function index(Request $request): View
    {
        app(AutoStudentAttendanceService::class)->runIfDue();

        // Get filter values
        $filterCampus = $request->get('filter_campus');
        $filterType = $request->get('filter_type');
        $filterClass = $request->get('filter_class');
        $filterSection = $request->get('filter_section');
        $filterDate = $request->get('filter_date', date('Y-m-d'));

        $staff = Auth::guard('staff')->user();
        $isStaffAttendanceUser = $staff !== null;
        $staffCampusLocked = (bool) ($staff && $staff->scopeCampusName());
        if ($staffCampusLocked) {
            $filterCampus = $staff->campus;
        }

        if ($staff && $filterClass && !$staff->isAssignedToClass($filterClass, $filterCampus)) {
            $filterClass = null;
            $filterSection = null;
        }

        // Get campuses
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromStudents = Student::whereNotNull('campus')->distinct()->pluck('campus');
            $allCampuses = $campusesFromClasses
                ->merge($campusesFromSections)
                ->merge($campusesFromStudents)
                ->unique()
                ->sort()
                ->values();
            $campuses = $allCampuses->map(function ($campus) {
                return (object)['campus_name' => $campus];
            });
        }

        if ($staffCampusLocked && $staff) {
            $campuses = collect([(object) ['campus_name' => trim((string) $staff->campus)]]);
        }

        // Staff portal: only classes assigned to this teacher (Subjects + Sections)
        $classes = $this->resolveAttendanceClasses($staff, $filterCampus);
        
        $sections = collect();
        if ($filterClass) {
            $sections = $this->resolveAttendanceSections($staff, $filterClass, $filterCampus);
        }
        
        // Get students based on filters
        $students = collect();
        $attendanceData = [];
        
        if ($filterClass && $filterDate) {
            $studentsQuery = Student::query();
            
            // Exclude passout students first
            $passoutClasses = ['passout', 'pass out', 'passed out', 'passedout', 'graduated', 'graduate', 'alumni'];
            $studentsQuery->where(function($q) use ($passoutClasses) {
                $q->whereNotNull('class')
                    ->where('class', '!=', '')
                    ->where(function($subQ) use ($passoutClasses) {
                        // Class should not match any passout value
                        $subQ->whereRaw("LOWER(TRIM(COALESCE(class, ''))) NOT IN ('" . implode("', '", array_map('strtolower', $passoutClasses)) . "')");
                    });
            });
            
            // Use case-insensitive matching for class (same as API)
            if ($filterClass) {
                $studentsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
            }
            
            // Use case-insensitive matching for section (same as API)
            if ($filterSection) {
                $studentsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))]);
            } elseif ($staff) {
                $assignedSections = $staff->assignedAttendanceSectionsForClass($filterClass, $filterCampus);
                if ($assignedSections->isEmpty()) {
                    $studentsQuery->whereRaw('1 = 0');
                } else {
                    $studentsQuery->where(function ($q) use ($assignedSections) {
                        foreach ($assignedSections as $section) {
                            $q->orWhereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($section))]);
                        }
                    });
                }
            }

            if ($filterCampus) {
                $studentsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
            }
            
            // Explicitly load WhatsApp number from Admit Student (students table).
            $allStudents = $studentsQuery
                ->select('students.*', 'students.whatsapp_number as admit_whatsapp_number')
                ->orderBy('student_name', 'asc')
                ->get();
            
            // Get attendance data for the selected date
            // Query attendance by student_id and date (case-insensitive matching not needed here as we're using IDs)
            $studentIds = $allStudents->pluck('id');
            $attendances = StudentAttendance::whereIn('student_id', $studentIds)
                ->whereDate('attendance_date', $filterDate)
                ->get()
                ->keyBy('student_id');
            
            // Build attendance data array for all students
            foreach ($allStudents as $student) {
                $attendance = $attendances->get($student->id);
                $attendanceData[$student->id] = $attendance ? $attendance->status : 'N/A';
            }
            
            $students = $allStudents;
        }
        
        $types = collect(['normal students' => 'Normal Students']);
        $statusOptions = ['Present', 'Absent', 'Holiday', 'Sunday', 'Leave', 'N/A'];
        
        return view('attendance.student', compact(
            'campuses', 'classes', 'sections', 'types', 'statusOptions',
            'filterCampus', 'filterType', 'filterClass', 'filterSection', 'filterDate',
            'students', 'attendanceData', 'staffCampusLocked', 'isStaffAttendanceUser', 'staff'
        ));
    }

    /**
     * Print present students for today.
     */
    public function printPresentToday(Request $request): View
    {
        $today = Carbon::today();

        $presentAttendances = StudentAttendance::with('student')
            ->whereDate('attendance_date', $today)
            ->whereRaw('LOWER(status) = ?', ['present'])
            ->orderBy('campus')
            ->orderBy('class')
            ->orderBy('section')
            ->orderBy('student_id')
            ->get();

        return view('attendance.present-today-print', [
            'presentAttendances' => $presentAttendances,
            'printedAt' => Carbon::now()->format('d-m-Y H:i'),
            'dateLabel' => $today->format('d M Y'),
        ]);
    }

    /**
     * Get sections by class name (AJAX).
     */
    public function getSectionsByClass(Request $request)
    {
        $className = $request->get('class');
        $campus = $request->get('campus');
        $staff = Auth::guard('staff')->user();
        if ($staff && $staff->scopeCampusName()) {
            $campus = trim((string) $staff->campus);
        }

        if (!$className) {
            return response()->json(['sections' => []]);
        }

        $sections = $this->resolveAttendanceSections($staff, $className, $campus);

        return response()->json(['sections' => $sections]);
    }

    /**
     * Get classes by campus (AJAX).
     */
    public function getClassesByCampus(Request $request)
    {
        $staff = Auth::guard('staff')->user();
        $campus = $request->get('campus');
        if ($staff && $staff->scopeCampusName()) {
            $campus = trim((string) $staff->campus);
        }

        $classes = $this->resolveAttendanceClasses($staff, $campus);

        return response()->json(['classes' => $classes]);
    }

    /**
     * Bulk update student attendance.
     */
    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'student_ids' => ['required', 'array'],
            'student_ids.*' => ['required', 'exists:students,id'],
            'attendance_date' => ['required', 'date'],
            'status' => ['required', 'in:Present,Absent,Holiday,Sunday,Leave,N/A'],
            'notify_late_absent' => ['nullable', 'in:Yes,No'],
        ]);

        $studentIds = $validated['student_ids'];
        $attendanceDate = $validated['attendance_date'];
        $status = $validated['status'];
        $notifyLateAbsent = ($validated['notify_late_absent'] ?? 'No') === 'Yes';

        $updatedCount = 0;
        $errors = [];

        foreach ($studentIds as $studentId) {
            try {
                $student = Student::findOrFail($studentId);

                $staff = Auth::guard('staff')->user();
                if ($staff && !$staff->mayMarkAttendanceForStudent($student)) {
                    $errors[] = "Not allowed to update attendance for student ID {$studentId}.";

                    continue;
                }

                // Create or update attendance
                StudentAttendance::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'attendance_date' => $attendanceDate,
                    ],
                    [
                        'status' => $status,
                        'campus' => $student->campus,
                        'class' => $student->class,
                        'section' => $student->section,
                    ]
                );

                if ($notifyLateAbsent && in_array($status, ['Absent', 'Late'], true)) {
                    Log::info('Attendance bulk notification condition matched', [
                        'student_id' => $student->id,
                        'status' => $status,
                        'attendance_date' => $attendanceDate,
                    ]);
                    $this->sendAttendanceNotification($student, $status, $attendanceDate);
                } else {
                    Log::info('Attendance bulk notification skipped by condition', [
                        'student_id' => $student->id,
                        'notify_late_absent' => $notifyLateAbsent,
                        'status' => $status,
                    ]);
                }

                $updatedCount++;
            } catch (\Exception $e) {
                $errors[] = "Failed to update student ID {$studentId}: " . $e->getMessage();
            }
        }

        if (count($errors) > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Some students could not be updated.',
                'errors' => $errors,
                'updated_count' => $updatedCount,
            ], 200);
        }

        $this->notifyAdminsAboutStaffStudentAttendance($status, $attendanceDate, $updatedCount);

        return response()->json([
            'success' => true,
            'message' => "Successfully marked {$updatedCount} students as {$status}.",
            'updated_count' => $updatedCount,
        ]);
    }

    /**
     * Store or update student attendance.
     */
    public function store(Request $request)
    {
        $this->writeAttendanceDebug('store_request_received', [
            'student_id' => $request->input('student_id'),
            'attendance_date' => $request->input('attendance_date'),
            'status' => $request->input('status'),
            'notify_late_absent' => $request->input('notify_late_absent'),
        ]);

        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'attendance_date' => ['required', 'date'],
            'status' => ['required', 'in:Present,Absent,Holiday,Sunday,Leave,N/A'],
            'notify_late_absent' => ['nullable', 'in:Yes,No'],
        ]);

        $student = Student::findOrFail($validated['student_id']);

        $staff = Auth::guard('staff')->user();
        if ($staff && !$staff->mayMarkAttendanceForStudent($student)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to mark attendance for this student.',
            ], 403);
        }

        // Create or update attendance
        StudentAttendance::updateOrCreate(
            [
                'student_id' => $validated['student_id'],
                'attendance_date' => $validated['attendance_date'],
            ],
            [
                'status' => $validated['status'],
                'campus' => $student->campus,
                'class' => $student->class,
                'section' => $student->section,
            ]
        );

        $notificationMeta = null;
        if (($validated['notify_late_absent'] ?? 'No') === 'Yes' && in_array($validated['status'], ['Absent', 'Late'], true)) {
            Log::info('Attendance single notification condition matched', [
                'student_id' => $student->id,
                'status' => $validated['status'],
                'attendance_date' => $validated['attendance_date'],
            ]);
            $notificationMeta = $this->sendAttendanceNotification($student, $validated['status'], $validated['attendance_date']);
        } else {
            Log::info('Attendance single notification skipped by condition', [
                'student_id' => $student->id,
                'notify_late_absent' => $validated['notify_late_absent'] ?? 'No',
                'status' => $validated['status'],
            ]);
        }

        $this->writeAttendanceDebug('store_request_processed', [
            'student_id' => $validated['student_id'],
            'attendance_date' => $validated['attendance_date'],
            'status' => $validated['status'],
            'notify_late_absent' => $validated['notify_late_absent'] ?? 'No',
            'notification_meta' => $notificationMeta,
        ]);

        $this->notifyAdminsAboutStaffStudentAttendance(
            $validated['status'],
            $validated['attendance_date'],
            1,
            $student
        );

        return response()->json([
            'success' => true,
            'message' => 'Attendance updated successfully!',
            'status' => $validated['status'],
            'notification' => $notificationMeta,
        ]);
    }

    /**
     * Scan barcode and mark attendance for today.
     */
    public function scanBarcode(Request $request)
    {
        app(AutoStudentAttendanceService::class)->runIfDue();

        $this->mergeBarcodeScanBodyIntoRequest($request);

        $validated = $request->validate([
            'id' => ['nullable', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:255'],
        ]);

        $barcode = trim((string) ($validated['id'] ?? $validated['barcode'] ?? ''));
        if ($barcode === '') {
            return response()->json([
                'success' => false,
                'message' => 'Student ID is required. Send JSON body: {"id":"ST1-001"}',
            ], 422);
        }
        $barcodeLower = strtolower($barcode);

        $compact = strtolower(str_replace(['-', ' ', '_', '.', '/'], '', $barcode));

        $student = Student::query()
            ->where(function ($q) use ($barcodeLower, $compact, $barcode) {
                $q->whereRaw('LOWER(TRIM(student_code)) = ?', [$barcodeLower])
                    ->orWhereRaw('LOWER(TRIM(gr_number)) = ?', [$barcodeLower]);

                if ($compact !== '') {
                    $q->orWhereRaw(
                        "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(gr_number), '-', ''), ' ', ''), '_', ''), '.', '')) = ?",
                        [$compact]
                    )
                        ->orWhereRaw(
                            "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(student_code), '-', ''), ' ', ''), '_', ''), '.', '')) = ?",
                            [$compact]
                        );
                }

                if (ctype_digit(str_replace([' ', '-'], '', $barcode))) {
                    $q->orWhere('id', (int) $barcode);
                }
            })
            ->first();

        if (!$student) {
            $staffScan = app(\App\Http\Controllers\StaffAttendanceController::class)->scanIdCardGate($request);
            $staffPayload = $staffScan->getData(true);
            if (is_array($staffPayload) && !empty($staffPayload['success'])) {
                return $staffScan;
            }

            return response()->json([
                'success' => false,
                'message' => 'Student or staff not found for this barcode.',
            ], 404);
        }

        $staff = Auth::guard('staff')->user();
        if ($staff && !$staff->mayMarkAttendanceForStudent($student)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to mark attendance for this student.',
            ], 403);
        }

        $today = Carbon::today()->format('Y-m-d');
        $attendance = StudentAttendance::where('student_id', $student->id)
            ->whereDate('attendance_date', $today)
            ->first();

        $alreadyMarked = $attendance !== null;
        $status = $alreadyMarked ? $attendance->status : 'Present';

        if (!$alreadyMarked) {
            StudentAttendance::create([
                'student_id' => $student->id,
                'attendance_date' => $today,
                'status' => 'Present',
                'campus' => $student->campus,
                'class' => $student->class,
                'section' => $student->section,
                'remarks' => 'Barcode scan',
            ]);
        }

        $duesFee = $this->calculateStudentDues($student);

        return response()->json([
            'success' => true,
            'message' => $alreadyMarked ? 'Good bye' : 'Attendance marked as Present.',
            'data' => [
                'student' => [
                    'id' => $student->id,
                    'name' => $student->student_name,
                    'roll' => $student->student_code ?: ($student->gr_number ?: $student->id),
                    'parent' => $student->father_name ?: 'N/A',
                    'class_section' => trim(($student->class ?? 'N/A') . ' / ' . ($student->section ?? 'N/A')),
                    'campus' => $student->campus ?? 'N/A',
                    'dues' => $duesFee,
                ],
                'attendance' => [
                    'date' => $today,
                    'status' => $status,
                    'already_marked' => $alreadyMarked,
                    'time' => Carbon::now()->format('h:i A'),
                ],
            ],
        ]);
    }

    /**
     * Calculate student dues for current year.
     */
    private function calculateStudentDues(Student $student): float
    {
        $currentYear = Carbon::now()->year;
        $totalFee = $student->monthly_fee ? (float) $student->monthly_fee * 12 : 0.0;

        if (!$student->student_code) {
            return 0.0;
        }

        $payments = StudentPayment::where('student_code', $student->student_code)
            ->whereYear('payment_date', $currentYear)
            ->get();

        $paidFee = (float) $payments->sum('payment_amount');
        $discount = (float) $payments->sum('discount');
        $lateFee = (float) $payments->sum('late_fee');

        return max($totalFee - $paidFee - $discount + $lateFee, 0.0);
    }

    private function sendAttendanceNotification(Student $student, string $status, string $attendanceDate): array
    {
        $title = 'Attendance Alert';
        $message = "Dear {$student->student_name}, your attendance is marked {$status} on {$attendanceDate}.";
        $inAppSaved = false;

        if (Schema::hasTable('student_notifications')) {
            try {
                StudentNotification::create([
                    'student_id' => $student->id,
                    'title' => $title,
                    'message' => $message,
                    'data' => [
                        'type' => 'attendance',
                        'status' => $status,
                        'date' => $attendanceDate,
                    ],
                    'created_by_type' => 'attendance',
                    'created_by_id' => null,
                ]);
                $inAppSaved = true;
            } catch (\Throwable $e) {
                Log::warning('Attendance in-app notification save failed', [
                    'student_id' => $student->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $tokens = [];
        if (Schema::hasTable('student_device_tokens')) {
            $tokens = StudentDeviceToken::query()
                ->where('student_id', $student->id)
                ->where('is_active', true)
                ->pluck('fcm_token')
                ->filter()
                ->values()
                ->all();
        }

        if (empty($tokens)) {
            Log::info('Attendance notification skipped: no active student device tokens', [
                'student_id' => $student->id,
                'status' => $status,
                'attendance_date' => $attendanceDate,
            ]);
        }

        app(FirebasePushService::class)->sendToTokens($tokens, $title, $message, [
            'screen' => 'notifications',
            'student_id' => (string) $student->id,
            'status' => $status,
            'date' => $attendanceDate,
        ]);

        return [
            'in_app_saved' => $inAppSaved,
            'tokens_targeted' => count($tokens),
        ];
    }

    /**
     * Class dropdown options: staff see only their assigned classes; admin sees all for campus.
     */
    private function resolveAttendanceClasses(?Staff $staff, ?string $campus): Collection
    {
        $staff = $staff ?? Auth::guard('staff')->user();

        // Staff portal login: never show every class in campus — only Manage Section / Subjects assignments.
        if ($staff instanceof Staff) {
            return $staff->assignedAttendanceClassNames($campus);
        }

        $classesQuery = ClassModel::whereNotNull('class_name');
        if ($campus) {
            $classesQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }

        $classes = $classesQuery
            ->orderBy('numeric_no', 'asc')
            ->orderBy('class_name', 'asc')
            ->distinct()
            ->pluck('class_name')
            ->map(fn ($class) => trim((string) $class))
            ->filter(fn ($class) => $class !== '')
            ->unique()
            ->values();

        if ($classes->isNotEmpty()) {
            return $classes;
        }

        $fallback = Student::whereNotNull('class');
        if ($campus) {
            $fallback->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }

        return $fallback->distinct()
            ->pluck('class')
            ->map(fn ($class) => trim((string) $class))
            ->filter(fn ($class) => $class !== '')
            ->unique()
            ->sort()
            ->values();
    }

    /**
     * Section dropdown options: staff see only assigned sections for the class; admin sees all.
     */
    private function resolveAttendanceSections(?Staff $staff, string $class, ?string $campus): Collection
    {
        $staff = $staff ?? Auth::guard('staff')->user();

        if ($staff instanceof Staff) {
            return $staff->assignedAttendanceSectionsForClass($class, $campus);
        }

        $sectionsQuery = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
        if ($campus) {
            $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }

        $sections = $sectionsQuery
            ->whereNotNull('name')
            ->orderBy('name', 'asc')
            ->distinct()
            ->pluck('name')
            ->values();

        if ($sections->isNotEmpty()) {
            return $sections;
        }

        $fromStudents = Student::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
        if ($campus) {
            $fromStudents->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }

        return $fromStudents
            ->whereNotNull('section')
            ->distinct()
            ->pluck('section')
            ->sort()
            ->values();
    }

    private function writeAttendanceDebug(string $event, array $payload = []): void
    {
        try {
            $line = json_encode([
                'time' => now()->toDateTimeString(),
                'event' => $event,
                'payload' => $payload,
            ], JSON_UNESCAPED_SLASHES);

            if (is_string($line)) {
                file_put_contents(
                    storage_path('app/attendance-debug.log'),
                    $line . PHP_EOL,
                    FILE_APPEND
                );
            }
        } catch (\Throwable $e) {
            // Keep attendance flow unaffected if debug write fails.
        }
    }

    /**
     * Barcode gun / mobile JSON body without Content-Type: application/json.
     */
    private function mergeBarcodeScanBodyIntoRequest(Request $request): void
    {
        foreach (['id', 'barcode', 'student_code', 'gr_number', 'scan', 'id_card'] as $key) {
            if (is_string($request->input($key)) && trim((string) $request->input($key)) !== '') {
                return;
            }
        }

        $json = $request->json()->all();
        if (is_array($json) && $json !== []) {
            $request->merge($json);

            return;
        }

        $content = trim((string) $request->getContent());
        if ($content !== '' && str_starts_with($content, '{')) {
            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                $request->merge($decoded);
            }
        }
    }

}

