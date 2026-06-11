<?php

namespace App\Http\Controllers;

use App\Models\Salary;
use App\Models\Staff;
use App\Models\Campus;
use App\Models\GeneralSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StaffSalaryReportController extends Controller
{
    /**
     * Display the staff salary reports with filters.
     */
    public function index(Request $request): View
    {
        $filterCampus = $this->normalizeFilter($request->get('filter_campus'));
        $filterMonth = $this->normalizeFilter($request->get('filter_month'));
        $filterYear = $this->normalizeFilter($request->get('filter_year'));
        $filterStaffId = $this->normalizeFilter($request->get('staff_id'));

        $campuses = Campus::orderBy('campus_name', 'asc')->pluck('campus_name');
        if ($campuses->isEmpty()) {
            $campuses = Staff::whereNotNull('campus')->distinct()->pluck('campus')->sort()->values();
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

        $currentYear = date('Y');
        $years = collect();
        for ($i = 0; $i < 6; $i++) {
            $years->push($currentYear - $i);
        }

        $filtersApplied = $this->hasActiveFilters($filterCampus, $filterMonth, $filterYear, $filterStaffId);
        $salaryRecords = collect();
        $totals = [
            'basic' => 0,
            'salary_generated' => 0,
            'amount_paid' => 0,
            'loan_repayment' => 0,
        ];
        if ($filtersApplied) {
            $salaries = $this->buildSalaryQuery($filterCampus, $filterMonth, $filterYear, $filterStaffId)
                ->orderBy('year', 'desc')
                ->orderBy('salary_month', 'desc')
                ->get();
            $salaryRecords = $this->mapSalaryRecords($salaries, true);
            $totals = [
                'basic' => $salaries->sum('basic'),
                'salary_generated' => $salaries->sum('salary_generated'),
                'amount_paid' => $salaries->sum('amount_paid'),
                'loan_repayment' => $salaries->sum('loan_repayment'),
            ];
        }

        return view('reports.staff-salary', compact(
            'campuses',
            'months',
            'years',
            'salaryRecords',
            'totals',
            'filtersApplied',
            'filterCampus',
            'filterMonth',
            'filterYear',
            'filterStaffId'
        ));
    }

    /**
     * Print staff salary report (browser print).
     */
    public function print(Request $request): View|RedirectResponse
    {
        $filterCampus = $this->normalizeFilter($request->get('filter_campus'));
        $filterMonth = $this->normalizeFilter($request->get('filter_month'));
        $filterYear = $this->normalizeFilter($request->get('filter_year'));
        $filterStaffId = $this->normalizeFilter($request->get('staff_id'));

        if (!$this->hasActiveFilters($filterCampus, $filterMonth, $filterYear, $filterStaffId)) {
            return redirect()
                ->route('reports.staff-salary')
                ->with('error', 'Please apply at least one filter (Campus, Staff, Month, or Year) before printing.');
        }

        $salaries = $this->buildSalaryQuery($filterCampus, $filterMonth, $filterYear, $filterStaffId)
            ->orderBy('year', 'desc')
            ->orderBy('salary_month', 'desc')
            ->get();

        $salaryRecords = $this->mapSalaryRecords($salaries, true);
        $filterDescription = $this->buildFilterDescription($filterCampus, $filterMonth, $filterYear, $filterStaffId);
        $totals = [
            'basic' => $salaries->sum('basic'),
            'salary_generated' => $salaries->sum('salary_generated'),
            'amount_paid' => $salaries->sum('amount_paid'),
            'loan_repayment' => $salaries->sum('loan_repayment'),
        ];

        $settings = GeneralSetting::getSettings();
        $schoolName = trim((string) ($settings->school_name ?? $settings->system_name ?? config('app.name', 'School Management System')));
        $schoolEmail = trim((string) ($settings->school_email ?? ''));
        $schoolAddress = trim((string) ($settings->address ?? ''));
        $schoolPhone = trim((string) ($settings->school_phone ?? ''));

        return view('reports.staff-salary-print', [
            'salaryRecords' => $salaryRecords,
            'filterDescription' => $filterDescription,
            'totals' => $totals,
            'schoolName' => $schoolName,
            'schoolEmail' => $schoolEmail,
            'schoolAddress' => $schoolAddress,
            'schoolPhone' => $schoolPhone,
            'schoolLogoUrl' => $this->resolveSchoolLogoUrl($settings->logo ?? null),
        ]);
    }

    /**
     * Export staff salary report (csv, excel, pdf).
     */
    public function export(Request $request, string $format): RedirectResponse|StreamedResponse|\Illuminate\Http\Response
    {
        $format = strtolower(trim($format));
        if (!in_array($format, ['csv', 'excel', 'pdf'], true)) {
            abort(404);
        }

        $filterCampus = $this->normalizeFilter($request->get('filter_campus'));
        $filterMonth = $this->normalizeFilter($request->get('filter_month'));
        $filterYear = $this->normalizeFilter($request->get('filter_year'));
        $filterStaffId = $this->normalizeFilter($request->get('staff_id'));

        if (!$this->hasActiveFilters($filterCampus, $filterMonth, $filterYear, $filterStaffId)) {
            return redirect()
                ->route('reports.staff-salary')
                ->with('error', 'Please apply at least one filter (Campus, Staff, Month, or Year) before exporting.');
        }

        $salaries = $this->buildSalaryQuery($filterCampus, $filterMonth, $filterYear, $filterStaffId)
            ->orderBy('year', 'desc')
            ->orderBy('salary_month', 'desc')
            ->get();

        $filenameDate = now()->format('Y-m-d_H-i');
        $filterDescription = $this->buildFilterDescription($filterCampus, $filterMonth, $filterYear, $filterStaffId);

        if ($format === 'csv') {
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="staff-salary-' . $filenameDate . '.csv"',
            ];

            $records = $this->mapSalaryRecords($salaries, false);

            $callback = function () use ($records, $filterDescription) {
                $stream = fopen('php://output', 'w');
                fprintf($stream, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($stream, ['Filters: ' . $filterDescription]);
                fputcsv($stream, [
                    '#', 'Staff Name', 'Emp ID', 'Campus', 'Designation', 'Salary Month', 'Year',
                    'Present', 'Absent', 'Late', 'Early Exit', 'Basic', 'Salary Generated',
                    'Amount Paid', 'Loan Repayment', 'Status',
                ]);

                foreach ($records as $index => $r) {
                    fputcsv($stream, [
                        $index + 1,
                        $r['staff_name'],
                        $r['emp_id'],
                        $r['campus'],
                        $r['designation'],
                        $r['salary_month'],
                        $r['year'],
                        $r['present'],
                        $r['absent'],
                        $r['late'],
                        $r['early_exit'],
                        number_format((float) $r['basic'], 2, '.', ''),
                        number_format((float) $r['salary_generated'], 2, '.', ''),
                        number_format((float) $r['amount_paid'], 2, '.', ''),
                        number_format((float) $r['loan_repayment'], 2, '.', ''),
                        $r['status'],
                    ]);
                }

                $totalBasic = $records->sum(fn ($r) => (float) $r['basic']);
                $totalGen = $records->sum(fn ($r) => (float) $r['salary_generated']);
                $totalPaid = $records->sum(fn ($r) => (float) $r['amount_paid']);
                $totalLoan = $records->sum(fn ($r) => (float) $r['loan_repayment']);
                fputcsv($stream, [
                    '', '', '', '', '', '', '', '', '', '', '', 'TOTAL',
                    number_format($totalBasic, 2, '.', ''),
                    number_format($totalGen, 2, '.', ''),
                    number_format($totalPaid, 2, '.', ''),
                    number_format($totalLoan, 2, '.', ''),
                    '',
                ]);

                fclose($stream);
            };

            return response()->stream($callback, 200, $headers);
        }

        $rows = $this->mapSalaryRecords($salaries, true)->map(function ($r, $index) {
            return [
                '#' => $index + 1,
                'Staff Name' => $r['staff_name'],
                'Emp ID' => $r['emp_id'],
                'Campus' => $r['campus'],
                'Designation' => $r['designation'],
                'Salary Month' => $r['salary_month'],
                'Year' => $r['year'],
                'Present' => $r['present'],
                'Absent' => $r['absent'],
                'Late' => $r['late'],
                'Early Exit' => $r['early_exit'],
                'Basic' => $r['basic'],
                'Salary Generated' => $r['salary_generated'],
                'Amount Paid' => $r['amount_paid'],
                'Loan Repayment' => $r['loan_repayment'],
                'Status' => $r['status'],
            ];
        });

        $html = view('reports.staff-salary-export-excel', [
            'rows' => $rows,
            'filterDescription' => $filterDescription,
        ])->render();

        if ($format === 'excel') {
            return response($html, 200, [
                'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="staff-salary-' . $filenameDate . '.xls"',
            ]);
        }

        $settings = GeneralSetting::getSettings();
        $schoolName = trim((string) ($settings->school_name ?? $settings->system_name ?? config('app.name', 'School Management System')));
        $schoolEmail = trim((string) ($settings->school_email ?? ''));
        $schoolAddress = trim((string) ($settings->address ?? ''));
        $schoolPhone = trim((string) ($settings->school_phone ?? ''));
        $schoolLogoUrl = $this->resolveSchoolLogoUrl($settings->logo ?? null);

        $totals = [
            'basic' => $salaries->sum('basic'),
            'salary_generated' => $salaries->sum('salary_generated'),
            'amount_paid' => $salaries->sum('amount_paid'),
            'loan_repayment' => $salaries->sum('loan_repayment'),
        ];

        $pdf = Pdf::loadView('reports.staff-salary-pdf', [
            'rows' => $rows,
            'filterDescription' => $filterDescription,
            'schoolName' => $schoolName,
            'schoolEmail' => $schoolEmail,
            'schoolAddress' => $schoolAddress,
            'schoolPhone' => $schoolPhone,
            'schoolLogoUrl' => $schoolLogoUrl,
            'totals' => $totals,
            'printedAt' => now()->format('d-m-Y H:i'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('staff-salary-' . $filenameDate . '.pdf');
    }

    private function resolveSchoolLogoUrl(?string $logoPath): ?string
    {
        $logoPath = trim((string) $logoPath);
        if ($logoPath === '') {
            return null;
        }

        if (str_starts_with($logoPath, 'http://') || str_starts_with($logoPath, 'https://')) {
            return $logoPath;
        }

        if (str_starts_with($logoPath, 'storage/')) {
            return asset($logoPath);
        }

        return asset('storage/' . ltrim($logoPath, '/'));
    }

    private function buildFilterDescription(
        ?string $filterCampus,
        ?string $filterMonth,
        ?string $filterYear,
        ?string $filterStaffId
    ): string {
        $parts = [];
        if ($filterCampus !== null) {
            $parts[] = 'Campus: ' . $filterCampus;
        }
        if ($filterStaffId !== null) {
            $staff = Staff::find((int) $filterStaffId);
            $parts[] = 'Staff: ' . ($staff ? $staff->name : $filterStaffId);
        }
        if ($filterMonth !== null) {
            $parts[] = 'Month: ' . $this->monthNumberToName($filterMonth);
        }
        if ($filterYear !== null) {
            $parts[] = 'Year: ' . $filterYear;
        }

        return $parts !== [] ? implode(' | ', $parts) : 'All';
    }

    /**
     * Get staff by campus (AJAX).
     */
    public function getStaffByCampus(Request $request): JsonResponse
    {
        $campus = $this->normalizeFilter($request->get('campus'));

        if ($campus === null) {
            return response()->json(['staff' => []]);
        }

        $staff = Staff::whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campus)])
            ->orderBy('name')
            ->get(['id', 'name', 'emp_id']);

        return response()->json(['staff' => $staff]);
    }

    /**
     * Get salary records (AJAX).
     */
    public function getSalaryRecords(Request $request): JsonResponse
    {
        $filterCampus = $this->normalizeFilter($request->get('filter_campus'));
        $filterMonth = $this->normalizeFilter($request->get('filter_month'));
        $filterYear = $this->normalizeFilter($request->get('filter_year'));
        $filterStaffId = $this->normalizeFilter($request->get('staff_id'));

        if (!$this->hasActiveFilters($filterCampus, $filterMonth, $filterYear, $filterStaffId)) {
            return response()->json([
                'success' => true,
                'records' => [],
                'total_basic' => '0.00',
                'total_salary_generated' => '0.00',
                'total_amount_paid' => '0.00',
                'total_loan_repayment' => '0.00',
            ]);
        }

        $salaries = $this->buildSalaryQuery($filterCampus, $filterMonth, $filterYear, $filterStaffId)
            ->orderBy('year', 'desc')
            ->orderBy('salary_month', 'desc')
            ->get();

        $salaryRecords = $this->mapSalaryRecords($salaries, true);

        $totalBasic = $salaries->sum('basic');
        $totalSalaryGenerated = $salaries->sum('salary_generated');
        $totalAmountPaid = $salaries->sum('amount_paid');
        $totalLoanRepayment = $salaries->sum('loan_repayment');

        return response()->json([
            'success' => true,
            'records' => $salaryRecords->values()->all(),
            'total_basic' => number_format($totalBasic, 2),
            'total_salary_generated' => number_format($totalSalaryGenerated, 2),
            'total_amount_paid' => number_format($totalAmountPaid, 2),
            'total_loan_repayment' => number_format($totalLoanRepayment, 2),
        ]);
    }

    /**
     * Display the summarized salary & attendance report for all staff.
     */
    public function summarized(Request $request): View
    {
        $filterCampus = $this->normalizeFilter($request->get('filter_campus'));
        $filterYear = $request->get('filter_year', date('Y'));

        $staffQuery = Staff::query();

        if ($filterCampus !== null) {
            $staffQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($filterCampus)]);
        }

        $allStaff = $staffQuery->orderBy('name')->get();

        $monthNames = [
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
        ];

        $campuses = Campus::orderBy('campus_name', 'asc')->pluck('campus_name');
        if ($campuses->isEmpty()) {
            $campuses = Staff::whereNotNull('campus')->distinct()->pluck('campus')->sort()->values();
        }

        $currentYear = date('Y');
        $years = collect();
        for ($i = 0; $i < 6; $i++) {
            $years->push($currentYear - $i);
        }

        $staffReports = collect();

        foreach ($allStaff as $staff) {
            $salaries = Salary::where('staff_id', $staff->id)
                ->where('year', $filterYear)
                ->get()
                ->keyBy('salary_month');

            $monthlyData = [];
            foreach ($monthNames as $monthNum => $monthName) {
                $salary = $salaries->get($monthNum);

                $monthlyData[] = [
                    'month' => $monthName,
                    'month_num' => $monthNum,
                    'present' => $salary ? $salary->present : 0,
                    'absent' => $salary ? $salary->absent : 0,
                    'late' => $salary ? $salary->late : 0,
                    'leaves' => $salary ? $salary->leaves : 0,
                    'holidays' => $salary ? $salary->holidays : 0,
                    'sundays' => $salary ? $salary->sundays : 0,
                    'basic_salary' => $salary ? $salary->basic : ($staff->salary ?? 0),
                    'salary_generated' => $salary ? $salary->salary_generated : 0,
                    'amount_paid' => $salary ? $salary->amount_paid : 0,
                    'loan_repayment' => $salary ? $salary->loan_repayment : 0,
                ];
            }

            $staffReports->push([
                'staff' => $staff,
                'monthly_data' => $monthlyData,
            ]);
        }

        return view('reports.staff-salary-summarized', compact(
            'staffReports',
            'filterCampus',
            'filterYear',
            'campuses',
            'years',
            'monthNames'
        ));
    }

    private function normalizeFilter(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return trim((string) $value);
    }

    private function hasActiveFilters(?string $campus, ?string $month, ?string $year, ?string $staffId): bool
    {
        return $campus !== null
            || $month !== null
            || $year !== null
            || $staffId !== null;
    }

    private function buildSalaryQuery(
        ?string $filterCampus,
        ?string $filterMonth,
        ?string $filterYear,
        ?string $filterStaffId
    ): Builder {
        $query = Salary::with('staff');

        if ($filterMonth !== null) {
            $monthName = $this->monthNumberToName($filterMonth);
            $query->where(function (Builder $q) use ($filterMonth, $monthName) {
                $q->where('salary_month', $filterMonth)
                    ->orWhere('salary_month', $monthName);
            });
        }
        if ($filterYear !== null) {
            $query->where('year', $filterYear);
        }
        if ($filterStaffId !== null) {
            $query->where('staff_id', (int) $filterStaffId);
        }
        if ($filterCampus !== null) {
            $campusKey = strtolower($filterCampus);
            $query->whereHas('staff', function ($q) use ($campusKey) {
                $q->whereRaw('LOWER(TRIM(campus)) = ?', [$campusKey]);
            });
        }

        return $query;
    }

    private function monthNumberToName(string $monthNumber): string
    {
        $months = [
            '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April',
            '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August',
            '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December',
        ];

        return $months[$monthNumber] ?? $monthNumber;
    }

    private function mapSalaryRecords($salaries, bool $formatMoney = false)
    {
        $monthNames = [
            '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April',
            '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August',
            '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December',
        ];

        $records = collect();

        foreach ($salaries as $salary) {
            if (!$salary->staff) {
                continue;
            }

            $records->push([
                'staff_id' => $salary->staff_id,
                'staff_name' => $salary->staff->name,
                'emp_id' => $salary->staff->emp_id ?? 'N/A',
                'campus' => $salary->staff->campus ?? 'N/A',
                'designation' => $salary->staff->designation ?? 'N/A',
                'photo' => $salary->staff->photo,
                'salary_month' => $monthNames[$salary->salary_month] ?? $salary->salary_month,
                'year' => $salary->year,
                'present' => $salary->present,
                'absent' => $salary->absent,
                'late' => $salary->late,
                'early_exit' => $salary->early_exit ?? 0,
                'basic' => $formatMoney ? number_format($salary->basic, 2) : $salary->basic,
                'salary_generated' => $formatMoney ? number_format($salary->salary_generated, 2) : $salary->salary_generated,
                'amount_paid' => $formatMoney ? number_format($salary->amount_paid, 2) : $salary->amount_paid,
                'loan_repayment' => $formatMoney ? number_format($salary->loan_repayment, 2) : $salary->loan_repayment,
                'status' => $salary->status,
            ]);
        }

        return $records;
    }
}
