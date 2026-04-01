<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudentPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParentFeeController extends Controller
{
    /**
     * Simple fee list per student for this parent.
     *
     * GET /api/parent/student-fees?fee_year=2025
     *
     * Response per student:
     * - student_name
     * - initial_amount (base monthly_fee from admit)
     * - fee_year
     * - paid (sum of payments in that year)
     * - discount (sum of discount in that year)
     * - late_fee (sum of late_fee in that year)
     * - due_amount (initial_amount - paid - discount + late_fee, minimum 0)
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

            $studentCodes = $students->pluck('student_code')->filter()->values();

            $allPaymentsByCode = collect();
            if ($studentCodes->isNotEmpty()) {
                $allPaymentsByCode = StudentPayment::whereIn('student_code', $studentCodes)
                    ->whereYear('payment_date', $feeYear)
                    ->orderBy('payment_date', 'desc')
                    ->get()
                    ->groupBy('student_code');
            }

            $data = $students->map(function ($student) use ($allPaymentsByCode, $feeYear) {
                $code = $student->student_code;

                $payments = $code && $allPaymentsByCode->has($code)
                    ? $allPaymentsByCode->get($code)
                    : collect();

                $generatedPayments = $payments->filter(function ($p) {
                    return strtolower(trim((string) ($p->method ?? ''))) === 'generated';
                });
                $paidPayments = $payments->reject(function ($p) {
                    return strtolower(trim((string) ($p->method ?? ''))) === 'generated';
                });

                // Generated fees (what system generated as payable)
                $generatedAmount = (float) $generatedPayments->sum(function ($fee) {
                    $amount = (float) ($fee->payment_amount ?? 0);
                    $discount = (float) ($fee->discount ?? 0);
                    $lateFee = (float) ($fee->late_fee ?? 0);
                    return max(0, $amount - $discount) + $lateFee;
                });

                // Actual paid history
                $paidAmount = (float) $paidPayments->sum('payment_amount');
                $paidDiscount = (float) $paidPayments->sum('discount');
                $paidLateFee = (float) $paidPayments->sum('late_fee');
                $paidNetAmount = max(0, $paidAmount - $paidDiscount + $paidLateFee);

                $monthlyFee = $student->monthly_fee !== null ? (float) $student->monthly_fee : 0.0;
                $annualFee = $monthlyFee * 12;

                // Prefer generated fee as due base (web-like), fallback to annual fee
                $expectedFee = $generatedAmount > 0 ? $generatedAmount : $annualFee;
                $dueAmount = max($expectedFee - $paidNetAmount, 0.0);

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
                    'paid' => round($paidAmount, 2),
                    'discount' => round($paidDiscount, 2),
                    'late_fee' => round($paidLateFee, 2),
                    'paid_net_amount' => round($paidNetAmount, 2),
                    'due_amount' => round($dueAmount, 2),
                    'total_payments' => $payments->count(),
                    'paid_payments_count' => $paidPayments->count(),
                    'generated_payments_count' => $generatedPayments->count(),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Student fees list retrieved successfully',
                'data' => [
                    'parent_id' => $parent->id,
                    'parent_name' => $parent->name,
                    'fee_year' => $feeYear,
                    'total_students' => $students->count(),
                    'students' => $data,
                ],
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
}


