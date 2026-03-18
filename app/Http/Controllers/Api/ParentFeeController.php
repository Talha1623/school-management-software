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
        $parent = $request->user();

        if (!$parent) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $feeYear = (int) $request->get('fee_year', date('Y'));

        $students = $parent->students()
            ->orderBy('class', 'asc')
            ->orderBy('section', 'asc')
            ->orderBy('student_name', 'asc')
            ->get();

        // Aggregate payments per student_code for the selected year
        $studentCodes = $students->pluck('student_code')->filter()->values();

        // Get paid payments (exclude "Generated" method payments)
        $paymentsByCode = collect();
        if ($studentCodes->isNotEmpty()) {
            $paymentsByCode = StudentPayment::whereIn('student_code', $studentCodes)
                ->where('method', '!=', 'Generated') // Only actual paid payments, exclude generated fees
                ->whereYear('payment_date', $feeYear)
                ->get()
                ->groupBy('student_code');
        }

        // Get generated fees (method = 'Generated')
        $generatedFeesByCode = collect();
        if ($studentCodes->isNotEmpty()) {
            $generatedFeesByCode = StudentPayment::whereIn('student_code', $studentCodes)
                ->where('method', '=', 'Generated') // Only generated fees
                ->whereYear('payment_date', $feeYear)
                ->get()
                ->groupBy('student_code');
        }

        $data = $students->map(function ($student) use ($paymentsByCode, $generatedFeesByCode, $feeYear) {
            $code = $student->student_code;
            
            // Get paid payments
            $payments = $code && $paymentsByCode->has($code)
                ? $paymentsByCode->get($code)
                : collect();

            // Get generated fees
            $generatedFees = $code && $generatedFeesByCode->has($code)
                ? $generatedFeesByCode->get($code)
                : collect();

            // Calculate paid amount from actual paid payments (method != 'Generated')
            $paid = (float) $payments->sum('payment_amount');
            $discount = (float) $payments->sum('discount');
            $lateFee = (float) $payments->sum('late_fee');

            // Calculate generated fees amount
            // Generated amount = payment_amount - discount + late_fee (for generated records)
            $generatedAmount = (float) $generatedFees->sum(function($fee) {
                $amount = (float) ($fee->payment_amount ?? 0);
                $discount = (float) ($fee->discount ?? 0);
                $lateFee = (float) ($fee->late_fee ?? 0);
                return max(0, $amount - $discount) + $lateFee;
            });

            $initialAmount = $student->monthly_fee !== null ? (float) $student->monthly_fee : 0.0;
            $dueAmount = max($initialAmount - $paid - $discount + $lateFee, 0.0);

            return [
                'id' => $student->id,
                'student_name' => $student->student_name,
                'student_code' => $student->student_code,
                'class' => $student->class,
                'section' => $student->section,
                'campus' => $student->campus,
                'fee_year' => $feeYear,
                // base fee from admit student (monthly_fee)
                'initial_amount' => $initialAmount,
                'generated_amount' => round($generatedAmount, 2), // Total generated fees amount
                'paid' => $paid,
                'discount' => $discount,
                'late_fee' => $lateFee,
                'due_amount' => $dueAmount,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Student fees list retrieved successfully',
            'data' => [
                'parent_id' => $parent->id,
                'parent_name' => $parent->name,
                'total_students' => $students->count(),
                'students' => $data,
            ],
        ], 200);
    }
}


