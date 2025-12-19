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

        $paymentsByCode = collect();
        if ($studentCodes->isNotEmpty()) {
            $paymentsByCode = StudentPayment::whereIn('student_code', $studentCodes)
                ->whereYear('payment_date', $feeYear)
                ->get()
                ->groupBy('student_code');
        }

        $data = $students->map(function ($student) use ($paymentsByCode, $feeYear) {
            $code = $student->student_code;
            $payments = $code && $paymentsByCode->has($code)
                ? $paymentsByCode->get($code)
                : collect();

            $paid = (float) $payments->sum('payment_amount');
            $discount = (float) $payments->sum('discount');
            $lateFee = (float) $payments->sum('late_fee');

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


