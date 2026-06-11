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
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Carbon\Carbon;

class AccountantAuthController extends Controller
{
    /**
     * Show the accountant login form.
     */
    public function showLoginForm(Request $request): View|RedirectResponse
    {
        if (Auth::guard('accountant')->check()) {
            return redirect()->route('accountant.dashboard');
        }

        $email = $request->query('email', old('email'));

        return view('accountant.login', compact('email'));
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

        $accountant = Accountant::where('email', $credentials['email'])->first();

        if (!$accountant) {
            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

        if (!Hash::check($credentials['password'], $accountant->password)) {
            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

        if (!$accountant->hasWebLoginAccess()) {
            return back()->withErrors([
                'email' => 'You do not have web login access. Please contact administrator.',
            ])->onlyInput('email');
        }

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

    public function changePassword(): View|string
    {
        $accountant = Auth::guard('accountant')->user();

        if (view()->exists('accountant.change-password')) {
            return view('accountant.change-password', compact('accountant'));
        }

        if (view()->exists('accountant.change_password')) {
            return view('accountant.change_password', compact('accountant'));
        }

        return Blade::render(<<<'BLADE'
@extends('layouts.accountant')

@section('title', 'Change Password')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-4 mb-4">
            <h4 class="mb-4 fs-16 fw-semibold">Change Password</h4>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="row mb-4">
                <div class="col-md-4">
                    <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Name</label>
                    <input type="text" class="form-control form-control-sm" value="{{ $accountant->name ?? 'N/A' }}" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Email</label>
                    <input type="email" class="form-control form-control-sm" value="{{ $accountant->email ?? 'N/A' }}" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus</label>
                    <input type="text" class="form-control form-control-sm" value="{{ $accountant->campus ?? 'N/A' }}" readonly>
                </div>
            </div>

            <form method="POST" action="{{ route('accountant.change-password.update') }}">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="current_password" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Current Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control form-control-sm" id="current_password" name="current_password" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="new_password" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">New Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control form-control-sm" id="new_password" name="new_password" required minlength="6">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="new_password_confirmation" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Confirm New Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control form-control-sm" id="new_password_confirmation" name="new_password_confirmation" required minlength="6">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary px-4 py-2" style="color: white;">Update Password</button>
            </form>
        </div>
    </div>
</div>
@endsection
BLADE, compact('accountant'));
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required'],
            'new_password' => ['required', 'min:6', 'confirmed'],
        ], [
            'current_password.required' => 'Current password is required.',
            'new_password.required' => 'New password is required.',
            'new_password.min' => 'New password must be at least 6 characters.',
            'new_password.confirmed' => 'New password confirmation does not match.',
        ]);

        $accountant = Auth::guard('accountant')->user();

        if (!Hash::check($request->current_password, $accountant->password)) {
            return back()->withErrors([
                'current_password' => 'Current password is incorrect.',
            ])->withInput();
        }

        $accountant->password = $request->new_password;
        $accountant->save();

        return back()->with('success', 'Password changed successfully!');
    }

    /**
     * Show accountant dashboard (only this accountant's own transactions on their campus).
     */
    public function dashboard(): View|RedirectResponse
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('dashboard');
        }

        $accountant = Auth::guard('accountant')->user();

        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $incomeToday = (float) $this->scopedStudentPayments($accountant)
            ->whereDate('payment_date', $today)
            ->sum('payment_amount');
        $incomeToday += (float) $this->scopedCustomPayments($accountant)
            ->whereDate('payment_date', $today)
            ->sum('payment_amount');
        $incomeToday += (float) $this->scopedSaleRecords($accountant)
            ->whereDate('sale_date', $today)
            ->sum('total_amount');

        $expenseToday = (float) $this->scopedManagementExpenses($accountant)
            ->whereDate('date', $today)
            ->sum('amount');

        $balanceToday = $incomeToday - $expenseToday;

        $incomeThisMonth = (float) $this->scopedStudentPayments($accountant)
            ->whereBetween('payment_date', [$startOfMonth, $endOfMonth])
            ->sum('payment_amount');
        $incomeThisMonth += (float) $this->scopedCustomPayments($accountant)
            ->whereBetween('payment_date', [$startOfMonth, $endOfMonth])
            ->sum('payment_amount');
        $incomeThisMonth += (float) $this->scopedSaleRecords($accountant)
            ->whereBetween('sale_date', [$startOfMonth, $endOfMonth])
            ->sum('total_amount');

        $expenseThisMonth = (float) $this->scopedManagementExpenses($accountant)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $balanceThisMonth = $incomeThisMonth - $expenseThisMonth;

        $monthlyIncomeData = [];
        $monthlyExpenseData = [];
        $monthLabels = [];

        for ($i = 11; $i >= 0; $i--) {
            $monthStart = Carbon::now()->subMonths($i)->startOfMonth();
            $monthEnd = Carbon::now()->subMonths($i)->endOfMonth();

            $monthIncome = (float) $this->scopedStudentPayments($accountant)
                ->whereBetween('payment_date', [$monthStart, $monthEnd])
                ->sum('payment_amount');
            $monthIncome += (float) $this->scopedCustomPayments($accountant)
                ->whereBetween('payment_date', [$monthStart, $monthEnd])
                ->sum('payment_amount');
            $monthIncome += (float) $this->scopedSaleRecords($accountant)
                ->whereBetween('sale_date', [$monthStart, $monthEnd])
                ->sum('total_amount');

            $monthExpense = (float) $this->scopedManagementExpenses($accountant)
                ->whereBetween('date', [$monthStart, $monthEnd])
                ->sum('amount');

            $monthlyIncomeData[] = round($monthIncome, 2);
            $monthlyExpenseData[] = round($monthExpense, 2);
            $monthLabels[] = $monthStart->format('M');
        }

        return view('accountant.dashboard', compact(
            'accountant',
            'incomeToday',
            'expenseToday',
            'balanceToday',
            'incomeThisMonth',
            'expenseThisMonth',
            'balanceThisMonth',
            'monthlyIncomeData',
            'monthlyExpenseData',
            'monthLabels'
        ));
    }

    private function scopedStudentPayments(Accountant $accountant)
    {
        $query = StudentPayment::query()->where('method', '!=', 'Generated');
        $this->applyAccountantDashboardScope($query, $accountant, 'accountant');

        return $query;
    }

    private function scopedCustomPayments(Accountant $accountant)
    {
        $query = CustomPayment::query();
        $this->applyAccountantDashboardScope($query, $accountant, 'accountant');

        return $query;
    }

    private function scopedSaleRecords(Accountant $accountant)
    {
        $query = SaleRecord::query();
        $this->applyAccountantDashboardScope($query, $accountant, 'received_by');

        return $query;
    }

    private function scopedManagementExpenses(Accountant $accountant)
    {
        $query = ManagementExpense::query();
        $this->applyAccountantDashboardScope($query, $accountant, 'created_by');

        return $query;
    }

    /**
     * Limit dashboard stats to the logged-in accountant (and their campus).
     */
    private function applyAccountantDashboardScope($query, Accountant $accountant, string $actorColumn): void
    {
        if ($accountant->campus) {
            $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim((string) $accountant->campus))]);
        }

        $nameKey = strtolower(trim((string) $accountant->name));
        if ($nameKey === '') {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereRaw('LOWER(TRIM(' . $actorColumn . ')) = ?', [$nameKey]);
    }
}
