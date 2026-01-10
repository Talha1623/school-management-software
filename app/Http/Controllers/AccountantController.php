<?php

namespace App\Http\Controllers;

use App\Models\Accountant;
use App\Models\Campus;
use App\Models\StudentPayment;
use App\Models\Student;
use App\Models\ManagementExpense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AccountantController extends Controller
{
    /**
     * Display a listing of accountants.
     */
    public function index(Request $request): View
    {
        $query = Accountant::query();

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('campus', 'like', "%{$search}%");
            });
        }

        // Pagination
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $accountants = $query->latest()->paginate($perPage)->withQueryString();

        // Summary statistics
        $totalAccountants = Accountant::count();
        $activeAccountants = Accountant::where('app_login_enabled', true)
            ->where('web_login_enabled', true)
            ->count();
        $restrictedAccountants = Accountant::where(function($q) {
            $q->where('app_login_enabled', false)
              ->orWhere('web_login_enabled', false);
        })->count();

        // Get campuses for dropdown
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        
        // If no campuses found, get from accountants
        if ($campuses->isEmpty()) {
            $campusesFromAccountants = Accountant::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            // Convert to collection of objects with campus_name property
            $campuses = collect();
            foreach ($campusesFromAccountants as $campusName) {
                $campuses->push((object)['campus_name' => $campusName]);
            }
        }

        return view('accountant', compact('accountants', 'totalAccountants', 'activeAccountants', 'restrictedAccountants', 'campuses'));
    }

    /**
     * Store a newly created accountant.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:accountants,email', 'max:255'],
            'campus' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        // Password will be hashed automatically by the model's setPasswordAttribute mutator
        $validated['app_login_enabled'] = true;
        $validated['web_login_enabled'] = true;

        $accountant = Accountant::create($validated);

        // If request expects JSON (AJAX), return JSON response
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Accountant created successfully!',
                'accountant' => [
                    'id' => $accountant->id,
                    'name' => $accountant->name,
                    'email' => $accountant->email,
                    'campus' => $accountant->campus,
                    'app_login_enabled' => $accountant->app_login_enabled,
                    'web_login_enabled' => $accountant->web_login_enabled,
                ]
            ]);
        }

        return redirect()
            ->route('accountants')
            ->with('success', 'Accountant created successfully!');
    }

    /**
     * Display the specified accountant.
     */
    public function show(Accountant $accountant)
    {
        return response()->json([
            'id' => $accountant->id,
            'name' => $accountant->name,
            'email' => $accountant->email,
            'campus' => $accountant->campus,
        ]);
    }

    /**
     * Update the specified accountant.
     */
    public function update(Request $request, Accountant $accountant)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:accountants,email,' . $accountant->id, 'max:255'],
            'campus' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        // Password will be hashed automatically by the model's setPasswordAttribute mutator
        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $accountant->update($validated);

        // If request expects JSON (AJAX), return JSON response
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Accountant updated successfully!',
                'accountant' => [
                    'id' => $accountant->id,
                    'name' => $accountant->name,
                    'email' => $accountant->email,
                    'campus' => $accountant->campus,
                    'app_login_enabled' => $accountant->app_login_enabled,
                    'web_login_enabled' => $accountant->web_login_enabled,
                ]
            ]);
        }

        return redirect()
            ->route('accountants')
            ->with('success', 'Accountant updated successfully!');
    }

    /**
     * Remove the specified accountant.
     */
    public function destroy(Accountant $accountant)
    {
        $accountant->delete();

        return redirect()
            ->route('accountants')
            ->with('success', 'Accountant deleted successfully!');
    }

    /**
     * Toggle app login status.
     */
    public function toggleAppLogin(Accountant $accountant)
    {
        $accountant->app_login_enabled = !$accountant->app_login_enabled;
        $accountant->save();

        return response()->json([
            'success' => true,
            'app_login_enabled' => $accountant->app_login_enabled,
            'message' => 'App login status updated successfully!'
        ]);
    }

    /**
     * Toggle web login status.
     */
    public function toggleWebLogin(Accountant $accountant)
    {
        $accountant->web_login_enabled = !$accountant->web_login_enabled;
        $accountant->save();

        return response()->json([
            'success' => true,
            'web_login_enabled' => $accountant->web_login_enabled,
            'email' => $accountant->email,
            'message' => 'Web login status updated successfully!'
        ]);
    }

    /**
     * Export accountants to Excel, CSV, or PDF
     */
    public function export(Request $request, string $format)
    {
        $query = Accountant::query();
        
        // Apply search filter if present
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('campus', 'like', "%{$search}%");
            });
        }
        
        $accountants = $query->latest()->get();
        
        switch ($format) {
            case 'excel':
                return $this->exportExcel($accountants);
            case 'csv':
                return $this->exportCSV($accountants);
            case 'pdf':
                return $this->exportPDF($accountants);
            default:
                return redirect()->route('accountants')
                    ->with('error', 'Invalid export format!');
        }
    }

    /**
     * Export to Excel (CSV format for Excel compatibility)
     */
    private function exportExcel($accountants)
    {
        $filename = 'accountants_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($accountants) {
            $file = fopen('php://output', 'w');
            
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, ['ID', 'Name', 'Email', 'Campus', 'App Login', 'Web Login', 'Created At']);
            
            foreach ($accountants as $accountant) {
                fputcsv($file, [
                    $accountant->id,
                    $accountant->name,
                    $accountant->email,
                    $accountant->campus ?? 'N/A',
                    $accountant->app_login_enabled ? 'Enabled' : 'Disabled',
                    $accountant->web_login_enabled ? 'Enabled' : 'Disabled',
                    $accountant->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to CSV
     */
    private function exportCSV($accountants)
    {
        $filename = 'accountants_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($accountants) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, ['ID', 'Name', 'Email', 'Campus', 'App Login', 'Web Login', 'Created At']);
            
            foreach ($accountants as $accountant) {
                fputcsv($file, [
                    $accountant->id,
                    $accountant->name,
                    $accountant->email,
                    $accountant->campus ?? 'N/A',
                    $accountant->app_login_enabled ? 'Enabled' : 'Disabled',
                    $accountant->web_login_enabled ? 'Enabled' : 'Disabled',
                    $accountant->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to PDF
     */
    private function exportPDF($accountants)
    {
        $html = view('accountant-pdf', compact('accountants'))->render();
        
        return response($html)
            ->header('Content-Type', 'text/html');
    }

    /**
     * Accountant Pages - Task Management
     */
    public function taskManagement(Request $request): View
    {
        // Use TaskManagementController logic to get tasks
        $query = \App\Models\Task::query();
        
        // Filter tasks assigned to current accountant
        $currentAccountant = Auth::guard('accountant')->user();
        if ($currentAccountant) {
            // Get tasks assigned to this accountant (by name or email)
            // Use LIKE for partial matching in case of extra spaces or variations
            $accountantName = strtolower(trim($currentAccountant->name ?? ''));
            $accountantEmail = strtolower(trim($currentAccountant->email ?? ''));
            
            $query->where(function($q) use ($accountantName, $accountantEmail) {
                if (!empty($accountantName)) {
                    $q->whereRaw('LOWER(TRIM(assign_to)) LIKE ?', ["%{$accountantName}%"]);
                }
                if (!empty($accountantEmail)) {
                    $q->orWhereRaw('LOWER(TRIM(assign_to)) LIKE ?', ["%{$accountantEmail}%"]);
                }
            });
        } else {
            // If no accountant is logged in, show no tasks
            $query->whereRaw('1 = 0');
        }
        
        // Search functionality
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(task_title) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(description) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(type) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $tasks = $query->latest()->paginate($perPage)->withQueryString();
        
        // Summary statistics for current accountant
        $totalTasks = $query->count();
        $pendingTasks = (clone $query)->where('status', 'Pending')->count();
        $activeTasks = (clone $query)->whereIn('status', ['Accepted', 'Pending'])->count();
        $completedTasks = (clone $query)->where('status', 'Completed')->count();
        
        return view('accountant.task-management', compact('tasks', 'totalTasks', 'pendingTasks', 'activeTasks', 'completedTasks'));
    }

    /**
     * Accountant Pages - Fee Payment
     */
    public function feePayment(): View
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
        
        return view('accountant.fee-payment', compact(
            'unpaidInvoices',
            'incomeToday',
            'expenseToday',
            'balanceToday',
            'latestPayments'
        ));
    }

    /**
     * Accountant Pages - Family Fee Calculator
     */
    public function familyFeeCalculator(): View
    {
        return view('accountant.family-fee-calculator');
    }

    /**
     * Accountant Pages - Generate Monthly Fee
     */
    public function generateMonthlyFee(): View
    {
        return view('accountant.generate-monthly-fee');
    }

    /**
     * Accountant Pages - Generate Custom Fee
     */
    public function generateCustomFee(): View
    {
        return view('accountant.generate-custom-fee');
    }

    /**
     * Accountant Pages - Generate Transport Fee
     */
    public function generateTransportFee(): View
    {
        return view('accountant.generate-transport-fee');
    }

    /**
     * Accountant Pages - Fee Type
     */
    public function feeType(): View
    {
        return view('accountant.fee-type');
    }

    /**
     * Accountant Pages - Parents Credit System
     */
    public function parentsCreditSystem(): View
    {
        return view('accountant.parents-credit-system');
    }

    /**
     * Accountant Pages - Direct Payment
     */
    public function directPayment(): View
    {
        return view('accountant.direct-payment');
    }

    /**
     * Accountant Pages - Student Payment
     */
    public function studentPayment(): View
    {
        return view('accountant.student-payment');
    }

    /**
     * Accountant Pages - Custom Payment
     */
    public function customPayment(): View
    {
        return view('accountant.custom-payment');
    }

    /**
     * Accountant Pages - SMS to Fee Defaulters
     */
    public function smsFeeDefaulters(): View
    {
        return view('accountant.sms-fee-defaulters');
    }

    /**
     * Accountant Pages - Deleted Fees
     */
    public function deletedFees(): View
    {
        return view('accountant.deleted-fees');
    }

    /**
     * Accountant Pages - Print Fee Vouchers
     */
    public function printFeeVouchers(): View
    {
        return view('accountant.print-fee-vouchers');
    }

    /**
     * Accountant Pages - Print Balance Sheet
     */
    public function printBalanceSheet(): View
    {
        return view('accountant.print-balance-sheet');
    }

    /**
     * Accountant Pages - Expense Management
     */
    public function expenseManagement(): View
    {
        return view('accountant.expense-management');
    }

    /**
     * Accountant Pages - Reporting Area
     */
    public function reportingArea(): View
    {
        return view('accountant.reporting-area');
    }

    /**
     * Accountant Pages - Academic Calendar
     */
    public function academicCalendar(): View
    {
        return view('accountant.academic-calendar');
    }

    /**
     * Accountant Pages - Stock & Inventory
     */
    public function stockInventory(): View
    {
        return view('accountant.stock-inventory');
    }

    /**
     * Accountant Pages - Point of Sale (uses same data/logic as super admin but with accountant layout)
     */
    public function pointOfSale(Request $request): View
    {
        // Use the same PointOfSaleController logic
        $products = \App\Models\Product::orderBy('product_name', 'asc')->get();
        
        // Return view with accountant layout - we'll use a shared partial
        return view('accountant.point-of-sale', compact('products'));
    }

    /**
     * Accountant Pages - Manage Categories (uses same data/logic as super admin but with accountant layout)
     */
    public function manageCategories(Request $request): View
    {
        // Use the same StockCategoryController logic
        $query = \App\Models\StockCategory::query();
        
        // Search functionality
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(category_name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(description) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(campus) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $categories = $query->orderBy('category_name')->paginate($perPage)->withQueryString();
        
        // Get campuses for dropdown
        $campuses = \App\Models\Campus::orderBy('campus_name', 'asc')->get();
        
        if ($campuses->isEmpty()) {
            $campusesFromClasses = \App\Models\ClassModel::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $campusesFromSections = \App\Models\Section::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $allCampuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
            
            $campuses = collect();
            foreach ($allCampuses as $campusName) {
                $campuses->push((object)['campus_name' => $campusName]);
            }
        }
        
        return view('accountant.manage-categories', compact('categories', 'campuses'));
    }

    /**
     * Accountant Pages - Product and Stock (uses same data/logic as super admin but with accountant layout)
     */
    public function productAndStock(Request $request): View
    {
        // Use the same ProductController logic
        $query = \App\Models\Product::query();
        
        // Search functionality
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(product_name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(category) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(campus) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $products = $query->orderBy('product_name')->paginate($perPage)->withQueryString();

        // Get categories for dropdown
        $categories = \App\Models\StockCategory::whereNotNull('category_name')->distinct()->pluck('category_name')->sort()->values();

        // Get campuses
        $campuses = \App\Models\Campus::orderBy('campus_name', 'asc')->get();
        
        if ($campuses->isEmpty()) {
            $campusesFromClasses = \App\Models\ClassModel::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $campusesFromSections = \App\Models\Section::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $allCampuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
            
            $campuses = collect();
            foreach ($allCampuses as $campusName) {
                $campuses->push((object)['campus_name' => $campusName]);
            }
        }
        
        return view('accountant.product-and-stock', compact('products', 'categories', 'campuses'));
    }

    /**
     * Accountant Pages - Manage All Sales (uses same data/logic as super admin but with accountant layout)
     */
    public function manageAllSales(Request $request): View
    {
        // Use the same SaleRecordController logic
        // Get filter values
        $filterMonth = $request->get('filter_month');
        $filterDate = $request->get('filter_date');
        $filterYear = $request->get('filter_year');
        $filterMethod = $request->get('filter_method');

        // Month options
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

        // Year options (current year and previous 5 years)
        $currentYear = date('Y');
        $years = collect();
        for ($i = 0; $i < 6; $i++) {
            $years->push($currentYear - $i);
        }

        // Get payment methods from sale records
        $methods = \App\Models\SaleRecord::whereNotNull('method')->distinct()->pluck('method')->sort()->values();
        
        if ($methods->isEmpty()) {
            $methods = collect(['Cash', 'Bank Transfer', 'Cheque', 'Online Payment', 'Card']);
        }

        // Query sale records - show all by default, filter if provided
        $query = \App\Models\SaleRecord::with('product');

        if ($filterMonth) {
            $query->whereMonth('sale_date', $filterMonth);
        }
        if ($filterDate) {
            $query->whereDate('sale_date', $filterDate);
        }
        if ($filterYear) {
            $query->whereYear('sale_date', $filterYear);
        }
        if ($filterMethod) {
            $query->where('method', $filterMethod);
        }

        // Get all records (filtered or all)
        $saleRecords = $query->orderBy('sale_date', 'desc')->orderBy('created_at', 'desc')->get();
        
        // Calculate totals
        $totalSales = $saleRecords->sum('total_amount');
        $totalQuantity = $saleRecords->sum('quantity');
        
        // Debug info (for troubleshooting)
        $totalRecordsInDB = \App\Models\SaleRecord::count();
        $todayRecords = \App\Models\SaleRecord::whereDate('sale_date', now()->toDateString())->count();

        return view('accountant.manage-all-sales', compact(
            'months',
            'years',
            'methods',
            'saleRecords',
            'filterMonth',
            'filterDate',
            'filterYear',
            'filterMethod',
            'totalSales',
            'totalQuantity',
            'totalRecordsInDB',
            'todayRecords'
        ));
    }

    /**
     * Accountant Pages - Add / Manage Expense (uses same data/logic as super admin but with accountant layout)
     */
    public function addManageExpense(Request $request): View
    {
        // Use the same ManagementExpenseController logic
        $query = \App\Models\ManagementExpense::query();
        
        // Search functionality
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(campus) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(category) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(title) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(description) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(method) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $expenses = $query->orderBy('date', 'desc')->paginate($perPage)->withQueryString();
        
        // Get campuses from Campus model
        $campuses = \App\Models\Campus::orderBy('campus_name', 'asc')->get();
        
        // If no campuses found, get from classes or sections
        if ($campuses->isEmpty()) {
            $campusesFromClasses = \App\Models\ClassModel::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $campusesFromSections = \App\Models\Section::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $allCampuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
            
            // Convert to collection of objects with campus_name property
            $campuses = collect();
            foreach ($allCampuses as $campusName) {
                $campuses->push((object)['campus_name' => $campusName]);
            }
        }
        
        // Get expense categories for dropdown
        $categories = \App\Models\ExpenseCategory::orderBy('category_name')->get();
        
        return view('accountant.add-manage-expense', compact('expenses', 'categories', 'campuses'));
    }

    /**
     * Accountant Pages - Expense Categories (uses same data/logic as super admin but with accountant layout)
     */
    public function expenseCategories(Request $request): View
    {
        // Use the same ExpenseCategoryController logic
        $query = \App\Models\ExpenseCategory::query();
        
        // Search functionality
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(category_name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(campus) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $categories = $query->orderBy('category_name')->paginate($perPage)->withQueryString();
        
        // Get campuses from Campus model
        $campuses = \App\Models\Campus::orderBy('campus_name', 'asc')->get();
        
        // If no campuses found, get from classes or sections
        if ($campuses->isEmpty()) {
            $campusesFromClasses = \App\Models\ClassModel::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $campusesFromSections = \App\Models\Section::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $allCampuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
            
            // Convert to collection of objects with campus_name property
            $campuses = collect();
            foreach ($allCampuses as $campusName) {
                $campuses->push((object)['campus_name' => $campusName]);
            }
        }
        
        return view('accountant.expense-categories', compact('categories', 'campuses'));
    }
}
