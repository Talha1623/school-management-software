<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParentAccount;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentDiscount;
use App\Models\StudentPayment;
use App\Services\FeePaymentWebTables;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class ParentAttendanceController extends Controller
{
    /**
     * Get one-year fee record for a specific child of authenticated parent.
     *
     * URL: /api/parent/attendance/student/{studentId}/yearly-record
     */
    public function yearlyStudentRecord(Request $request, int $studentId): JsonResponse
    {
        try {
            $parent = $this->resolveAuthenticatedParent($request);
            if (!$parent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token. Please login with parent account.',
                    'data' => null,
                    'token' => null,
                ], 401);
            }

            // Ensure requested student belongs to authenticated parent
            $student = $parent->students()->find($studentId);
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not allowed to view this student attendance record.',
                    'data' => null,
                    'token' => null,
                ], 403);
            }

            // Last 12 months record (including today)
            $endDate = Carbon::today()->endOfDay();
            $startDate = Carbon::today()->subYear()->addDay()->startOfDay();

            $studentCodeCandidates = $this->buildStudentCodeCandidates($student);
            if ($studentCodeCandidates->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student code not found for this student.',
                    'data' => null,
                    'token' => null,
                ], 400);
            }

            $recordsQuery = StudentPayment::query();
            $this->applyStudentCodeFilter($recordsQuery, $studentCodeCandidates);

            $records = $recordsQuery
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->where(function ($q2) use ($startDate, $endDate) {
                        $q2->whereNotNull('payment_date')
                            ->whereDate('payment_date', '>=', $startDate->format('Y-m-d'))
                            ->whereDate('payment_date', '<=', $endDate->format('Y-m-d'));
                    })->orWhere(function ($q2) use ($startDate, $endDate) {
                        // Include generated rows that may not have payment_date set.
                        $q2->whereNull('payment_date')
                            ->whereBetween('created_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()]);
                    });
                })
                ->orderBy('created_at', 'asc')
                ->get();

            $isSystemGenerated = static function ($payment): bool {
                $method = strtolower(trim((string) ($payment->method ?? '')));
                return in_array($method, ['generated', 'installment'], true);
            };

            $paidRecords = $records->filter(function ($payment) use ($isSystemGenerated) {
                return !$isSystemGenerated($payment);
            })->values();

            $generatedRecords = $records->filter(function ($payment) use ($isSystemGenerated) {
                return $isSystemGenerated($payment);
            })->values();

            $annualMonthlyFee = round(((float) ($student->monthly_fee ?? 0)) * 12, 2);
            $totalGeneratedFee = round((float) $generatedRecords->sum(function ($payment) {
                return max(0.0, (float) ($payment->payment_amount ?? 0) - (float) ($payment->discount ?? 0))
                    + (float) ($payment->late_fee ?? 0);
            }), 2);
            // Keep backward compatibility for `total_fee`, but prefer generated fee if available.
            $totalFee = $totalGeneratedFee > 0 ? $totalGeneratedFee : $annualMonthlyFee;

            $transportFeeGenerated = round((float) $generatedRecords
                ->filter(function ($payment) {
                    $title = strtolower(trim((string) ($payment->payment_title ?? '')));
                    return str_contains($title, 'transport');
                })
                ->sum(function ($payment) {
                    return max(0.0, (float) ($payment->payment_amount ?? 0) - (float) ($payment->discount ?? 0))
                        + (float) ($payment->late_fee ?? 0);
                }), 2);

            $cardFeeGenerated = round((float) $generatedRecords
                ->filter(function ($payment) {
                    $title = strtolower(trim((string) ($payment->payment_title ?? '')));
                    return str_contains($title, 'card');
                })
                ->sum(function ($payment) {
                    return max(0.0, (float) ($payment->payment_amount ?? 0) - (float) ($payment->discount ?? 0))
                        + (float) ($payment->late_fee ?? 0);
                }), 2);

            $admissionFeeGenerated = round((float) $generatedRecords
                ->filter(function ($payment) {
                    $title = strtolower(trim((string) ($payment->payment_title ?? '')));
                    return str_contains($title, 'admission');
                })
                ->sum(function ($payment) {
                    return max(0.0, (float) ($payment->payment_amount ?? 0) - (float) ($payment->discount ?? 0))
                        + (float) ($payment->late_fee ?? 0);
                }), 2);

            $otherFeeGenerated = round((float) $generatedRecords
                ->filter(function ($payment) {
                    $title = strtolower(trim((string) ($payment->payment_title ?? '')));
                    return !str_contains($title, 'monthly fee')
                        && !str_contains($title, 'transport')
                        && !str_contains($title, 'card')
                        && !str_contains($title, 'admission');
                })
                ->sum(function ($payment) {
                    return max(0.0, (float) ($payment->payment_amount ?? 0) - (float) ($payment->discount ?? 0))
                        + (float) ($payment->late_fee ?? 0);
                }), 2);

            $generatedByTitle = $generatedRecords
                ->groupBy(function ($payment) {
                    return trim((string) ($payment->payment_title ?? 'Untitled'));
                })
                ->map(function ($items, $title) {
                    $amount = (float) $items->sum(function ($payment) {
                        return max(0.0, (float) ($payment->payment_amount ?? 0) - (float) ($payment->discount ?? 0))
                            + (float) ($payment->late_fee ?? 0);
                    });

                    return [
                        'title' => $title,
                        'amount' => round($amount, 2),
                        'transactions_count' => $items->count(),
                        'status' => $amount > 0 ? 'unpaid' : 'paid',
                    ];
                })
                ->values();

            // Align generated-fee heads with web pending-fee table to avoid drift.
            $webPending = FeePaymentWebTables::searchResultsForStudent($student);
            $webPendingRows = collect($webPending['rows'] ?? []);
            $webGeneratedTotal = round((float) (($webPending['totals']['generated_fee'] ?? 0)), 2);

            if ($webGeneratedTotal > 0 || $webPendingRows->isNotEmpty()) {
                $titleAmount = function (callable $predicate) use ($webPendingRows): float {
                    return (float) $webPendingRows
                        ->filter(function ($row) use ($predicate) {
                            $title = strtolower(trim((string) ($row['fee_type'] ?? '')));
                            return $predicate($title);
                        })
                        ->sum(function ($row) {
                            return (float) ($row['generated_fee'] ?? 0);
                        });
                };

                $transportFeeGenerated = round($titleAmount(fn ($title) => str_contains($title, 'transport')), 2);
                $cardFeeGenerated = round($titleAmount(fn ($title) => str_contains($title, 'card')), 2);
                $admissionFeeGenerated = round($titleAmount(fn ($title) => str_contains($title, 'admission')), 2);
                $otherFeeGenerated = round($titleAmount(fn ($title) => !str_contains($title, 'monthly fee') && !str_contains($title, 'transport') && !str_contains($title, 'card') && !str_contains($title, 'admission')), 2);
                $totalGeneratedFee = $webGeneratedTotal;
                $totalFee = $totalGeneratedFee > 0 ? $totalGeneratedFee : $totalFee;

                $generatedByTitle = $webPendingRows
                    ->map(function ($row) {
                        return [
                            'title' => (string) ($row['fee_type'] ?? 'Untitled'),
                            'amount' => round((float) ($row['generated_fee'] ?? 0), 2),
                            'transactions_count' => 1,
                            'status' => strtolower((string) ($row['status'] ?? 'unpaid')),
                            'paid' => round((float) ($row['paid'] ?? 0), 2),
                            'due' => round((float) ($row['due'] ?? 0), 2),
                        ];
                    })
                    ->values();
            }
            $totalPaid = round((float) $paidRecords->sum('payment_amount'), 2);
            $totalDiscount = round((float) $paidRecords->sum('discount'), 2);
            $totalLateFee = round((float) $paidRecords->sum('late_fee'), 2);
            $totalNetPaid = round(max(0, $totalPaid - $totalDiscount + $totalLateFee), 2);
            // Web / student API parity: outstanding due (not "annual - rolling window paid")
            $remainingFee = 0.0;
            foreach ($studentCodeCandidates as $candidateCode) {
                $remainingFee = max($remainingFee, $this->calculateFeeDueLikeWeb((string) $candidateCode));
            }

            // Group by fee month (payment title month when present) — same idea as web voucher / fee breakdown
            $historyRecords = $records->values();

            $paymentByDate = $historyRecords
                ->groupBy(function ($payment) {
                    $bucket = $this->resolveFeeMonthDate($payment);
                    if ($bucket) {
                        return $bucket->format('Y-m-01');
                    }

                    return !empty($payment->payment_date)
                        ? Carbon::parse($payment->payment_date)->startOfMonth()->format('Y-m-01')
                        : 'N/A';
                })
                ->map(function ($dayPayments, $dateKey) use ($isSystemGenerated) {
                    $first = $dayPayments->first();
                    $paidDayPayments = $dayPayments->filter(function ($payment) use ($isSystemGenerated) {
                        return !$isSystemGenerated($payment);
                    })->values();
                    $hasGenerated = $dayPayments->contains(function ($payment) use ($isSystemGenerated) {
                        return $isSystemGenerated($payment);
                    });

                    return [
                        'date' => $dateKey !== 'N/A' ? $dateKey : null,
                        'date_formatted' => $dateKey !== 'N/A' ? Carbon::parse($dateKey)->format('d M Y') : null,
                        'payment_titles' => $dayPayments
                            ->groupBy(function ($payment) {
                                return trim((string) ($payment->payment_title ?? 'Untitled'));
                            })
                            ->map(function ($titlePayments, $title) {
                                $titleAmount = round((float) $titlePayments->sum('payment_amount'), 2);
                                return $title . ':' . $titleAmount;
                            })
                            ->values(),
                        'methods' => $dayPayments->pluck('method')->filter()->unique()->values(),
                        'has_generated' => $hasGenerated,
                        'payment_amount' => round((float) $paidDayPayments->sum('payment_amount'), 2),
                        'discount' => round((float) $paidDayPayments->sum('discount'), 2),
                        'late_fee' => round((float) $paidDayPayments->sum('late_fee'), 2),
                        // Net paid for paid rows; if only generated rows exist, expose generated amount.
                        'net_amount' => $paidDayPayments->isNotEmpty()
                            ? round(
                                (float) $paidDayPayments->sum('payment_amount')
                                - (float) $paidDayPayments->sum('discount')
                                + (float) $paidDayPayments->sum('late_fee'),
                                2
                            )
                            : round((float) $dayPayments->sum('payment_amount'), 2),
                        'transactions_count' => $dayPayments->count(),
                        'first_method' => $first?->method,
                        'status' => $paidDayPayments->isNotEmpty()
                            ? 'paid'
                            : ($hasGenerated ? 'unpaid' : 'pending'),
                    ];
                })
                ->sortBy('date')
                ->values();

            $overallStatus = $remainingFee > 0 ? 'unpaid' : 'paid';

            return response()->json([
                'success' => true,
                'message' => 'Student yearly fee record retrieved successfully',
                'data' => [
                    'student' => [
                        'id' => $student->id,
                        'student_name' => $student->student_name,
                        'student_code' => $student->student_code,
                        'class' => $student->class,
                        'section' => $student->section,
                        'campus' => $student->campus,
                    ],
                    'range' => [
                        'from' => $startDate->format('Y-m-d'),
                        'to' => $endDate->format('Y-m-d'),
                        'label' => 'Last 1 Year',
                    ],
                    'summary' => [
                        'monthly_fee' => round((float) ($student->monthly_fee ?? 0), 2),
                        'annual_monthly_fee' => $annualMonthlyFee,
                        'total_fee' => $totalFee,
                        'generated_total_fee' => $totalGeneratedFee,
                        'transport_fee_generated' => $transportFeeGenerated,
                        'card_fee_generated' => $cardFeeGenerated,
                        'admission_fee_generated' => $admissionFeeGenerated,
                        'other_fee_generated' => $otherFeeGenerated,
                        'total_paid' => $totalPaid,
                        'total_discount' => $totalDiscount,
                        'total_late_fee' => $totalLateFee,
                        'total_net_paid' => $totalNetPaid,
                        'remaining_fee' => $remainingFee,
                        'status' => $overallStatus,
                        'total_transactions' => $records->count(),
                        'generated_breakdown' => $generatedByTitle,
                    ],
                    'payments_by_date' => $paymentByDate,
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving yearly fee record: ' . $e->getMessage(),
                'data' => null,
                'token' => null,
            ], 500);
        }
    }

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
            $parent = $this->resolveAuthenticatedParent($request);
            
            if (!$parent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token. Please login with parent account.',
                    'data' => null,
                    'token' => null,
                ], 401);
            }

            // Optional filter: specific student_id (must belong to this parent)
            if ($request->filled('student_id')) {
                $studentId = (int) $request->student_id;
                
                // Verify the student belongs to this parent
                $student = $parent->students()->find($studentId);
                
                if (!$student) {
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

            // If student_id is provided, return that student's attendance with monthly summary
            if ($request->filled('student_id')) {
                $studentId = (int) $request->student_id;
                
                // Get the specific student
                $targetStudent = $parent->students()->find($studentId);
                
                if (!$targetStudent) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Student not found or does not belong to this parent',
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
                $monthlyAttendances = StudentAttendance::where('student_id', $studentId)
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
                $attendance = StudentAttendance::where('student_id', $studentId)
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
                // Get first student from parent's children (similar to student API using authenticated student)
                $parentStudents = $parent->students()->get();
                
                if ($parentStudents->isEmpty()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'No students connected to this parent.',
                        'data' => [
                            'date' => $attendanceDate->format('Y-m-d'),
                            'date_formatted' => $attendanceDate->format('d M Y'),
                            'class' => null,
                            'section' => null,
                            'campus' => null,
                            'students' => [],
                        ],
                        'token' => $request->user()->currentAccessToken()->token ?? null,
                    ], 200);
                }

                // Use first student's class information (similar to student API)
                $targetStudent = $parentStudents->first();

                if (!$targetStudent->campus || !$targetStudent->class || !$targetStudent->section) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Student information incomplete. Cannot fetch attendance.',
                        'data' => null,
                        'token' => null,
                    ], 400);
                }

                // Get all students in the same class and section
                $classStudents = Student::whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($targetStudent->campus))])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($targetStudent->class))])
                    ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($targetStudent->section))])
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
     * Ensure authenticated user is a ParentAccount model.
     */
    private function resolveAuthenticatedParent(Request $request): ?ParentAccount
    {
        $user = $request->user();
        return $user instanceof ParentAccount ? $user : null;
    }

    /**
     * Same due math as Fee Payment web + /api/student/fees (StudentFeeController / ParentFeeController).
     */
    private function calculateFeeDueLikeWeb(string $studentCode): float
    {
        $norm = strtolower(trim($studentCode));

        $generatedFees = StudentPayment::whereRaw('LOWER(TRIM(student_code)) = ?', [$norm])
            ->whereIn('method', ['Generated', 'Installment'])
            ->get();

        $paidFees = StudentPayment::whereRaw('LOWER(TRIM(student_code)) = ?', [$norm])
            ->where('method', '!=', 'Generated')
            ->where('method', '!=', 'Installment')
            ->get();

        $studentDiscounts = StudentDiscount::whereRaw('LOWER(TRIM(student_code)) = ?', [$norm])->get();
        $totalStudentDiscount = $studentDiscounts->sum(function ($discount) {
            return (float) ($discount->discount_amount ?? 0);
        });

        $totalDue = 0.0;
        $generatedByTitle = $generatedFees->groupBy('payment_title');
        $paidByTitle = $paidFees->groupBy('payment_title');

        $installmentBaseTitles = [];
        foreach ($generatedByTitle as $title => $items) {
            if (preg_match('/^(.+)\/\d+$/', (string) $title, $matches)) {
                $installmentBaseTitles[$matches[1]] = true;
            }
        }

        foreach ($generatedByTitle as $title => $items) {
            $isInstallment = preg_match('/\/\d+$/', (string) $title);

            if (!$isInstallment && isset($installmentBaseTitles[$title])) {
                continue;
            }

            $isMonthlyFee = str_starts_with((string) $title, 'Monthly Fee - ');

            $originalAmount = (float) $items->sum(function ($item) {
                return (float) ($item->payment_amount ?? 0);
            });

            $generatedLate = (float) $items->sum(function ($item) {
                return (float) ($item->late_fee ?? 0);
            });

            $generatedDiscount = 0.0;
            if ($isInstallment) {
                $generatedDiscount = (float) $items->sum(function ($item) {
                    return (float) ($item->discount ?? 0);
                });
            }

            $paidDiscount = (float) $paidByTitle->get($title, collect())->sum(function ($item) {
                return (float) ($item->discount ?? 0);
            });

            $appliedStudentDiscount = 0.0;
            if ($isMonthlyFee && $totalStudentDiscount > 0 && !$isInstallment) {
                $appliedStudentDiscount = round($totalStudentDiscount, 2);
            }

            $totalDiscount = $generatedDiscount + $paidDiscount + $appliedStudentDiscount;

            $paidAmountOnly = (float) $paidByTitle->get($title, collect())->sum(function ($item) {
                return (float) ($item->payment_amount ?? 0);
            });

            $paidLate = (float) $paidByTitle->get($title, collect())->sum(function ($item) {
                return (float) ($item->late_fee ?? 0);
            });

            $remainingAmount = max(0, ($originalAmount - $totalDiscount) - $paidAmountOnly);
            $remainingLate = max(0, $generatedLate - $paidLate);
            $remainingTotal = $remainingAmount + $remainingLate;

            if ($remainingTotal > 0) {
                $totalDue += $remainingTotal;
            }
        }

        return round($totalDue, 2);
    }

    /**
     * Resolve fee month using payment title first, then payment date fallback.
     */
    private function resolveFeeMonthDate($payment): ?Carbon
    {
        $title = trim((string) ($payment->payment_title ?? ''));
        if ($title !== '' && preg_match('/([A-Za-z]+)\s+(\d{4})/', $title, $m)) {
            try {
                return Carbon::createFromFormat('F Y', $m[1] . ' ' . $m[2])->startOfMonth();
            } catch (\Exception $e) {
                // Ignore parse errors and fallback to payment_date.
            }
        }

        if (!empty($payment->payment_date)) {
            return Carbon::parse($payment->payment_date)->startOfMonth();
        }

        return null;
    }

    /**
     * Build robust student code candidates (student_code + gr_number variants).
     */
    private function buildStudentCodeCandidates(Student $student)
    {
        $raw = collect([
            (string) ($student->student_code ?? ''),
            (string) ($student->gr_number ?? ''),
        ])->map(fn ($v) => trim(strtolower($v)))
            ->filter(fn ($v) => $v !== '');

        $expanded = $raw->flatMap(function ($code) {
            $compact = preg_replace('/[^a-z0-9]/', '', $code);
            return collect([$code, str_replace(' ', '', $code), str_replace('-', '', $code), $compact]);
        });

        return $expanded
            ->map(fn ($v) => trim(strtolower((string) $v)))
            ->filter(fn ($v) => $v !== '')
            ->unique()
            ->values();
    }

    /**
     * Apply flexible student code matching on student_payments.student_code.
     */
    private function applyStudentCodeFilter(Builder $query, $studentCodeCandidates): void
    {
        $studentCodeCandidates = collect($studentCodeCandidates)
            ->map(fn ($v) => trim(strtolower((string) $v)))
            ->filter(fn ($v) => $v !== '')
            ->unique()
            ->values();

        $query->where(function ($q) use ($studentCodeCandidates) {
            foreach ($studentCodeCandidates as $code) {
                $q->orWhereRaw('LOWER(TRIM(student_code)) = ?', [$code]);
                $q->orWhereRaw("REPLACE(REPLACE(LOWER(TRIM(student_code)), '-', ''), ' ', '') = ?", [str_replace(['-', ' '], '', $code)]);
            }
        });
    }
}

