<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class FamilyVoucherController extends Controller
{
    /**
     * Show the family vouchers page with filters.
     */
    public function index(Request $request): View
    {
        $copyTypes = [
            'three_copies' => 'Three Copy',
            'two_copies' => 'Two Copy',
            'one_copy' => 'One Copy',
        ];
        $months = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December',
        ];

        // Group students by parent (using father_name or parent_id)
        $query = Student::select(
            DB::raw('COALESCE(father_name, "Unknown") as parent_name'),
            DB::raw('GROUP_CONCAT(DISTINCT student_name) as student_names'),
            DB::raw('GROUP_CONCAT(DISTINCT student_code) as student_codes'),
            DB::raw('GROUP_CONCAT(DISTINCT class) as classes'),
            DB::raw('GROUP_CONCAT(DISTINCT section) as sections'),
            DB::raw('MAX(campus) as campus'),
            DB::raw('COUNT(*) as student_count')
        )
        ->groupBy('father_name');
        
        // Apply filters
        if ($request->filled('campus')) {
            $query->where('campus', $request->campus);
        }
        
        // Type and vouchers_for are filter options, not stored in Student model
        // They will be used for voucher generation
        
        $families = $query->orderBy('parent_name')->paginate(20)->withQueryString();
        
        return view('accounting.fee-voucher.family', compact('families', 'copyTypes', 'months'));
    }

    public function print(Request $request): View
    {
        $parentName = $request->get('parent_name');
        $type = $request->get('type', 'three_copies');
        $vouchersFor = $request->get('vouchers_for', date('F'));
        $currentYear = date('Y');

        $copyMap = [
            'three_copies' => ['PARENT COPY', 'SCHOOL COPY', 'STUDENT COPY'],
            'two_copies' => ['PARENT COPY', 'SCHOOL COPY'],
            'one_copy' => ['PARENT COPY'],
        ];
        $copyLabels = $copyMap[$type] ?? $copyMap['three_copies'];

        $studentsQuery = Student::whereNotNull('student_code')
            ->where('student_code', '!=', '');
        if ($parentName) {
            $studentsQuery->whereRaw('LOWER(TRIM(father_name)) = ?', [strtolower(trim($parentName))]);
        }
        if ($request->filled('campus')) {
            $studentsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($request->campus))]);
        }
        $students = $studentsQuery->orderBy('student_name')->get();

        $vouchers = [];
        foreach ($students as $student) {
            $pendingPayments = \App\Models\StudentPayment::where('student_code', $student->student_code)
                ->where('method', 'Generated')
                ->orderBy('payment_date', 'asc')
                ->get();

            $subtotal = $pendingPayments->sum(function ($payment) {
                return (float) ($payment->payment_amount ?? 0) - (float) ($payment->discount ?? 0);
            });

            $feeHistory = [];
            $months = ['January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'];
            foreach ($months as $month) {
                $paymentTitle = "Monthly Fee - {$month} {$currentYear}";
                $payment = \App\Models\StudentPayment::where('student_code', $student->student_code)
                    ->where('payment_title', $paymentTitle)
                    ->first();
                $feeHistory[$month] = [
                    'total' => $payment ? (float) $payment->payment_amount : 0,
                    'paid' => $payment && $payment->method !== 'Generated' ? (float) $payment->payment_amount : 0,
                ];
            }

            $lateFee = $pendingPayments->sum(function ($payment) {
                return (float) ($payment->late_fee ?? 0);
            });

            $dynamicLateFee = 0;
            foreach ($pendingPayments as $payment) {
                if ((float) ($payment->late_fee ?? 0) > 0) {
                    continue;
                }
                if (!preg_match('/Monthly Fee - (\w+) (\d{4})/i', $payment->payment_title ?? '', $matches)) {
                    continue;
                }
                $feeMonth = $matches[1];
                $feeYear = $matches[2];
                $dueDateForPayment = $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date) : null;
                if (!$dueDateForPayment || !$dueDateForPayment->lt(\Carbon\Carbon::today())) {
                    continue;
                }

                $monthlyFeeRecord = \App\Models\MonthlyFee::where('fee_month', $feeMonth)
                    ->where('fee_year', $feeYear)
                    ->where(function ($q) use ($student) {
                        $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($student->campus ?? ''))])
                            ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($student->class ?? ''))])
                            ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($student->section ?? ''))]);
                    })
                    ->first();

                if ($monthlyFeeRecord && (float) $monthlyFeeRecord->late_fee > 0) {
                    $dynamicLateFee += (float) $monthlyFeeRecord->late_fee;
                }
            }
            $lateFee += $dynamicLateFee;

            $latestDueDate = null;
            if ($pendingPayments->isNotEmpty()) {
                $maxDate = $pendingPayments->max(function ($payment) {
                    return $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date) : null;
                });
                if ($maxDate) {
                    $latestDueDate = $maxDate;
                }
            }

            if (!$latestDueDate) {
                $monthlyFeeRecord = \App\Models\MonthlyFee::where('fee_month', $vouchersFor)
                    ->where('fee_year', $currentYear)
                    ->where(function ($q) use ($student) {
                        $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($student->campus ?? ''))])
                            ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($student->class ?? ''))])
                            ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($student->section ?? ''))]);
                    })
                    ->first();
                $latestDueDate = $monthlyFeeRecord ? \Carbon\Carbon::parse($monthlyFeeRecord->due_date) : \Carbon\Carbon::now()->addDays(15);
            }

            $currentFeesSubtotal = $subtotal;
            $arrearsPayments = $pendingPayments->filter(function ($payment) {
                if (!$payment->payment_date) {
                    return false;
                }
                $dueDate = \Carbon\Carbon::parse($payment->payment_date);
                return $dueDate->lt(\Carbon\Carbon::today());
            });
            $arrearsAmount = $arrearsPayments->sum(function ($payment) {
                return (float) ($payment->payment_amount ?? 0) - (float) ($payment->discount ?? 0);
            });

            $total = $currentFeesSubtotal + $arrearsAmount + $lateFee;
            $afterDueDate = $total;

            $pendingFeesList = $pendingPayments->map(function ($payment) {
                return [
                    'description' => $payment->payment_title,
                    'amount' => (float) ($payment->payment_amount ?? 0) - (float) ($payment->discount ?? 0),
                    'sort_order' => 1,
                ];
            });

            $vouchers[] = [
                'student' => $student,
                'pending_fees' => $pendingFeesList,
                'current_fees_subtotal' => $currentFeesSubtotal,
                'arrears_amount' => $arrearsAmount,
                'subtotal' => $currentFeesSubtotal + $arrearsAmount,
                'late_fee' => $lateFee,
                'total' => $total,
                'after_due_date' => $afterDueDate,
                'voucher_validity' => $latestDueDate->copy()->addDays(10),
                'due_date' => $latestDueDate,
                'voucher_number' => 'FV-' . $student->student_code . '-' . date('ymd'),
                'month' => $vouchersFor,
                'year' => $currentYear,
                'fee_history' => $feeHistory,
            ];
        }

        return view('accounting.fee-voucher.print', compact('vouchers', 'copyLabels'));
    }
}

