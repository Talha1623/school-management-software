<?php

namespace App\Http\Controllers;

use App\Models\CustomPayment;
use App\Models\Accountant;
use App\Models\AdminRole;
use App\Models\Campus;
use App\Models\ClassModel;
use App\Models\Message;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentDiscount;
use App\Models\StudentPayment;
use App\Services\FeePaymentWebTables;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CustomPaymentController extends Controller
{
    /**
     * Show the custom payment form.
     */
    public function create(Request $request): View
    {
        $studentCode = $request->get('student_code');

        $campuses = Campus::orderBy('campus_name', 'asc')->pluck('campus_name');
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
            $campuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
        }

        $methods = ['Cash Payment', 'Bank Transfer', 'Cheque', 'Online Payment', 'Card Payment'];
        $accountants = Accountant::orderBy('name')->get();

        return view('accounting.direct-payment.custom', compact('campuses', 'methods', 'accountants', 'studentCode'));
    }

    /**
     * Get accountants by campus for custom payment.
     */
    public function getAccountantsByCampus(Request $request)
    {
        $campus = $request->get('campus');
        $query = Accountant::query()->orderBy('name', 'asc');
        if ($campus) {
            $query->where('campus', $campus);
        }
        $accountants = $query->get(['id', 'name', 'campus']);

        return response()->json($accountants);
    }

    /**
     * Store a newly created custom payment (Super Admin).
     */
    public function store(Request $request): RedirectResponse
    {
        return $this->recordPayment(
            $request,
            'accounting.direct-payment.custom',
            $request->input('notify_admin', 'Yes')
        );
    }

    /**
     * Store custom payment and settle student_payments ledger (Fee Payment reads this).
     */
    public function recordPayment(Request $request, string $redirectRoute, string $notifyAdmin = 'Yes'): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['nullable', 'string', 'max:255'],
            'student_code' => ['required', 'string', 'max:255'],
            'payment_title' => ['required', 'string', 'max:255'],
            'payment_amount' => ['required', 'numeric', 'min:0'],
            'accountant' => ['nullable', 'string', 'max:255'],
            'method' => ['required', 'string', 'max:255'],
            'payment_date' => ['nullable', 'date'],
            'generated_id' => ['nullable', 'integer'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'notify_admin' => ['nullable', 'string', 'in:Yes,No'],
        ]);

        if (empty($validated['payment_date'])) {
            $validated['payment_date'] = now()->toDateString();
        }

        if (empty($validated['accountant'])) {
            $validated['accountant'] = $this->resolveRecordingAccountantName();
        }

        $validated = $this->resolveStudentForPayment($validated);

        $smsNotification = $notifyAdmin === 'No' ? 'No' : 'Yes';
        $discountAmount = (float) $request->input('discount', 0);

        $this->recordOnStudentLedger($validated, $discountAmount, $smsNotification);

        if (auth()->guard('accountant')->check()) {
            $student = Student::where('student_code', $validated['student_code'])->first();
            if ($student) {
                $this->notifyAdminsAboutAccountantCustomPayment($validated, $student);
            }
        }

        return redirect()
            ->route($redirectRoute)
            ->with('success', 'Payment recorded successfully! Fee Payment ledger has been updated.');
    }

    /**
     * Always post to student_payments (Fee Payment). Settles generated fee when one exists;
     * otherwise records a paid row for the title/amount entered on the form.
     *
     * @param  array<string, mixed>  $validated
     */
    private function recordOnStudentLedger(array $validated, float $discountAmount, string $smsNotification): string
    {
        $generatedId = request()->input('generated_id');
        $paymentAmount = round((float) ($validated['payment_amount'] ?? 0), 2);

        $existingFee = $this->findUnpaidGeneratedFee(
            $validated['student_code'],
            $validated['payment_title'],
            $generatedId ? (int) $generatedId : null
        );

        if ($existingFee) {
            $remainingDue = $this->getRemainingDue($validated['student_code'], (string) $existingFee->payment_title);

            // Without explicit fee selection, do not settle a different fee than the amount entered.
            if (! $generatedId && $paymentAmount > $remainingDue + 0.02) {
                $existingFee = null;
            }
        }

        if ($existingFee) {
            if ($paymentAmount > $this->getRemainingDue($validated['student_code'], (string) $existingFee->payment_title) + 0.02) {
                $validated['payment_amount'] = $this->getRemainingDue(
                    $validated['student_code'],
                    (string) $existingFee->payment_title
                );
            }

            $this->settleGeneratedFeeOnLedger($validated, $existingFee, $discountAmount, $smsNotification);

            return (string) $existingFee->payment_title;
        }

        $title = trim((string) $validated['payment_title']);
        $method = (string) ($validated['method'] ?? 'Cash Payment');
        $paymentAmount = round((float) ($validated['payment_amount'] ?? 0), 2);
        $discountAmount = round($discountAmount, 2);

        $payload = [
            'campus' => $validated['campus'] ?? null,
            'student_code' => $validated['student_code'],
            'payment_title' => $title,
            'payment_amount' => $paymentAmount,
            'discount' => $discountAmount,
            'method' => $method,
            'payment_date' => $validated['payment_date'],
            'sms_notification' => $smsNotification,
            'accountant' => $validated['accountant'] ?? null,
            'late_fee' => 0,
        ];

        $this->applyLedgerGrossFromPrincipal($payload, 0, $method, null);

        StudentPayment::create($payload);

        return $title;
    }

    /**
     * Apply payment to the latest generated fee row (same rules as Student Payment).
     *
     * @param  array<string, mixed>  $validated
     */
    private function settleGeneratedFeeOnLedger(
        array $validated,
        StudentPayment $existingFee,
        float $discountAmount,
        string $smsNotification
    ): void {
        $studentCode = $validated['student_code'];
        $title = (string) $existingFee->payment_title;
        $method = (string) ($validated['method'] ?? 'Cash Payment');

        $student = Student::where('student_code', $studentCode)->first();
        $dueParts = $student
            ? FeePaymentWebTables::outstandingDuePartsForTitle($student, $title)
            : ['late_fee' => 0.0, 'total' => 0.0];

        $lateFee = (float) ($dueParts['late_fee'] ?? 0);
        $remainingDueBeforePayment = (float) ($dueParts['total'] ?? 0);

        if ($remainingDueBeforePayment <= 0.02) {
            return;
        }

        $maxPayableWithThisRequest = round($remainingDueBeforePayment, 2);
        $paymentAmount = round((float) ($validated['payment_amount'] ?? 0), 2);
        $discountAmount = round($discountAmount, 2);

        if ($discountAmount > $maxPayableWithThisRequest + 0.02) {
            throw ValidationException::withMessages([
                'discount' => $maxPayableWithThisRequest <= 0.02
                    ? 'No due amount remains to apply a discount.'
                    : 'Discount cannot be greater than ' . number_format($maxPayableWithThisRequest, 2) . '.',
            ]);
        }

        $maxCashAfterDiscount = max(0, round($maxPayableWithThisRequest - $discountAmount, 2));
        if ($paymentAmount > $maxCashAfterDiscount + 0.02) {
            $paymentAmount = $maxCashAfterDiscount;
        }

        $totalCredit = round($paymentAmount + $discountAmount, 2);
        if ($totalCredit > $maxPayableWithThisRequest + 0.02) {
            $paymentAmount = max(0, round($maxPayableWithThisRequest - $discountAmount, 2));
            $totalCredit = round($paymentAmount + $discountAmount, 2);
        }

        $payload = [
            'campus' => $validated['campus'] ?? null,
            'student_code' => $studentCode,
            'payment_title' => $title,
            'payment_amount' => $paymentAmount,
            'discount' => $discountAmount,
            'method' => $method,
            'payment_date' => $validated['payment_date'],
            'sms_notification' => $smsNotification,
            'accountant' => $validated['accountant'] ?? null,
            'late_fee' => $lateFee,
        ];

        $this->applyLedgerGrossFromPrincipal($payload, $lateFee, $method, $remainingDueBeforePayment);

        $isPartialPayment = $maxPayableWithThisRequest > 0 && $totalCredit < $maxPayableWithThisRequest - 0.02;

        if ($isPartialPayment) {
            StudentPayment::create($payload);

            return;
        }

        // Full payment: update the generated row so Fee Payment shows it as paid.
        $existingFee->update([
            'campus' => $validated['campus'] ?? $existingFee->campus,
            'payment_amount' => $payload['payment_amount'],
            'discount' => $payload['discount'],
            'method' => $payload['method'],
            'payment_date' => $payload['payment_date'],
            'sms_notification' => $payload['sms_notification'],
            'late_fee' => $payload['late_fee'],
            'accountant' => $payload['accountant'],
        ]);
        $existingFee->touch();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function applyLedgerGrossFromPrincipal(
        array &$payload,
        float $lateFee,
        string $method,
        ?float $remainingDueBeforePayment = null
    ): void {
        if ($this->isWalletMethod($method)) {
            return;
        }

        $late = max(0, round((float) $lateFee, 2));
        if ($late <= 0) {
            return;
        }

        $payload['late_fee'] = $late;

        $principal = (float) ($payload['payment_amount'] ?? 0);
        $discount = (float) ($payload['discount'] ?? 0);

        if ($remainingDueBeforePayment !== null
            && round($principal + $discount, 2) + 0.02 >= round($remainingDueBeforePayment, 2)) {
            return;
        }

        $payload['payment_amount'] = round($principal + $late, 2);
    }

    private function isWalletMethod(?string $method): bool
    {
        $m = strtolower(trim((string) $method));

        return $m === 'wallet' || str_contains($m, 'wallet');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function resolveStudentForPayment(array $validated): array
    {
        $query = Student::where('student_code', $validated['student_code']);

        if (! empty($validated['campus'])) {
            $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($validated['campus']))]);
        }

        $student = $query->first();

        if (! $student) {
            $message = ! empty($validated['campus'])
                ? 'Student not found with this code in the selected campus.'
                : 'Student not found with this code.';

            throw ValidationException::withMessages([
                'student_code' => $message,
            ]);
        }

        if (empty($validated['campus'])) {
            $validated['campus'] = $student->campus;
        }

        return $validated;
    }

    private function findUnpaidGeneratedFee(string $studentCode, string $paymentTitle, ?int $generatedId): ?StudentPayment
    {
        if ($generatedId) {
            $fee = StudentPayment::ledgerActive()
                ->where('id', $generatedId)
                ->where('student_code', $studentCode)
                ->whereIn('method', ['Generated', 'Installment'])
                ->first();

            if ($fee && $this->hasRemainingDue($studentCode, (string) $fee->payment_title)) {
                return $fee;
            }
        }

        $normalizedTitle = strtolower(trim($paymentTitle));

        $fees = StudentPayment::ledgerActive()
            ->where('student_code', $studentCode)
            ->whereIn('method', ['Generated', 'Installment'])
            ->orderByDesc('id')
            ->get();

        foreach ($fees as $fee) {
            $feeTitle = strtolower(trim((string) $fee->payment_title));
            if ($feeTitle !== $normalizedTitle) {
                continue;
            }
            if ($this->hasRemainingDue($studentCode, (string) $fee->payment_title)) {
                return $fee;
            }
        }

        return null;
    }

    private function getRemainingDue(string $studentCode, string $paymentTitle): float
    {
        $student = Student::where('student_code', $studentCode)->first();
        if (! $student) {
            return 0.0;
        }

        return round(
            FeePaymentWebTables::outstandingDuePartsForTitle($student, $paymentTitle)['total'],
            2
        );
    }

    private function hasRemainingDue(string $studentCode, string $paymentTitle): bool
    {
        return $this->getRemainingDue($studentCode, $paymentTitle) > 0.02;
    }

    private function resolveRecordingAccountantName(): ?string
    {
        if (auth()->guard('admin')->check()) {
            return auth()->guard('admin')->user()->name ?? null;
        }

        if (auth()->guard('accountant')->check()) {
            return auth()->guard('accountant')->user()->name ?? null;
        }

        return auth()->user()->name ?? null;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function notifyAdminsAboutAccountantCustomPayment(array $validated, Student $student): void
    {
        $accountant = auth()->guard('accountant')->user();
        if (! $accountant) {
            return;
        }

        $classSection = trim((string) ($student->class ?? '') . (($student->section ?? '') !== '' ? ' - ' . $student->section : ''));
        $text = sprintf(
            '%s recorded custom payment of %s for %s (%s). Fee: %s. Method: %s. Campus: %s.',
            $accountant->name ?? 'Accountant',
            number_format((float) ($validated['payment_amount'] ?? 0), 2),
            $student->student_name ?? 'Student',
            $validated['student_code'],
            $validated['payment_title'],
            $validated['method'],
            $validated['campus'] ?? ($student->campus ?? 'N/A')
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
}
