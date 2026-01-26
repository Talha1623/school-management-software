<?php

namespace App\Http\Controllers;

use App\Models\ManagementExpense;
use App\Models\ExpenseCategory;
use App\Models\Campus;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\StudentPayment;
use App\Models\CustomPayment;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DetailedExpenseController extends Controller
{
    /**
     * Display the detailed expense reports with filters.
     */
    public function index(Request $request): View
    {
        // Get filter values
        $filterCampus = $request->get('filter_campus');
        $filterClass = $request->get('filter_class');
        $filterSection = $request->get('filter_section');
        $filterMonth = $request->get('filter_month');
        $filterDate = $request->get('filter_date');
        $filterYear = $request->get('filter_year');
        $filterMethod = $request->get('filter_method');

        // Get campuses from Campus model first, then fallback
        $campuses = Campus::orderBy('campus_name', 'asc')->pluck('campus_name');
        if ($campuses->isEmpty()) {
            $campuses = ManagementExpense::whereNotNull('campus')->distinct()->pluck('campus')->sort()->values();
        }

        // Get classes
        $classesQuery = ClassModel::whereNotNull('class_name');
        if ($filterCampus) {
            $classesQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
        }
        $classes = $classesQuery->distinct()->pluck('class_name')->sort()->values();
        if ($classes->isEmpty()) {
            $classesFromStudents = Student::whereNotNull('class');
            if ($filterCampus) {
                $classesFromStudents->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
            }
            $classesFromStudents = $classesFromStudents->distinct()->pluck('class')->sort()->values();
            $classes = $classesFromStudents->isEmpty()
                ? collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th'])
                : $classesFromStudents;
        }

        // Get sections
        $sectionsQuery = Section::whereNotNull('name');
        if ($filterCampus) {
            $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
        }
        if ($filterClass) {
            $sectionsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
        }
        $sections = $sectionsQuery->distinct()->pluck('name')->sort()->values();
        if ($sections->isEmpty()) {
            $sectionsFromSubjects = \App\Models\Subject::whereNotNull('section');
            if ($filterCampus) {
                $sectionsFromSubjects->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
            }
            if ($filterClass) {
                $sectionsFromSubjects->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
            }
            $sectionsFromSubjects = $sectionsFromSubjects->distinct()->pluck('section')->sort()->values();
            $sections = $sectionsFromSubjects->isEmpty()
                ? collect(['A', 'B', 'C', 'D', 'E'])
                : $sectionsFromSubjects;
        }

        // Get expense categories
        $categories = ExpenseCategory::whereNotNull('category_name')->distinct()->pluck('category_name')->sort()->values();
        if ($categories->isEmpty()) {
            $categories = collect(['Office Supplies', 'Utilities', 'Maintenance', 'Transportation', 'Other']);
        }

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

        // Get payment methods from expenses
        $methods = ManagementExpense::whereNotNull('method')->distinct()->pluck('method')->sort()->values();
        
        if ($methods->isEmpty()) {
            $methods = collect(['Cash', 'Bank Transfer', 'Cheque', 'Online Payment', 'Card']);
        }

        // Query expenses
        $expensesQuery = ManagementExpense::query();
        
        if ($filterCampus) {
            $expensesQuery->where('campus', $filterCampus);
        }
        if ($filterMonth) {
            $expensesQuery->whereMonth('date', $filterMonth);
        }
        if ($filterDate) {
            $expensesQuery->whereDate('date', $filterDate);
        }
        if ($filterYear) {
            $expensesQuery->whereYear('date', $filterYear);
        }
        if ($filterMethod) {
            $expensesQuery->where('method', $filterMethod);
        }
        
        $expenses = $expensesQuery->orderBy('date', 'desc')->get();

        // Prepare expense records
        $expenseRecords = collect();

        foreach ($expenses as $expense) {
            $expenseRecords->push([
                'id' => $expense->id,
                'campus' => $expense->campus,
                'category' => $expense->category,
                'title' => $expense->title,
                'description' => $expense->description,
                'amount' => $expense->amount,
                'method' => $expense->method,
                'date' => $expense->date,
                'invoice_receipt' => $expense->invoice_receipt,
                'notify_admin' => $expense->notify_admin,
                'accountant' => $expense->accountant ?? 'N/A',
            ]);
        }

        // Month label for summary
        $monthLabel = null;
        if ($filterMonth && $months->has($filterMonth)) {
            $monthLabel = $months->get($filterMonth);
        } elseif ($filterDate) {
            $monthLabel = \Carbon\Carbon::parse($filterDate)->format('F');
        } else {
            $monthLabel = 'All';
        }

        // Total Expense (based on filters)
        $totalExpense = $expenses->sum('amount');

        // Total Income for summary (filtered by campus/class/section/month/date/year/method)
        $studentPaymentsQuery = StudentPayment::query();
        if ($filterCampus) {
            $studentPaymentsQuery->where('campus', $filterCampus);
        }
        if ($filterMonth) {
            $studentPaymentsQuery->whereMonth('payment_date', $filterMonth);
        }
        if ($filterDate) {
            $studentPaymentsQuery->whereDate('payment_date', $filterDate);
        }
        if ($filterYear) {
            $studentPaymentsQuery->whereYear('payment_date', $filterYear);
        }
        if ($filterMethod) {
            $studentPaymentsQuery->where('method', $filterMethod);
        }
        $studentPayments = $studentPaymentsQuery->get();

        if ($filterClass || $filterSection) {
            $studentCodes = Student::when($filterCampus, function($q) use ($filterCampus) {
                    $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
                })
                ->when($filterClass, function($q) use ($filterClass) {
                    $q->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
                })
                ->when($filterSection, function($q) use ($filterSection) {
                    $q->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))]);
                })
                ->pluck('student_code')
                ->filter()
                ->values();
            $studentPayments = $studentPayments->whereIn('student_code', $studentCodes);
        }

        $studentIncome = $studentPayments->sum(function($payment) {
            return ($payment->payment_amount ?? 0) - ($payment->discount ?? 0);
        });

        $customPaymentsQuery = CustomPayment::query();
        if ($filterCampus) {
            $customPaymentsQuery->where('campus', $filterCampus);
        }
        if ($filterMonth) {
            $customPaymentsQuery->whereMonth('payment_date', $filterMonth);
        }
        if ($filterDate) {
            $customPaymentsQuery->whereDate('payment_date', $filterDate);
        }
        if ($filterYear) {
            $customPaymentsQuery->whereYear('payment_date', $filterYear);
        }
        if ($filterMethod) {
            $customPaymentsQuery->where('method', $filterMethod);
        }
        $customIncome = $customPaymentsQuery->sum('payment_amount');

        $totalIncome = $studentIncome + $customIncome;
        $profitLoss = $totalIncome - $totalExpense;

        return view('reports.detailed-expense', compact(
            'campuses',
            'classes',
            'sections',
            'categories',
            'months',
            'years',
            'methods',
            'expenseRecords',
            'monthLabel',
            'totalExpense',
            'totalIncome',
            'profitLoss',
            'filterCampus',
            'filterClass',
            'filterSection',
            'filterMonth',
            'filterDate',
            'filterYear',
            'filterMethod'
        ));
    }

    /**
     * Get classes by campus (AJAX endpoint)
     */
    public function getClassesByCampus(Request $request): JsonResponse
    {
        $campus = $request->get('campus');

        $classesQuery = ClassModel::whereNotNull('class_name');
        if ($campus) {
            $classesQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        $classes = $classesQuery->distinct()->pluck('class_name')->sort()->values();

        if ($classes->isEmpty()) {
            $classesFromStudents = Student::whereNotNull('class');
            if ($campus) {
                $classesFromStudents->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            $classesFromStudents = $classesFromStudents->distinct()->pluck('class')->sort()->values();
            $classes = $classesFromStudents->isEmpty()
                ? collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th'])
                : $classesFromStudents;
        }

        $classes = $classes->map(function($class) {
            return trim((string) $class);
        })->filter(function($class) {
            return $class !== '';
        })->unique()->sort()->values();

        return response()->json(['classes' => $classes]);
    }

    /**
     * Get sections by class (AJAX endpoint)
     */
    public function getSectionsByClass(Request $request): JsonResponse
    {
        $class = $request->get('class');
        $campus = $request->get('campus');

        if (!$class) {
            return response()->json(['sections' => []]);
        }

        $sectionsQuery = Section::whereNotNull('name')
            ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
        if ($campus) {
            $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        $sections = $sectionsQuery->distinct()->pluck('name')->sort()->values();

        if ($sections->isEmpty()) {
            $sectionsFromSubjects = \App\Models\Subject::whereNotNull('section')
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
            if ($campus) {
                $sectionsFromSubjects->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            $sections = $sectionsFromSubjects->distinct()->pluck('section')->sort()->values();
        }

        return response()->json(['sections' => $sections]);
    }
}

