<?php

namespace App\Http\Controllers;

use App\Models\StudentPayment;
use App\Models\Student;
use App\Models\ManagementExpense;
use App\Models\Campus;
use App\Models\ClassModel;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeePaymentController extends Controller
{
    /**
     * Display the fee payment page.
     */
    public function index(): View
    {
        // Calculate Unpaid Invoices - Count of students with unpaid fees
        $students = Student::whereNotNull('student_code')
            ->whereNotNull('monthly_fee')
            ->where('monthly_fee', '>', 0)
            ->get();
        
        $unpaidInvoices = 0;
        foreach ($students as $student) {
            $totalPaid = StudentPayment::where('student_code', $student->student_code)
                ->where('method', '!=', 'Generated') // Only count actual payments, not generated fees
                ->sum('payment_amount');
            
            $monthlyFee = $student->monthly_fee ?? 0;
            if ($monthlyFee > $totalPaid) {
                $unpaidInvoices++;
            }
        }
        
        // Calculate Income Today - Sum of actual payments (excluding generated fees)
        $incomeToday = StudentPayment::whereDate('payment_date', today())
            ->where('method', '!=', 'Generated') // Only actual payments
            ->sum('payment_amount');
        
        // Calculate Expense Today - Sum of management expenses
        $expenseToday = ManagementExpense::whereDate('date', today())
            ->sum('amount');
        
        // Calculate Balance Today
        $balanceToday = $incomeToday - $expenseToday;
        
        // Get latest payments with student information (only actual payments, not generated, and exclude installments)
        $latestPayments = StudentPayment::join('students', function ($join) {
                $join->on(\DB::raw('LOWER(TRIM(student_payments.student_code))'), '=', \DB::raw('LOWER(TRIM(students.student_code))'));
            })
            ->whereNotNull('student_payments.method')
            ->where('student_payments.method', '!=', 'Generated') // Only show actual payments
            ->whereRaw("LOWER(student_payments.payment_title) NOT LIKE '%installment%'") // Exclude installments
            ->whereNotNull('students.student_code')
            ->select(
                'student_payments.*',
                'students.student_name',
                'students.father_name',
                'students.class',
                'students.section'
            )
            ->orderBy('student_payments.created_at', 'desc')
            ->limit(10)
            ->get();
        
        // Get campuses for Partial Payment modal
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
            $allCampuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
            
            $campuses = collect();
            foreach ($allCampuses as $campusName) {
                $campuses->push((object)['campus_name' => $campusName]);
            }
        }
        
        return view('fee-payment', compact(
            'unpaidInvoices',
            'incomeToday',
            'expenseToday',
            'balanceToday',
            'latestPayments',
            'campuses'
        ));
    }
}

