<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentPayment;
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
            // If student_id is provided but doesn't match authenticated student, ignore it and show class attendance
            $useStudentId = false;
            if ($request->filled('student_id')) {
                $studentId = (int) $request->student_id;
                
                // Verify the student_id belongs to authenticated student
                // Use loose comparison to handle type differences (int vs string)
                if ((int)$studentId == (int)$student->id) {
                    $useStudentId = true;
                }
                // If doesn't match, we'll ignore student_id and show class attendance instead
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

            // If student_id is provided and matches authenticated student, return that student's attendance with monthly summary
            $targetStudent = $student; // Default to authenticated student
            if ($useStudentId) {
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
                $monthlyAttendances = StudentAttendance::where('student_id', $targetStudentId)
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
                $attendance = StudentAttendance::where('student_id', $targetStudentId)
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

    /**
     * Staff / teacher app: scan student ID card and mark Present (same response as student API).
     *
     * POST /api/teacher/attendance/scan-id-card
     * Body: { "id": "ST1-001" } — card / student code (also: barcode, student_code, gr_number)
     */
    public function scanIdCardForStaff(Request $request): JsonResponse
    {
        $authUser = $request->user();
        if (!$authUser instanceof Staff) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Staff login required.',
                'token' => null,
            ], 403);
        }

        return $this->scanIdCard($request);
    }

    /**
     * Scan student ID card (barcode / admission no) and mark today as Present.
     * Student token: self check-in or scan own card. Staff token: scan any student card.
     *
     * POST /api/student/attendance/scan-id-card
     * Body: { "id": "ST1-001" } — card / student code (also: barcode, student_code, gr_number)
     */
    public function scanIdCard(Request $request): JsonResponse
    {
        try {
            $this->mergeScanInputsIntoRequest($request);

            $authUser = $request->user();
            $authStudent = $authUser instanceof Student ? $authUser : null;
            $staff = $authUser instanceof Staff ? $authUser : null;

            if (!$authStudent && !$staff) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Student or staff login required.',
                    'token' => null,
                ], 403);
            }

            $selfCheckIn = $authStudent && filter_var(
                $request->input('self_check_in', $request->boolean('self_check_in')),
                FILTER_VALIDATE_BOOLEAN
            );

            $requireSelfMatch = $authStudent && filter_var(
                $request->input('require_self_match', false),
                FILTER_VALIDATE_BOOLEAN
            );

            $barcode = $this->resolveScanRawFromRequest($request);

            if ($barcode === '' && $selfCheckIn) {
                $student = $authStudent;
            } elseif ($barcode === '') {
                return response()->json([
                    'success' => false,
                    'message' => 'Student ID is required. Send POST JSON body: {"id":"ST1-001"} (id = code on card / student_code).',
                    'token' => null,
                ], 422);
            } else {
                $student = $this->resolveStudentFromScanValue($barcode);

                if (!$student && $authStudent && $this->scanMatchesLoggedInStudent($barcode, $authStudent)) {
                    $student = $authStudent;
                }
            }

            if (!$student) {
                $hint = $authStudent ? [
                    'your_student_code' => $authStudent->student_code,
                    'your_gr_number' => $authStudent->gr_number,
                    'your_id' => $authStudent->id,
                    'scanned_value' => $barcode,
                ] : ['scanned_value' => $barcode];

                return response()->json([
                    'success' => false,
                    'message' => 'Student not found for this ID. Check the code on the card (e.g. ST1-001, GR-1A-007).',
                    'token' => null,
                    'hint' => $hint,
                ], 404);
            }

            if ($requireSelfMatch && $authStudent && (int) $student->id !== (int) $authStudent->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'This ID card does not match your logged-in account.',
                    'token' => null,
                ], 403);
            }

            if ($staff && !$staff->mayMarkAttendanceForStudent($student)) {
                $assignedClasses = $staff->assignedAttendanceClassNames();
                $message = $assignedClasses->isEmpty()
                    ? 'No class assigned to you. Admin must set you as class teacher in Manage Section.'
                    : 'You can only mark attendance for your assigned class(es): '
                        . $assignedClasses->implode(', ')
                        . '. Scanned student is in '
                        . trim(($student->class ?? 'N/A') . ' / ' . ($student->section ?? 'N/A'))
                        . '.';

                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'token' => null,
                    'assigned_classes' => $assignedClasses->values(),
                ], 403);
            }

            $today = Carbon::today()->format('Y-m-d');
            $attendance = StudentAttendance::where('student_id', $student->id)
                ->whereDate('attendance_date', $today)
                ->first();

            $alreadyMarked = $attendance !== null;
            $status = $alreadyMarked ? $attendance->status : 'Present';
            $scanRemarks = $staff ? 'Staff app ID card scan' : 'Student app ID card scan';

            if (!$alreadyMarked) {
                StudentAttendance::create([
                    'student_id' => $student->id,
                    'attendance_date' => $today,
                    'status' => 'Present',
                    'campus' => $student->campus,
                    'class' => $student->class,
                    'section' => $student->section,
                    'remarks' => $scanRemarks,
                ]);
            }

            $token = $authUser->currentAccessToken()->token ?? null;
            $markedForScannedCard = $authStudent
                ? (int) $student->id !== (int) $authStudent->id
                : true;

            return response()->json([
                'success' => true,
                'message' => $alreadyMarked
                    ? 'Attendance already marked for today.'
                    : 'Attendance marked as Present.',
                'data' => [
                    'scanned_value' => $barcode !== '' ? $barcode : null,
                    'marked_for_scanned_card' => $markedForScannedCard,
                    'scanned_by' => $staff ? 'staff' : 'student',
                    'student' => [
                        'id' => $student->id,
                        'name' => $student->student_name,
                        'roll' => $student->student_code ?: ($student->gr_number ?: (string) $student->id),
                        'student_code' => $student->student_code,
                        'gr_number' => $student->gr_number,
                        'parent' => $student->father_name ?: 'N/A',
                        'class_section' => trim(($student->class ?? 'N/A') . ' / ' . ($student->section ?? 'N/A')),
                        'campus' => $student->campus ?? 'N/A',
                        'dues' => $this->calculateStudentDues($student),
                    ],
                    'attendance' => [
                        'date' => $today,
                        'status' => $status,
                        'already_marked' => $alreadyMarked,
                        'marked_present_now' => !$alreadyMarked,
                        'time' => Carbon::now()->format('h:i A'),
                    ],
                ],
                'token' => $token,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while scanning ID card: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }

    /**
     * Accept JSON body when Content-Type is missing (Postman / mobile scanners).
     */
    private function mergeScanInputsIntoRequest(Request $request): void
    {
        if ($this->resolveScanRawFromRequest($request) !== '') {
            return;
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

    private function resolveScanRawFromRequest(Request $request): string
    {
        foreach ([
            'barcode',
            'gr_number',
            'student_code',
            'id_card',
            'scan',
            'admission_no',
            'roll_number',
            'roll',
            'student_id',
            'id',
            'code',
            'qr',
            'qr_code',
        ] as $key) {
            $value = $request->input($key);
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
            if (is_numeric($value)) {
                return trim((string) $value);
            }
        }

        return '';
    }

    private function resolveStudentFromScanValue(string $raw): ?Student
    {
        foreach ($this->parseScannedCardValues($raw) as $candidate) {
            $found = $this->findStudentByCardScan($candidate);
            if ($found) {
                return $found;
            }
        }

        return null;
    }

    /**
     * Values extracted from barcode gun or ID-card QR (ID:code|Name:...).
     *
     * @return list<string>
     */
    private function parseScannedCardValues(string $raw): array
    {
        $values = [];
        $trimmed = trim($raw);
        if ($trimmed === '') {
            return [];
        }

        $values[] = $trimmed;
        $decoded = trim(urldecode($trimmed));
        if ($decoded !== $trimmed) {
            $values[] = $decoded;
        }

        foreach ([$trimmed, $decoded] as $text) {
            if (preg_match('/\bID:\s*([^|]+)/iu', $text, $matches)) {
                $values[] = trim($matches[1]);
            }
        }

        return array_values(array_unique(array_filter($values, static fn ($v) => $v !== '')));
    }

    private function scanMatchesLoggedInStudent(string $raw, Student $authStudent): bool
    {
        foreach ($this->parseScannedCardValues($raw) as $candidate) {
            if (is_numeric($candidate) && (int) $candidate === (int) $authStudent->id) {
                return true;
            }
            if ($authStudent->student_code
                && strcasecmp(trim((string) $authStudent->student_code), $candidate) === 0) {
                return true;
            }
            if ($authStudent->gr_number
                && strcasecmp(trim((string) $authStudent->gr_number), $candidate) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve student from scanned value (barcode machine + printed ID / QR).
     */
    private function findStudentByCardScan(string $barcode): ?Student
    {
        $barcodeLower = strtolower(trim($barcode));
        $compact = $this->normalizeCardCode($barcode);
        $normalizedCnic = $this->normalizeIdCardDigits($barcode);
        $isDbPrimaryKey = ctype_digit(str_replace([' ', '-'], '', $barcode));

        $query = Student::query()
            ->where(function ($q) use ($barcodeLower, $barcode, $compact, $normalizedCnic, $isDbPrimaryKey) {
                $q->whereRaw('LOWER(TRIM(student_code)) = ?', [$barcodeLower])
                    ->orWhereRaw('LOWER(TRIM(gr_number)) = ?', [$barcodeLower])
                    ->orWhereRaw('LOWER(TRIM(b_form_number)) = ?', [$barcodeLower]);

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

                if ($isDbPrimaryKey) {
                    $q->orWhere('id', (int) $barcode);
                }

                if ($normalizedCnic !== '') {
                    $q->orWhereRaw(
                        'LOWER(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(father_id_card), "-", ""), " ", ""), "_", ""), ".", "")) = ?',
                        [$normalizedCnic]
                    );
                }
            });

        return $query->first();
    }

    private function normalizeCardCode(string $value): string
    {
        return strtolower(str_replace(['-', ' ', '_', '.', '/'], '', trim($value)));
    }

    private function normalizeIdCardDigits(string $value): string
    {
        $digitMap = [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ];
        $cleaned = trim(strtr($value, $digitMap));

        return str_replace(['-', ' ', '_', '.', '/'], '', strtolower($cleaned));
    }

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
}

