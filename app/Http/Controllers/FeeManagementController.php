<?php

namespace App\Http\Controllers;

use App\Models\StudentPayment;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class FeeManagementController extends Controller
{
    /**
     * Display the fee management page.
     */
    public function index(): View
    {
        // Get statistics
        $unpaidInvoices = 0; // TODO: Calculate unpaid invoices
        $incomeToday = StudentPayment::whereDate('payment_date', today())
            ->sum('payment_amount');
        $expenseToday = 0; // TODO: Calculate expenses
        $balanceToday = $incomeToday - $expenseToday;
        
        return view('fee-management', compact(
            'unpaidInvoices',
            'incomeToday',
            'expenseToday',
            'balanceToday'
        ));
    }
}

