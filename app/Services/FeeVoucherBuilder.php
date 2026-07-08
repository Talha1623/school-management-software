<?php

namespace App\Services;

use App\Models\AdvanceFee;
use App\Models\ClassModel;
use App\Models\GeneralSetting;
use App\Models\MonthlyFee;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class FeeVoucherBuilder
{
    public function resolveContext(Request $request, bool $forPrint = true): array
    {
        $settings = GeneralSetting::getSettings();
        $currentYear = (int) date('Y');
        $runningSession = trim((string) ($settings->running_session ?? ''));
        if ($runningSession !== '') {
            preg_match_all('/\d{4}/', $runningSession, $matches);
            if (!empty($matches[0])) {
                $currentYear = (int) end($matches[0]);
            }
        }
        $displayYear = $runningSession !== '' ? $runningSession : (string) $currentYear;
        $vouchersFor = trim((string) ($request->input('vouchers_for') ?? ''));
        if ($vouchersFor === '' && $forPrint) {
            $vouchersFor = date('F');
        }
        $type = trim((string) ($request->input('type') ?? ''));
        if ($type === '' && $forPrint) {
            $type = 'three_copies';
        }

        $copyMap = [
            'three_copies' => ['Bank Copy', 'Parent Copy', 'School Copy'],
            'two_copies' => ['Bank Copy', 'Parent Copy'],
            'thermal_copies' => ['THERMAL COPY'],
        ];

        return [
            'settings' => $settings,
            'currentYear' => $currentYear,
            'displayYear' => $displayYear,
            'vouchersFor' => $vouchersFor,
            'type' => $type,
            'copyLabels' => $copyMap[$type] ?? $copyMap['three_copies'],
        ];
    }

    public function buildStudentVouchers(Request $request): array
    {
        $context = $this->resolveContext($request, true);
        $students = $this->filterStudents($request, $context);
        $vouchers = [];

        foreach ($students as $student) {
            $vouchers[] = $this->buildForStudent(
                $student,
                $context['vouchersFor'],
                $context['currentYear'],
                $context['displayYear']
            );
        }

        return $this->printViewData($context, $vouchers);
    }

    public function buildFamilyVouchers(Request $request): array
    {
        $context = $this->resolveContext($request, true);
        $familyGroups = $this->collectFamilyStudentGroups($request, $context);
        $vouchers = [];
        $walletPool = [];

        foreach ($familyGroups as $familyStudents) {
            $childVouchers = [];
            foreach ($familyStudents as $student) {
                $childVouchers[] = $this->buildForStudent(
                    $student,
                    $context['vouchersFor'],
                    $context['currentYear'],
                    $context['displayYear'],
                    $walletPool
                );
            }

            $vouchers[] = $this->mergeFamilyVouchers($childVouchers);
        }

        return $this->printViewData($context, $vouchers);
    }

    public function listFamilies(Request $request): LengthAwarePaginator
    {
        $context = $this->resolveContext($request, true);
        $familyGroups = $this->collectFamilyStudentGroups($request, $context);
        $families = $familyGroups->map(function (Collection $familyStudents, string $familyKey) {
            $primary = $familyStudents->first();

            return (object) [
                'family_key' => $familyKey,
                'parent_id' => $primary->parent_account_id,
                'parent_name' => $primary->parentAccount->name ?? $primary->father_name ?? 'Unknown',
                'student_names' => $familyStudents->pluck('student_name')->filter()->implode(', '),
                'student_codes' => $familyStudents->pluck('student_code')->filter()->implode(', '),
                'classes' => $familyStudents->pluck('class')->filter()->unique()->implode(', '),
                'sections' => $familyStudents->pluck('section')->filter()->unique()->implode(', '),
                'campus' => $familyStudents->pluck('campus')->filter()->unique()->implode(', '),
                'student_count' => $familyStudents->count(),
            ];
        })->sortBy('parent_name')->values();

        $page = max(1, (int) $request->get('page', 1));
        $perPage = 20;

        return new LengthAwarePaginator(
            $families->forPage($page, $perPage)->values(),
            $families->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }

    public function familyGroupKey(Student $student): string
    {
        if (!empty($student->parent_account_id)) {
            return 'parent:' . $student->parent_account_id;
        }

        $idCard = strtolower(trim((string) ($student->father_id_card ?? '')));
        if ($idCard !== '') {
            return 'father_card:' . $idCard;
        }

        $fatherName = strtolower(trim((string) ($student->father_name ?? '')));
        if ($fatherName !== '') {
            return 'father_name:' . $fatherName;
        }

        return 'student:' . $student->id;
    }

    /**
     * Same students as Student Vouchers, grouped into families.
     *
     * @return Collection<int, Collection<int, Student>>
     */
    private function collectFamilyStudentGroups(Request $request, array $context): Collection
    {
        $pendingStudentCodes = $this->pendingStudentCodes($request, $context);
        if ($pendingStudentCodes->isEmpty()) {
            return collect();
        }

        $matchedStudents = $this->filterStudents($request, $context);

        if ($request->filled('family_key')) {
            $familyKey = (string) $request->family_key;
            $familyStudents = $this->studentsForFamilyKey($familyKey, $pendingStudentCodes, $matchedStudents);

            return $familyStudents->isEmpty()
                ? collect()
                : collect([$familyKey => $familyStudents]);
        }

        if ($request->filled('parent_id')) {
            $familyKey = 'parent:' . $request->parent_id;
            $familyStudents = $this->studentsForFamilyKey($familyKey, $pendingStudentCodes, $matchedStudents);

            return $familyStudents->isEmpty()
                ? collect()
                : collect([$familyKey => $familyStudents]);
        }

        if ($matchedStudents->isEmpty()) {
            return collect();
        }

        $groupKeys = $matchedStudents
            ->map(fn (Student $student) => $this->familyGroupKey($student))
            ->unique()
            ->values();

        return $groupKeys
            ->mapWithKeys(function (string $familyKey) use ($pendingStudentCodes, $matchedStudents) {
                $familyStudents = $this->studentsForFamilyKey($familyKey, $pendingStudentCodes, $matchedStudents);

                return $familyStudents->isEmpty() ? [] : [$familyKey => $familyStudents];
            });
    }

    private function studentsForFamilyKey(
        string $familyKey,
        Collection $pendingStudentCodes,
        Collection $matchedStudents
    ): Collection {
        if (str_starts_with($familyKey, 'parent:')) {
            $parentId = substr($familyKey, 7);

            return Student::where('parent_account_id', $parentId)
                ->whereIn('student_code', $pendingStudentCodes)
                ->with('parentAccount')
                ->orderBy('student_name')
                ->get();
        }

        if (str_starts_with($familyKey, 'father_card:')) {
            $idCard = substr($familyKey, 12);

            return Student::whereRaw('LOWER(TRIM(father_id_card)) = ?', [$idCard])
                ->whereIn('student_code', $pendingStudentCodes)
                ->with('parentAccount')
                ->orderBy('student_name')
                ->get();
        }

        if (str_starts_with($familyKey, 'father_name:')) {
            $fatherName = substr($familyKey, 12);

            return Student::whereRaw('LOWER(TRIM(father_name)) = ?', [$fatherName])
                ->whereIn('student_code', $pendingStudentCodes)
                ->with('parentAccount')
                ->orderBy('student_name')
                ->get();
        }

        if (str_starts_with($familyKey, 'student:')) {
            $studentId = (int) substr($familyKey, 8);

            return $matchedStudents
                ->where('id', $studentId)
                ->values();
        }

        return collect();
    }

    private function pendingStudentCodes(Request $request, array $context): Collection
    {
        $currentYear = $context['currentYear'];
        $vouchersFor = $context['vouchersFor'];
        $parentStudentCodes = collect();

        if ($request->filled('parent_id')) {
            $parentStudentCodes = Student::where('parent_account_id', $request->parent_id)
                ->whereNotNull('student_code')
                ->where('student_code', '!=', '')
                ->pluck('student_code');
        }

        $pendingPaymentsQuery = StudentPayment::where('method', 'Generated')
            ->whereNotNull('student_code')
            ->where('student_code', '!=', '');

        if ($request->filled('parent_id') && $parentStudentCodes->isNotEmpty()) {
            $pendingPaymentsQuery->whereIn('student_code', $parentStudentCodes);
        }

        if ($request->filled('student_code')) {
            $pendingPaymentsQuery->where('student_code', $request->student_code);
        }

        // Keep voucher list inclusive: show every student that has any pending generated fee.
        // Restricting by selected month can hide siblings/family members with valid unpaid dues.

        return $pendingPaymentsQuery->distinct()->pluck('student_code');
    }

    public function buildStudentFilterQuery(Request $request, array $context): \Illuminate\Database\Eloquent\Builder
    {
        $query = Student::query();
        $parentStudentCodes = collect();

        if ($request->filled('parent_id')) {
            $parentStudentCodes = Student::where('parent_account_id', $request->parent_id)
                ->whereNotNull('student_code')
                ->where('student_code', '!=', '')
                ->pluck('student_code');
        }

        if ($request->filled('campus')) {
            $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($request->campus))]);
        }

        if ($request->filled('class')) {
            $query->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($request->class))]);
        }

        if ($request->filled('section')) {
            $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($request->section))]);
        }

        $pendingStudentCodes = $this->pendingStudentCodes($request, $context);

        if ($request->filled('parent_id')) {
            if ($parentStudentCodes->isNotEmpty()) {
                $query->whereIn('student_code', $parentStudentCodes);
            } else {
                $query->whereRaw('1 = 0');
            }
        } elseif ($request->filled('student_code')) {
            $query->where('student_code', $request->student_code);
        } elseif ($pendingStudentCodes->isNotEmpty()) {
            $query->whereIn('student_code', $pendingStudentCodes);
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    public function filterStudents(Request $request, array $context): Collection
    {
        return $this->buildStudentFilterQuery($request, $context)
            ->orderBy('student_name')
            ->get();
    }

    private function printViewData(array $context, array $vouchers): array
    {
        return [
            'vouchers' => $vouchers,
            'type' => $context['type'] ?: 'three_copies',
            'vouchersFor' => $context['vouchersFor'],
            'currentYear' => $context['currentYear'],
            'copyLabels' => $context['copyLabels'],
            'settings' => $context['settings'],
        ];
    }

    public function buildForStudent(
        Student $student,
        string $vouchersFor,
        int $currentYear,
        string $displayYear,
        array &$walletPool = []
    ): array {
        $pendingPayments = StudentPayment::where('student_code', $student->student_code)
            ->where('method', 'Generated')
            ->orderBy('payment_date', 'asc')
            ->get();

        $feeHistory = FeePaymentWebTables::monthlyFeeHistoryForStudent($student, $currentYear);

        $today = Carbon::today();

        $latestDueDate = null;
        if ($pendingPayments->isNotEmpty()) {
            $maxDate = $pendingPayments->max(fn ($payment) => $payment->payment_date ? Carbon::parse($payment->payment_date) : null);
            if ($maxDate) {
                $latestDueDate = $maxDate;
            }
        }

        if (!$latestDueDate) {
            $monthlyFeeRecord = MonthlyFee::where('fee_month', $vouchersFor)
                ->where('fee_year', $currentYear)
                ->where(function ($q) use ($student) {
                    $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($student->campus ?? ''))])
                        ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($student->class ?? ''))])
                        ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($student->section ?? ''))]);
                })
                ->first();

            $latestDueDate = $monthlyFeeRecord
                ? Carbon::parse($monthlyFeeRecord->due_date)
                : Carbon::now()->addDays(15);
        }

        $dueDate = $latestDueDate;
        $voucherValidity = Carbon::parse($dueDate)->addDays(5);
        $voucherNumber = strtoupper(substr($vouchersFor, 0, 3))
            . '-' . str_pad((string) $student->id, 5, '0', STR_PAD_LEFT)
            . '-' . substr((string) $currentYear, -2);

        // Match Fee Payment Search Results: principal in fee lines, late fee separate, total = sum(due) + auto late.
        $isMonthlyOrTransport = fn ($title) => preg_match('/^(Monthly Fee|Transport Fee) - /i', (string) $title);
        $searchResults = FeePaymentWebTables::searchResultsForStudent($student);
        $outstandingRows = collect($searchResults['rows'] ?? []);

        $paymentDatesByTitle = $pendingPayments
            ->groupBy(fn ($payment) => strtolower(trim((string) ($payment->payment_title ?? ''))))
            ->map(fn ($items) => $items->sortByDesc('id')->first()?->payment_date);

        $arrearsAmount = 0.0;
        $arrearsFees = collect();
        $currentFees = collect();

        foreach ($outstandingRows as $row) {
            $feeTitle = (string) ($row['fee_type'] ?? '');
            if ($feeTitle === '') {
                continue;
            }

            $principalDue = round((float) ($row['amount'] ?? 0), 2);
            if ($principalDue <= 0.0001) {
                continue;
            }

            $description = $this->formatFeeDescription($student, $feeTitle);
            $paymentDate = $paymentDatesByTitle->get(strtolower(trim($feeTitle)));
            $isArrear = $isMonthlyOrTransport($feeTitle)
                && $paymentDate
                && Carbon::parse($paymentDate)->lt($today);

            $line = [
                'description' => $description,
                'amount' => $principalDue,
                'sort_order' => $isArrear ? 0 : (preg_match('/^(Monthly Fee|Transport Fee) - /i', $feeTitle) ? 1 : 2),
            ];

            if ($isArrear) {
                $arrearsAmount += $principalDue;
                $arrearsFees->push($line);
            } else {
                $currentFees->push($line);
            }
        }

        $pendingFeesList = $arrearsFees->merge($currentFees)
            ->sortBy('sort_order')
            ->map(fn ($fee) => [
                'description' => $fee['description'],
                'amount' => $fee['amount'],
            ])
            ->values();

        $subtotal = round((float) $outstandingRows->sum(fn ($row) => (float) ($row['amount'] ?? 0)), 2);
        $currentFeesSubtotal = round(max(0, $subtotal - $arrearsAmount), 2);
        $lateFee = round((float) ($searchResults['totals']['late_fee'] ?? $outstandingRows->sum(
            fn ($row) => (float) ($row['remaining_late'] ?? $row['late_fee'] ?? 0)
        )), 2);
        $totalBeforeWallet = round((float) ($searchResults['totals']['due'] ?? 0), 2);
        $afterDueLate = FeePaymentWebTables::projectedAfterDueLateTotal($student, $outstandingRows->all());
        $totalAfterDueBeforeWallet = round(max(0, $subtotal + $afterDueLate), 2);

        $walletKey = $this->walletKeyForStudent($student);
        if (!array_key_exists($walletKey, $walletPool)) {
            $walletPool[$walletKey] = $this->availableWalletCreditForStudent($student);
        }
        $walletCredit = $walletPool[$walletKey];
        $walletApplied = min($walletCredit, $totalBeforeWallet);
        $walletAppliedAfterDue = min($walletCredit, $totalAfterDueBeforeWallet);
        if ($walletApplied > 0) {
            $pendingFeesList->push([
                'description' => 'Advance Fee / Wallet Credit',
                'amount' => -$walletApplied,
            ]);
            $walletPool[$walletKey] = max(0, round($walletCredit - $walletApplied, 2));
        }

        $total = max(0, round($totalBeforeWallet - $walletApplied, 2));
        $afterDueDate = max(0, round($totalAfterDueBeforeWallet - $walletAppliedAfterDue, 2));

        return [
            'student' => $student,
            'pending_fees' => $pendingFeesList,
            'current_fees_subtotal' => $currentFeesSubtotal,
            'arrears_amount' => $arrearsAmount,
            'subtotal' => $subtotal,
            'late_fee' => $lateFee,
            'wallet_credit' => $walletCredit,
            'wallet_applied' => $walletApplied,
            'total' => $total,
            'after_due_date' => $afterDueDate,
            'due_date' => $dueDate,
            'voucher_validity' => $voucherValidity,
            'voucher_number' => $voucherNumber,
            'fee_history' => $feeHistory,
            'month' => $vouchersFor,
            'year' => $currentYear,
            'year_label' => $displayYear,
        ];
    }

    public function mergeFamilyVouchers(array $childVouchers): array
    {
        $students = collect($childVouchers)->pluck('student');
        $primary = $students->first();

        $pendingFees = collect();
        foreach ($childVouchers as $childVoucher) {
            $studentName = $childVoucher['student']->student_name ?? 'Student';
            foreach ($childVoucher['pending_fees'] as $fee) {
                $pendingFees->push([
                    'description' => $studentName . ' - ' . ($fee['description'] ?? ''),
                    'amount' => $fee['amount'],
                ]);
            }
        }

        $feeHistory = [];
        foreach (['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'] as $month) {
            $feeHistory[$month] = ['total' => 0, 'paid' => 0];
            foreach ($childVouchers as $childVoucher) {
                $feeHistory[$month]['total'] += (float) ($childVoucher['fee_history'][$month]['total'] ?? 0);
                $feeHistory[$month]['paid'] += (float) ($childVoucher['fee_history'][$month]['paid'] ?? 0);
            }
        }

        $familyId = $primary->parent_account_id ?: $primary->id;
        $monthCode = strtoupper(substr((string) ($childVouchers[0]['month'] ?? date('F')), 0, 3));
        $parentName = $primary->parentAccount->name
            ?? $primary->father_name
            ?? '—';

        return [
            'is_family' => true,
            'student' => $primary,
            'family_label' => [
                'names' => $students->pluck('student_name')->filter()->unique()->implode(', '),
                'classes' => $students->map(fn ($student) => trim(($student->class ?? '') . '/' . ($student->section ?? ''), '/'))->filter()->unique()->implode(', '),
                'roll_nos' => $students->pluck('student_code')->filter()->unique()->implode(', '),
                'parent' => $parentName,
                'campus' => $students->pluck('campus')->filter()->unique()->implode(', '),
            ],
            'pending_fees' => $pendingFees->values()->all(),
            'current_fees_subtotal' => collect($childVouchers)->sum('current_fees_subtotal'),
            'arrears_amount' => collect($childVouchers)->sum('arrears_amount'),
            'subtotal' => collect($childVouchers)->sum('subtotal'),
            'late_fee' => collect($childVouchers)->sum('late_fee'),
            'wallet_credit' => collect($childVouchers)->sum('wallet_credit'),
            'wallet_applied' => collect($childVouchers)->sum('wallet_applied'),
            'total' => collect($childVouchers)->sum('total'),
            'after_due_date' => collect($childVouchers)->sum('after_due_date'),
            'due_date' => collect($childVouchers)->max('due_date'),
            'voucher_validity' => collect($childVouchers)->max('voucher_validity'),
            'voucher_number' => 'FAM-' . str_pad((string) $familyId, 5, '0', STR_PAD_LEFT) . '-' . $monthCode . '-' . substr((string) ($childVouchers[0]['year'] ?? date('Y')), -2),
            'fee_history' => $feeHistory,
            'month' => $childVouchers[0]['month'] ?? date('F'),
            'year' => $childVouchers[0]['year'] ?? (int) date('Y'),
            'year_label' => $childVouchers[0]['year_label'] ?? (string) date('Y'),
        ];
    }

    private function walletKeyForStudent(Student $student): string
    {
        return (string) ($student->parent_account_id ?: $student->father_id_card ?: $student->father_name ?: $student->student_code);
    }

    private function formatFeeDescription(Student $student, string $title): string
    {
        if (strtolower(trim($title)) === 'admission fee') {
            return 'Generate Admission Fee';
        }

        if (preg_match('/Monthly Fee - (\w+) (\d+)/', $title, $matches)) {
            return "Monthly Fee Of {$matches[1]} ({$matches[2]})";
        }

        if (preg_match('/Transport Fee - (\w+) (\d+)/', $title, $matches)) {
            $routeLabel = !empty($student->transport_route)
                ? "Transport Route ({$student->transport_route})"
                : 'Transport Route';

            return "{$routeLabel} - {$matches[1]} ({$matches[2]})";
        }

        return $title !== '' ? $title : 'Custom Fee';
    }

    private function availableWalletCreditForStudent(Student $student): float
    {
        $advanceFee = null;

        if (!empty($student->parent_account_id)) {
            $advanceFee = AdvanceFee::where('parent_id', (string) $student->parent_account_id)->first();
        }

        if (!$advanceFee && !empty($student->father_id_card)) {
            $advanceFee = AdvanceFee::where('id_card_number', $student->father_id_card)->first();
        }

        return $advanceFee ? max(0, round((float) ($advanceFee->available_credit ?? 0), 2)) : 0.0;
    }

    public function classNamesForCampus(?string $campus): Collection
    {
        return $this->classesForCampus($campus)
            ->pluck('class_name')
            ->map(fn ($name) => trim((string) $name))
            ->filter(fn ($name) => $name !== '')
            ->unique()
            ->sort()
            ->values();
    }

    public function classesForCampus(?string $campus): Collection
    {
        if (empty($campus)) {
            return collect();
        }

        $campusNorm = strtolower(trim($campus));

        $classes = ClassModel::query()
            ->whereNotNull('class_name')
            ->whereRaw('LOWER(TRIM(campus)) = ?', [$campusNorm])
            ->orderBy('class_name', 'asc')
            ->get();

        if ($classes->isNotEmpty()) {
            return $classes;
        }

        return Student::query()
            ->whereNotNull('class')
            ->whereRaw('LOWER(TRIM(campus)) = ?', [$campusNorm])
            ->distinct()
            ->orderBy('class')
            ->pluck('class')
            ->map(fn ($name) => trim((string) $name))
            ->filter(fn ($name) => $name !== '')
            ->unique()
            ->sort()
            ->values()
            ->map(fn ($name) => (object) ['class_name' => $name]);
    }

    public function sectionsForCampusAndClass(?string $campus, string $class): Collection
    {
        $classNorm = strtolower(trim($class));
        if ($classNorm === '') {
            return collect();
        }

        $sectionsQuery = Section::query()
            ->whereNotNull('name')
            ->whereRaw('LOWER(TRIM(class)) = ?', [$classNorm]);

        if (!empty($campus)) {
            $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }

        $sections = $sectionsQuery->orderBy('name', 'asc')->get();
        if ($sections->isNotEmpty()) {
            return $sections;
        }

        $studentQuery = Student::query()
            ->whereNotNull('section')
            ->whereRaw('LOWER(TRIM(class)) = ?', [$classNorm]);

        if (!empty($campus)) {
            $studentQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }

        return $studentQuery
            ->distinct()
            ->orderBy('section')
            ->pluck('section')
            ->map(fn ($name) => trim((string) $name))
            ->filter(fn ($name) => $name !== '')
            ->unique()
            ->sort()
            ->values()
            ->map(fn ($name) => (object) ['name' => $name]);
    }
}
