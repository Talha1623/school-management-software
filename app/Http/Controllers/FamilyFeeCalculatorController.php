<?php

namespace App\Http\Controllers;

use App\Models\ParentAccount;
use App\Models\Student;
use App\Models\StudentPayment;
use App\Services\FeePaymentWebTables;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class FamilyFeeCalculatorController extends Controller
{
    /**
     * School admin / super admin Family Fee Calculator (ICMS accounting panel).
     */
    public function index(): View
    {
        $parentAccounts = ParentAccount::select('id', 'name')
            ->orderBy('name', 'asc')
            ->get();

        $families = $parentAccounts->map(function ($parent) {
            return [
                'id' => 'parent_' . $parent->id,
                'name' => $parent->name,
                'type' => 'Parent Account',
            ];
        });

        return view('accounting.family-fee-calculator', [
            'families' => $families,
            'ffcSearchUrl' => route('accounting.family-fee-calculator.search-by-id-card'),
            'ffcPayAllUrl' => route('accounting.family-fee-calculator.pay-all'),
        ]);
    }

    /**
     * Accountant portal — dedicated route for logged-in accountants (see AccountantMiddleware).
     */
    public function accountantIndex(): View
    {
        return view('accountant.family-fee-calculator', [
            'ffcSearchUrl' => route('accountant.family-fee-calculator.search-by-id-card'),
            'ffcPayAllUrl' => route('accountant.family-fee-calculator.pay-all'),
        ]);
    }

    /**
     * Shared search API for super admin accounting + accountant dashboard.
     */
    public function searchByIdCard(Request $request): JsonResponse
    {
        $fatherIdCard = $request->get('father_id_card');

        if (!$fatherIdCard) {
            return response()->json([
                'success' => false,
                'message' => 'Father ID Card is required',
            ], 400);
        }

        [$parentAccount, $students] = $this->resolveFamilyStudents((string) $fatherIdCard);

        if ($students->isEmpty()) {
            [$cleaned] = $this->normalizeIdCard($fatherIdCard);
            $testQuery = Student::where('father_id_card', 'LIKE', '%' . $cleaned . '%')->count();

            return response()->json([
                'success' => true,
                'found' => false,
                'message' => 'No children found with this Father ID Card Number',
                'debug' => [
                    'searched_id_card' => $cleaned,
                    'original_input' => $fatherIdCard,
                    'parent_account_found' => $parentAccount !== null,
                    'test_like_query_count' => $testQuery,
                ],
            ]);
        }

        $fatherInfo = $this->buildFatherInfo($parentAccount, $students, (string) $fatherIdCard);
        $currentMonthLabel = Carbon::now()->format('F Y');

        return response()->json([
            'success' => true,
            'found' => true,
            'father' => $fatherInfo,
            'students' => $this->mapStudentsForSearchResponse($students, $currentMonthLabel),
        ]);
    }

    public function studentsByFamily(Request $request): JsonResponse
    {
        $familyId = $request->get('family_id');

        if (!$familyId) {
            return response()->json(['error' => 'Family ID is required'], 400);
        }

        $students = collect();

        if (str_starts_with((string) $familyId, 'parent_')) {
            $parentId = str_replace('parent_', '', $familyId);
            $query = Student::where('parent_account_id', $parentId)
                ->select('id', 'student_name', 'class', 'section', 'student_code')
                ->orderBy('student_name', 'asc');
            $this->applyAccountantCampusFilter($query);
            $students = $query->get();
        }

        $formattedStudents = $students->map(function ($student) {
            return [
                'id' => $student->id,
                'name' => $student->student_name,
                'class' => $student->class ?? 'N/A',
                'section' => $student->section ?? 'N/A',
                'admission_no' => $student->student_code ?? 'N/A',
            ];
        });

        return response()->json(['students' => $formattedStudents]);
    }

    private function normalizeIdCard(?string $idCard): array
    {
        $digitMap = [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ];
        $cleaned = trim(strtr((string) $idCard, $digitMap));

        $lower = strtolower($cleaned);
        $normalized = str_replace(['-', ' ', '_', '.', '/'], '', $lower);

        return [$cleaned, $lower, $normalized];
    }

    private function findParentAccount(?string $rawIdCard): ?ParentAccount
    {
        if (!$rawIdCard) {
            return null;
        }

        [$cleaned, $lower, $normalized] = $this->normalizeIdCard($rawIdCard);

        return ParentAccount::where(function ($query) use ($lower, $normalized, $rawIdCard) {
            $query->whereRaw('LOWER(TRIM(id_card_number)) = ?', [$lower])
                ->orWhereRaw('TRIM(id_card_number) = ?', [$rawIdCard])
                ->orWhereRaw(
                    'LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(id_card_number), "-", ""), " ", ""), "_", ""), ".", ""), "/", "")) = ?',
                    [$normalized]
                )
                ->orWhereRaw(
                    'LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(id_card_number), "-", ""), " ", ""), "_", ""), ".", ""), "/", "")) LIKE ?',
                    ['%' . $normalized . '%']
                );
        })->first();
    }

    private function findStudentsByFatherIdCard(?string $rawIdCard): Collection
    {
        [$cleaned, , $normalized] = $this->normalizeIdCard($rawIdCard);

        $studentsByFatherIdCardQuery = Student::where(function ($query) use ($cleaned, $normalized, $rawIdCard) {
            $query->where('father_id_card', $cleaned)
                ->orWhere('father_id_card', $rawIdCard)
                ->orWhere('father_id_card', 'LIKE', $cleaned)
                ->orWhere('father_id_card', 'LIKE', '%' . $cleaned . '%')
                ->orWhere('father_id_card', 'LIKE', '%' . $rawIdCard . '%')
                ->orWhereRaw('LOWER(father_id_card) = LOWER(?)', [$cleaned])
                ->orWhereRaw('LOWER(father_id_card) = LOWER(?)', [$rawIdCard])
                ->orWhereRaw('TRIM(father_id_card) = ?', [$cleaned])
                ->orWhereRaw('TRIM(father_id_card) = ?', [$rawIdCard])
                ->orWhereRaw('LOWER(TRIM(father_id_card)) = LOWER(TRIM(?))', [$cleaned])
                ->orWhereRaw('CAST(father_id_card AS CHAR) = ?', [$cleaned])
                ->orWhereRaw('CAST(father_id_card AS CHAR) LIKE ?', ['%' . $cleaned . '%'])
                ->orWhereRaw(
                    'LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(father_id_card), "-", ""), " ", ""), "_", ""), ".", ""), "/", "")) LIKE ?',
                    ['%' . $normalized . '%']
                );
        });

        $this->applyAccountantCampusFilter($studentsByFatherIdCardQuery);

        $studentsByFatherIdCard = $studentsByFatherIdCardQuery
            ->select($this->studentSearchColumns())
            ->get();

        if ($studentsByFatherIdCard->isNotEmpty()) {
            return $studentsByFatherIdCard;
        }

        $fallbackQuery = Student::whereRaw(
            'father_id_card = ? OR father_id_card LIKE ? OR LOWER(father_id_card) = LOWER(?)',
            [$cleaned, '%' . $cleaned . '%', $cleaned]
        );

        $this->applyAccountantCampusFilter($fallbackQuery);

        return $fallbackQuery->select($this->studentSearchColumns())->get();
    }

    /**
     * @return array{0: ?ParentAccount, 1: Collection<int, Student>}
     */
    private function resolveFamilyStudents(string $rawIdCard): array
    {
        $parentAccount = $this->findParentAccount($rawIdCard);
        $studentsByFatherIdCard = $this->findStudentsByFatherIdCard($rawIdCard);

        $studentsByParentAccount = collect();
        if ($parentAccount) {
            $parentAccountQuery = Student::where('parent_account_id', $parentAccount->id)
                ->select($this->studentSearchColumns());
            $this->applyAccountantCampusFilter($parentAccountQuery);
            $studentsByParentAccount = $parentAccountQuery->get();
        }

        $students = $studentsByParentAccount
            ->merge($studentsByFatherIdCard)
            ->unique('id')
            ->sortBy('student_name')
            ->values();

        return [$parentAccount, $students];
    }

    private function getAccountantCampus(): ?string
    {
        if (!auth()->guard('accountant')->check()) {
            return null;
        }

        $campus = auth()->guard('accountant')->user()->campus ?? null;

        return $campus ? strtolower(trim((string) $campus)) : null;
    }

    private function applyAccountantCampusFilter(Builder $query): void
    {
        $campus = $this->getAccountantCampus();
        if ($campus) {
            $query->whereRaw('LOWER(TRIM(campus)) = ?', [$campus]);
        }
    }

    /**
     * @return list<string>
     */
    private function studentSearchColumns(): array
    {
        return [
            'id',
            'student_name',
            'student_code',
            'class',
            'section',
            'campus',
            'monthly_fee',
            'transport_fare',
            'generate_other_fee',
            'other_fee_amount',
            'generate_admission_fee',
            'admission_fee_amount',
            'fee_type',
            'father_name',
            'father_phone',
            'father_email',
            'home_address',
        ];
    }

    private function mapStudentsForSearchResponse(Collection $students, string $currentMonthLabel): array
    {
        $monthlyTitle = "Monthly Fee - {$currentMonthLabel}";

        return $students->map(function ($student) use ($monthlyTitle, $currentMonthLabel) {
            $feePayload = FeePaymentWebTables::feeSearchPayloadCnicRoute($student);
            $agg = $this->computeDueAggregatesFromFeePayload($feePayload);
            $studentCode = trim((string) ($student->student_code ?? ''));

            $monthlyFeeRow = collect($feePayload['fee_rows'] ?? [])
                ->first(fn ($row) => ($row['title'] ?? '') === $monthlyTitle);

            $hasCurrentMonthGenerated = $monthlyFeeRow !== null
                || ($studentCode !== ''
                    && StudentPayment::query()
                        ->where('student_code', $studentCode)
                        ->where('payment_title', $monthlyTitle)
                        ->whereIn('method', ['Generated', 'Installment'])
                        ->exists());

            $currentMonthRemaining = $monthlyFeeRow !== null
                ? (float) ($monthlyFeeRow['due'] ?? 0)
                : 0.0;

            if ($hasCurrentMonthGenerated) {
                $monthlyStatus = $currentMonthRemaining > 0.00001
                    ? "Monthly Fee - {$currentMonthLabel} (Not Paid)"
                    : "Monthly Fee - {$currentMonthLabel} (Paid)";
            } elseif ($studentCode !== '' && StudentPayment::query()
                ->where('student_code', $studentCode)
                ->where('payment_title', $monthlyTitle)
                ->whereNotIn('method', ['Generated', 'Installment'])
                ->exists()) {
                $monthlyStatus = "Monthly Fee - {$currentMonthLabel} (Paid)";
            } else {
                $monthlyStatus = "Monthly Fee - {$currentMonthLabel} (N/A)";
            }

            return [
                'id' => $student->id,
                'student_name' => $student->student_name,
                'student_code' => $student->student_code,
                'class' => $student->class,
                'section' => $student->section,
                'campus' => $student->campus,
                'monthly_fee' => $student->monthly_fee,
                'transport_fare' => $student->transport_fare,
                'generate_other_fee' => $student->generate_other_fee,
                'other_fee_amount' => $student->other_fee_amount,
                'generate_admission_fee' => $student->generate_admission_fee,
                'admission_fee_amount' => $student->admission_fee_amount,
                'due_monthly_fee' => $agg['due_monthly'],
                'due_transport_fare' => $agg['due_transport'],
                'due_admission_fee' => $agg['due_admission'],
                'due_other_fee' => $agg['due_other'],
                'due_total' => $agg['due_total'],
                'pending_fee_lines' => $agg['pending_fee_lines'],
                'monthly_fee_status' => $monthlyStatus,
            ];
        })->values()->toArray();
    }

    private function buildFatherInfo(?ParentAccount $parentAccount, $students, string $rawIdCard): array
    {
        if ($parentAccount) {
            return [
                'id' => $parentAccount->id,
                'name' => $parentAccount->name,
                'id_card_number' => $parentAccount->id_card_number,
                'phone' => $parentAccount->phone,
                'email' => $parentAccount->email,
                'address' => $parentAccount->address,
            ];
        }

        $firstStudent = $students->first();
        return [
            'id' => null,
            'name' => $firstStudent->father_name ?? 'N/A',
            'id_card_number' => $rawIdCard,
            'phone' => $firstStudent->father_phone ?? 'N/A',
            'email' => $firstStudent->father_email ?? 'N/A',
            'address' => $firstStudent->home_address ?? 'N/A',
        ];
    }

    private function getAccountantName(): ?string
    {
        if (auth()->guard('accountant')->check()) {
            return auth()->guard('accountant')->user()->name ?? null;
        }
        if (auth()->guard('admin')->check()) {
            return auth()->guard('admin')->user()->name ?? null;
        }
        if (auth()->check()) {
            return auth()->user()->name ?? null;
        }
        return null;
    }

    public function payAll(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'father_id_card' => ['required', 'string', 'max:255'],
            'method' => ['nullable', 'string', 'max:255'],
        ]);

        $rawIdCard = $validated['father_id_card'];
        [$parentAccount, $students] = $this->resolveFamilyStudents($rawIdCard);

        if ($students->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No children found with this Father ID Card Number',
            ], 404);
        }

        $paymentDate = Carbon::now()->format('Y-m-d');
        $method = $validated['method'] ?? 'Cash Payment';
        $accountantName = $this->getAccountantName();

        $receiptStudents = [];
        $grandTotal = 0;

        foreach ($students as $student) {
            $agg = $this->computeDueAggregatesForStudent($student);
            $paidAmount = $this->payAllRemainingGeneratedFeesForStudent(
                $student,
                $method,
                $paymentDate,
                $accountantName
            );
            $grandTotal += $paidAmount;

            $receiptStudents[] = [
                'id' => $student->id,
                'student_name' => $student->student_name,
                'student_code' => $student->student_code,
                'class' => $student->class,
                'section' => $student->section,
                'campus' => $student->campus,
                'monthly_fee' => $agg['due_monthly'],
                'transport_fare' => $agg['due_transport'],
                'admission_fee_amount' => $agg['due_admission'],
                'other_fee_amount' => $agg['due_other'],
                'total' => round($paidAmount, 2),
            ];
        }

        $fatherInfo = $this->buildFatherInfo($parentAccount, $students, $rawIdCard);

        return response()->json([
            'success' => true,
            'message' => 'All fees paid successfully.',
            'father' => $fatherInfo,
            'students' => $receiptStudents,
            'grand_total' => $grandTotal,
            'payment_date' => $paymentDate,
            'payment_method' => $method,
        ]);
    }

    /**
     * Same due rules as Fee Payment Search Results (CNIC / search-by-id-card).
     *
     * @return array{due_monthly: float, due_transport: float, due_admission: float, due_other: float, due_total: float, pending_fee_lines: array<int, array{title: string, amount: float, late_fee: float, total: float}>}
     */
    private function computeDueAggregatesForStudent(Student $student): array
    {
        return $this->computeDueAggregatesFromFeePayload(
            FeePaymentWebTables::feeSearchPayloadCnicRoute($student)
        );
    }

    /**
     * @param array{fee_rows?: array<int, array<string, mixed>>, unpaid_amount?: float} $payload
     * @return array{due_monthly: float, due_transport: float, due_admission: float, due_other: float, due_total: float, pending_fee_lines: array<int, array{title: string, amount: float, late_fee: float, total: float}>}
     */
    private function computeDueAggregatesFromFeePayload(array $payload): array
    {
        $dueMonthly = 0.0;
        $dueTransport = 0.0;
        $dueAdmission = 0.0;
        $dueOther = 0.0;
        $pendingFeeLines = [];

        foreach ($payload['fee_rows'] ?? [] as $fee) {
            $title = (string) ($fee['title'] ?? '');
            $due = (float) ($fee['due'] ?? 0);

            if ($due <= 0.00001) {
                continue;
            }

            $pendingFeeLines[] = [
                'title' => $title,
                'amount' => (float) ($fee['amount'] ?? 0),
                'late_fee' => (float) ($fee['late_fee'] ?? 0),
                'total' => $due,
            ];

            if (str_starts_with($title, 'Monthly Fee - ')) {
                $dueMonthly += $due;
            } elseif (str_starts_with($title, 'Transport Fee')) {
                $dueTransport += $due;
            } elseif (strtolower(trim($title)) === 'admission fee') {
                $dueAdmission += $due;
            } else {
                $dueOther += $due;
            }
        }

        return [
            'due_monthly' => round($dueMonthly, 2),
            'due_transport' => round($dueTransport, 2),
            'due_admission' => round($dueAdmission, 2),
            'due_other' => round($dueOther, 2),
            'due_total' => round((float) ($payload['unpaid_amount'] ?? 0), 2),
            'pending_fee_lines' => $pendingFeeLines,
        ];
    }

    /**
     * Pay each pending fee line (includes custom fees from Generate Custom Fee).
     */
    private function payAllRemainingGeneratedFeesForStudent(
        Student $student,
        string $method,
        string $paymentDate,
        ?string $accountantName
    ): float {
        $agg = $this->computeDueAggregatesForStudent($student);
        $paidSum = 0.0;

        foreach ($agg['pending_fee_lines'] as $line) {
            $remaining = (float) $line['total'];
            if ($remaining <= 0) {
                continue;
            }

            $this->payStudentFee($student, (string) $line['title'], $remaining, $method, $paymentDate, $accountantName);
            $paidSum += $remaining;
        }

        return round($paidSum, 2);
    }

    private function payStudentFee(Student $student, string $paymentTitle, float $amount, string $method, string $paymentDate, ?string $accountantName): void
    {
        if ($amount <= 0 || empty($student->student_code)) {
            return;
        }

        $existingFee = StudentPayment::query()
            ->where('student_code', $student->student_code)
            ->where('payment_title', $paymentTitle)
            ->whereIn('method', ['Generated', 'Installment'])
            ->orderByDesc('id')
            ->first();

        if ($existingFee) {
            $existingFee->update([
                'payment_amount' => $amount,
                'discount' => 0,
                'method' => $method,
                'payment_date' => $paymentDate,
                'sms_notification' => 'Yes',
                'late_fee' => $existingFee->late_fee ?? 0,
                'accountant' => $accountantName,
            ]);
            return;
        }

        StudentPayment::create([
            'campus' => $student->campus ?? null,
            'student_code' => $student->student_code,
            'payment_title' => $paymentTitle,
            'payment_amount' => $amount,
            'discount' => 0,
            'method' => $method,
            'payment_date' => $paymentDate,
            'sms_notification' => 'Yes',
            'late_fee' => 0,
            'accountant' => $accountantName,
        ]);
    }
}
