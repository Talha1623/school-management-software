<?php

namespace App\Http\Controllers;

use App\Models\StudentPayment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeePaymentController extends Controller
{
    /**
     * Display the fee payment page.
     */
    public function index(): View
    {
        // Get statistics
        $unpaidInvoices = 0; // TODO: Calculate unpaid invoices
        $incomeToday = StudentPayment::whereDate('payment_date', today())
            ->sum('payment_amount');
        $expenseToday = 0; // TODO: Calculate expenses
        $balanceToday = $incomeToday - $expenseToday;
        
        // Get latest payments with student information
        $latestPayments = StudentPayment::leftJoin('students', 'student_payments.student_code', '=', 'students.student_code')
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

