<?php

namespace App\Http\Controllers;

use App\Models\Accountant;
use App\Models\StudentPayment;
use App\Models\CustomPayment;
use App\Models\SaleRecord;
use App\Models\ManagementExpense;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Carbon\Carbon;

class AccountantAuthController extends Controller
{
    /**
     * Show the accountant login form.
     */
    public function showLoginForm(): View|RedirectResponse
    {
        // If already logged in, redirect to dashboard
        if (Auth::guard('accountant')->check()) {
            return redirect()->route('accountant.dashboard');
        }
        
        return view('accountant.login');
    }

    /**
     * Handle accountant login.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Find accountant by email
        $accountant = Accountant::where('email', $credentials['email'])->first();

        if (!$accountant) {
            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

        // Check password
        if (!Hash::check($credentials['password'], $accountant->password)) {
            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

        // Check if accountant has web login access
        if (!$accountant->hasWebLoginAccess()) {
            return back()->withErrors([
                'email' => 'You do not have web login access. Please contact administrator.',
            ])->onlyInput('email');
        }

        // Login the accountant
        Auth::guard('accountant')->login($accountant, $request->filled('remember'));

        $request->session()->regenerate();

        return redirect()->intended(route('accountant.dashboard'));
    }

    /**
     * Handle accountant logout.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('accountant')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('accountant.login');
    }

    /**
     * Show accountant dashboard.
     */
    public function dashboard(): View
    {
        $accountant = Auth::guard('accountant')->user();
        
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        
        // Filter by campus if accountant has a campus
        $campusFilter = $accountant->campus ? ['campus' => $accountant->campus] : [];
        
        // Income Today (Student Payments + Custom Payments + Sales)
        $incomeToday = StudentPayment::whereDate('payment_date', $today)
            ->when($accountant->campus, function($q) use ($accountant) {
                return $q->where('campus', $accountant->campus);
            })
            ->sum('payment_amount');
        
        $incomeToday += CustomPayment::whereDate('payment_date', $today)
            ->when($accountant->campus, function($q) use ($accountant) {
                return $q->where('campus', $accountant->campus);
            })
            ->sum('payment_amount');
        
        $incomeToday += SaleRecord::whereDate('sale_date', $today)
            ->when($accountant->campus, function($q) use ($accountant) {
                return $q->where('campus', $accountant->campus);
            })
            ->sum('total_amount');
        
        // Expense Today (Management Expenses)
        $expenseToday = ManagementExpense::whereDate('date', $today)
            ->when($accountant->campus, function($q) use ($accountant) {
                return $q->where('campus', $accountant->campus);
            })
            ->sum('amount');
        
        // Balance Today
        $balanceToday = $incomeToday - $expenseToday;
        
        // Income This Month
        $incomeThisMonth = StudentPayment::whereBetween('payment_date', [$startOfMonth, $endOfMonth])
            ->when($accountant->campus, function($q) use ($accountant) {
                return $q->where('campus', $accountant->campus);
            })
            ->sum('payment_amount');
        
        $incomeThisMonth += CustomPayment::whereBetween('payment_date', [$startOfMonth, $endOfMonth])
            ->when($accountant->campus, function($q) use ($accountant) {
                return $q->where('campus', $accountant->campus);
            })
            ->sum('payment_amount');
        
        $incomeThisMonth += SaleRecord::whereBetween('sale_date', [$startOfMonth, $endOfMonth])
            ->when($accountant->campus, function($q) use ($accountant) {
                return $q->where('campus', $accountant->campus);
            })
            ->sum('total_amount');
        
        // Expense This Month
        $expenseThisMonth = ManagementExpense::whereBetween('date', [$startOfMonth, $endOfMonth])
            ->when($accountant->campus, function($q) use ($accountant) {
                return $q->where('campus', $accountant->campus);
            })
            ->sum('amount');
        
        // Balance This Month
        $balanceThisMonth = $incomeThisMonth - $expenseThisMonth;
        
        return view('accountant.dashboard', compact(
            'accountant',
            'incomeToday',
            'expenseToday',
            'balanceToday',
            'incomeThisMonth',
            'expenseThisMonth',
            'balanceThisMonth'
        ));
    }
}

