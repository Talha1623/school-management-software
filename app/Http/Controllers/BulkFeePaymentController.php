<?php

namespace App\Http\Controllers;

use App\Models\AdminRole;
use App\Models\ClassModel;
use App\Models\Message;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentPayment;
use App\Models\Subject;
use App\Services\FeePaymentWebTables;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BulkFeePaymentController extends Controller
{
    /**
     * Bulk Fee Payment fee-type filter options (value => label).
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function bulkFeeTypeOptions(): array
    {
        return [
            ['value' => 'monthly_fee', 'label' => 'Monthly Fee'],
            ['value' => 'transport_fee', 'label' => 'Transport Fee'],
            ['value' => 'admission_fee', 'label' => 'Admission Fee'],
            ['value' => 'arrears', 'label' => 'Arrears'],
            ['value' => 'custom_fee', 'label' => 'Custom Fee'],
            ['value' => 'cards_fee', 'label' => 'Cards Fee'],
        ];
    }

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
        $bulkFeeTypes = self::bulkFeeTypeOptions();

        // Determine which view to use based on route
        $viewName = $isAccountantRoute ? 'accountant.bulk-fee-payment' : 'accounting.parent-wallet.bulk-fee-payment';
        
        return view($viewName, compact('campuses', 'classes', 'bulkFeeTypes'));
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
            $this->applyBulkFeeTypeFilter($query, $feeType);
        }

        $payments = $query->orderByDesc('id')->get();
        $feeTypeFilter = $feeType ? strtolower(trim((string) $feeType)) : '';

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

            if ($feeTypeFilter !== '' && ! $this->paymentTitleMatchesBulkFeeType((string) $payment->payment_title, $feeTypeFilter)) {
                continue;
            }

            $titleKey = strtolower(trim($payment->student_code)) . '|' . strtolower(trim((string) $payment->payment_title));
            if (isset($seenTitleKeys[$titleKey])) {
                continue;
            }
            $seenTitleKeys[$titleKey] = true;

            $studentCode = $payment->student_code;

            $dueParts = FeePaymentWebTables::outstandingDuePartsForTitle(
                $student,
                (string) $payment->payment_title
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
        $totalPaid = 0.0;
        $totalDiscount = 0.0;
        $totalLateFee = 0.0;
        $campuses = [];
        $feeTitles = [];

        foreach ($items as $item) {
            $generatedId = (int) ($item['generated_id'] ?? 0);
            $paymentAmount = round((float) ($item['payment'] ?? 0), 2);
            $discount = round((float) ($item['discount'] ?? 0), 2);
            $lateFeeFromForm = max(0, round((float) ($item['late_fee'] ?? 0), 2));
            $paymentDate = ! empty($item['payment_date'])
                ? $item['payment_date']
                : now()->format('Y-m-d');

            if ($generatedId <= 0) {
                continue;
            }

            $generatedFee = StudentPayment::ledgerActive()
                ->where('id', $generatedId)
                ->whereIn('method', ['Generated', 'Installment'])
                ->first();
            if (! $generatedFee) {
                continue;
            }

            $studentCode = (string) $generatedFee->student_code;
            $title = (string) $generatedFee->payment_title;

            $student = Student::where('student_code', $studentCode)->first();
            if (! $student) {
                continue;
            }

            if ($lateFeeFromForm > 0.0001) {
                $this->persistGeneratedLateFeeForTitle($studentCode, $title, $lateFeeFromForm);
                $generatedFee->refresh();
            }

            $dueParts = FeePaymentWebTables::outstandingDuePartsForTitle($student, $title);
            $lateFee = max(
                $lateFeeFromForm,
                round((float) ($dueParts['late_fee'] ?? 0), 2)
            );
            $remainingDueBeforePayment = round((float) ($dueParts['total'] ?? 0), 2);
            $maxPayable = $remainingDueBeforePayment;

            if ($paymentAmount <= 0.02 && $discount <= 0.02) {
                if ($lateFeeFromForm > 0.0001) {
                    $saved++;
                }
                continue;
            }

            if ($maxPayable <= 0.02) {
                continue;
            }

            if ($discount > $maxPayable + 0.02) {
                continue;
            }

            $maxCash = max(0, round($maxPayable - $discount, 2));
            if ($paymentAmount > $maxCash + 0.02) {
                $paymentAmount = $maxCash;
            }

            $totalCredit = round($paymentAmount + $discount, 2);
            if ($totalCredit <= 0.02) {
                continue;
            }

            $isPartialPayment = $maxPayable > 0 && $totalCredit < $maxPayable - 0.02;

            $payload = [
                'campus' => $generatedFee->campus,
                'student_code' => $studentCode,
                'payment_title' => $title,
                'payment_amount' => $paymentAmount,
                'discount' => $discount,
                'method' => 'Bulk Payment',
                'payment_date' => $paymentDate,
                'sms_notification' => 'Yes',
                'late_fee' => $lateFee,
                'accountant' => $accountantName,
            ];

            $this->applyBulkPaymentLedger(
                $payload,
                $lateFee,
                $remainingDueBeforePayment,
                preserveEnteredCash: ! $isPartialPayment
            );

            if ($isPartialPayment) {
                $this->persistGeneratedLateFeeForTitle($studentCode, $title, $lateFee);
                StudentPayment::create($payload);
            } else {
                $generatedFee->update([
                    'campus' => $payload['campus'],
                    'payment_amount' => $payload['payment_amount'],
                    'discount' => $payload['discount'],
                    'method' => $payload['method'],
                    'payment_date' => $payload['payment_date'],
                    'sms_notification' => $payload['sms_notification'],
                    'late_fee' => $payload['late_fee'],
                    'accountant' => $accountantName,
                ]);
                $generatedFee->touch();
            }

            $saved++;
            $totalPaid += (float) ($payload['payment_amount'] ?? 0);
            $totalDiscount += $discount;
            $totalLateFee += (float) ($payload['late_fee'] ?? 0);

            if (! empty($generatedFee->campus)) {
                $campuses[(string) $generatedFee->campus] = true;
            }
            if (! empty($title)) {
                $feeTitles[$title] = true;
            }
        }

        if ($saved > 0 && auth()->guard('accountant')->check()) {
            $this->notifyAdminsAboutAccountantBulkFeePayment(
                $saved,
                $totalPaid,
                $totalDiscount,
                $totalLateFee,
                array_keys($campuses),
                array_keys($feeTitles)
            );
        }

        return response()->json([
            'success' => true,
            'saved' => $saved,
        ]);
    }

    private function notifyAdminsAboutAccountantBulkFeePayment(
        int $savedCount,
        float $totalPaid,
        float $totalDiscount,
        float $totalLateFee,
        array $campuses,
        array $feeTitles
    ): void {
        $accountant = auth()->guard('accountant')->user();
        if (!$accountant) {
            return;
        }

        $campusText = !empty($campuses) ? implode(', ', $campuses) : 'N/A';
        $feeText = !empty($feeTitles) ? implode(', ', $feeTitles) : 'N/A';

        $text = sprintf(
            '%s recorded bulk fee payment for %d student(s). Total Paid: %s. Discount: %s. Late Fee: %s. Campus: %s. Fee: %s.',
            $accountant->name ?? 'Accountant',
            $savedCount,
            number_format($totalPaid, 2),
            number_format($totalDiscount, 2),
            number_format($totalLateFee, 2),
            $campusText,
            $feeText
        );

        AdminRole::query()
            ->select('id')
            ->orderBy('id')
            ->get()
            ->each(function (AdminRole $admin) use ($accountant, $text) {
                Message::create([
                    'from_type' => 'accountant_notification',
                    'from_id' => $accountant->id,
                    'to_type' => 'admin',
                    'to_id' => $admin->id,
                    'text' => $text,
                    'attachment_path' => null,
                    'attachment_type' => null,
                    'read_at' => null,
                ]);
            });
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

    /**
     * @param  array<string, mixed>  $payload
     */
    private function applyBulkPaymentLedger(
        array &$payload,
        float $unpaidLate,
        ?float $remainingDueBeforePayment,
        bool $preserveEnteredCash = false
    ): void {
        $cash = max(0, round((float) ($payload['payment_amount'] ?? 0), 2));
        $discount = max(0, round((float) ($payload['discount'] ?? 0), 2));
        $unpaidLate = max(0, round($unpaidLate, 2));

        [$gross, $latePaid] = $this->splitBulkCashAcrossPrincipalAndLate(
            $cash,
            $discount,
            $unpaidLate,
            $remainingDueBeforePayment
        );

        if ($preserveEnteredCash) {
            $payload['payment_amount'] = $cash;
            $payload['late_fee'] = $latePaid;

            return;
        }

        $payload['payment_amount'] = $gross;
        $payload['late_fee'] = $latePaid;
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function splitBulkCashAcrossPrincipalAndLate(
        float $cash,
        float $discount,
        float $unpaidLate,
        ?float $remainingDueBeforePayment
    ): array {
        if ($cash <= 0.0001) {
            return [0.0, 0.0];
        }

        if ($remainingDueBeforePayment === null || $remainingDueBeforePayment <= 0.0001) {
            $latePaid = min($unpaidLate, $cash);

            return [round($cash, 2), round($latePaid, 2)];
        }

        $totalDue = round($remainingDueBeforePayment, 2);
        $unpaidPrincipal = max(0, round($totalDue - $unpaidLate, 2));
        $principalAfterDiscount = max(0, round($unpaidPrincipal - $discount, 2));
        $principalPaid = min($principalAfterDiscount, $cash);
        $remainder = max(0, round($cash - $principalPaid, 2));
        $latePaid = min($unpaidLate, $remainder);

        return [round($principalPaid + $latePaid, 2), round($latePaid, 2)];
    }

    /**
     * Store late fee on generated/installment rows so Fee Payment shows the same amount.
     */
    private function persistGeneratedLateFeeForTitle(string $studentCode, string $title, float $lateFee): void
    {
        $lateFee = max(0, round($lateFee, 2));
        if ($lateFee <= 0.0001) {
            return;
        }

        StudentPayment::ledgerActive()
            ->where('student_code', $studentCode)
            ->where('payment_title', $title)
            ->whereIn('method', ['Generated', 'Installment'])
            ->update(['late_fee' => $lateFee]);
    }

    private function applyBulkFeeTypeFilter($query, string $feeType): void
    {
        $category = $this->normalizeBulkFeeTypeKey($feeType);
        if ($category === '') {
            return;
        }

        $query->where(function ($inner) use ($category) {
            match ($category) {
                'monthly_fee' => $inner->whereRaw('LOWER(TRIM(payment_title)) LIKE ?', ['monthly fee%']),
                'transport_fee' => $inner->whereRaw('LOWER(TRIM(payment_title)) LIKE ?', ['transport fee%']),
                'admission_fee' => $inner->whereRaw('LOWER(TRIM(payment_title)) LIKE ?', ['admission fee%']),
                'arrears' => $inner->where(function ($q) {
                    $q->whereRaw('LOWER(TRIM(payment_title)) LIKE ?', ['%arrear%'])
                        ->orWhereRaw('LOWER(TRIM(payment_title)) LIKE ?', ['%arrears%']);
                }),
                'cards_fee' => $inner->where(function ($q) {
                    $q->whereRaw('LOWER(TRIM(payment_title)) LIKE ?', ['%card fee%'])
                        ->orWhereRaw('LOWER(TRIM(payment_title)) LIKE ?', ['%card fees%'])
                        ->orWhereRaw('LOWER(TRIM(payment_title)) LIKE ?', ['%cards fee%'])
                        ->orWhereRaw('LOWER(TRIM(payment_title)) LIKE ?', ['%cards fees%']);
                }),
                'custom_fee' => $inner->whereRaw('LOWER(TRIM(payment_title)) NOT LIKE ?', ['monthly fee%'])
                    ->whereRaw('LOWER(TRIM(payment_title)) NOT LIKE ?', ['transport fee%'])
                    ->whereRaw('LOWER(TRIM(payment_title)) NOT LIKE ?', ['admission fee%'])
                    ->whereRaw('LOWER(TRIM(payment_title)) NOT LIKE ?', ['%arrear%'])
                    ->whereRaw('LOWER(TRIM(payment_title)) NOT LIKE ?', ['%card fee%'])
                    ->whereRaw('LOWER(TRIM(payment_title)) NOT LIKE ?', ['%card fees%'])
                    ->whereRaw('LOWER(TRIM(payment_title)) NOT LIKE ?', ['%cards fee%'])
                    ->whereRaw('LOWER(TRIM(payment_title)) NOT LIKE ?', ['%cards fees%']),
                default => null,
            };
        });
    }

    private function normalizeBulkFeeTypeKey(string $feeType): string
    {
        $key = strtolower(trim($feeType));
        $aliases = [
            'monthly fee' => 'monthly_fee',
            'transport fee' => 'transport_fee',
            'admission fee' => 'admission_fee',
            'arrears' => 'arrears',
            'arrear' => 'arrears',
            'custom fee' => 'custom_fee',
            'cards fee' => 'cards_fee',
            'card fee' => 'cards_fee',
            'card fees' => 'cards_fee',
            'cards fees' => 'cards_fee',
        ];

        if (isset($aliases[$key])) {
            return $aliases[$key];
        }

        $allowed = array_column(self::bulkFeeTypeOptions(), 'value');

        return in_array($key, $allowed, true) ? $key : '';
    }

    private function paymentTitleMatchesBulkFeeType(string $paymentTitle, string $feeType): bool
    {
        $category = $this->normalizeBulkFeeTypeKey($feeType);
        if ($category === '') {
            return true;
        }

        $head = strtolower($this->bulkFeeHeadFromTitle($paymentTitle));

        return match ($category) {
            'monthly_fee' => str_starts_with($head, 'monthly fee'),
            'transport_fee' => str_starts_with($head, 'transport fee'),
            'admission_fee' => str_starts_with($head, 'admission fee'),
            'arrears' => str_contains($head, 'arrear'),
            'cards_fee' => (bool) preg_match('/\b(cards?)\s*fees?\b/i', $head),
            'custom_fee' => ! str_starts_with($head, 'monthly fee')
                && ! str_starts_with($head, 'transport fee')
                && ! str_starts_with($head, 'admission fee')
                && ! str_contains($head, 'arrear')
                && ! preg_match('/\b(cards?)\s*fees?\b/i', $head),
            default => true,
        };
    }

    private function bulkFeeHeadFromTitle(string $paymentTitle): string
    {
        $title = trim($paymentTitle);
        if ($title === '') {
            return '';
        }

        $parts = preg_split('/\s[-–—]\s/u', $title, 2);

        return trim((string) ($parts[0] ?? $title));
    }
}
