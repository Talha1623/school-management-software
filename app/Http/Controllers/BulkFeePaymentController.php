<?php

namespace App\Http\Controllers;

use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentPayment;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BulkFeePaymentController extends Controller
{
    public function index(): View
    {
        // Get campuses from Campus model first (primary source)
        $campuses = \App\Models\Campus::whereNotNull('campus_name')
            ->orderBy('campus_name', 'asc')
            ->pluck('campus_name')
            ->values();
        
        // If no campuses found in Campus model, get from other sources
        if ($campuses->isEmpty()) {
            $campusesFromStudents = Student::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $campusesFromClasses = ClassModel::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $campusesFromSections = Section::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $campuses = $campusesFromStudents
                ->merge($campusesFromClasses)
                ->merge($campusesFromSections)
                ->unique()
                ->sort()
                ->values();
        }

        // Filter campuses for accountant
        $isAccountantRoute = request()->route()->getName() === 'accountant.bulk-fee-payment';
        if ($isAccountantRoute && auth()->guard('accountant')->check()) {
            $accountant = auth()->guard('accountant')->user();
            if ($accountant && $accountant->campus) {
                $campuses = $campuses->filter(function ($campus) use ($accountant) {
                    return $campus === $accountant->campus;
                })->values();
            }
        }

        $classes = ClassModel::orderBy('class_name', 'asc')->get();

        $feeTypes = StudentPayment::whereNotNull('payment_title')
            ->distinct()
            ->orderBy('payment_title', 'asc')
            ->pluck('payment_title')
            ->values();

        // Determine which view to use based on route
        $viewName = $isAccountantRoute ? 'accountant.bulk-fee-payment' : 'accounting.parent-wallet.bulk-fee-payment';
        
        return view($viewName, compact('campuses', 'classes', 'feeTypes'));
    }

    public function data(Request $request): JsonResponse
    {
        $campus = $request->get('campus');
        $class = $request->get('class');
        $section = $request->get('section');
        $feeType = $request->get('fee_type');

        // Filter by accountant campus if accountant route
        $isAccountantRoute = request()->route()->getName() === 'accountant.bulk-fee-payment.data';
        if ($isAccountantRoute && auth()->guard('accountant')->check()) {
            $accountant = auth()->guard('accountant')->user();
            if ($accountant && $accountant->campus) {
                // Override campus filter with accountant's campus
                $campus = $accountant->campus;
            }
        }

        $query = StudentPayment::with('student')
            ->where('method', 'Generated');

        // Always filter by campus if provided, and ensure student exists and is not deleted
        $query->whereHas('student', function ($q) use ($campus, $class, $section) {
            // Exclude deleted students (check if soft deletes are used)
            if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive(\App\Models\Student::class))) {
                $q->withoutTrashed();
            }
            
            // Filter by campus (required)
            if ($campus) {
                $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            
            // Filter by class if provided
            if ($class) {
                $q->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
            }
            
            // Filter by section if provided
            if ($section) {
                $q->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($section))]);
            }
        });

        // Also filter StudentPayment by campus for consistency
        if ($campus) {
            $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }

        if ($feeType) {
            $query->whereRaw('LOWER(TRIM(payment_title)) = ?', [strtolower(trim($feeType))]);
        }

        $payments = $query->orderBy('payment_date', 'desc')->get();

        $items = $payments->map(function ($payment) use ($campus) {
            $student = $payment->student;
            
            // Skip if student is deleted or doesn't exist
            if (!$student) {
                return null;
            }
            
            // Double-check campus filter (in case payment campus doesn't match student campus)
            if ($campus && $student->campus) {
                if (strtolower(trim($student->campus)) !== strtolower(trim($campus))) {
                    return null;
                }
            }
            
            $amount = (float) $payment->payment_amount;
            $lateFee = (float) ($payment->late_fee ?? 0);

            $paidBase = StudentPayment::where('student_code', $payment->student_code)
                ->where('payment_title', $payment->payment_title)
                ->where('method', '!=', 'Generated')
                ->sum(DB::raw('COALESCE(payment_amount,0) + COALESCE(discount,0)'));
            $paidLate = StudentPayment::where('student_code', $payment->student_code)
                ->where('payment_title', $payment->payment_title)
                ->where('method', '!=', 'Generated')
                ->sum(DB::raw('COALESCE(late_fee,0)'));

            $remainingAmount = max($amount - (float) $paidBase, 0);
            $remainingLate = max($lateFee - (float) $paidLate, 0);
            $totalDue = $remainingAmount + $remainingLate;

            if ($totalDue <= 0) {
                return null;
            }

            return [
                'generated_id' => $payment->id,
                'student_code' => $payment->student_code,
                'student_name' => $student->student_name ?? 'N/A',
                'parent_name' => $student->father_name ?? 'N/A',
                'payment_title' => $payment->payment_title ?? 'N/A',
                'amount' => $remainingAmount,
                'late_fee' => $remainingLate,
                'total_due' => $totalDue,
                'payment' => 0,
                'discount' => 0,
                'payment_date' => now()->format('Y-m-d'),
                'fully_paid' => 'No',
            ];
        })->filter()->values();

        return response()->json(['items' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $items = $request->input('items', []);
        if (!is_array($items) || empty($items)) {
            return response()->json(['success' => false, 'message' => 'No payment data found.'], 422);
        }

        // Get accountant name based on guard
        $accountantName = 'System';
        if (auth()->guard('accountant')->check()) {
            $accountantName = auth()->guard('accountant')->user()->name ?? 'System';
        } elseif (auth()->guard('admin')->check()) {
            $accountantName = auth()->guard('admin')->user()->name ?? 'System';
        } elseif (auth()->check()) {
            $accountantName = auth()->user()->name ?? null;
        }
        $saved = 0;

        foreach ($items as $item) {
            $generatedId = (int) ($item['generated_id'] ?? 0);
            $paymentAmount = (float) ($item['payment'] ?? 0);
            $discount = (float) ($item['discount'] ?? 0);
            $lateFee = (float) ($item['late_fee'] ?? 0);
            $paymentDate = !empty($item['payment_date'])
                ? $item['payment_date']
                : now()->format('Y-m-d');

            if ($generatedId <= 0) {
                continue;
            }

            $generatedFee = StudentPayment::where('id', $generatedId)
                ->where('method', 'Generated')
                ->first();
            if (!$generatedFee) {
                continue;
            }

            if ($lateFee >= 0) {
                $generatedFee->late_fee = $lateFee;
                $generatedFee->save();
            }

            $totalGenerated = (float) ($generatedFee->payment_amount ?? 0) + (float) ($generatedFee->late_fee ?? 0);
            $paidBaseNow = $paymentAmount + $discount;
            $paidLateNow = ($paidBaseNow >= $totalGenerated) ? (float) ($generatedFee->late_fee ?? 0) : 0;
            $totalPaidNow = $paidBaseNow + $paidLateNow;

            if ($totalPaidNow <= 0) {
                continue;
            }

            StudentPayment::create([
                'campus' => $generatedFee->campus,
                'student_code' => $generatedFee->student_code,
                'payment_title' => $generatedFee->payment_title,
                'payment_amount' => $paymentAmount,
                'discount' => $discount,
                'method' => 'Bulk Payment',
                'payment_date' => $paymentDate,
                'sms_notification' => 'Yes',
                'late_fee' => $paidLateNow,
                'accountant' => $accountantName,
            ]);

            $saved++;
        }

        return response()->json([
            'success' => true,
            'saved' => $saved,
        ]);
    }
}
