<?php

namespace App\Http\Controllers;

use App\Models\GeneralSetting;
use App\Models\Loan;
use App\Models\Salary;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class SalaryLoanReportController extends Controller
{
    /**
     * Display the salary and loan report dashboard.
     */
    public function index(): View
    {
        $currentMonth = Carbon::now()->format('m');
        $currentMonthName = Carbon::now()->format('F');
        $currentYear = Carbon::now()->format('Y');

        $unpaidSalaries = Salary::with('staff')
            ->where('status', '!=', 'Paid')
            ->get();

        $unpaidAmount = $unpaidSalaries->sum(function ($salary) {
            $generated = $salary->salary_generated ?? 0;
            $paid = $salary->amount_paid ?? 0;
            return max(0, $generated - $paid);
        });

        $paidSalaries = Salary::with('staff')
            ->where('status', 'Paid')
            ->where('salary_month', $currentMonthName)
            ->where('year', $currentYear)
            ->get();

        $loanApplications = Loan::with('staff')
            ->whereIn('status', ['Pending', 'Approved'])
            ->get();

        $loanDefaulters = $this->getLoanDefaulters();

        return view('salary-loan.report', compact(
            'unpaidSalaries',
            'unpaidAmount',
            'paidSalaries',
            'loanApplications',
            'loanDefaulters',
            'currentMonth',
            'currentYear'
        ));
    }

    /**
     * Printable: Unpaid salaries report.
     */
    public function printUnpaid(): View
    {
        $unpaidSalaries = Salary::with('staff')
            ->where('status', '!=', 'Paid')
            ->orderBy('year', 'desc')
            ->orderBy('salary_month', 'desc')
            ->get();

        $totalGenerated = (float) $unpaidSalaries->sum(fn ($s) => (float) ($s->salary_generated ?? 0));
        $totalPaidSum = (float) $unpaidSalaries->sum(fn ($s) => (float) ($s->amount_paid ?? 0));
        $totalDue = (float) $unpaidSalaries->sum(function ($s) {
            $g = (float) ($s->salary_generated ?? 0);
            $p = (float) ($s->amount_paid ?? 0);

            return max(0, $g - $p);
        });

        return view('salary-loan.report-unpaid', [
            'unpaidSalaries' => $unpaidSalaries,
            'settings' => GeneralSetting::getSettings(),
            'printedAt' => Carbon::now()->format('d M Y, h:i A'),
            'totalGenerated' => $totalGenerated,
            'totalPaidSum' => $totalPaidSum,
            'totalDue' => $totalDue,
        ]);
    }

    /**
     * Printable: Paid salaries report (current month).
     */
    public function printPaid(): View
    {
        $currentMonthName = Carbon::now()->format('F');
        $currentYear = Carbon::now()->format('Y');

        $paidSalaries = Salary::with('staff')
            ->where('status', 'Paid')
            ->where('salary_month', $currentMonthName)
            ->where('year', $currentYear)
            ->orderBy('salary_month', 'desc')
            ->get();

        $totalAmountPaid = (float) $paidSalaries->sum(fn ($s) => (float) ($s->amount_paid ?? 0));

        return view('salary-loan.report-paid', [
            'paidSalaries' => $paidSalaries,
            'periodLabel' => $currentMonthName . ' ' . $currentYear,
            'settings' => GeneralSetting::getSettings(),
            'printedAt' => Carbon::now()->format('d M Y, h:i A'),
            'totalAmountPaid' => $totalAmountPaid,
        ]);
    }

    /**
     * Printable: Loan applications report.
     */
    public function printLoanApplications(): View
    {
        $loanApplications = Loan::with('staff')
            ->whereIn('status', ['Pending', 'Approved'])
            ->orderBy('created_at', 'desc')
            ->get();

        $totalRequested = (float) $loanApplications->sum(fn ($l) => (float) ($l->requested_amount ?? 0));
        $totalApproved = (float) $loanApplications->whereNotNull('approved_amount')->sum('approved_amount');

        return view('salary-loan.report-loan-applications', [
            'loanApplications' => $loanApplications,
            'settings' => GeneralSetting::getSettings(),
            'printedAt' => Carbon::now()->format('d M Y, h:i A'),
            'totalRequested' => $totalRequested,
            'totalApproved' => $totalApproved,
        ]);
    }

    /**
     * Printable: Loan defaulter teachers report.
     */
    public function printLoanDefaulters(): View
    {
        $loanDefaulters = $this->getLoanDefaulters();

        $totalApproved = (float) $loanDefaulters->sum(fn ($row) => (float) ($row['loan']->approved_amount ?? 0));
        $totalRepaid = (float) $loanDefaulters->sum(fn ($row) => (float) ($row['repaid'] ?? 0));
        $totalDue = (float) $loanDefaulters->sum(fn ($row) => (float) ($row['due'] ?? 0));

        return view('salary-loan.report-loan-defaulters', [
            'loanDefaulters' => $loanDefaulters,
            'settings' => GeneralSetting::getSettings(),
            'printedAt' => Carbon::now()->format('d M Y, h:i A'),
            'totalApproved' => $totalApproved,
            'totalRepaid' => $totalRepaid,
            'totalDue' => $totalDue,
        ]);
    }

    private function getLoanDefaulters()
    {
        $loans = Loan::with('staff')
            ->whereIn('status', ['Pending', 'Approved'])
            ->get();

        return $loans->map(function ($loan) {
            $repaid = Salary::where('staff_id', $loan->staff_id)->sum('loan_repayment');
            $approved = $loan->approved_amount ?? 0;
            $due = max(0, $approved - $repaid);

            return [
                'loan' => $loan,
                'repaid' => $repaid,
                'due' => $due,
            ];
        })->filter(function ($item) {
            return $item['due'] > 0;
        })->values();
    }
}
