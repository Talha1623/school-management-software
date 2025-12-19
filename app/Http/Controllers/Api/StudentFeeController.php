<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudentPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentFeeController extends Controller
{
    /**
     * Get Student Fee Details
     * Returns fee information for the logged-in student
     * 
     * GET /api/student/fees?fee_year=2025
     * 
     * Response:
     * - student_name
     * - initial_amount (base monthly_fee from admit)
     * - fee_year
     * - paid (sum of payments in that year)
     * - discount (sum of discount in that year)
     * - late_fee (sum of late_fee in that year)
     * - due_amount (initial_amount - paid - discount + late_fee, minimum 0)
     * - payment_history (optional, if requested)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getFees(Request $request): JsonResponse
    {
        try {
            $student = $request->user();

            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'token' => null,
                ], 404);
            }

            $feeYear = (int) $request->get('fee_year', date('Y'));
            $includeHistory = $request->get('include_history', false);

            // Get student code
            $studentCode = $student->student_code;

            if (!$studentCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student code not found',
                    'token' => null,
                ], 400);
            }

            // Get payments for this student in the selected year
            $payments = StudentPayment::where('student_code', $studentCode)
                ->whereYear('payment_date', $feeYear)
                ->orderBy('payment_date', 'desc')
                ->get();

            // Calculate totals
            $paid = (float) $payments->sum('payment_amount');
            $discount = (float) $payments->sum('discount');
            $lateFee = (float) $payments->sum('late_fee');

            // Initial amount is the monthly_fee from student record
            $initialAmount = $student->monthly_fee !== null ? (float) $student->monthly_fee : 0.0;
            
            // Calculate due amount: initial_amount - paid - discount + late_fee
            // Late fee is added to due, discount is subtracted
            $dueAmount = max($initialAmount - $paid - $discount + $lateFee, 0.0);

            // Prepare payment history if requested
            $paymentHistory = null;
            if ($includeHistory) {
                $paymentHistory = $payments->map(function ($payment) {
                    return [
                        'id' => $payment->id,
                        'payment_title' => $payment->payment_title,
                        'payment_amount' => (float) $payment->payment_amount,
                        'discount' => (float) $payment->discount,
                        'late_fee' => (float) $payment->late_fee,
                        'method' => $payment->method,
                        'payment_date' => $payment->payment_date ? $payment->payment_date->format('Y-m-d') : null,
                        'payment_date_formatted' => $payment->payment_date ? $payment->payment_date->format('d M Y') : null,
                        'accountant' => $payment->accountant,
                        'created_at' => $payment->created_at ? $payment->created_at->format('Y-m-d H:i:s') : null,
                    ];
                })->values();
            }

            return response()->json([
                'success' => true,
                'message' => 'Student fee details retrieved successfully',
                'data' => [
                    'student' => [
                        'id' => $student->id,
                        'student_name' => $student->student_name,
                        'student_code' => $student->student_code,
                        'class' => $student->class,
                        'section' => $student->section,
                        'campus' => $student->campus,
                    ],
                    'fee_year' => $feeYear,
                    'fee_summary' => [
                        'initial_amount' => $initialAmount,
                        'paid' => $paid,
                        'discount' => $discount,
                        'late_fee' => $lateFee,
                        'due_amount' => $dueAmount,
                    ],
                    'payment_history' => $paymentHistory,
                    'total_payments' => $payments->count(),
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving fee details: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }

    /**
     * Get Payment History
     * Returns detailed payment history for the logged-in student
     * 
     * GET /api/student/fees/payment-history?fee_year=2025&per_page=30
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getPaymentHistory(Request $request): JsonResponse
    {
        try {
            $student = $request->user();

            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'token' => null,
                ], 404);
            }

            $studentCode = $student->student_code;

            if (!$studentCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student code not found',
                    'token' => null,
                ], 400);
            }

            $feeYear = $request->get('fee_year');
            $perPage = $request->get('per_page', 30);
            $perPage = in_array((int)$perPage, [10, 25, 30, 50, 100], true) ? (int)$perPage : 30;

            // Build query
            $query = StudentPayment::where('student_code', $studentCode);

            // Filter by year if provided
            if ($feeYear) {
                $query->whereYear('payment_date', $feeYear);
            }

            // Pagination
            $payments = $query->orderBy('payment_date', 'desc')
                ->orderBy('id', 'desc')
                ->paginate($perPage);

            // Format payment history
            $paymentHistory = $payments->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'payment_title' => $payment->payment_title,
                    'payment_amount' => (float) $payment->payment_amount,
                    'discount' => (float) $payment->discount,
                    'late_fee' => (float) $payment->late_fee,
                    'net_amount' => (float) $payment->payment_amount - (float) $payment->discount + (float) $payment->late_fee,
                    'method' => $payment->method,
                    'payment_date' => $payment->payment_date ? $payment->payment_date->format('Y-m-d') : null,
                    'payment_date_formatted' => $payment->payment_date ? $payment->payment_date->format('d M Y') : null,
                    'accountant' => $payment->accountant,
                    'sms_notification' => $payment->sms_notification,
                    'created_at' => $payment->created_at ? $payment->created_at->format('Y-m-d H:i:s') : null,
                    'created_at_formatted' => $payment->created_at ? $payment->created_at->format('d M Y, h:i A') : null,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Payment history retrieved successfully',
                'data' => [
                    'student' => [
                        'id' => $student->id,
                        'student_name' => $student->student_name,
                        'student_code' => $student->student_code,
                    ],
                    'payment_history' => $paymentHistory,
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

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving payment history: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }
}

