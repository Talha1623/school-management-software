<?php
/**
 * Restores AccountantAuthController when live file was overwritten with AccountantController.
 * Run on server: php restore-accountant-auth-controller.php
 * Delete this file after use.
 */
$target = __DIR__ . '/app/Http/Controllers/AccountantAuthController.php';

$content = <<<'PHP'
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

    /**
     * Show accountant dashboard.
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

        $incomeToday = StudentPayment::whereDate('payment_date', $today)
            ->where('method', '!=', 'Generated')
            ->when($accountant->campus, function ($q) use ($accountant) {
                return $q->where('campus', $accountant->campus);
            })
            ->sum('payment_amount');

        $incomeToday += CustomPayment::whereDate('payment_date', $today)
            ->when($accountant->campus, function ($q) use ($accountant) {
                return $q->where('campus', $accountant->campus);
            })
            ->sum('payment_amount');

        $incomeToday += SaleRecord::whereDate('sale_date', $today)
            ->when($accountant->campus, function ($q) use ($accountant) {
                return $q->where('campus', $accountant->campus);
            })
            ->sum('total_amount');

        $expenseToday = ManagementExpense::whereDate('date', $today)
            ->when($accountant->campus, function ($q) use ($accountant) {
                return $q->where('campus', $accountant->campus);
            })
            ->sum('amount');

        $balanceToday = $incomeToday - $expenseToday;

        $incomeThisMonth = StudentPayment::whereBetween('payment_date', [$startOfMonth, $endOfMonth])
            ->where('method', '!=', 'Generated')
            ->when($accountant->campus, function ($q) use ($accountant) {
                return $q->where('campus', $accountant->campus);
            })
            ->sum('payment_amount');

        $incomeThisMonth += CustomPayment::whereBetween('payment_date', [$startOfMonth, $endOfMonth])
            ->when($accountant->campus, function ($q) use ($accountant) {
                return $q->where('campus', $accountant->campus);
            })
            ->sum('payment_amount');

        $incomeThisMonth += SaleRecord::whereBetween('sale_date', [$startOfMonth, $endOfMonth])
            ->when($accountant->campus, function ($q) use ($accountant) {
                return $q->where('campus', $accountant->campus);
            })
            ->sum('total_amount');

        $expenseThisMonth = ManagementExpense::whereBetween('date', [$startOfMonth, $endOfMonth])
            ->when($accountant->campus, function ($q) use ($accountant) {
                return $q->where('campus', $accountant->campus);
            })
            ->sum('amount');

        $balanceThisMonth = $incomeThisMonth - $expenseThisMonth;

        $monthlyIncomeData = [];
        $monthlyExpenseData = [];
        $monthLabels = [];

        for ($i = 11; $i >= 0; $i--) {
            $monthStart = Carbon::now()->subMonths($i)->startOfMonth();
            $monthEnd = Carbon::now()->subMonths($i)->endOfMonth();

            $monthIncome = StudentPayment::whereBetween('payment_date', [$monthStart, $monthEnd])
                ->where('method', '!=', 'Generated')
                ->when($accountant->campus, function ($q) use ($accountant) {
                    return $q->where('campus', $accountant->campus);
                })
                ->sum('payment_amount');

            $monthIncome += CustomPayment::whereBetween('payment_date', [$monthStart, $monthEnd])
                ->when($accountant->campus, function ($q) use ($accountant) {
                    return $q->where('campus', $accountant->campus);
                })
                ->sum('payment_amount');

            $monthIncome += SaleRecord::whereBetween('sale_date', [$monthStart, $monthEnd])
                ->when($accountant->campus, function ($q) use ($accountant) {
                    return $q->where('campus', $accountant->campus);
                })
                ->sum('total_amount');

            $monthExpense = ManagementExpense::whereBetween('date', [$monthStart, $monthEnd])
                ->when($accountant->campus, function ($q) use ($accountant) {
                    return $q->where('campus', $accountant->campus);
                })
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
}

PHP;

if (!is_dir(dirname($target))) {
    fwrite(STDERR, "Controllers directory missing.\n");
    exit(1);
}

file_put_contents($target, $content);
echo "RESTORED: app/Http/Controllers/AccountantAuthController.php\n";
echo "Run: composer dump-autoload -o && php artisan optimize:clear\n";
