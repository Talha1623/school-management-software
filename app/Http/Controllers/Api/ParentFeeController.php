<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudentDiscount;
use App\Models\StudentPayment;
use App\Services\FeePaymentWebTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParentFeeController extends Controller
{
    /**
     * Same due calculation as Fee Payment web screen (per title, installments, student discount).
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
     * Same row shape as student /api/student/fees/payment-history (web-aligned).
     */
    private function formatFeePaymentRow($payment): array
    {
        $method = strtolower(trim((string) ($payment->method ?? '')));
        $status = in_array($method, ['generated', 'installment'], true) ? 'generated' : 'paid';

        $paymentTitle = $payment->payment_title ?? '';
        if (stripos($paymentTitle, 'Monthly Fee') !== false) {
            $feeType = 'monthly_fee';
        } elseif (stripos($paymentTitle, 'transport') !== false) {
            $feeType = 'transport_fee';
        } elseif (stripos($paymentTitle, 'card') !== false) {
            $feeType = 'card_fee';
        } elseif (stripos($paymentTitle, 'Admission Fee') !== false) {
            $feeType = 'admission_fee';
        } elseif (stripos($paymentTitle, 'Custom Fee') !== false || stripos($paymentTitle, 'Library Fee') !== false || stripos($paymentTitle, 'Lab Fee') !== false) {
            $feeType = 'custom_fee';
        } else {
            $feeType = 'other';
        }

        $generatedAmount = 0.0;
        $paidAmount = 0.0;
        if ($status === 'generated') {
            $generatedAmount = (float) $payment->payment_amount - (float) $payment->discount + (float) $payment->late_fee;
        } else {
            $paidAmount = (float) $payment->payment_amount;
        }

        return [
            'id' => $payment->id,
            'payment_title' => $payment->payment_title,
            'payment_amount' => (float) $payment->payment_amount,
            'discount' => (float) $payment->discount,
            'late_fee' => (float) $payment->late_fee,
            'net_amount' => (float) $payment->payment_amount - (float) $payment->discount + (float) $payment->late_fee,
            'method' => $payment->method,
            'status' => $status,
            'fee_type' => $feeType,
            'generated_amount' => $generatedAmount,
            'paid_amount' => $paidAmount,
            'payment_date' => $payment->payment_date ? $payment->payment_date->format('Y-m-d') : null,
            'payment_date_formatted' => $payment->payment_date ? $payment->payment_date->format('d M Y') : null,
            'accountant' => $payment->accountant,
            'sms_notification' => $payment->sms_notification ?? null,
            'created_at' => $payment->created_at ? $payment->created_at->format('Y-m-d H:i:s') : null,
            'created_at_formatted' => $payment->created_at ? $payment->created_at->format('d M Y, h:i A') : null,
        ];
    }

    /**
     * Fee list per child for parent.
     *
     * GET /api/parent/student-fees?fee_year=2025&student_id=12
     *
     * With student_id: also returns data.student, data.search_results, data.latest_payments (Fee Payment web tables).
     *
     * - due_amount: Fee Payment screen (web) — calculateFeeDueLikeWeb (student_code matched case-insensitively)
     * - paid / discount / late_fee: actual payments in fee_year (not Generated/Installment rows)
     * - generated_amount: Generated + Installment rows in that year (reference)
     */
    public function studentFees(Request $request): JsonResponse
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

            $feeYear = (int) $request->get('fee_year', date('Y'));
            if ($feeYear < 2000 || $feeYear > 2100) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid fee_year. Year must be between 2000 and 2100.',
                    'token' => null,
                ], 422);
            }

            $students = $parent->students()
                ->orderBy('class', 'asc')
                ->orderBy('section', 'asc')
                ->orderBy('student_name', 'asc')
                ->get();

            $filterByStudentId = $request->filled('student_id');

            if ($filterByStudentId) {
                $sid = (int) $request->get('student_id');
                $students = $students->where('id', $sid)->values();
                if ($students->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Student not found or not linked to this parent account.',
                        'token' => null,
                    ], 404);
                }
            }

            // Normalize student_code for matching (same rows as web even if casing/spacing differs in DB)
            $normalizedCodes = $students->pluck('student_code')
                ->filter()
                ->map(fn ($c) => strtolower(trim((string) $c)))
                ->unique()
                ->values();

            $allPaymentsByCode = collect();
            if ($normalizedCodes->isNotEmpty()) {
                $allPaymentsByCode = StudentPayment::query()
                    ->whereYear('payment_date', $feeYear)
                    ->where(function ($q) use ($normalizedCodes) {
                        foreach ($normalizedCodes as $norm) {
                            $q->orWhereRaw('LOWER(TRIM(student_code)) = ?', [$norm]);
                        }
                    })
                    ->orderBy('payment_date', 'desc')
                    ->get()
                    ->groupBy(fn ($p) => strtolower(trim((string) ($p->student_code ?? ''))));
            }

            $isGeneratedOrInstallment = static function ($p): bool {
                $m = strtolower(trim((string) ($p->method ?? '')));

                return in_array($m, ['generated', 'installment'], true);
            };

            // Same "Latest Payments" query as web — summary paid/discount/late must match that table (not all paid rows in year).
            $latestPaymentsCache = [];

            $data = $students->map(function ($student) use ($allPaymentsByCode, $feeYear, $isGeneratedOrInstallment, &$latestPaymentsCache) {
                $code = $student->student_code;
                $norm = $code ? strtolower(trim((string) $code)) : '';

                $payments = $norm !== '' && $allPaymentsByCode->has($norm)
                    ? $allPaymentsByCode->get($norm)
                    : collect();

                $generatedPayments = $payments->filter($isGeneratedOrInstallment);
                $paidPayments = $payments->reject($isGeneratedOrInstallment);

                $generatedAmount = (float) $generatedPayments->sum(function ($fee) {
                    $amount = (float) ($fee->payment_amount ?? 0);
                    $discount = (float) ($fee->discount ?? 0);
                    $lateFee = (float) ($fee->late_fee ?? 0);

                    return max(0, $amount - $discount) + $lateFee;
                });

                $generatedTransportFee = (float) $generatedPayments
                    ->filter(function ($fee) {
                        return stripos((string) ($fee->payment_title ?? ''), 'transport') !== false;
                    })
                    ->sum(function ($fee) {
                        return max(0, (float) ($fee->payment_amount ?? 0) - (float) ($fee->discount ?? 0)) + (float) ($fee->late_fee ?? 0);
                    });

                $generatedCardFee = (float) $generatedPayments
                    ->filter(function ($fee) {
                        return stripos((string) ($fee->payment_title ?? ''), 'card') !== false;
                    })
                    ->sum(function ($fee) {
                        return max(0, (float) ($fee->payment_amount ?? 0) - (float) ($fee->discount ?? 0)) + (float) ($fee->late_fee ?? 0);
                    });

                $latest = $norm !== ''
                    ? FeePaymentWebTables::latestPaymentsForStudentCode((string) $student->student_code, $feeYear)
                    : ['rows' => [], 'total_amount_paid' => 0.0];
                $latestPaymentsCache[$student->id] = $latest;

                $latestRows = $latest['rows'];
                $paidAmount = (float) $latest['total_amount_paid'];
                $paidDiscount = round((float) array_sum(array_column($latestRows, 'discount')), 2);
                $paidLateFee = round((float) array_sum(array_column($latestRows, 'late_fee')), 2);
                $paidNetAmount = round(max(0.0, $paidAmount - $paidDiscount + $paidLateFee), 2);

                $monthlyFee = $student->monthly_fee !== null ? (float) $student->monthly_fee : 0.0;
                $annualFee = $monthlyFee * 12;

                $dueAmount = $code ? $this->calculateFeeDueLikeWeb($code) : 0.0;

                // Align yearly due with web due logic, which includes generated heads
                // like transport/card/custom fees and installments.
                $yearlyDueAmount = $dueAmount;

                return [
                    'id' => $student->id,
                    'student_name' => $student->student_name,
                    'student_code' => $student->student_code,
                    'class' => $student->class,
                    'section' => $student->section,
                    'campus' => $student->campus,
                    'fee_year' => $feeYear,
                    'monthly_fee' => round($monthlyFee, 2),
                    'annual_fee' => round($annualFee, 2),
                    'generated_amount' => round($generatedAmount, 2),
                    'transport_fee_generated' => round($generatedTransportFee, 2),
                    'card_fee_generated' => round($generatedCardFee, 2),
                    'paid' => round($paidAmount, 2),
                    'discount' => round($paidDiscount, 2),
                    'late_fee' => round($paidLateFee, 2),
                    'paid_net_amount' => round($paidNetAmount, 2),
                    'due_amount' => round($dueAmount, 2),
                    'yearly_due_amount' => round($yearlyDueAmount, 2),
                    'total_payments' => $payments->count(),
                    'paid_payments_count' => count($latestRows),
                    'generated_payments_count' => $generatedPayments->count(),
                ];
            });

            $payload = [
                'parent_id' => $parent->id,
                'parent_name' => $parent->name,
                'fee_year' => $feeYear,
                'total_students' => $students->count(),
                'students' => $data,
            ];

            if ($filterByStudentId && $data->isNotEmpty()) {
                $stModel = $students->first();
                $searchResults = FeePaymentWebTables::searchResultsForStudent($stModel);
                $latestPayments = $latestPaymentsCache[$stModel->id] ?? FeePaymentWebTables::latestPaymentsForStudentCode(
                    (string) $stModel->student_code,
                    $feeYear
                );

                $payload['student_id'] = (int) $request->get('student_id');
                $row = $data->first();
                $row['search_results'] = $searchResults;
                $row['latest_payments'] = $latestPayments;
                $payload['student'] = $row;
                $payload['students'] = collect([$row]);
                $payload['search_results'] = $searchResults;
                $payload['latest_payments'] = $latestPayments;
            }

            return response()->json([
                'success' => true,
                'message' => 'Student fees list retrieved successfully',
                'data' => $payload,
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving student fees: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }

    /**
     * Single-student fee detail (same structure as student payment-history API / web).
     *
     * GET /api/parent/student-fees/detail?student_id=5&fee_year=2026&per_page=30
     */
    public function studentFeeDetail(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'student_id' => ['required', 'integer', 'min:1'],
                'fee_year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
                'per_page' => ['nullable', 'integer', 'in:10,25,30,50,100'],
            ]);

            $parent = $request->user();

            if (!$parent) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'token' => null,
                ], 404);
            }

            $student = $parent->students()->where('id', (int) $validated['student_id'])->first();

            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student not found or not linked to this parent account.',
                    'token' => null,
                ], 404);
            }

            $studentCode = $student->student_code;
            if (empty($studentCode)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student code not found for this student.',
                    'token' => null,
                ], 400);
            }

            $normalizedStudentCode = strtolower(trim((string) $studentCode));
            $feeYear = isset($validated['fee_year']) ? (int) $validated['fee_year'] : null;
            $perPage = isset($validated['per_page']) ? (int) $validated['per_page'] : 30;

            $query = StudentPayment::whereRaw('LOWER(TRIM(student_code)) = ?', [$normalizedStudentCode]);
            if ($feeYear) {
                $query->whereYear('payment_date', $feeYear);
            }

            $allPaymentsQuery = StudentPayment::whereRaw('LOWER(TRIM(student_code)) = ?', [$normalizedStudentCode]);
            if ($feeYear) {
                $allPaymentsQuery->whereYear('payment_date', $feeYear);
            }
            $allPayments = $allPaymentsQuery->get();

            $allFormatted = $allPayments->map(function ($payment) {
                return $this->formatFeePaymentRow($payment);
            });
            $generatedLines = $allFormatted->filter(fn ($row) => $row['status'] === 'generated')->values();
            $paidLines = $allFormatted->filter(fn ($row) => $row['status'] === 'paid')->values();

            $payments = $query->orderBy('payment_date', 'desc')
                ->orderBy('id', 'desc')
                ->paginate($perPage);

            $paymentHistory = $payments->map(function ($payment) {
                return $this->formatFeePaymentRow($payment);
            });

            $actualPaidPayments = $allPayments->filter(function ($payment) {
                $method = strtolower(trim((string) ($payment->method ?? '')));

                return !in_array($method, ['generated', 'installment'], true);
            });

            $totalPaid = (float) $actualPaidPayments->sum('payment_amount');
            $totalDiscount = (float) $actualPaidPayments->sum('discount');
            $totalLateFee = (float) $actualPaidPayments->sum('late_fee');

            $monthlyFee = $student->monthly_fee !== null ? (float) $student->monthly_fee : 0.0;
            $annualFee = $monthlyFee * 12;

            $yearlyDueAmount = max($annualFee - $totalPaid - $totalDiscount + $totalLateFee, 0.0);
            $feePaymentDue = $this->calculateFeeDueLikeWeb((string) $studentCode);

            return response()->json([
                'success' => true,
                'message' => 'Student fee detail retrieved successfully',
                'data' => [
                    'student' => [
                        'id' => $student->id,
                        'student_name' => $student->student_name,
                        'student_code' => $student->student_code,
                        'class' => $student->class,
                        'section' => $student->section,
                        'campus' => $student->campus,
                    ],
                    'monthly_fee' => $monthlyFee,
                    'fee_summary' => [
                        'monthly_fee' => $monthlyFee,
                        'annual_fee' => $annualFee,
                        'total_paid' => $totalPaid,
                        'total_discount' => $totalDiscount,
                        'total_late_fee' => $totalLateFee,
                        'due_amount' => $feePaymentDue,
                        'total' => $feePaymentDue,
                        'yearly_due_amount' => $yearlyDueAmount,
                    ],
                    'fee_year' => $feeYear ?? (int) date('Y'),
                    'generated_entries' => $generatedLines,
                    'paid_entries' => $paidLines,
                    'payment_history' => $paymentHistory->values(),
                    'pagination' => [
                        'current_page' => $payments->currentPage(),
                        'last_page' => $payments->lastPage(),
                        'per_page' => $payments->perPage(),
                        'total' => $payments->total(),
                        'from' => $payments->firstItem(),
                        'to' => $payments->lastItem(),
                    ],
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'token' => null,
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving student fee detail: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }
}
