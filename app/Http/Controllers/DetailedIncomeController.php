<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use App\Models\ClassModel;
use App\Models\CustomPayment;
use App\Models\GeneralSetting;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentPayment;
use App\Services\AdvanceFeeWallet;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DetailedIncomeController extends Controller
{
    public function index(Request $request): View
    {
        return view('reports.detailed-income', $this->buildReportData($request));
    }

    public function export(Request $request, string $format)
    {
        $data = $this->buildReportData($request);
        $records = $data['incomeRecords'];

        if (!in_array($format, ['excel', 'csv', 'pdf'], true)) {
            return redirect()->route($this->detailedIncomeRouteName())->with('error', 'Invalid export format.');
        }

        if ($format === 'pdf') {
            $pdfData = array_merge($data, [
                'settings' => GeneralSetting::getSettings(),
                'printedAt' => now()->format('d M Y, h:i A'),
            ]);

            if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                $pdf = Pdf::loadView('reports.detailed-income-pdf', $pdfData);

                return $pdf->stream('detailed_income_' . now()->format('Ymd_His') . '.pdf');
            }

            return response()->view('reports.detailed-income-pdf', $pdfData);
        }

        return $this->exportSpreadsheet($records, $format);
    }

    public function print(Request $request): View
    {
        return view('reports.detailed-income-print', array_merge($this->buildReportData($request), [
            'settings' => GeneralSetting::getSettings(),
            'printedAt' => now()->format('d M Y, h:i A'),
        ]));
    }

    private function detailedIncomeRouteName(): string
    {
        return Auth::guard('accountant')->check()
            ? 'accountant.detailed-income'
            : 'reports.detailed-income';
    }

    private function exportSpreadsheet($records, string $format): StreamedResponse
    {
        $isExcel = $format === 'excel';
        $filename = 'detailed_income_' . now()->format('Ymd_His') . ($isExcel ? '.xls' : '.csv');
        $headers = [
            'Content-Type' => $isExcel ? 'application/vnd.ms-excel' : 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($records, $isExcel): void {
            $file = fopen('php://output', 'w');
            if ($isExcel) {
                fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            }

            fputcsv($file, ['#', 'Code', 'Student', 'Parent', 'Class', 'Title', 'Amount Paid', 'Discount', 'Method', 'Received By', 'Payment Date/Time']);

            foreach ($records as $index => $record) {
                $paymentDate = $record['payment_date'] ?? null;
                $formattedDate = $paymentDate
                    ? \Carbon\Carbon::parse($paymentDate)->format('d-m-Y h:i A')
                    : 'N/A';

                fputcsv($file, [
                    $index + 1,
                    $record['student_code'] ?? 'N/A',
                    $record['student_name'] ?? 'N/A',
                    $record['parent_name'] ?? 'N/A',
                    $record['class'] ?? 'N/A',
                    $record['payment_title'] ?? 'N/A',
                    (float) ($record['payment_amount'] ?? 0),
                    (float) ($record['discount'] ?? 0),
                    $record['method'] ?? 'N/A',
                    $record['received_by'] ?? 'N/A',
                    $formattedDate,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function buildReportData(Request $request): array
    {
        $filterCampus = $request->get('filter_campus');
        $filterClass = $request->get('filter_class');
        $filterSection = $request->get('filter_section');
        $filterMonth = $request->get('filter_month');
        $filterDate = $request->get('filter_date');
        $filterYear = $request->get('filter_year');
        $filterMethod = $request->get('filter_method');

        if (Auth::guard('accountant')->check()) {
            $accountantCampus = Auth::guard('accountant')->user()->campus ?? null;
            if ($accountantCampus) {
                $filterCampus = $accountantCampus;
                $request->merge(['filter_campus' => $accountantCampus]);
            }
        }

        $campuses = Campus::orderBy('campus_name', 'asc')->pluck('campus_name');
        if (Auth::guard('accountant')->check()) {
            $accountantCampus = Auth::guard('accountant')->user()->campus ?? null;
            if ($accountantCampus) {
                $campuses = $campuses->filter(fn ($campus) => $campus === $accountantCampus)->values();
            }
        }
        if ($campuses->isEmpty()) {
            $campusesFromPayments = StudentPayment::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromCustom = CustomPayment::whereNotNull('campus')->distinct()->pluck('campus');
            $campuses = $campusesFromPayments->merge($campusesFromCustom)->unique()->sort()->values();
        }

        $classesQuery = ClassModel::whereNotNull('class_name');
        if ($filterCampus) {
            $classesQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim((string) $filterCampus))]);
        }
        $classes = $classesQuery->distinct()->pluck('class_name')->sort()->values();
        if ($classes->isEmpty()) {
            $classesFromStudents = Student::whereNotNull('class');
            if ($filterCampus) {
                $classesFromStudents->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim((string) $filterCampus))]);
            }
            $classesFromStudents = $classesFromStudents->distinct()->pluck('class')->sort()->values();
            $classes = $classesFromStudents->isEmpty()
                ? collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th'])
                : $classesFromStudents;
        }

        $sectionsQuery = Section::whereNotNull('name');
        if ($filterCampus) {
            $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim((string) $filterCampus))]);
        }
        if ($filterClass) {
            $sectionsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim((string) $filterClass))]);
        }
        $sections = $sectionsQuery->distinct()->pluck('name')->sort()->values();
        if ($sections->isEmpty()) {
            $sectionsFromSubjects = \App\Models\Subject::whereNotNull('section');
            if ($filterCampus) {
                $sectionsFromSubjects->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim((string) $filterCampus))]);
            }
            if ($filterClass) {
                $sectionsFromSubjects->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim((string) $filterClass))]);
            }
            $sectionsFromSubjects = $sectionsFromSubjects->distinct()->pluck('section')->sort()->values();
            $sections = $sectionsFromSubjects->isEmpty()
                ? collect(['A', 'B', 'C', 'D', 'E'])
                : $sectionsFromSubjects;
        }

        $months = collect([
            '01' => 'January',
            '02' => 'February',
            '03' => 'March',
            '04' => 'April',
            '05' => 'May',
            '06' => 'June',
            '07' => 'July',
            '08' => 'August',
            '09' => 'September',
            '10' => 'October',
            '11' => 'November',
            '12' => 'December',
        ]);

        $currentYear = (int) date('Y');
        $years = collect();
        for ($i = 0; $i < 6; $i++) {
            $years->push($currentYear - $i);
        }

        $methodGroups = $this->incomeMethodGroups();
        $preferredMethodOrder = array_keys($methodGroups);
        $canonicalizeDisplayMethod = fn ($value) => $this->canonicalizeIncomeMethod($value, $methodGroups);

        $methodsFromPaymentsQuery = StudentPayment::query()->ledgerActive();
        $this->scopePaidStudentPayments($methodsFromPaymentsQuery);
        $methodsFromPayments = $methodsFromPaymentsQuery
            ->whereNotNull('method')
            ->distinct()
            ->pluck('method');

        $methodsFromCustom = CustomPayment::query()
            ->whereNotNull('method')
            ->distinct()
            ->pluck('method');

        $methods = $methodsFromPayments
            ->merge($methodsFromCustom)
            ->map(fn ($m) => $this->canonicalizeIncomeMethod($m, $methodGroups))
            ->filter(fn ($m) => $m !== 'N/A')
            ->unique(fn ($m) => strtolower(trim((string) $m)))
            ->sortBy(function ($m) use ($preferredMethodOrder) {
                $i = array_search($m, $preferredMethodOrder, true);

                return $i === false ? 9999 : $i;
            })
            ->values();

        $methods = collect($preferredMethodOrder)->merge($methods)->unique()->values();

        $incomeRecords = collect();

        $studentPaymentsQuery = StudentPayment::query()->ledgerActive();
        $this->scopePaidStudentPayments($studentPaymentsQuery);
        $this->scopeStudentPaymentsCampus($studentPaymentsQuery, $filterCampus);
        if ($filterMonth) {
            $studentPaymentsQuery->whereMonth('payment_date', $filterMonth);
        }
        if ($filterDate) {
            $studentPaymentsQuery->whereDate('payment_date', $filterDate);
        }
        if ($filterYear) {
            $studentPaymentsQuery->whereYear('payment_date', $filterYear);
        }
        if ($filterMethod) {
            $this->applyIncomeMethodFilter($studentPaymentsQuery, (string) $filterMethod, $methodGroups);
        }

        if ($filterClass || $filterSection) {
            $eligibleStudentCodes = Student::query()
                ->when($filterCampus, fn ($q) => $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim((string) $filterCampus))]))
                ->when($filterClass, fn ($q) => $q->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim((string) $filterClass))]))
                ->when($filterSection, fn ($q) => $q->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim((string) $filterSection))]))
                ->pluck('student_code')
                ->filter()
                ->values();

            if ($eligibleStudentCodes->isEmpty()) {
                $studentPaymentsQuery->whereRaw('1 = 0');
            } else {
                $studentPaymentsQuery->whereIn('student_code', $eligibleStudentCodes);
            }
        }

        foreach ($studentPaymentsQuery->get() as $payment) {
            $cash = round(max(0, (float) ($payment->payment_amount ?? 0)), 2);
            if ($cash <= 0.00001 && (float) ($payment->discount ?? 0) <= 0.00001) {
                continue;
            }

            $student = Student::where('student_code', $payment->student_code)->first();

            if ($filterClass && (!$student || strtolower(trim((string) $student->class)) !== strtolower(trim((string) $filterClass)))) {
                continue;
            }
            if ($filterSection && (!$student || strtolower(trim((string) $student->section)) !== strtolower(trim((string) $filterSection)))) {
                continue;
            }

            // Discount handling:
            // Some installment flows store the discount on the generated/installment (unpaid) row,
            // while the paid ledger row has discount=0. For reporting, show the effective discount.
            $effectiveDiscount = (float) ($payment->discount ?? 0);
            $title = (string) ($payment->payment_title ?? '');
            if ($effectiveDiscount <= 0.00001 && $title !== '' && preg_match('/\/\d+$/', $title)) {
                $effectiveDiscount = (float) StudentPayment::query()
                    ->ledgerActive()
                    ->where('student_code', $payment->student_code)
                    ->where('payment_title', $title)
                    ->whereIn('method', ['Generated', 'Installment'])
                    ->sum('discount');
            }

            $incomeRecords->push([
                'type' => 'Student Payment',
                'student_code' => $payment->student_code,
                'student_name' => $student ? $student->student_name : 'N/A',
                'parent_name' => $student ? ($student->father_name ?? 'N/A') : 'N/A',
                'campus' => $payment->campus ?? ($student ? $student->campus : 'N/A'),
                'class' => $student ? $student->class : 'N/A',
                'section' => $student ? $student->section : 'N/A',
                'payment_title' => $title,
                'payment_amount' => $payment->payment_amount,
                'discount' => $effectiveDiscount,
                'payment_date' => $payment->payment_date,
                'method' => $canonicalizeDisplayMethod($payment->method),
                'received_by' => $payment->accountant ?? 'N/A',
            ]);
        }

        $customPaymentsQuery = CustomPayment::query();
        if ($filterCampus) {
            $customPaymentsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim((string) $filterCampus))]);
        }
        if ($filterMonth) {
            $customPaymentsQuery->whereMonth('payment_date', $filterMonth);
        }
        if ($filterDate) {
            $customPaymentsQuery->whereDate('payment_date', $filterDate);
        }
        if ($filterYear) {
            $customPaymentsQuery->whereYear('payment_date', $filterYear);
        }
        if ($filterMethod) {
            $filterKey = strtolower(trim((string) $filterMethod));
            if ($filterKey !== '' && $filterKey !== 'generated') {
                $customPaymentsQuery->where('method', $filterMethod);
            }
        }

        foreach ($customPaymentsQuery->get() as $payment) {
            if ($payment->isMirroredOnStudentLedger()) {
                continue;
            }
            if ($filterClass || $filterSection) {
                continue;
            }
            $incomeRecords->push([
                'type' => 'Custom Payment',
                'student_code' => 'N/A',
                'student_name' => 'N/A',
                'parent_name' => 'N/A',
                'campus' => $payment->campus ?? 'N/A',
                'class' => 'N/A',
                'section' => 'N/A',
                'payment_title' => $payment->payment_title,
                'payment_amount' => $payment->payment_amount,
                'discount' => 0,
                'payment_date' => $payment->payment_date,
                'method' => $canonicalizeDisplayMethod($payment->method),
                'received_by' => $payment->accountant ?? 'N/A',
            ]);
        }

        $incomeRecords = $incomeRecords->sortByDesc('payment_date')->values();

        return compact(
            'campuses',
            'classes',
            'sections',
            'months',
            'years',
            'methods',
            'incomeRecords',
            'filterCampus',
            'filterClass',
            'filterSection',
            'filterMonth',
            'filterDate',
            'filterYear',
            'filterMethod'
        );
    }

    public function getClassesByCampus(Request $request): JsonResponse
    {
        $campus = $request->get('campus');

        $classesQuery = ClassModel::whereNotNull('class_name');
        if ($campus) {
            $classesQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        if (Auth::guard('accountant')->check()) {
            $accountantCampus = Auth::guard('accountant')->user()->campus ?? null;
            if ($accountantCampus) {
                $classesQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($accountantCampus))]);
            }
        }
        $classes = $classesQuery->distinct()->pluck('class_name')->sort()->values();

        if ($classes->isEmpty()) {
            $classesFromStudents = Student::whereNotNull('class');
            if ($campus) {
                $classesFromStudents->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            $classesFromStudents = $classesFromStudents->distinct()->pluck('class')->sort()->values();
            $classes = $classesFromStudents->isEmpty()
                ? collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th'])
                : $classesFromStudents;
        }

        $classes = $classes->map(fn ($class) => trim((string) $class))
            ->filter(fn ($class) => $class !== '')
            ->unique()
            ->sort()
            ->values();

        return response()->json(['classes' => $classes]);
    }

    public function getSectionsByClass(Request $request): JsonResponse
    {
        $class = $request->get('class');
        $campus = $request->get('campus');

        if (!$class) {
            return response()->json(['sections' => []]);
        }

        $sectionsQuery = Section::whereNotNull('name')
            ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
        if ($campus) {
            $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        if (Auth::guard('accountant')->check()) {
            $accountantCampus = Auth::guard('accountant')->user()->campus ?? null;
            if ($accountantCampus) {
                $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($accountantCampus))]);
            }
        }
        $sections = $sectionsQuery->distinct()->pluck('name')->sort()->values();

        if ($sections->isEmpty()) {
            $sectionsFromSubjects = \App\Models\Subject::whereNotNull('section')
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
            if ($campus) {
                $sectionsFromSubjects->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            $sections = $sectionsFromSubjects->distinct()->pluck('section')->sort()->values();
        }

        return response()->json(['sections' => $sections]);
    }

    /**
     * @return array<string, list<string>>
     */
    private function incomeMethodGroups(): array
    {
        return [
            'Cash Payment' => ['cash payment', 'cash'],
            'Bank Transfer' => ['bank transfer'],
            'Cheque' => ['cheque', 'check'],
            'Online Payment' => ['online payment', 'online'],
            'Card Payment' => ['card payment', 'card'],
            'Wallet' => ['wallet', 'wallet payment', 'advance fee', 'advance fees'],
        ];
    }

    private function canonicalizeIncomeMethod(?string $method, array $methodGroups): string
    {
        if (AdvanceFeeWallet::isWalletMethod($method)) {
            return 'Wallet';
        }

        $key = strtolower(trim((string) $method));
        if ($key === '' || AdvanceFeeWallet::isUnpaidLedgerMethod($method)) {
            return 'N/A';
        }

        foreach ($methodGroups as $canonical => $variants) {
            if (in_array($key, $variants, true)) {
                return $canonical;
            }
        }

        return trim((string) $method) ?: 'N/A';
    }

    private function scopePaidStudentPayments($query): void
    {
        $query->where(function ($q) {
            $q->whereRaw('LOWER(TRIM(method)) NOT IN (?, ?)', ['generated', 'installment'])
                ->whereNotNull('method')
                ->whereRaw('TRIM(method) != ?', ['']);
        });
    }

    /**
     * Match payments to campus via payment row or student profile (wallet rows often have empty campus).
     */
    private function scopeStudentPaymentsCampus($query, ?string $filterCampus): void
    {
        if (!$filterCampus || trim((string) $filterCampus) === '') {
            return;
        }

        $campusLower = strtolower(trim($filterCampus));
        $studentCodes = Student::query()
            ->whereRaw('LOWER(TRIM(campus)) = ?', [$campusLower])
            ->whereNotNull('student_code')
            ->pluck('student_code')
            ->filter()
            ->values();

        $query->where(function ($q) use ($campusLower, $studentCodes) {
            $q->whereRaw('LOWER(TRIM(campus)) = ?', [$campusLower]);
            if ($studentCodes->isNotEmpty()) {
                $q->orWhereIn('student_code', $studentCodes);
            }
        });
    }

    private function applyIncomeMethodFilter($query, string $filterMethod, array $methodGroups, string $column = 'method'): void
    {
        $filterKey = strtolower(trim($filterMethod));
        if ($filterKey === '' || AdvanceFeeWallet::isUnpaidLedgerMethod($filterMethod)) {
            return;
        }

        if ($filterKey === 'wallet') {
            AdvanceFeeWallet::applyWalletMethodWhere($query, $column);

            return;
        }

        $variants = null;
        foreach ($methodGroups as $canonical => $groupVariants) {
            if (strtolower(trim($canonical)) === $filterKey) {
                $variants = $groupVariants;
                break;
            }
        }

        if ($variants) {
            $query->where(function ($q) use ($variants, $column) {
                foreach ($variants as $v) {
                    $q->orWhereRaw('LOWER(TRIM(' . $column . ')) = ?', [strtolower(trim($v))]);
                }
            });

            return;
        }

        $query->whereRaw('LOWER(TRIM(' . $column . ')) = ?', [$filterKey]);
    }
}
