<?php

namespace App\Http\Controllers;

use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BulkFeePaymentController extends Controller
{
    public function index(): View
    {
        $campuses = Student::whereNotNull('campus')
            ->distinct()
            ->pluck('campus')
            ->sort()
            ->values();

        $campuses = $campuses
            ->merge(\App\Models\Campus::whereNotNull('campus_name')->pluck('campus_name'))
            ->merge(ClassModel::whereNotNull('campus')->distinct()->pluck('campus'))
            ->merge(Section::whereNotNull('campus')->distinct()->pluck('campus'))
            ->unique()
            ->sort()
            ->values();

        $classes = ClassModel::orderBy('class_name', 'asc')->get();

        $feeTypes = StudentPayment::whereNotNull('payment_title')
            ->distinct()
            ->orderBy('payment_title', 'asc')
            ->pluck('payment_title')
            ->values();

        return view('accounting.parent-wallet.bulk-fee-payment', compact('campuses', 'classes', 'feeTypes'));
    }

    public function data(Request $request): JsonResponse
    {
        $campus = $request->get('campus');
        $class = $request->get('class');
        $section = $request->get('section');
        $feeType = $request->get('fee_type');

        $query = StudentPayment::with('student')
            ->where('method', 'Generated');

        if ($campus) {
            $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }

        if ($feeType) {
            $query->whereRaw('LOWER(TRIM(payment_title)) = ?', [strtolower(trim($feeType))]);
        }

        if ($class || $section) {
            $query->whereHas('student', function ($q) use ($class, $section) {
                if ($class) {
                    $q->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
                }
                if ($section) {
                    $q->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($section))]);
                }
            });
        }

        $payments = $query->orderBy('payment_date', 'desc')->get();

        $items = $payments->map(function ($payment) {
            $student = $payment->student;
            $amount = (float) $payment->payment_amount;
            $lateFee = (float) ($payment->late_fee ?? 0);
            $totalDue = $amount + $lateFee;

            return [
                'student_code' => $payment->student_code,
                'student_name' => $student->student_name ?? 'N/A',
                'parent_name' => $student->father_name ?? 'N/A',
                'payment_title' => $payment->payment_title ?? 'N/A',
                'amount' => $amount,
                'late_fee' => $lateFee,
                'total_due' => $totalDue,
                'payment' => 0,
                'discount' => 0,
                'payment_date' => now()->format('Y-m-d'),
                'fully_paid' => $totalDue <= 0 ? 'Yes' : 'No',
            ];
        })->values();

        return response()->json(['items' => $items]);
    }
}
