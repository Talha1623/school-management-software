<?php

namespace App\Http\Controllers;

use App\Models\StudentPayment;
use App\Models\Student;
use App\Models\ManagementExpense;
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
        
        // Get latest payments with student information (only actual payments, not generated)
        $latestPayments = StudentPayment::leftJoin('students', 'student_payments.student_code', '=', 'students.student_code')
            ->where('student_payments.method', '!=', 'Generated') // Only show actual payments
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
        
        return view('fee-payment', compact(
            'unpaidInvoices',
            'incomeToday',
            'expenseToday',
            'balanceToday',
            'latestPayments'
        ));
    }
}

