<?php

namespace App\Http\Controllers;

use App\Models\StudentPayment;
use App\Models\Student;
use App\Models\StudentDiscount;
use App\Models\Campus;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\AdvanceFee;
use App\Services\AdvanceFeeWallet;
use App\Models\AdminRole;
use App\Models\Message;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StudentPaymentController extends Controller
{
    private function parentAdvanceFeeFor(Student $student): ?AdvanceFee
    {
        if (!empty($student->parent_account_id)) {
            $advanceFee = AdvanceFee::where('parent_id', (string) $student->parent_account_id)->first();
            if ($advanceFee) {
                return $advanceFee;
            }
        }

        if (!empty($student->father_id_card)) {
            return AdvanceFee::where('id_card_number', $student->father_id_card)->first();
        }

        return null;
    }

    private function isWalletMethod(?string $method): bool
    {
        return AdvanceFeeWallet::isWalletMethod($method);
    }

    private function applyWalletPayment(
        Request $request,
        ?Student $student,
        array &$validated,
        float &$lateFee,
        ?string $method,
        ?float $remainingDueBeforePayment = null
    ) {
        if (! $this->isWalletMethod($method)) {
            return null;
        }

        if (!$student) {
            return $this->walletPaymentError($request, "No wallet found for this student's parent. Please use a different payment method.");
        }

        $requestedTotal = max(0, (float) ($validated['payment_amount'] ?? 0));
        if ($requestedTotal <= 0 && $lateFee > 0) {
            $requestedTotal = (float) $lateFee;
        }

        $walletResult = AdvanceFeeWallet::debitForStudent($student, $requestedTotal);
        if (!$walletResult['ok']) {
            return $this->walletPaymentError($request, $walletResult['message']);
        }

        $walletDebit = (float) $walletResult['debited'];
        $discount = max(0, round((float) ($validated['discount'] ?? 0), 2));
        [$gross, $latePaid] = $this->splitCashAcrossPrincipalAndLate(
            $walletDebit,
            $discount,
            max(0, round((float) $lateFee, 2)),
            $remainingDueBeforePayment
        );

        $validated['payment_amount'] = $gross;
        $lateFee = $latePaid;
        $validated['late_fee'] = $latePaid;

        return null;
    }

    /**
     * Ledger + fee-payment UI: {@see StudentPayment::remainingDueForTitle} treats payment rows as
     * gross cash in payment_amount with late_fee as the late slice (principal paid = max(0, amount - late)).
     * Fee Payment partial modal sends payment_amount as total cash received (principal + late combined).
     */
    private function applyLedgerGrossFromPrincipal(array &$validated, float $lateFee, string $method, ?float $remainingDueBeforePayment = null): void
    {
        if ($this->isWalletMethod($method)) {
            return;
        }

        $cash = max(0, round((float) ($validated['payment_amount'] ?? 0), 2));
        $discount = max(0, round((float) ($validated['discount'] ?? 0), 2));
        $unpaidLate = max(0, round((float) $lateFee, 2));

        [$gross, $latePaid] = $this->splitCashAcrossPrincipalAndLate(
            $cash,
            $discount,
            $unpaidLate,
            $remainingDueBeforePayment
        );

        $validated['payment_amount'] = $gross;
        $validated['late_fee'] = $latePaid;
    }

    /**
     * Apply cash to principal first, then any remainder to unpaid late (never attach full unpaid late on partial pay).
     *
     * @return array{0: float, 1: float} [gross payment_amount, late_fee slice]
     */
    private function splitCashAcrossPrincipalAndLate(
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

    private function resolveRecordingAccountantName(): ?string
    {
        if (auth()->guard('accountant')->check()) {
            return auth()->guard('accountant')->user()->name ?? null;
        }

        if (auth()->guard('admin')->check()) {
            return auth()->guard('admin')->user()->name ?? null;
        }

        return auth()->user()->name ?? null;
    }

    private function notifyAdminsAboutAccountantFeePayment(StudentPayment $payment, ?Student $student, string $paymentStatus): void
    {
        $accountant = auth()->guard('accountant')->user();
        if (!$accountant) {
            return;
        }

        $amount = (float) ($payment->payment_amount ?? 0);
        $discount = (float) ($payment->discount ?? 0);
        $lateFee = (float) ($payment->late_fee ?? 0);
        $studentName = $student?->student_name ?? $payment->student_code ?? 'Student';
        $classSection = trim((string) ($student?->class ?? '') . (($student?->section ?? '') !== '' ? ' - ' . $student->section : ''));

        $text = sprintf(
            '%s recorded %s fee payment of %s for %s (%s). Fee: %s. Method: %s. Discount: %s. Late Fee: %s.',
            $accountant->name ?? 'Accountant',
            $paymentStatus,
            number_format($amount, 2),
            $studentName,
            $payment->student_code,
            $payment->payment_title,
            $payment->method,
            number_format($discount, 2),
            number_format($lateFee, 2)
        );

        if ($classSection !== '') {
            $text .= ' Class: ' . $classSection . '.';
        }

        AdminRole::query()
            ->select('id')
            ->orderBy('id')
            ->get()
            ->each(function (AdminRole $admin) use ($accountant, $text) {
                Message::create([
                    'from_type' => 'accountant',
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

    private function walletPaymentError(Request $request, string $message)
    {
        if ($request->ajax() || $request->wantsJson() || $request->header('Accept') === 'application/json' || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 400);
        }

        return redirect()
            ->back()
            ->withInput()
            ->withErrors(['method' => $message]);
    }

    /**
     * Show the student payment form.
     */
    public function create(Request $request): View
    {
        $studentCode = $request->get('student_code');
        $student = null;
        
        if ($studentCode) {
            $student = Student::where('student_code', $studentCode)->first();
        }

        $campuses = Campus::orderBy('campus_name', 'asc')->pluck('campus_name');
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
            $campuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
        }

        $methods = ['Cash Payment', 'Bank Transfer', 'Cheque', 'Online Payment', 'Card Payment', 'Wallet'];
        
        return view('accounting.direct-payment.student', compact('student', 'studentCode', 'campuses', 'methods'));
    }

    public function getStudentByCode(Request $request)
    {
        $studentCode = $request->get('student_code');
        $campus = $request->get('campus');
        
        if (!$studentCode) {
            return response()->json(['success' => false, 'message' => 'Student code is required']);
        }

        $studentQuery = Student::where('student_code', $studentCode);
        
        // Filter by campus if provided
        if ($campus && trim($campus) !== '') {
            $studentQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        
        $student = $studentQuery->first();
        
        if (!$student) {
            $message = $campus && trim($campus) !== '' 
                ? 'Student not found with this code in the selected campus'
                : 'Student not found with this code';
            return response()->json([
                'success' => false,
                'message' => $message
            ]);
        }

        // Use the same logic as Fee Payment search results
        // Include both "Generated" and "Installment" methods as unpaid fees
        $generatedFees = StudentPayment::where('student_code', $studentCode)
            ->whereIn('method', ['Generated', 'Installment'])
            ->get();
        
        // Exclude "Installment" method from paid fees - installments are unpaid fees
        $paidFees = StudentPayment::where('student_code', $studentCode)
            ->where('method', '!=', 'Generated')
            ->where('method', '!=', 'Installment')
            ->get();

        // Get StudentDiscount records for this student
        $studentDiscounts = \App\Models\StudentDiscount::where('student_code', $studentCode)
            ->get();
        $totalStudentDiscount = $studentDiscounts->sum(function($discount) {
            return (float) ($discount->discount_amount ?? 0);
        });

        $unpaidGeneratedFees = [];
        $totalDue = 0;
        $generatedByTitle = $generatedFees->groupBy('payment_title');
        $paidByTitle = $paidFees->groupBy('payment_title');

        // Collect all installment titles and their base fee titles
        // If installments exist for a fee, exclude the original fee title
        $installmentBaseTitles = [];
        foreach ($generatedByTitle as $title => $items) {
            if (preg_match('/^(.+)\/\d+$/', $title, $matches)) {
                $baseTitle = $matches[1];
                $installmentBaseTitles[$baseTitle] = true;
            }
        }

        foreach ($generatedByTitle as $title => $items) {
            $latestGenerated = $items->sortByDesc('id')->first();
            // Check if this is an installment (title ends with /number)
            $isInstallment = preg_match('/\/\d+$/', $title);
            
            // Skip original fee title if installments exist for it
            if (!$isInstallment && isset($installmentBaseTitles[$title])) {
                continue;
            }
            
            // Check if this is a monthly fee (title starts with "Monthly Fee - ")
            $isMonthlyFee = str_starts_with($title, 'Monthly Fee - ');

            $paidForTitle = StudentPayment::paidLedgerRowsForLatestGeneratedTitle(
                $paidByTitle->get($title, collect()),
                $latestGenerated
            );

            // Calculate original amount (before discount) from generated records
            $originalAmount = $items->sum(function ($item) {
                return (float) ($item->payment_amount ?? 0);
            });
            
            $generatedLate = $items->sum(function ($item) {
                return (float) ($item->late_fee ?? 0);
            });
            
            $generatedDiscount = $items->sum(function ($item) {
                return (float) ($item->discount ?? 0);
            });
            
            // Discount from payment records (for regular fees or additional discounts on installments)
            $paidDiscount = $paidForTitle->sum(function ($item) {
                return (float) ($item->discount ?? 0);
            });
            
            // Apply StudentDiscount to monthly fees
            // Student discount should NOT be applied to installments - only to full (non-installment) fees
            $appliedStudentDiscount = 0;
            if ($isMonthlyFee && $totalStudentDiscount > 0 && !$isInstallment) {
                // Only apply student discount to regular (non-installment) monthly fees
                $appliedStudentDiscount = round($totalStudentDiscount, 2);
            }
            
            // Total discount = generated discount (for installments) + payment discount + student discount (only for non-installment fees)
            $totalDiscount = $generatedDiscount + $paidDiscount + $appliedStudentDiscount;
            
            // Generated Fee = Original Amount - Total Discount + Late Fee
            $generatedFee = max(0, $originalAmount - $totalDiscount) + $generatedLate;
            
            // Calculate paid amounts
            $paidAmountOnly = $paidForTitle->sum(function ($item) {
                $amount = (float) ($item->payment_amount ?? 0);
                $late = (float) ($item->late_fee ?? 0);
                return max(0, $amount - $late);
            });
            $paidLate = $paidForTitle->sum(function ($item) {
                return (float) ($item->late_fee ?? 0);
            });
            
            // Calculate remaining amounts
            $remainingAmount = max(0, ($originalAmount - $totalDiscount) - $paidAmountOnly);
            $remainingLate = max(0, $generatedLate - $paidLate);
            $remainingTotal = $remainingAmount + $remainingLate;

            // Only include if there's an unpaid balance (same as Fee Payment search)
            if ($remainingTotal > 0) {
                // Return in format expected by dropdown: id, payment_title, payment_amount, late_fee, payment_date, discount
                // Use the latest generated record's ID and date, but calculate the remaining amount
                $unpaidGeneratedFees[] = [
                    'id' => $latestGenerated->id,
                    'payment_title' => $title,
                    'payment_amount' => round($remainingTotal, 2), // Remaining amount to pay
                    'late_fee' => round($remainingLate, 2),
                    'payment_date' => $latestGenerated->payment_date,
                    'discount' => round($totalDiscount, 2),
                ];
                $totalDue += $remainingTotal;
            }
        }

        // Sort by payment_date ascending (oldest first)
        usort($unpaidGeneratedFees, function($a, $b) {
            return strtotime($a['payment_date'] ?? '1970-01-01') - strtotime($b['payment_date'] ?? '1970-01-01');
        });

        // Get latest fee title if available
        $latestFee = !empty($unpaidGeneratedFees) ? $unpaidGeneratedFees[0] : null;
        $feeTitle = $latestFee ? $latestFee['payment_title'] : '';

        return response()->json([
            'success' => true,
            'student' => [
                'student_code' => $student->student_code,
                'student_name' => $student->student_name,
                'campus' => $student->campus,
                'class' => $student->class,
                'section' => $student->section,
                'monthly_fee' => $student->monthly_fee ?? 0,
            ],
            'generated_fees' => $unpaidGeneratedFees,
            'fee_due' => $totalDue,
            'fee_title' => $feeTitle
        ]);
    }

    /**
     * Get students by campus (AJAX).
     */
    public function getStudentsByCampus(Request $request)
    {
        $campus = $request->get('campus');
        
        if (!$campus) {
            return response()->json(['success' => false, 'students' => []]);
        }

        $students = Student::whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))])
            ->whereNotNull('student_code')
            ->where('student_code', '!=', '')
            ->orderBy('student_name', 'asc')
            ->get(['id', 'student_code', 'student_name', 'campus', 'class', 'section', 'monthly_fee']);

        return response()->json([
            'success' => true,
            'students' => $students
        ]);
    }

    /**
     * Store a newly created student payment.
     */
    public function store(Request $request)
    {
        try {
            try {
                $validated = $request->validate([
                    'campus' => ['nullable', 'string', 'max:255'],
                    'student_code' => ['required', 'string', 'max:255'],
                    'payment_title' => ['required', 'string', 'max:255'],
                    'payment_amount' => ['required', 'numeric', 'min:0'],
                    'discount' => ['nullable', 'numeric', 'min:0'],
                    'method' => ['required', 'string', 'max:255'],
                    'payment_date' => ['required', 'date'],
                    'sms_notification' => ['required', 'string', 'in:Yes,No'],
                    'generated_id' => ['nullable', 'integer'],
                ]);
            } catch (\Illuminate\Validation\ValidationException $e) {
                // If request expects JSON, return JSON response with validation errors
                if ($request->ajax() || $request->wantsJson() || $request->header('Accept') === 'application/json' || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => $e->errors()
                    ], 422);
                }
                throw $e;
            }

        $validated['discount'] = round((float) ($validated['discount'] ?? 0), 2);

        // Initialize late_fee
        $lateFee = 0;

        // Get student to find campus, class, section for fee calculations
        $student = Student::where('student_code', $validated['student_code'])->first();

        // Check if this is an installment (payment_title contains /number pattern)
        $isInstallment = preg_match('/\/\d+$/', $validated['payment_title']);
        $isInstallmentCreation = $isInstallment && empty($validated['generated_id']);
        
        // Store original method for wallet deduction
        $originalMethod = $validated['method'] ?? 'Cash Payment';
        
        // Only newly created installments should be stored as Generated (unpaid).
        // When paying an existing installment (generated_id present), keep actual payment method.
        if ($isInstallmentCreation) {
            $validated['method'] = 'Generated'; // Installments should be unpaid (Generated) initially
        }
        
        // Check if there's an existing generated fee record for this student and title
        $existingFee = null;
        
        // For newly created installments, always create new records.
        // For installment payments, allow matching existing generated row.
        if (!$isInstallmentCreation) {
            if (!empty($validated['generated_id'])) {
                $existingFee = StudentPayment::ledgerActive()
                    ->where('id', $validated['generated_id'])
                    ->where('student_code', $validated['student_code'])
                    ->whereIn('method', ['Generated', 'Installment'])
                    ->first();

                if ($existingFee && $existingFee->payment_title) {
                    $validated['payment_title'] = $existingFee->payment_title;
                }
            }

            if (!$existingFee) {
                $existingFee = StudentPayment::ledgerActive()
                    ->where('student_code', $validated['student_code'])
                    ->where('payment_title', $validated['payment_title'])
                    ->whereIn('method', ['Generated', 'Installment'])
                    ->orderByDesc('id')
                    ->first();
            }
        }

        if ($existingFee) {
            $generatedLate = StudentPayment::ledgerActive()
                ->where('student_code', $validated['student_code'])
                ->where('payment_title', $validated['payment_title'])
                ->whereIn('method', ['Generated', 'Installment'])
                ->sum('late_fee');
            $paidFeesForTitle = StudentPayment::ledgerActive()
                ->where('student_code', $validated['student_code'])
                ->where('payment_title', $validated['payment_title'])
                ->whereNotIn('method', ['Generated', 'Installment'])
                ->get();
            $paidFeesForTitle = StudentPayment::paidLedgerRowsForLatestGeneratedTitle($paidFeesForTitle, $existingFee);
            $paidLate = (float) $paidFeesForTitle->sum(function ($item) {
                return (float) ($item->late_fee ?? 0);
            });
            $lateFee = max(0, (float) $generatedLate - $paidLate);

            $totalStudentDiscount = (float) StudentDiscount::where('student_code', $validated['student_code'])
                ->get()
                ->sum(fn ($discount) => (float) ($discount->discount_amount ?? 0));

            $remainingDueBeforePayment = StudentPayment::remainingDueForTitle(
                $validated['student_code'],
                $validated['payment_title'],
                $totalStudentDiscount
            );
            $maxPayableWithThisRequest = round($remainingDueBeforePayment, 2);

            $paymentAmount = round((float) ($validated['payment_amount'] ?? 0), 2);
            $discountAmount = round((float) ($validated['discount'] ?? 0), 2);

            // Payment + discount both reduce due (not additive beyond remaining due).
            $maxDiscountAllowed = max(0, round($maxPayableWithThisRequest - $paymentAmount, 2));
            $maxPaymentAllowed = max(0, round($maxPayableWithThisRequest - $discountAmount, 2));
            $totalCredit = round($paymentAmount + $discountAmount, 2);

            if ($discountAmount > $maxDiscountAllowed + 0.02) {
                $errorMessage = $maxPayableWithThisRequest <= 0.02
                    ? 'No due amount remains to apply a discount.'
                    : 'Discount cannot be greater than ' . number_format($maxDiscountAllowed, 2)
                        . ' (remaining due after payment amount).';

                if ($request->ajax() || $request->wantsJson() || $request->header('Accept') === 'application/json' || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage,
                    ], 422);
                }

                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['discount' => $errorMessage]);
            }

            if ($paymentAmount > $maxPaymentAllowed + 0.02) {
                $errorMessage = 'Payment Amount cannot be greater than ' . number_format($maxPaymentAllowed, 2)
                    . ($discountAmount > 0 ? ' (remaining due after discount).' : '.');

                if ($request->ajax() || $request->wantsJson() || $request->header('Accept') === 'application/json' || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage,
                    ], 422);
                }

                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['payment_amount' => $errorMessage]);
            }

            if ($totalCredit > $maxPayableWithThisRequest + 0.02) {
                $errorMessage = 'Payment Amount and Discount combined cannot be greater than the current Due Amount.';

                if ($request->ajax() || $request->wantsJson() || $request->header('Accept') === 'application/json' || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage,
                    ], 422);
                }

                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['payment_amount' => $errorMessage]);
            }

            $isPartialPayment = $maxPayableWithThisRequest > 0 && $totalCredit < $maxPayableWithThisRequest - 0.02;

            if ($isPartialPayment) {
                // Partial payment: keep generated fee and add a paid record
                $recordingAccountant = $this->resolveRecordingAccountantName();
                if ($recordingAccountant !== null) {
                    $validated['accountant'] = $recordingAccountant;
                }
                // Ensure campus is persisted for reports (Accounts Summary filters by campus).
                // Some fee-payment flows submit campus empty on partial payments, so default it.
                if (empty($validated['campus'])) {
                    $validated['campus'] = $existingFee?->campus ?: ($student?->campus ?: null);
                }

                $walletResponse = $this->applyWalletPayment(
                    $request,
                    $student,
                    $validated,
                    $lateFee,
                    $originalMethod,
                    $remainingDueBeforePayment
                );
                if ($walletResponse) {
                    return $walletResponse;
                }
                $this->applyLedgerGrossFromPrincipal($validated, $lateFee, $originalMethod, $remainingDueBeforePayment);

                $payment = StudentPayment::create($validated);
                $this->notifyAdminsAboutAccountantFeePayment($payment, $student, 'partial');

                $successMessage = 'Payment recorded successfully!';
                if ($lateFee > 0) {
                    $successMessage .= " Late fee of " . number_format($lateFee, 2) . " has been added.";
                }

                if ($request->ajax() || $request->wantsJson() || $request->header('Accept') === 'application/json' || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                    return response()->json([
                        'success' => true,
                        'message' => $successMessage,
                        'payment' => [
                            'id' => $payment->id,
                            'student_code' => $payment->student_code,
                            'student_name' => $student->student_name ?? null,
                            'father_name' => $student->father_name ?? null,
                            'class' => $student->class ?? null,
                            'section' => $student->section ?? null,
                            'payment_title' => $payment->payment_title,
                            'payment_amount' => (float) ($payment->payment_amount ?? 0),
                            'discount' => (float) ($payment->discount ?? 0),
                            'late_fee' => (float) ($payment->late_fee ?? 0),
                            'payment_date' => $payment->payment_date ? Carbon::parse($payment->payment_date)->format('d-m-Y h:i:s A') : null,
                            'accountant' => $payment->accountant ?? null,
                        ],
                    ]);
                }

                return redirect()
                    ->route('accounting.direct-payment.student')
                    ->with('success', $successMessage);
            }

            $walletResponse = $this->applyWalletPayment(
                $request,
                $student,
                $validated,
                $lateFee,
                $originalMethod,
                $remainingDueBeforePayment
            );
            if ($walletResponse) {
                return $walletResponse;
            }
            $this->applyLedgerGrossFromPrincipal($validated, $lateFee, $originalMethod, $remainingDueBeforePayment);

            // Update the existing generated fee record with actual payment details
            // Ensure campus is persisted (some older generated rows were created without campus),
            // otherwise campus-filtered reports (Detailed Income / Accounts Summary) may miss paid rows.
            if (empty($validated['campus'])) {
                $validated['campus'] = $existingFee?->campus ?: ($student?->campus ?: null);
            }
            $existingFee->update([
                'campus' => $validated['campus'],
                'payment_amount' => $validated['payment_amount'],
                'discount' => $validated['discount'] ?? 0,
                'method' => $validated['method'],
                'payment_date' => $validated['payment_date'],
                'sms_notification' => $validated['sms_notification'],
                'late_fee' => $lateFee,
                'accountant' => $this->resolveRecordingAccountantName(),
            ]);

            $successMessage = 'Payment recorded successfully!';
            if ($lateFee > 0) {
                $successMessage .= " Late fee of " . number_format($lateFee, 2) . " has been added.";
            }

            $payment = $existingFee->fresh();
            $this->notifyAdminsAboutAccountantFeePayment($payment, $student, 'paid');

            if ($request->ajax() || $request->wantsJson() || $request->header('Accept') === 'application/json' || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => true,
                    'message' => $successMessage,
                    'payment' => [
                        'id' => $payment->id,
                        'student_code' => $payment->student_code,
                        'student_name' => $student->student_name ?? null,
                        'father_name' => $student->father_name ?? null,
                        'class' => $student->class ?? null,
                        'section' => $student->section ?? null,
                        'payment_title' => $payment->payment_title,
                        'payment_amount' => (float) ($payment->payment_amount ?? 0),
                        'discount' => (float) ($payment->discount ?? 0),
                        'late_fee' => (float) ($payment->late_fee ?? 0),
                        'payment_date' => $payment->payment_date ? Carbon::parse($payment->payment_date)->format('d-m-Y h:i:s A') : null,
                        'accountant' => $payment->accountant ?? null,
                    ],
                ]);
            }

            return redirect()
                ->route('accounting.direct-payment.student')
                ->with('success', $successMessage);
        }
        
        // Add late_fee to validated data
        $validated['late_fee'] = $lateFee;
        
        // Add accountant if available
        $recordingAccountant = $this->resolveRecordingAccountantName();
        if ($recordingAccountant !== null) {
            $validated['accountant'] = $recordingAccountant;
        }

        // If payment method is "Wallet", deduct only the amount covered by the parent wallet.
        if (!$isInstallment) {
            $walletResponse = $this->applyWalletPayment($request, $student, $validated, $lateFee, $originalMethod);
            if ($walletResponse) {
                return $walletResponse;
            }
        }

        $validated['late_fee'] = $lateFee;
        $this->applyLedgerGrossFromPrincipal($validated, $lateFee, $originalMethod);

        try {
            $payment = StudentPayment::create($validated);
            if ($isInstallmentCreation) {
                StudentPayment::removeOrphanedBaseGeneratedForInstallment(
                    $validated['student_code'],
                    $validated['payment_title']
                );
            }
        } catch (\Exception $e) {
            // If request expects JSON, return JSON response with error
            if ($request->ajax() || $request->wantsJson() || $request->header('Accept') === 'application/json' || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'message' => 'Error creating payment record: ' . $e->getMessage()
                ], 500);
            }
            throw $e;
        }

        $successMessage = 'Payment recorded successfully!';
        $this->notifyAdminsAboutAccountantFeePayment($payment, $student, $isInstallmentCreation ? 'installment' : 'paid');
        if ($lateFee > 0) {
            $successMessage .= " Late fee of " . number_format($lateFee, 2) . " has been added.";
        }

        // If request is AJAX or expects JSON, return JSON response
        if ($request->ajax() || $request->wantsJson() || $request->header('Accept') === 'application/json' || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'message' => $successMessage,
            'payment' => [
                'id' => $payment->id,
                'student_code' => $payment->student_code,
                'student_name' => $student->student_name ?? null,
                'father_name' => $student->father_name ?? null,
                'class' => $student->class ?? null,
                'section' => $student->section ?? null,
                'payment_title' => $payment->payment_title,
                'payment_amount' => (float) ($payment->payment_amount ?? 0),
                'discount' => (float) ($payment->discount ?? 0),
                'late_fee' => (float) ($payment->late_fee ?? 0),
                'payment_date' => $payment->payment_date ? Carbon::parse($payment->payment_date)->format('d-m-Y h:i:s A') : null,
                'accountant' => $payment->accountant ?? null,
            ],
            ]);
        }

        return redirect()
            ->route('accounting.direct-payment.student')
            ->with('success', $successMessage);
        } catch (\Exception $e) {
            // Catch any unexpected exceptions and return JSON response
            if ($request->ajax() || $request->wantsJson() || $request->header('Accept') === 'application/json' || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                \Log::error('Error in StudentPaymentController@store: ' . $e->getMessage(), [
                    'exception' => $e,
                    'request_data' => $request->all()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Error creating payment: ' . $e->getMessage()
                ], 500);
            }
            throw $e;
        }
    }
}

