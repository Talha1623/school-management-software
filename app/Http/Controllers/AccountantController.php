<?php

namespace App\Http\Controllers;

use App\Models\Accountant;
use App\Models\Campus;
use App\Models\StudentPayment;
use App\Models\Student;
use App\Models\ManagementExpense;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Carbon\Carbon;

class AccountantController extends Controller
{
    /**
     * Display a listing of accountants.
     */
    public function index(Request $request): View
    {
        $query = Accountant::query();

        // Campus filter
        $filterCampus = $request->get('campus');
        if ($filterCampus) {
            $query->where('campus', $filterCampus);
        }

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
        $summaryQuery = Accountant::query();
        if ($filterCampus) {
            $summaryQuery->where('campus', $filterCampus);
        }

        $totalAccountants = (clone $summaryQuery)->count();
        $activeAccountants = (clone $summaryQuery)
            ->where('app_login_enabled', true)
            ->where('web_login_enabled', true)
            ->count();
        $restrictedAccountants = (clone $summaryQuery)
            ->where(function($q) {
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

        return view('accountant', compact('accountants', 'totalAccountants', 'activeAccountants', 'restrictedAccountants', 'campuses', 'filterCampus'));
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
        $latestPayments = StudentPayment::join('students', function ($join) {
                $join->on(\DB::raw('LOWER(TRIM(student_payments.student_code))'), '=', \DB::raw('LOWER(TRIM(students.student_code))'));
            })
            ->whereNotNull('student_payments.method')
            ->where('student_payments.method', '!=', 'Generated') // Only show actual payments
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
        
        return view('accountant.fee-payment', compact(
            'unpaidInvoices',
            'incomeToday',
            'expenseToday',
            'balanceToday',
            'latestPayments'
        ));
    }

    /**
     * Search student by name or code (filtered by accountant's campus)
     */
    public function searchStudent(Request $request)
    {
        $search = $request->get('search');
        
        if (!$search) {
            return response()->json([
                'success' => false,
                'message' => 'Search term is required'
            ], 400);
        }
        
        // Get accountant's assigned campus
        $accountantCampus = null;
        if (auth()->guard('accountant')->check()) {
            $accountant = auth()->guard('accountant')->user();
            $accountantCampus = $accountant->campus;
        }
        
        $searchLower = strtolower(trim($search));
        
        // Search students by name or code
        $query = \App\Models\Student::where(function($query) use ($search, $searchLower) {
                $query->whereRaw('LOWER(student_name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhere('student_code', 'like', "%{$search}%")
                      ->orWhere('gr_number', 'like', "%{$search}%");
            });
        
        // Filter by accountant's campus if assigned
        if ($accountantCampus) {
            $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($accountantCampus))]);
        }
        
        $matchedStudents = $query->select('id', 'student_name', 'student_code', 'father_name', 'father_id_card', 'parent_account_id', 'class', 'section', 'campus', 'monthly_fee')
            ->orderBy('student_name', 'asc')
            ->limit(50)
            ->get();

        $students = $matchedStudents->map(function ($student) {
            return (object) [
                'id' => $student->id,
                'student_name' => $student->student_name,
                'student_code' => $student->student_code,
                'father_name' => $student->father_name,
                'class' => $student->class,
                'section' => $student->section,
                'campus' => $student->campus,
                'monthly_fee' => $student->monthly_fee,
            ];
        });
        
        return response()->json([
            'success' => true,
            'students' => $students->map(function ($student) {
                $generatedFees = \App\Models\StudentPayment::where('student_code', $student->student_code)
                    ->where('method', 'Generated')
                    ->get();
                $paidFees = \App\Models\StudentPayment::where('student_code', $student->student_code)
                    ->where('method', '!=', 'Generated')
                    ->get();

                $pendingFees = [];
                $feeRows = [];
                $totalDue = 0;
                $generatedByTitle = $generatedFees->groupBy('payment_title');
                $paidByTitle = $paidFees->groupBy('payment_title');

                // Collect all installment titles and their base fee titles
                $installmentBaseTitles = [];
                foreach ($generatedByTitle as $title => $items) {
                    if (preg_match('/^(.+)\/\d+$/', $title, $matches)) {
                        $baseTitle = $matches[1];
                        $installmentBaseTitles[$baseTitle] = true;
                    }
                }

                foreach ($generatedByTitle as $title => $items) {
                    $latestGenerated = $items->sortByDesc('id')->first();
                    $isInstallment = preg_match('/\/\d+$/', $title);
                    
                    if (!$isInstallment && isset($installmentBaseTitles[$title])) {
                        continue;
                    }
                    
                    $generatedAmount = $items->sum(function ($item) {
                        return (float) ($item->payment_amount ?? 0) - (float) ($item->discount ?? 0);
                    });
                    $generatedLate = $items->sum(function ($item) {
                        return (float) ($item->late_fee ?? 0);
                    });
                    $paidDiscount = $paidByTitle->get($title, collect())->sum(function ($item) {
                        return (float) ($item->discount ?? 0);
                    });
                    $paidAmount = $paidByTitle->get($title, collect())->sum(function ($item) {
                        return (float) ($item->payment_amount ?? 0) + (float) ($item->discount ?? 0);
                    });
                    $paidLate = $paidByTitle->get($title, collect())->sum(function ($item) {
                        return (float) ($item->late_fee ?? 0);
                    });

                    $totalGenerated = $generatedAmount;
                    $totalPaid = $paidAmount + $paidLate;
                    $remainingAmount = max(0, $generatedAmount - $paidAmount);
                    $remainingLate = max(0, $generatedLate - $paidLate);
                    $remainingTotal = $remainingAmount + $remainingLate;

                    if ($remainingTotal > 0) {
                        $feeRows[] = [
                            'title' => $title,
                            'total' => round($totalGenerated, 2),
                            'discount' => round($paidDiscount, 2),
                            'late_fee' => round($generatedLate, 2),
                            'paid' => round($totalPaid, 2),
                            'due' => round($remainingTotal, 2),
                            'amount' => round($remainingAmount, 2),
                            'remaining_late' => round($remainingLate, 2),
                            'generated_id' => $latestGenerated ? $latestGenerated->id : null,
                            'is_installment' => $isInstallment,
                        ];
                        $pendingFees[] = [
                            'title' => $title,
                            'amount' => round($remainingAmount, 2),
                            'late_fee' => round($remainingLate, 2),
                            'total' => round($remainingTotal, 2),
                        ];
                        $totalDue += $remainingTotal;
                    }
                }

                $processedTitles = collect($feeRows)->pluck('title')->toArray();
                $installmentPayments = $paidFees->filter(function ($payment) use ($processedTitles) {
                    $title = $payment->payment_title ?? '';
                    return preg_match('/\/\d+$/', $title) && !in_array($title, $processedTitles);
                })->map(function ($payment) {
                    return [
                        'title' => $payment->payment_title,
                        'total' => round((float) ($payment->payment_amount ?? 0), 2),
                        'discount' => round((float) ($payment->discount ?? 0), 2),
                        'late_fee' => round((float) ($payment->late_fee ?? 0), 2),
                        'paid' => round((float) ($payment->payment_amount ?? 0) + (float) ($payment->discount ?? 0), 2),
                        'due' => 0,
                        'amount' => 0,
                        'remaining_late' => 0,
                        'generated_id' => null,
                        'is_installment' => true,
                    ];
                })->values()->toArray();

                $feeRows = array_merge($feeRows, $installmentPayments);

                return [
                    'id' => $student->id,
                    'student_name' => $student->student_name,
                    'student_code' => $student->student_code,
                    'father_name' => $student->father_name,
                    'class' => $student->class,
                    'section' => $student->section,
                    'campus' => $student->campus,
                    'monthly_fee' => $student->monthly_fee,
                    'has_unpaid' => $totalDue > 0,
                    'unpaid_amount' => round($totalDue, 2),
                    'pending_fees' => $pendingFees,
                    'fee_rows' => $feeRows,
                ];
            })
        ]);
    }

    /**
     * Search student by CNIC / Parent ID (filtered by accountant's campus)
     */
    public function searchStudentByCNIC(Request $request)
    {
        $cnic = $request->get('cnic');
        
        if (!$cnic) {
            return response()->json([
                'success' => false,
                'message' => 'CNIC / Parent ID is required'
            ], 400);
        }
        
        // Get accountant's assigned campus
        $accountantCampus = null;
        if (auth()->guard('accountant')->check()) {
            $accountant = auth()->guard('accountant')->user();
            $accountantCampus = $accountant->campus;
        }
        
        // Clean and normalize the input CNIC
        $cleanedCnic = trim($cnic);
        $normalizedInputCnic = str_replace(['-', ' ', '_', '.'], '', strtolower($cleanedCnic));
        
        // Find parent account by ID card number
        $parentAccount = \App\Models\ParentAccount::where(function($query) use ($normalizedInputCnic, $cleanedCnic) {
            $query->whereRaw('LOWER(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(id_card_number), "-", ""), " ", ""), "_", ""), ".", "")) = ?', [$normalizedInputCnic])
                  ->orWhereRaw('LOWER(TRIM(id_card_number)) = LOWER(TRIM(?))', [$cleanedCnic]);
        })->first();
        
        // Get students by father_id_card
        $studentsQuery = \App\Models\Student::where(function($query) use ($normalizedInputCnic, $cleanedCnic) {
            $query->whereRaw('LOWER(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(father_id_card), "-", ""), " ", ""), "_", ""), ".", "")) = ?', [$normalizedInputCnic])
                  ->orWhereRaw('LOWER(TRIM(father_id_card)) = LOWER(TRIM(?))', [$cleanedCnic])
                  ->orWhere('father_id_card', $cleanedCnic);
        });
        
        // Filter by accountant's campus if assigned
        if ($accountantCampus) {
            $studentsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($accountantCampus))]);
        }
        
        $studentsByFatherIdCard = $studentsQuery->select('id', 'student_name', 'student_code', 'class', 'section', 'campus', 'monthly_fee', 'father_name', 'father_phone', 'father_email', 'home_address')
            ->get();
        
        // If parent account exists, also get students connected via parent_account_id
        $studentsByParentAccount = collect();
        if ($parentAccount) {
            $parentAccountQuery = \App\Models\Student::where('parent_account_id', $parentAccount->id);
            
            // Filter by accountant's campus if assigned
            if ($accountantCampus) {
                $parentAccountQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($accountantCampus))]);
            }
            
            $studentsByParentAccount = $parentAccountQuery->select('id', 'student_name', 'student_code', 'class', 'section', 'campus', 'monthly_fee', 'transport_fare', 'generate_other_fee', 'other_fee_amount', 'generate_admission_fee', 'admission_fee_amount')
                ->get();
        }
        
        // Merge both collections and remove duplicates
        $students = $studentsByParentAccount->merge($studentsByFatherIdCard)->unique('id')->sortBy('student_name')->values();
        
        return response()->json([
            'success' => true,
            'students' => $students->map(function ($student) {
                $generatedFees = \App\Models\StudentPayment::where('student_code', $student->student_code)
                    ->where('method', 'Generated')
                    ->get();
                $paidFees = \App\Models\StudentPayment::where('student_code', $student->student_code)
                    ->where('method', '!=', 'Generated')
                    ->get();

                $pendingFees = [];
                $feeRows = [];
                $totalDue = 0;
                $generatedByTitle = $generatedFees->groupBy('payment_title');
                $paidByTitle = $paidFees->groupBy('payment_title');

                $installmentBaseTitles = [];
                foreach ($generatedByTitle as $title => $items) {
                    if (preg_match('/^(.+)\/\d+$/', $title, $matches)) {
                        $baseTitle = $matches[1];
                        $installmentBaseTitles[$baseTitle] = true;
                    }
                }

                foreach ($generatedByTitle as $title => $items) {
                    $latestGenerated = $items->sortByDesc('id')->first();
                    $isInstallment = preg_match('/\/\d+$/', $title);
                    
                    if (!$isInstallment && isset($installmentBaseTitles[$title])) {
                        continue;
                    }
                    
                    $generatedAmount = $items->sum(function ($item) {
                        return (float) ($item->payment_amount ?? 0) - (float) ($item->discount ?? 0);
                    });
                    $generatedLate = $items->sum(function ($item) {
                        return (float) ($item->late_fee ?? 0);
                    });
                    $paidDiscount = $paidByTitle->get($title, collect())->sum(function ($item) {
                        return (float) ($item->discount ?? 0);
                    });
                    $paidAmount = $paidByTitle->get($title, collect())->sum(function ($item) {
                        return (float) ($item->payment_amount ?? 0) + (float) ($item->discount ?? 0);
                    });
                    $paidLate = $paidByTitle->get($title, collect())->sum(function ($item) {
                        return (float) ($item->late_fee ?? 0);
                    });

                    $totalGenerated = $generatedAmount;
                    $totalPaid = $paidAmount + $paidLate;
                    $remainingAmount = max(0, $generatedAmount - $paidAmount);
                    $remainingLate = max(0, $generatedLate - $paidLate);
                    $remainingTotal = $remainingAmount + $remainingLate;

                    if ($remainingTotal > 0) {
                        $feeRows[] = [
                            'title' => $title,
                            'total' => round($totalGenerated, 2),
                            'discount' => round($paidDiscount, 2),
                            'late_fee' => round($generatedLate, 2),
                            'paid' => round($totalPaid, 2),
                            'due' => round($remainingTotal, 2),
                            'amount' => round($remainingAmount, 2),
                            'remaining_late' => round($remainingLate, 2),
                            'generated_id' => $latestGenerated ? $latestGenerated->id : null,
                            'is_installment' => $isInstallment,
                        ];
                        $pendingFees[] = [
                            'title' => $title,
                            'amount' => round($remainingAmount, 2),
                            'late_fee' => round($remainingLate, 2),
                            'total' => round($remainingTotal, 2),
                        ];
                        $totalDue += $remainingTotal;
                    }
                }

                $processedTitles = collect($feeRows)->pluck('title')->toArray();
                $installmentPayments = $paidFees->filter(function ($payment) use ($processedTitles) {
                    $title = $payment->payment_title ?? '';
                    return preg_match('/\/\d+$/', $title) && !in_array($title, $processedTitles);
                })->map(function ($payment) {
                    return [
                        'title' => $payment->payment_title,
                        'total' => round((float) ($payment->payment_amount ?? 0), 2),
                        'discount' => round((float) ($payment->discount ?? 0), 2),
                        'late_fee' => round((float) ($payment->late_fee ?? 0), 2),
                        'paid' => round((float) ($payment->payment_amount ?? 0) + (float) ($payment->discount ?? 0), 2),
                        'due' => 0,
                        'amount' => 0,
                        'remaining_late' => 0,
                        'generated_id' => null,
                        'is_installment' => true,
                    ];
                })->values()->toArray();

                $feeRows = array_merge($feeRows, $installmentPayments);

                return [
                    'id' => $student->id,
                    'student_name' => $student->student_name,
                    'student_code' => $student->student_code,
                    'father_name' => $student->father_name ?? '',
                    'class' => $student->class,
                    'section' => $student->section,
                    'campus' => $student->campus,
                    'monthly_fee' => $student->monthly_fee,
                    'has_unpaid' => $totalDue > 0,
                    'unpaid_amount' => round($totalDue, 2),
                    'pending_fees' => $pendingFees,
                    'fee_rows' => $feeRows,
                ];
            })
        ]);
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
    /**
     * Accountant Pages - Generate Monthly Fee
     */
    public function generateMonthlyFee(): View
    {
        $defaultCampus = null;
        if (auth()->guard('accountant')->check()) {
            $defaultCampus = auth()->guard('accountant')->user()->campus;
        }

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

        if ($defaultCampus) {
            $campuses = $campuses->filter(function ($campus) use ($defaultCampus) {
                return ($campus->campus_name ?? $campus) === $defaultCampus;
            })->values();
        }
        
        // Get classes from ClassModel
        $classesQuery = \App\Models\ClassModel::orderBy('class_name', 'asc');
        if ($defaultCampus) {
            $classesQuery->where('campus', $defaultCampus);
        }
        $classes = $classesQuery->get();
        
        // If no classes found, provide empty collection
        if ($classes->isEmpty()) {
            $classes = collect();
        }
        
        // Get sections from Section model
        $sections = \App\Models\Section::whereNotNull('name')
            ->distinct()
            ->orderBy('name', 'asc')
            ->pluck('name')
            ->sort()
            ->values();
        
        // Months of the year
        $months = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];
        
        // Generate years (current year - 2 to current year + 5)
        $currentYear = date('Y');
        $years = [];
        for ($y = $currentYear - 2; $y <= $currentYear + 5; $y++) {
            $years[] = $y;
        }
        
        return view('accountant.generate-monthly-fee', compact('campuses', 'classes', 'sections', 'months', 'years', 'currentYear', 'defaultCampus'));
    }

    /**
     * Accountant Pages - Generate Custom Fee
     */
    /**
     * Accountant Pages - Generate Custom Fee
     */
    public function generateCustomFee(): View
    {
        $defaultCampus = null;
        if (auth()->guard('accountant')->check()) {
            $defaultCampus = auth()->guard('accountant')->user()->campus;
        }

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

        if ($defaultCampus) {
            $campuses = $campuses->filter(function ($campus) use ($defaultCampus) {
                return ($campus->campus_name ?? $campus) === $defaultCampus;
            })->values();
        }
        
        // Get classes from ClassModel
        $classesQuery = \App\Models\ClassModel::orderBy('class_name', 'asc');
        if ($defaultCampus) {
            $classesQuery->where('campus', $defaultCampus);
        }
        $classes = $classesQuery->get();
        
        // If no classes found, provide empty collection
        if ($classes->isEmpty()) {
            $classes = collect();
        }
        
        // Get sections from Section model
        $sections = \App\Models\Section::whereNotNull('name')
            ->distinct()
            ->orderBy('name', 'asc')
            ->pluck('name')
            ->sort()
            ->values();
        
        // Get fee types from FeeType model
        $feeTypes = \App\Models\FeeType::whereNotNull('fee_name')
            ->distinct()
            ->orderBy('fee_name', 'asc')
            ->pluck('fee_name')
            ->sort()
            ->values();
        
        return view('accountant.generate-custom-fee', compact('campuses', 'classes', 'sections', 'feeTypes', 'defaultCampus'));
    }

    /**
     * Accountant Pages - Generate Transport Fee
     */
    public function generateTransportFee(): View
    {
        $defaultCampus = null;
        if (auth()->guard('accountant')->check()) {
            $defaultCampus = auth()->guard('accountant')->user()->campus;
        }

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

        if ($defaultCampus) {
            $campuses = $campuses->filter(function ($campus) use ($defaultCampus) {
                return ($campus->campus_name ?? $campus) === $defaultCampus;
            })->values();
        }
        
        // Get classes from ClassModel
        $classesQuery = \App\Models\ClassModel::orderBy('class_name', 'asc');
        if ($defaultCampus) {
            $classesQuery->where('campus', $defaultCampus);
        }
        $classes = $classesQuery->get();
        
        // If no classes found, provide empty collection
        if ($classes->isEmpty()) {
            $classes = collect();
        }
        
        // Months of the year
        $months = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];
        
        // Generate years (current year - 2 to current year + 5)
        $currentYear = date('Y');
        $years = [];
        for ($y = $currentYear - 2; $y <= $currentYear + 5; $y++) {
            $years[] = $y;
        }
        
        return view('accountant.generate-transport-fee', compact('campuses', 'classes', 'months', 'years', 'currentYear', 'defaultCampus'));
    }

    /**
     * Store the generated transport fee for accountant.
     */
    public function storeTransportFee(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string', 'max:255'],
            'class' => ['required', 'string', 'max:255'],
            'section' => ['required', 'string', 'max:255'],
            'fee_month' => ['required', 'string', 'max:255'],
            'fee_year' => ['required', 'string', 'max:255'],
            'selected_students' => ['nullable', 'array'],
            'selected_students.*' => ['exists:students,id'],
        ]);

        // Check if students are selected
        $selectedStudentIds = $request->input('selected_students', []);

        if (empty($selectedStudentIds)) {
            return redirect()
                ->route('accountant.generate-transport-fee')
                ->with('error', 'Please select at least one student to generate fees.');
        }

        // Create the transport fee configuration
        $transportFee = \App\Models\TransportFee::create($validated);

        // Get selected students
        $students = \App\Models\Student::whereIn('id', $selectedStudentIds)->get();

        // Generate fee for each selected student
        $paymentTitle = "Transport Fee - {$validated['fee_month']} {$validated['fee_year']}";
        $dueDate = Carbon::now()->addDays(15);

        $generatedCount = 0;
        $skippedCount = 0;

        foreach ($students as $student) {
            // Skip if student doesn't have transport fare or student_code
            if (empty($student->transport_fare) || $student->transport_fare <= 0 || empty($student->student_code)) {
                $skippedCount++;
                continue;
            }

            // Check if fee already exists for this student, month, and year
            $existingFee = StudentPayment::where('student_code', $student->student_code)
                ->where('payment_title', $paymentTitle)
                ->first();

            if ($existingFee) {
                $skippedCount++;
                continue;
            }

            // Get accountant name based on guard
            $accountantName = 'System';
            if (auth()->guard('accountant')->check()) {
                $accountantName = auth()->guard('accountant')->user()->name ?? 'System';
            } elseif (auth()->guard('admin')->check()) {
                $accountantName = auth()->guard('admin')->user()->name ?? 'System';
            }

            // Create fee record for this student
            StudentPayment::create([
                'campus' => $student->campus ?? $validated['campus'],
                'student_code' => $student->student_code,
                'payment_title' => $paymentTitle,
                'payment_amount' => (float) $student->transport_fare,
                'discount' => 0,
                'method' => 'Generated',
                'payment_date' => $dueDate->format('Y-m-d'),
                'sms_notification' => 'Yes',
                'late_fee' => 0,
                'accountant' => $accountantName,
            ]);

            $generatedCount++;
        }

        if ($generatedCount == 0) {
            $message = "No transport fees were generated. ";
            if ($skippedCount > 0) {
                $message .= "All selected students were skipped (no transport fare set or fees already exist).";
            } else {
                $message .= "Selected students don't have transport fare configured.";
            }

            return redirect()
                ->route('accountant.generate-transport-fee')
                ->with('error', $message);
        }

        $message = "Transport fee generated successfully for {$generatedCount} student(s)!";
        if ($skippedCount > 0) {
            $message .= " {$skippedCount} student(s) skipped (no transport fare set or already exists).";
        }

        return redirect()
            ->route('accountant.generate-transport-fee')
            ->with('success', $message);
    }

    /**
     * Get sections by class name for accountant (AJAX).
     */
    public function getTransportFeeSectionsByClass(Request $request)
    {
        $className = $request->get('class');
        $campus = $request->get('campus');
        
        if (!$className) {
            return response()->json(['sections' => []]);
        }

        // Get sections for the selected class
        $sectionsQuery = \App\Models\Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($className))]);
        if ($campus) {
            $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        $sections = $sectionsQuery
            ->orderBy('name', 'asc')
            ->get(['id', 'name'])
            ->map(function($section) {
                return [
                    'id' => $section->id,
                    'name' => $section->name
                ];
            });

        return response()->json(['sections' => $sections]);
    }

    /** 
     * Accountant Pages - Fee Type
     */
    public function feeType(Request $request): View
    {
        $query = \App\Models\FeeType::query();
        
        // Search functionality
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(fee_name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(campus) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $feeTypes = $query->orderBy('fee_name')->paginate($perPage)->withQueryString();
        
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
        
        // Filter campuses based on logged-in accountant's assigned campus
        if (auth()->guard('accountant')->check()) {
            $accountant = auth()->guard('accountant')->user();
            if ($accountant && $accountant->campus) {
                // Filter to show only the accountant's assigned campus
                $campuses = $campuses->filter(function ($campus) use ($accountant) {
                    $campusName = $campus->campus_name ?? $campus;
                    return $campusName === $accountant->campus;
                })->values();
            }
        }
        
        return view('accountant.fee-type', compact('feeTypes', 'campuses'));
    }

    /**
     * Accountant Pages - Parents Credit System
     */
    public function parentsCreditSystem(Request $request): View
    {
        // Get accountant's assigned campus
        $accountantCampus = null;
        if (auth()->guard('accountant')->check()) {
            $accountant = auth()->guard('accountant')->user();
            $accountantCampus = $accountant->campus;
        }
        
        // Get parent IDs that have students in the accountant's campus
        $parentIdsInCampus = collect();
        if ($accountantCampus) {
            $parentIdsInCampus = \App\Models\Student::whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($accountantCampus))])
                ->whereNotNull('parent_account_id')
                ->distinct()
                ->pluck('parent_account_id');
        }
        
        // Ensure every parent account has an advance fee record (only for accountant's campus if assigned)
        $parentsQuery = \App\Models\ParentAccount::select('id', 'name', 'email', 'phone', 'id_card_number');
        if ($accountantCampus && $parentIdsInCampus->isNotEmpty()) {
            $parentsQuery->whereIn('id', $parentIdsInCampus);
        }
        $parents = $parentsQuery->get();
        
        foreach ($parents as $parent) {
            \App\Models\AdvanceFee::firstOrCreate(
                ['parent_id' => (string) $parent->id],
                [
                    'name' => $parent->name,
                    'email' => $parent->email,
                    'phone' => $parent->phone,
                    'id_card_number' => $parent->id_card_number,
                    'available_credit' => 0,
                    'increase' => 0,
                    'decrease' => 0,
                    'childs' => 0,
                ]
            );
        }

        $query = \App\Models\AdvanceFee::query();
        
        // Filter by accountant's campus - only show parents who have students in that campus
        if ($accountantCampus && $parentIdsInCampus->isNotEmpty()) {
            $query->whereIn('parent_id', $parentIdsInCampus);
        }
        
        // Search functionality
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(email) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(phone) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(id_card_number) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $advanceFees = $query->orderBy('name')->paginate($perPage)->withQueryString();
        
        // Get children count for each parent (only count children in accountant's campus)
        foreach ($advanceFees as $advanceFee) {
            if ($advanceFee->parent_id) {
                $childrenQuery = \App\Models\Student::where('parent_account_id', $advanceFee->parent_id);
                if ($accountantCampus) {
                    $childrenQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($accountantCampus))]);
                }
                $childrenCount = $childrenQuery->count();
                $advanceFee->children_count = $childrenCount;
            } else {
                $advanceFee->children_count = 0;
            }
        }
        
        return view('accountant.parents-credit-system', compact('advanceFees'));
    }
    
    /**
     * Show a single advance fee record (for AJAX)
     */
    public function showAdvanceFee($id)
    {
        $advanceFee = \App\Models\AdvanceFee::findOrFail($id);
        return response()->json($advanceFee);
    }
    
    /**
     * Get connected students for advance fee record
     */
    public function getConnectedStudents($id)
    {
        // Get accountant's assigned campus
        $accountantCampus = null;
        if (auth()->guard('accountant')->check()) {
            $accountant = auth()->guard('accountant')->user();
            $accountantCampus = $accountant->campus;
        }
        
        $advanceFee = \App\Models\AdvanceFee::findOrFail($id);
        $students = collect();

        if (!empty($advanceFee->parent_id) && ctype_digit((string) $advanceFee->parent_id)) {
            $parentAccount = \App\Models\ParentAccount::find((int) $advanceFee->parent_id);
            if ($parentAccount) {
                $parentStudents = $parentAccount->students;
                // Filter by accountant's campus if assigned
                if ($accountantCampus) {
                    $parentStudents = $parentStudents->filter(function($student) use ($accountantCampus) {
                        return strtolower(trim($student->campus ?? '')) === strtolower(trim($accountantCampus));
                    });
                }
                $students = $students->merge($parentStudents);
            }
        }

        if (!empty($advanceFee->id_card_number)) {
            $studentsByCardQuery = \App\Models\Student::where('father_id_card', $advanceFee->id_card_number);
            // Filter by accountant's campus if assigned
            if ($accountantCampus) {
                $studentsByCardQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($accountantCampus))]);
            }
            $students = $students->merge($studentsByCardQuery->get());
        }

        $students = $students->unique('id')->values();

        return response()->json([
            'students' => $students->map(function ($student) {
                return [
                    'id' => $student->id,
                    'student_name' => $student->student_name,
                    'student_code' => $student->student_code,
                    'class' => $student->class,
                    'section' => $student->section,
                    'campus' => $student->campus,
                ];
            }),
        ]);
    }
    
    /**
     * Update advance fee credit (increase/decrease)
     */
    public function updateAdvanceFeeCredit(Request $request, $id)
    {
        $validated = $request->validate([
            'increase' => ['nullable', 'numeric', 'min:0'],
            'decrease' => ['nullable', 'numeric', 'min:0'],
        ]);

        $advanceFee = \App\Models\AdvanceFee::findOrFail($id);
        
        $currentCredit = (float) ($advanceFee->available_credit ?? 0);
        $currentIncrease = (float) ($advanceFee->increase ?? 0);
        $currentDecrease = (float) ($advanceFee->decrease ?? 0);
        
        if (isset($validated['increase']) && $validated['increase'] > 0) {
            $increaseAmount = (float) $validated['increase'];
            $advanceFee->available_credit = $currentCredit + $increaseAmount;
            $advanceFee->increase = $currentIncrease + $increaseAmount;
        }
        
        if (isset($validated['decrease']) && $validated['decrease'] > 0) {
            $decreaseAmount = (float) $validated['decrease'];
            $advanceFee->available_credit = max(0, $currentCredit - $decreaseAmount);
            $advanceFee->decrease = $currentDecrease + $decreaseAmount;
        }

        $advanceFee->save();

        return redirect()
            ->route('accountant.parents-credit-system')
            ->with('success', 'Credit updated successfully!');
    }
    
    /**
     * Export parents credit system to Excel, CSV, or PDF
     */
    public function exportParentsCreditSystem(Request $request, string $format)
    {
        // Get accountant's assigned campus
        $accountantCampus = null;
        if (auth()->guard('accountant')->check()) {
            $accountant = auth()->guard('accountant')->user();
            $accountantCampus = $accountant->campus;
        }
        
        // Get parent IDs that have students in the accountant's campus
        $parentIdsInCampus = collect();
        if ($accountantCampus) {
            $parentIdsInCampus = \App\Models\Student::whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($accountantCampus))])
                ->whereNotNull('parent_account_id')
                ->distinct()
                ->pluck('parent_account_id');
        }
        
        $query = \App\Models\AdvanceFee::query();
        
        // Filter by accountant's campus - only show parents who have students in that campus
        if ($accountantCampus && $parentIdsInCampus->isNotEmpty()) {
            $query->whereIn('parent_id', $parentIdsInCampus);
        }
        
        // Apply search filter if present
        if ($request->has('search') && $request->search) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(email) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(phone) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(id_card_number) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $advanceFees = $query->orderBy('name')->get();
        
        // Get children count for each parent (only count children in accountant's campus)
        foreach ($advanceFees as $advanceFee) {
            if ($advanceFee->parent_id) {
                $childrenQuery = \App\Models\Student::where('parent_account_id', $advanceFee->parent_id);
                if ($accountantCampus) {
                    $childrenQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($accountantCampus))]);
                }
                $childrenCount = $childrenQuery->count();
                $advanceFee->children_count = $childrenCount;
            } else {
                $advanceFee->children_count = 0;
            }
        }
        
        switch ($format) {
            case 'excel':
                return $this->exportParentsCreditExcel($advanceFees);
            case 'csv':
                return $this->exportParentsCreditCSV($advanceFees);
            case 'pdf':
                return $this->exportParentsCreditPDF($advanceFees);
            default:
                return redirect()->route('accountant.parents-credit-system')
                    ->with('error', 'Invalid export format!');
        }
    }
    
    /**
     * Export to Excel (CSV format for Excel compatibility)
     */
    private function exportParentsCreditExcel($advanceFees)
    {
        $filename = 'parents_credit_system_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($advanceFees) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, ['Parent ID', 'Name', 'Email', 'Phone', 'ID Card Number', 'Available Credit', 'Increase', 'Decrease', 'Children']);
            
            foreach ($advanceFees as $fee) {
                $childrenCount = $fee->parent_id ? \App\Models\Student::where('parent_account_id', $fee->parent_id)->count() : 0;
                fputcsv($file, [
                    $fee->parent_id ?? 'N/A',
                    $fee->name ?? 'N/A',
                    $fee->email ?? 'N/A',
                    $fee->phone ?? 'N/A',
                    $fee->id_card_number ?? 'N/A',
                    number_format((float)($fee->available_credit ?? 0), 2),
                    number_format((float)($fee->increase ?? 0), 2),
                    number_format((float)($fee->decrease ?? 0), 2),
                    $childrenCount,
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
    
    /**
     * Export to CSV
     */
    private function exportParentsCreditCSV($advanceFees)
    {
        $filename = 'parents_credit_system_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($advanceFees) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, ['Parent ID', 'Name', 'Email', 'Phone', 'ID Card Number', 'Available Credit', 'Increase', 'Decrease', 'Children']);
            
            foreach ($advanceFees as $fee) {
                $childrenCount = $fee->parent_id ? \App\Models\Student::where('parent_account_id', $fee->parent_id)->count() : 0;
                fputcsv($file, [
                    $fee->parent_id ?? 'N/A',
                    $fee->name ?? 'N/A',
                    $fee->email ?? 'N/A',
                    $fee->phone ?? 'N/A',
                    $fee->id_card_number ?? 'N/A',
                    number_format((float)($fee->available_credit ?? 0), 2),
                    number_format((float)($fee->increase ?? 0), 2),
                    number_format((float)($fee->decrease ?? 0), 2),
                    $childrenCount,
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
    
    /**
     * Export to PDF
     */
    private function exportParentsCreditPDF($advanceFees)
    {
        // Get children count for each parent
        foreach ($advanceFees as $advanceFee) {
            if ($advanceFee->parent_id) {
                $childrenCount = \App\Models\Student::where('parent_account_id', $advanceFee->parent_id)->count();
                $advanceFee->children_count = $childrenCount;
            } else {
                $advanceFee->children_count = 0;
            }
        }
        
        $html = view('accountant.parents-credit-system-pdf', compact('advanceFees'))->render();
        
        return response($html)
            ->header('Content-Type', 'text/html');
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
        $defaultCampus = null;
        if (auth()->guard('accountant')->check()) {
            $defaultCampus = auth()->guard('accountant')->user()->campus;
        }

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

        if ($defaultCampus) {
            $campuses = $campuses->filter(function ($campus) use ($defaultCampus) {
                return ($campus->campus_name ?? $campus) === $defaultCampus;
            })->values();
        }
        
        // Payment methods
        $methods = ['Cash', 'Bank Transfer', 'Cheque', 'Online Payment', 'Card', 'Mobile Banking'];
        
        return view('accountant.student-payment', compact('campuses', 'methods', 'defaultCampus'));
    }

    /**
     * Store student payment for accountant.
     */
    public function storeStudentPayment(Request $request)
    {
        try {
            try {
                $validated = $request->validate([
                    'campus' => ['nullable', 'string', 'max:255'],
                    'student_code' => ['required', 'string', 'max:255'],
                    'payment_title' => ['required', 'string', 'max:255'],
                    'payment_amount' => ['required', 'numeric', 'min:0'],
                    'discount' => ['nullable', 'numeric', 'min:0'],
                    'method' => ['required', 'string', 'max:255'],
                    'payment_date' => ['nullable', 'date'],
                    'generated_id' => ['nullable', 'integer'],
                    'sms_notification' => ['nullable', 'string', 'in:Yes,No'],
                ]);
            } catch (\Illuminate\Validation\ValidationException $e) {
                if ($request->ajax() || $request->wantsJson() || $request->header('Accept') === 'application/json' || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => $e->errors()
                    ], 422);
                }
                throw $e;
            }

            // Set payment date to today if not provided
            if (!isset($validated['payment_date'])) {
                $validated['payment_date'] = now()->toDateString();
            }

            // Check if this is an installment (payment_title contains /number pattern)
            $isInstallment = preg_match('/\/\d+$/', $validated['payment_title']);
            
            // For installments, skip existing fee check
            if (!$isInstallment && !empty($validated['generated_id'])) {
                $generatedFee = \App\Models\StudentPayment::where('id', $validated['generated_id'])
                    ->where('student_code', $validated['student_code'])
                    ->where('method', 'Generated')
                    ->first();
                if ($generatedFee && $generatedFee->payment_title) {
                    $validated['payment_title'] = $generatedFee->payment_title;
                }
            }

            // Add default values
            if (!isset($validated['sms_notification'])) {
                $validated['sms_notification'] = 'Yes';
            }
            $validated['late_fee'] = 0;
            
            // Add accountant if available
            if (auth()->check()) {
                $validated['accountant'] = auth()->user()->name ?? null;
            }

            // Get student for response
            $student = \App\Models\Student::where('student_code', $validated['student_code'])->first();

            // Create payment record
            $payment = \App\Models\StudentPayment::create($validated);

            $successMessage = 'Payment recorded successfully!';

            // If request is AJAX or expects JSON, return JSON response
            if ($request->ajax() || $request->wantsJson() || $request->header('Accept') === 'application/json' || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => true,
                    'message' => $successMessage,
                    'payment' => [
                        'id' => $payment->id,
                        'student_code' => $payment->student_code,
                        'student_name' => $student->student_name ?? null,
                        'father_name' => $student->father_name ?? null,
                        'class' => $student->class ?? null,
                        'section' => $student->section ?? null,
                        'payment_title' => $payment->payment_title,
                        'payment_amount' => (float) ($payment->payment_amount ?? 0),
                        'discount' => (float) ($payment->discount ?? 0),
                        'late_fee' => (float) ($payment->late_fee ?? 0),
                        'payment_date' => $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d-m-Y h:i:s A') : null,
                        'accountant' => $payment->accountant ?? null,
                    ],
                ]);
            }

            return redirect()
                ->route('accountant.direct-payment.student')
                ->with('success', $successMessage);
        } catch (\Exception $e) {
            // Catch any unexpected exceptions and return JSON response
            if ($request->ajax() || $request->wantsJson() || $request->header('Accept') === 'application/json' || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                \Log::error('Error in AccountantController@storeStudentPayment: ' . $e->getMessage(), [
                    'exception' => $e,
                    'request_data' => $request->all()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Error creating payment: ' . $e->getMessage()
                ], 500);
            }
            throw $e;
        }
    }

    /**
     * Get student by student code (AJAX).
     */
    public function getStudentByCode(Request $request)
    {
        $studentCode = $request->get('student_code');
        $campus = $request->get('campus');
        
        if (!$studentCode) {
            return response()->json(['success' => false, 'message' => 'Student code is required']);
        }

        $studentQuery = \App\Models\Student::where('student_code', $studentCode);
        
        // Filter by campus if provided
        if ($campus && trim($campus) !== '') {
            $studentQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        
        $student = $studentQuery->first();
        
        if (!$student) {
            $message = $campus && trim($campus) !== '' 
                ? 'Student not found with this code in the selected campus'
                : 'Student not found with this code';
            return response()->json([
                'success' => false,
                'message' => $message
            ]);
        }

        $generatedFees = \App\Models\StudentPayment::where('student_code', $studentCode)
            ->where('method', 'Generated')
            ->orderBy('payment_date', 'asc')
            ->get(['id', 'payment_title', 'payment_amount', 'late_fee', 'payment_date']);

        return response()->json([
            'success' => true,
            'student' => [
                'student_code' => $student->student_code,
                'student_name' => $student->student_name,
                'campus' => $student->campus,
                'class' => $student->class,
                'section' => $student->section,
            ],
            'generated_fees' => $generatedFees
        ]);
    }

    /**
     * Accountant Pages - Custom Payment
     */
    public function customPayment(): View
    {
        $defaultCampus = null;
        if (auth()->guard('accountant')->check()) {
            $defaultCampus = auth()->guard('accountant')->user()->campus;
        }

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

        if ($defaultCampus) {
            $campuses = $campuses->filter(function ($campus) use ($defaultCampus) {
                return ($campus->campus_name ?? $campus) === $defaultCampus;
            })->values();
        }
        
        // Get accountants
        $accountants = \App\Models\Accountant::orderBy('name', 'asc')->get();
        if ($accountants->isEmpty()) {
            $accountants = collect();
        }
        
        // Payment methods
        $methods = ['Cash', 'Bank Transfer', 'Cheque', 'Online Payment', 'Card', 'Mobile Banking'];
        
        return view('accountant.custom-payment', compact('campuses', 'accountants', 'methods', 'defaultCampus'));
    }

    /**
     * Store custom payment for accountant.
     */
    public function storeCustomPayment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['nullable', 'string', 'max:255'],
            'payment_title' => ['required', 'string', 'max:255'],
            'payment_amount' => ['required', 'numeric', 'min:0'],
            'accountant' => ['nullable', 'string', 'max:255'],
            'method' => ['required', 'string', 'max:255'],
            'payment_date' => ['nullable', 'date'],
        ]);

        // Set payment date to today if not provided
        if (!isset($validated['payment_date'])) {
            $validated['payment_date'] = now()->toDateString();
        }

        // Add default values
        $validated['notify_admin'] = 'Yes';
        
        // If accountant not provided, use current logged in user
        if (empty($validated['accountant']) && auth()->check()) {
            $validated['accountant'] = auth()->user()->name ?? null;
        }

        // Create payment record
        \App\Models\CustomPayment::create($validated);

        return redirect()
            ->route('accountant.direct-payment.custom')
            ->with('success', 'Custom payment recorded successfully!');
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
    public function deletedFees(Request $request): View
    {
        $defaultCampus = null;
        if (auth()->guard('accountant')->check()) {
            $defaultCampus = auth()->guard('accountant')->user()->campus;
        }

        $filterCampus = $request->get('filter_campus');
        if ($defaultCampus) {
            $filterCampus = $defaultCampus;
        }

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

        if ($defaultCampus) {
            $campuses = $campuses->filter(function ($campus) use ($defaultCampus) {
                return ($campus->campus_name ?? $campus) === $defaultCampus;
            })->values();
        }

        // Get deleted fees
        $query = \App\Models\DeletedFee::query();

        if ($filterCampus) {
            $query->where('campus', $filterCampus);
        }
        
        // Search functionality
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(student_code) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(student_name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(parent_name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(payment_title) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(campus) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(deleted_by) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $deletedFees = $query->orderBy('deleted_at', 'desc')->paginate($perPage)->withQueryString();
        
        return view('accountant.deleted-fees', compact('deletedFees', 'campuses', 'filterCampus', 'defaultCampus'));
    }

    /**
     * Restore a deleted fee
     */
    public function restoreDeletedFee(\App\Models\DeletedFee $deletedFee)
    {
        try {
            // Get original payment data
            $originalData = $deletedFee->original_data ?? [];
            
            // If original_data exists, restore from it
            if (!empty($originalData)) {
                \App\Models\StudentPayment::create($originalData);
            } else {
                // Otherwise, create from deleted_fee fields
                \App\Models\StudentPayment::create([
                    'campus' => $deletedFee->campus,
                    'student_code' => $deletedFee->student_code,
                    'payment_title' => $deletedFee->payment_title,
                    'payment_amount' => $deletedFee->payment_amount,
                    'discount' => $deletedFee->discount ?? 0,
                    'method' => $deletedFee->method ?? 'Cash',
                    'payment_date' => $deletedFee->payment_date ?? now(),
                    'sms_notification' => 'Yes',
                    'accountant' => $deletedFee->deleted_by,
                    'late_fee' => 0,
                ]);
            }
            
            // Delete from deleted_fees table
            $deletedFee->delete();
            
            return redirect()
                ->route('accountant.deleted-fees')
                ->with('success', 'Fee restored successfully!');
        } catch (\Exception $e) {
            return redirect()
                ->route('accountant.deleted-fees')
                ->with('error', 'Failed to restore fee: ' . $e->getMessage());
        }
    }

    /**
     * Accountant Pages - Student Vouchers
     */
    public function studentVouchers(Request $request): View
    {
        // Get default campus from logged-in accountant
        $defaultCampus = null;
        if (auth()->guard('accountant')->check()) {
            $defaultCampus = auth()->guard('accountant')->user()->campus;
        }
        
        $filterCampus = $request->get('campus', $defaultCampus);
        
        // Get campuses - if filterCampus is set, show only that campus
        if (!empty($filterCampus)) {
            $campuses = \App\Models\Campus::whereRaw('LOWER(TRIM(campus_name)) = ?', [strtolower(trim($filterCampus))])
                ->orderBy('campus_name', 'asc')
                ->get();
            
            if ($campuses->isEmpty()) {
                // If not found in Campus table, create a collection with the campus name
                $campuses = collect();
                $campuses->push((object)['campus_name' => $filterCampus]);
            }
        } else {
            // Get all campuses if no filter
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
        }

        // Get classes (campus-wise)
        $classes = collect();
        if (!empty($filterCampus)) {
            $classes = \App\Models\ClassModel::whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))])
                ->orderBy('class_name', 'asc')
                ->get();
        }
        
        // Get sections (will be filtered by class via AJAX)
        $sections = collect();
        if ($request->filled('class')) {
            $sectionsQuery = \App\Models\Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($request->class))]);
            if (!empty($filterCampus)) {
                $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
            }
            $sections = $sectionsQuery
                ->orderBy('name', 'asc')
                ->get();
        }
        
        $query = \App\Models\Student::query();
        $currentYear = date('Y');
        $vouchersFor = $request->get('vouchers_for');
        $pendingPaymentsQuery = \App\Models\StudentPayment::where('method', 'Generated')
            ->whereNotNull('student_code')
            ->where('student_code', '!=', '');
        if ($vouchersFor) {
            $paymentTitle = "Monthly Fee - {$vouchersFor} {$currentYear}";
            $pendingPaymentsQuery->where('payment_title', $paymentTitle);
        }
        $pendingStudentCodes = $pendingPaymentsQuery->distinct()->pluck('student_code');
        if ($pendingStudentCodes->isNotEmpty()) {
            $query->whereIn('student_code', $pendingStudentCodes);
        } else {
            $query->whereRaw('1 = 0');
        }
        
        // Apply filters
        if (!empty($filterCampus)) {
            $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
        }

        if ($request->filled('class')) {
            $query->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($request->class))]);
        }
        
        if ($request->filled('section')) {
            $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($request->section))]);
        }
        
        $students = $query->orderBy('student_name')->paginate(20)->withQueryString();
        
        return view('accountant.fee-voucher.student', compact('students', 'classes', 'sections', 'campuses', 'filterCampus'));
    }

    /**
     * Accountant Pages - Family Vouchers
     */
    public function familyVouchers(Request $request): View
    {
        $copyTypes = [
            'three_copies' => 'Three Copy',
            'two_copies' => 'Two Copy',
            'one_copy' => 'One Copy',
        ];
        $months = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December',
        ];
        $defaultCampus = null;
        if (auth()->guard('accountant')->check()) {
            $defaultCampus = auth()->guard('accountant')->user()->campus;
        }

        $filterCampus = $request->get('campus');
        if ($defaultCampus) {
            $filterCampus = $defaultCampus;
        }

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

        if ($defaultCampus) {
            $campuses = $campuses->filter(function ($campus) use ($defaultCampus) {
                return ($campus->campus_name ?? $campus) === $defaultCampus;
            })->values();
        }

        // Group students by parent (using father_name or parent_id)
        $query = \App\Models\Student::select(
            \Illuminate\Support\Facades\DB::raw('COALESCE(father_name, "Unknown") as parent_name'),
            \Illuminate\Support\Facades\DB::raw('GROUP_CONCAT(DISTINCT student_name) as student_names'),
            \Illuminate\Support\Facades\DB::raw('GROUP_CONCAT(DISTINCT student_code) as student_codes'),
            \Illuminate\Support\Facades\DB::raw('GROUP_CONCAT(DISTINCT class) as classes'),
            \Illuminate\Support\Facades\DB::raw('GROUP_CONCAT(DISTINCT section) as sections'),
            \Illuminate\Support\Facades\DB::raw('MAX(campus) as campus'),
            \Illuminate\Support\Facades\DB::raw('COUNT(*) as student_count')
        )
        ->groupBy('father_name');
        
        // Apply filters
        if ($filterCampus) {
            $query->where('campus', $filterCampus);
        }
        
        $families = $query->orderBy('parent_name')->paginate(20)->withQueryString();
        
        return view('accountant.fee-voucher.family', compact('families', 'campuses', 'filterCampus', 'defaultCampus', 'copyTypes', 'months'));
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
     * Accountant Pages - Fee Defaulters Reports (uses same data/logic as super admin but with accountant layout)
     */
    public function feeDefaulters(Request $request): View
    {
        $defaultCampus = null;
        if (auth()->guard('accountant')->check()) {
            $defaultCampus = auth()->guard('accountant')->user()->campus;
        }

        if ($defaultCampus) {
            $request->merge(['filter_campus' => $defaultCampus]);
        }

        // Use the same FeeDefaultReportController logic
        $controller = new \App\Http\Controllers\FeeDefaultReportController();
        $view = $controller->index($request);
        
        // Change the view to accountant version
        $viewData = $view->getData();
        if ($defaultCampus && isset($viewData['campuses'])) {
            $viewData['campuses'] = $viewData['campuses']->filter(function ($campus) use ($defaultCampus) {
                return ($campus->campus_name ?? $campus) === $defaultCampus;
            })->values();
        }
        $viewData['defaultCampus'] = $defaultCampus;
        return view('accountant.fee-defaulters', $viewData);
    }

    /**
     * Accountant Pages - Accounts Summary Reports (uses same data/logic as super admin but with accountant layout)
     */
    public function accountsSummary(Request $request): View
    {
        $defaultCampus = null;
        if (auth()->guard('accountant')->check()) {
            $defaultCampus = auth()->guard('accountant')->user()->campus;
        }

        if ($defaultCampus) {
            $request->merge(['filter_campus' => $defaultCampus]);
        }

        // Use the same AccountsSummaryController logic
        $controller = new \App\Http\Controllers\AccountsSummaryController();
        $view = $controller->index($request);
        
        // Change the view to accountant version
        $viewData = $view->getData();
        if ($defaultCampus && isset($viewData['campuses'])) {
            $viewData['campuses'] = $viewData['campuses']->filter(function ($campus) use ($defaultCampus) {
                return ($campus->campus_name ?? $campus) === $defaultCampus;
            })->values();
        }
        $viewData['defaultCampus'] = $defaultCampus;
        return view('accountant.accounts-summary', $viewData);
    }

    /**
     * Accountant Pages - Detailed Income Report (uses same data/logic as super admin but with accountant layout)
     */
    public function detailedIncome(Request $request): View
    {
        $defaultCampus = null;
        if (auth()->guard('accountant')->check()) {
            $defaultCampus = auth()->guard('accountant')->user()->campus;
        }

        if ($defaultCampus) {
            $request->merge(['filter_campus' => $defaultCampus]);
        }

        // Use the same DetailedIncomeController logic
        $controller = new \App\Http\Controllers\DetailedIncomeController();
        $view = $controller->index($request);
        
        // Change the view to accountant version
        $viewData = $view->getData();
        if ($defaultCampus && isset($viewData['campuses'])) {
            $viewData['campuses'] = collect($viewData['campuses'])
                ->filter(function ($campus) use ($defaultCampus) {
                    return ($campus->campus_name ?? $campus) === $defaultCampus;
                })
                ->values();
        }
        $viewData['defaultCampus'] = $defaultCampus;
        return view('accountant.detailed-income', $viewData);
    }

    /**
     * Accountant Pages - Detailed Expense Report (uses same data/logic as super admin but with accountant layout)
     */
    public function detailedExpense(Request $request): View
    {
        // Use the same DetailedExpenseController logic
        $controller = new \App\Http\Controllers\DetailedExpenseController();
        $view = $controller->index($request);
        
        // Change the view to accountant version
        $viewData = $view->getData();
        return view('accountant.detailed-expense', $viewData);
    }

    /**
     * Accountant Pages - Academic Calendar
     */
    public function academicCalendar(Request $request): View
    {
        $year = $request->get('year', date('Y'));
        
        // Get all events for the year
        $events = \App\Models\Event::whereYear('event_date', $year)
            ->orderBy('event_date')
            ->get();
        
        // Group events by month
        $eventsByMonth = [];
        foreach ($events as $event) {
            $month = $event->event_date->format('n'); // 1-12
            if (!isset($eventsByMonth[$month])) {
                $eventsByMonth[$month] = [];
            }
            $eventsByMonth[$month][] = $event;
        }
        
        // Get campuses from Campus model
        $campuses = \App\Models\Campus::orderBy('campus_name', 'asc')->get();
        if ($campuses->isEmpty()) {
            // Fallback: get from other sources
            $campusesFromClasses = \App\Models\ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromSections = \App\Models\Section::whereNotNull('campus')->distinct()->pluck('campus');
            $allCampuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
            
            $campuses = $allCampuses->map(function($campusName) {
                return (object)['campus_name' => $campusName, 'id' => null];
            });
        }
        
        return view('accountant.academic-calendar', compact('eventsByMonth', 'year', 'campuses'));
    }

    /**
     * Store a newly created campus for Academic Calendar (AJAX).
     */
    public function storeAcademicCalendarCampus(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'campus_name' => ['required', 'string', 'max:255', 'unique:campuses,campus_name'],
        ]);

        $campus = \App\Models\Campus::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Campus added successfully!',
            'campus' => $campus
        ]);
    }

    /**
     * Remove the specified campus for Academic Calendar (AJAX).
     */
    public function destroyAcademicCalendarCampus(\App\Models\Campus $campus): \Illuminate\Http\JsonResponse
    {
        $campus->delete();

        return response()->json([
            'success' => true,
            'message' => 'Campus deleted successfully!'
        ]);
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
        
        // Get campuses dynamically
        $campuses = \App\Models\Campus::orderBy('campus_name', 'asc')->get();
        if ($campuses->isEmpty()) {
            // Fallback: get from products
            $campuses = \App\Models\Product::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->map(function($campusName) {
                    return (object)['campus_name' => $campusName];
                })
                ->sortBy('campus_name')
                ->values();
        }
        
        // Filter campuses based on logged-in accountant's assigned campus
        if (auth()->guard('accountant')->check()) {
            $accountant = auth()->guard('accountant')->user();
            if ($accountant && $accountant->campus) {
                $campuses = $campuses->filter(function ($campus) use ($accountant) {
                    $campusName = is_object($campus) ? ($campus->campus_name ?? $campus->name ?? '') : $campus;
                    return $campusName === $accountant->campus;
                })->values();
            }
        }
        
        // Return view with accountant layout - we'll use a shared partial
        return view('accountant.point-of-sale', compact('products', 'campuses'));
    }

    /**
     * Accountant Pages - Manage Categories (uses same data/logic as super admin but with accountant layout)
     */
    public function manageCategories(Request $request): View
    {
        $defaultCampus = null;
        if (auth()->guard('accountant')->check()) {
            $defaultCampus = auth()->guard('accountant')->user()->campus;
        }

        // Use the same StockCategoryController logic
        $query = \App\Models\StockCategory::query();
        if ($defaultCampus) {
            $query->where('campus', $defaultCampus);
        }
        
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

        if ($defaultCampus) {
            $campuses = $campuses->filter(function ($campus) use ($defaultCampus) {
                return ($campus->campus_name ?? $campus) === $defaultCampus;
            })->values();
        }
        
        return view('accountant.manage-categories', compact('categories', 'campuses', 'defaultCampus'));
    }

    /**
     * Accountant Pages - Product and Stock (uses same data/logic as super admin but with accountant layout)
     */
    public function productAndStock(Request $request): View
    {
        // Use the same ProductController logic
        $query = \App\Models\Product::query();
        
        // Filter by accountant's assigned campus
        $defaultCampus = null;
        if (auth()->guard('accountant')->check()) {
            $defaultCampus = auth()->guard('accountant')->user()->campus;
            if ($defaultCampus) {
                $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($defaultCampus))]);
            }
        }
        
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

        // Get categories for dropdown (with campus for filtering)
        $categoriesQuery = \App\Models\StockCategory::whereNotNull('category_name')
            ->whereNotNull('campus');
        
        // Filter categories by accountant's assigned campus
        if ($defaultCampus) {
            $categoriesQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($defaultCampus))]);
        }
        
        $categories = $categoriesQuery->orderBy('category_name')
            ->get(['category_name', 'campus']);

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
        
        // Filter campuses based on logged-in accountant's assigned campus
        if (auth()->guard('accountant')->check()) {
            $accountant = auth()->guard('accountant')->user();
            if ($accountant && $accountant->campus) {
                $campuses = $campuses->filter(function ($campus) use ($accountant) {
                    $campusName = is_object($campus) ? ($campus->campus_name ?? $campus->name ?? '') : $campus;
                    return $campusName === $accountant->campus;
                })->values();
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
        $defaultCampus = null;
        if (auth()->guard('accountant')->check()) {
            $defaultCampus = auth()->guard('accountant')->user()->campus;
        }

        // Use the same ManagementExpenseController logic
        $query = \App\Models\ManagementExpense::query();

        if ($defaultCampus) {
            $query->where('campus', $defaultCampus);
        }
        
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

        if ($defaultCampus) {
            $campuses = $campuses->filter(function ($campus) use ($defaultCampus) {
                return ($campus->campus_name ?? $campus) === $defaultCampus;
            })->values();
        }
        
        // Get expense categories for dropdown
        $categories = \App\Models\ExpenseCategory::orderBy('category_name')->get();
        
        return view('accountant.add-manage-expense', compact('expenses', 'categories', 'campuses', 'defaultCampus'));
    }

    /**
     * Accountant Pages - Expense Categories (uses same data/logic as super admin but with accountant layout)
     */
    public function expenseCategories(Request $request): View
    {
        $defaultCampus = null;
        if (auth()->guard('accountant')->check()) {
            $defaultCampus = auth()->guard('accountant')->user()->campus;
        }

        // Use the same ExpenseCategoryController logic
        $query = \App\Models\ExpenseCategory::query();
        if ($defaultCampus) {
            $query->where('campus', $defaultCampus);
        }
        
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

        if ($defaultCampus) {
            $campuses = $campuses->filter(function ($campus) use ($defaultCampus) {
                return ($campus->campus_name ?? $campus) === $defaultCampus;
            })->values();
        }
        
        return view('accountant.expense-categories', compact('categories', 'campuses', 'defaultCampus'));
    }
}
