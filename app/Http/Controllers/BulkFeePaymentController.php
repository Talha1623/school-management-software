<?php

namespace App\Http\Controllers;

use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentDiscount;
use App\Models\StudentPayment;
use App\Models\Subject;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        $query = StudentPayment::query()
            ->ledgerActive()
            ->with('student')
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

        $payments = $query->orderByDesc('id')->get();

        $studentDiscountCache = [];
        $seenTitleKeys = [];
        $items = collect();

        foreach ($payments as $payment) {
            $student = $payment->student;

            if (! $student) {
                continue;
            }

            if ($campus && $student->campus) {
                if (strtolower(trim($student->campus)) !== strtolower(trim($campus))) {
                    continue;
                }
            }

            $titleKey = strtolower(trim($payment->student_code)) . '|' . strtolower(trim((string) $payment->payment_title));
            if (isset($seenTitleKeys[$titleKey])) {
                continue;
            }
            $seenTitleKeys[$titleKey] = true;

            $studentCode = $payment->student_code;
            if (! array_key_exists($studentCode, $studentDiscountCache)) {
                $studentDiscountCache[$studentCode] = (float) StudentDiscount::where('student_code', $studentCode)
                    ->get()
                    ->sum(fn ($discount) => (float) ($discount->discount_amount ?? 0));
            }

            $dueParts = StudentPayment::remainingDuePartsForTitle(
                $studentCode,
                (string) $payment->payment_title,
                $studentDiscountCache[$studentCode]
            );

            if ($dueParts['total'] <= 0.02) {
                continue;
            }

            $items->push([
                'generated_id' => $payment->id,
                'student_code' => $studentCode,
                'student_name' => $student->student_name ?? 'N/A',
                'parent_name' => $student->father_name ?? 'N/A',
                'payment_title' => $payment->payment_title ?? 'N/A',
                'amount' => $dueParts['amount'],
                'late_fee' => $dueParts['late_fee'],
                'total_due' => $dueParts['total'],
                'payment' => 0,
                'discount' => 0,
                'payment_date' => now()->format('Y-m-d'),
                'fully_paid' => 'No',
            ]);
        }

        $items = $items->values();

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

    /**
     * Classes for selected campus (AJAX — same logic as head-wise dues; isolated URL for Bulk Fee Payment).
     */
    public function ajaxClassesByCampus(Request $request): JsonResponse
    {
        try {
            $campus = $request->get('campus');
            if (! $campus || trim((string) $campus) === '') {
                return response()->json(['classes' => []]);
            }

            $campusNorm = strtolower(trim((string) $campus));

            $classes = ClassModel::query()
                ->whereNotNull('class_name')
                ->whereRaw('LOWER(TRIM(campus)) = ?', [$campusNorm])
                ->distinct()
                ->pluck('class_name')
                ->sort()
                ->values();

            if ($classes->isEmpty()) {
                $fromStudents = Student::query()
                    ->whereNotNull('class')
                    ->whereRaw('LOWER(TRIM(campus)) = ?', [$campusNorm])
                    ->distinct()
                    ->pluck('class')
                    ->sort()
                    ->values();
                $classes = $fromStudents->isEmpty()
                    ? collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th'])
                    : $fromStudents;
            }

            $classes = $classes->map(fn ($c) => trim((string) $c))
                ->filter(fn ($c) => $c !== '')
                ->unique()
                ->sort()
                ->values();

            return response()->json(['classes' => $classes]);
        } catch (\Throwable $e) {
            \Log::error('bulk_fee.ajaxClassesByCampus', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'classes' => [],
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Sections for selected class/campus (AJAX — case-insensitive + student/subject fallbacks).
     */
    public function ajaxSectionsByClass(Request $request): JsonResponse
    {
        try {
            $class = $request->get('class');
            $campus = $request->get('campus');

            if (! $class || trim((string) $class) === '') {
                return response()->json(['sections' => []]);
            }

            $classNorm = strtolower(trim((string) $class));
            $campusNorm = $campus && trim((string) $campus) !== '' ? strtolower(trim((string) $campus)) : null;

            $sectionsQuery = Section::query()
                ->whereNotNull('name')
                ->whereRaw('LOWER(TRIM(class)) = ?', [$classNorm]);
            if ($campusNorm !== null) {
                $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [$campusNorm]);
            }
            $sections = $sectionsQuery->distinct()->pluck('name')->sort()->values();

            if ($sections->isEmpty()) {
                $fromStudents = Student::query()
                    ->whereNotNull('section')
                    ->whereRaw('LOWER(TRIM(class)) = ?', [$classNorm]);
                if ($campusNorm !== null) {
                    $fromStudents->whereRaw('LOWER(TRIM(campus)) = ?', [$campusNorm]);
                }
                $sections = $fromStudents->distinct()->pluck('section')->sort()->values();
            }

            if ($sections->isEmpty()) {
                $fromSubjects = Subject::query()
                    ->whereNotNull('section')
                    ->whereRaw('LOWER(TRIM(class)) = ?', [$classNorm]);
                if ($campusNorm !== null) {
                    $fromSubjects->whereRaw('LOWER(TRIM(campus)) = ?', [$campusNorm]);
                }
                $sections = $fromSubjects->distinct()->pluck('section')->sort()->values();
            }

            $sections = $sections->map(fn ($s) => trim((string) $s))
                ->filter(fn ($s) => $s !== '')
                ->unique()
                ->sort()
                ->values();

            $payload = $sections->map(fn ($name) => ['id' => null, 'name' => $name])->values();

            return response()->json(['sections' => $payload]);
        } catch (\Throwable $e) {
            \Log::error('bulk_fee.ajaxSectionsByClass', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'sections' => [],
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }
}
