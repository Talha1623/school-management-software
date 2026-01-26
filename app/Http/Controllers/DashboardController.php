<?php

namespace App\Http\Controllers;

use App\Models\StudentPayment;
use App\Models\Student;
use App\Models\ManagementExpense;
use App\Models\ParentAccount;
use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\StudentAttendance;
use App\Models\Campus;
use App\Models\Task;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        
        // Calculate Unpaid Invoices - Count and Total Amount
        $students = Student::whereNotNull('student_code')
            ->whereNotNull('monthly_fee')
            ->where('monthly_fee', '>', 0)
            ->get();
        
        $unpaidInvoicesCount = 0;
        $unpaidInvoicesAmount = 0;
        
        foreach ($students as $student) {
            $totalPaid = StudentPayment::where('student_code', $student->student_code)
                ->where('method', '!=', 'Generated') // Only actual payments
                ->sum('payment_amount');
            
            $monthlyFee = $student->monthly_fee ?? 0;
            if ($monthlyFee > $totalPaid) {
                $unpaidInvoicesCount++;
                $unpaidInvoicesAmount += ($monthlyFee - $totalPaid);
            }
        }
        
        // Calculate Income Today - Actual payments only (excluding generated fees)
        $incomeToday = StudentPayment::whereDate('payment_date', $today)
            ->where('method', '!=', 'Generated')
            ->sum('payment_amount');
        
        // Calculate Income This Month
        $incomeThisMonth = StudentPayment::whereBetween('payment_date', [$startOfMonth, $endOfMonth])
            ->where('method', '!=', 'Generated')
            ->sum('payment_amount');
        
        // Calculate Expense Today
        $expenseToday = ManagementExpense::whereDate('date', $today)
            ->sum('amount');
        
        // Calculate Expense This Month
        $expenseThisMonth = ManagementExpense::whereBetween('date', [$startOfMonth, $endOfMonth])
            ->sum('amount');
        
        // Calculate Profit Today
        $profitToday = $incomeToday - $expenseToday;
        
        // Calculate Profit This Month
        $profitThisMonth = $incomeThisMonth - $expenseThisMonth;
        
        // Calculate Active Students - Students with student_code (active students)
        $activeStudents = Student::whereNotNull('student_code')->get();
        $activeStudentsCount = $activeStudents->count();
        $boysCount = $activeStudents->where('gender', 'male')->count();
        $girlsCount = $activeStudents->where('gender', 'female')->count();
        $noGenderSetCount = $activeStudents->whereNotIn('gender', ['male', 'female'])->count();
        
        // Calculate Total Registered Parents
        $totalParents = ParentAccount::count();
        
        // Calculate Staff Statistics
        $allStaff = Staff::all();
        $totalStaff = $allStaff->count();
        $maleStaff = $allStaff->where('gender', 'Male')->count();
        $femaleStaff = $allStaff->where('gender', 'Female')->count();

        // Staff Attendance Chart (Last 7 Days)
        $staffAttendanceLabels = [];
        $staffPresentData = [];
        $staffAbsentData = [];
        $staffLeaveData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $staffAttendanceLabels[] = $date->format('D');
            $staffPresentData[] = StaffAttendance::whereDate('attendance_date', $date)
                ->where('status', 'Present')
                ->count();
            $staffAbsentData[] = StaffAttendance::whereDate('attendance_date', $date)
                ->where('status', 'Absent')
                ->count();
            $staffLeaveData[] = StaffAttendance::whereDate('attendance_date', $date)
                ->where('status', 'Leave')
                ->count();
        }
        
        // Calculate Present Students Today
        $allActiveStudentIds = $activeStudents->pluck('id');
        $presentStudentsToday = StudentAttendance::whereIn('student_id', $allActiveStudentIds)
            ->whereDate('attendance_date', $today)
            ->where('status', 'Present')
            ->count();
        
        // Calculate Attendance Percentage
        $attendancePercentage = 0;
        if ($activeStudentsCount > 0) {
            $totalMarkedAttendance = StudentAttendance::whereIn('student_id', $allActiveStudentIds)
                ->whereDate('attendance_date', $today)
                ->whereIn('status', ['Present', 'Absent'])
                ->count();
            
            if ($totalMarkedAttendance > 0) {
                $attendancePercentage = round(($presentStudentsToday / $totalMarkedAttendance) * 100, 1);
            }
        }
        
        // Calculate Month Wise Paid/Unpaid Fee Report (Last 12 months)
        $monthWisePaidFee = [];
        $monthWiseUnpaidFee = [];
        $monthLabels = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $monthStart = Carbon::now()->subMonths($i)->startOfMonth();
            $monthEnd = Carbon::now()->subMonths($i)->endOfMonth();
            $monthLabel = $monthStart->format('M-y');
            $monthLabels[] = $monthLabel;
            
            // Get all students with monthly fee
            $studentsWithFee = Student::whereNotNull('student_code')
                ->whereNotNull('monthly_fee')
                ->where('monthly_fee', '>', 0)
                ->get();
            
            $paidCount = 0;
            $unpaidCount = 0;
            
            foreach ($studentsWithFee as $student) {
                // Calculate total paid up to the end of this month
                $totalPaid = StudentPayment::where('student_code', $student->student_code)
                    ->where('method', '!=', 'Generated') // Only actual payments
                    ->whereDate('payment_date', '<=', $monthEnd)
                    ->sum('payment_amount');
                
                $monthlyFee = $student->monthly_fee ?? 0;
                
                if ($monthlyFee > 0) {
                    if ($totalPaid >= $monthlyFee) {
                        $paidCount++;
                    } else {
                        $unpaidCount++;
                    }
                }
            }
            
            $monthWisePaidFee[] = $paidCount;
            $monthWiseUnpaidFee[] = $unpaidCount;
        }
        
        // Calculate Class-Section Wise Attendance & Financial Data
        $classSectionData = [];
        
        // Get all active students with class and section
        $allStudents = Student::whereNotNull('student_code')
            ->whereNotNull('class')
            ->get();
        
        // Group students by class and section (case-insensitive)
        $groupedStudents = $allStudents->groupBy(function($student) {
            $class = trim($student->class ?? '');
            $section = trim($student->section ?? 'N/A');
            return strtolower($class) . '|' . strtolower($section);
        });
        
        foreach ($groupedStudents as $key => $students) {
            list($classNameLower, $sectionNameLower) = explode('|', $key);
            
            // Get the original class and section names from the first student
            $firstStudent = $students->first();
            $className = trim($firstStudent->class ?? '');
            $sectionName = trim($firstStudent->section ?? 'N/A');
            
            $studentIds = $students->pluck('id');
            $studentCodes = $students->pluck('student_code')->filter();
            
            // Section Strength (total students in this class-section)
            $sectionStrength = $students->count();
            
            // Present Today
            $presentToday = StudentAttendance::whereIn('student_id', $studentIds)
                ->whereDate('attendance_date', $today)
                ->where('status', 'Present')
                ->count();
            
            // Absent Today
            $absentToday = StudentAttendance::whereIn('student_id', $studentIds)
                ->whereDate('attendance_date', $today)
                ->where('status', 'Absent')
                ->count();
            
            // On Leave
            $onLeave = StudentAttendance::whereIn('student_id', $studentIds)
                ->whereDate('attendance_date', $today)
                ->where('status', 'Leave')
                ->count();
            
            // Expected (same as section strength)
            $expected = $sectionStrength;
            
            // Generated (sum of all Generated fees for students in this class-section)
            $generated = 0;
            if ($studentCodes->isNotEmpty()) {
                $generated = StudentPayment::whereIn('student_code', $studentCodes)
                    ->where('method', 'Generated')
                    ->sum('payment_amount') ?? 0;
            }
            
            // Paid Amount (sum of all actual payments, excluding Generated)
            $paidAmount = 0;
            if ($studentCodes->isNotEmpty()) {
                $paidAmount = StudentPayment::whereIn('student_code', $studentCodes)
                    ->where('method', '!=', 'Generated')
                    ->sum('payment_amount') ?? 0;
            }
            
            // Balance (Generated - Paid Amount)
            $balance = $generated - $paidAmount;
            
            $classSectionData[] = [
                'class' => $className,
                'section' => $sectionName,
                'section_strength' => $sectionStrength,
                'present_today' => $presentToday,
                'absent_today' => $absentToday,
                'on_leave' => $onLeave,
                'expected' => $expected,
                'generated' => $generated,
                'paid_amount' => $paidAmount,
                'balance' => $balance,
            ];
        }
        
        // Sort by class name (natural sort, case-insensitive)
        usort($classSectionData, function($a, $b) {
            $classCompare = strnatcasecmp($a['class'], $b['class']);
            if ($classCompare !== 0) {
                return $classCompare;
            }
            return strnatcasecmp($a['section'], $b['section']);
        });
        
        // Calculate totals
        $totalSectionStrength = array_sum(array_column($classSectionData, 'section_strength'));
        $totalPresentToday = array_sum(array_column($classSectionData, 'present_today'));
        $totalAbsentToday = array_sum(array_column($classSectionData, 'absent_today'));
        $totalOnLeave = array_sum(array_column($classSectionData, 'on_leave'));
        $totalExpected = array_sum(array_column($classSectionData, 'expected'));
        $totalGenerated = array_sum(array_column($classSectionData, 'generated'));
        $totalPaidAmount = array_sum(array_column($classSectionData, 'paid_amount'));
        $totalBalance = array_sum(array_column($classSectionData, 'balance'));
        
        // Get Latest Admissions (latest 6 students)
        $latestAdmissions = Student::whereNotNull('student_code')
            ->where(function($query) {
                $query->whereNotNull('admission_date')
                      ->orWhereNotNull('created_at');
            })
            ->orderByRaw('COALESCE(admission_date, created_at) DESC')
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get(['id', 'student_name', 'student_code', 'admission_date', 'created_at']);
        
        // Calculate Total Admissions This Month
        $totalAdmissionsThisMonth = Student::whereNotNull('student_code')
            ->where(function($query) use ($startOfMonth, $endOfMonth) {
                $query->whereBetween('admission_date', [$startOfMonth, $endOfMonth])
                      ->orWhereBetween('created_at', [$startOfMonth, $endOfMonth]);
            })
            ->count();
        
        // Calculate Weekly Income & Expense (Last 7 Days)
        $weeklyIncome = [];
        $weeklyExpense = [];
        $weeklyLabels = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dateStart = $date->copy()->startOfDay();
            $dateEnd = $date->copy()->endOfDay();
            
            // Income for this day
            $dayIncome = StudentPayment::whereBetween('payment_date', [$dateStart, $dateEnd])
                ->where('method', '!=', 'Generated')
                ->sum('payment_amount') ?? 0;
            
            // Expense for this day
            $dayExpense = ManagementExpense::whereBetween('date', [$dateStart, $dateEnd])
                ->sum('amount') ?? 0;
            
            $weeklyIncome[] = $dayIncome;
            $weeklyExpense[] = $dayExpense;
            $weeklyLabels[] = $date->format('D d'); // e.g., "Mon 24"
        }
        
        // Calculate Monthly Income & Expense (Last 12 Months)
        $monthlyIncome = [];
        $monthlyExpense = [];
        $monthlyLabels = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $monthStart = Carbon::now()->subMonths($i)->startOfMonth();
            $monthEnd = Carbon::now()->subMonths($i)->endOfMonth();
            $monthLabel = $monthStart->format('M Y'); // e.g., "Nov 2025"
            
            // Income for this month
            $monthIncome = StudentPayment::whereBetween('payment_date', [$monthStart, $monthEnd])
                ->where('method', '!=', 'Generated')
                ->sum('payment_amount') ?? 0;
            
            // Expense for this month
            $monthExpense = ManagementExpense::whereBetween('date', [$monthStart, $monthEnd])
                ->sum('amount') ?? 0;
            
            $monthlyIncome[] = $monthIncome;
            $monthlyExpense[] = $monthExpense;
            $monthlyLabels[] = $monthLabel;
        }
        
        // Calculate Student Limit (Current Active Students / Max Limit)
        $currentActiveStudents = $activeStudentsCount; // Already calculated above
        $maxStudentLimit = 300; // Default limit, can be made configurable
        $studentLimitDisplay = $currentActiveStudents . ' / ' . $maxStudentLimit;
        
        // Calculate Max Campuses (Count unique campuses)
        $maxCampuses = Campus::count();
        // If no campuses in Campus model, count from students
        if ($maxCampuses == 0) {
            $maxCampuses = Student::whereNotNull('campus')
                ->distinct()
                ->count('campus');
        }
        // If still 0, default to 1
        if ($maxCampuses == 0) {
            $maxCampuses = 1;
        }
        
        // Get Latest Tasks (Latest 6 tasks)
        $latestTasks = Task::orderBy('created_at', 'desc')
            ->limit(6)
            ->get(['id', 'task_title', 'status', 'created_at']);
        
        return view('dashboards.index', compact(
            'unpaidInvoicesCount',
            'unpaidInvoicesAmount',
            'incomeToday',
            'incomeThisMonth',
            'expenseToday',
            'expenseThisMonth',
            'profitToday',
            'profitThisMonth',
            'activeStudentsCount',
            'boysCount',
            'girlsCount',
            'noGenderSetCount',
            'totalParents',
            'totalStaff',
            'maleStaff',
            'femaleStaff',
            'staffAttendanceLabels',
            'staffPresentData',
            'staffAbsentData',
            'staffLeaveData',
            'presentStudentsToday',
            'attendancePercentage',
            'monthWisePaidFee',
            'monthWiseUnpaidFee',
            'monthLabels',
            'classSectionData',
            'totalSectionStrength',
            'totalPresentToday',
            'totalAbsentToday',
            'totalOnLeave',
            'totalExpected',
            'totalGenerated',
            'totalPaidAmount',
            'totalBalance',
            'latestAdmissions',
            'totalAdmissionsThisMonth',
            'weeklyIncome',
            'weeklyExpense',
            'weeklyLabels',
            'monthlyIncome',
            'monthlyExpense',
            'monthlyLabels',
            'studentLimitDisplay',
            'maxCampuses',
            'latestTasks'
        ));
    }

    public function crm()
    {
        return view('dashboards.crm');
    }

    public function projectManagement()
    {
        return view('dashboards.project-management');
    }

    public function lms()
    {
        return view('dashboards.lms');
    }

    public function helpDesk()
    {
        return view('dashboards.help-desk');
    }

    public function hrManagement()
    {
        return view('dashboards.hr-management');
    }

    public function school()
    {
        return view('dashboards.school');
    }

    public function marketing()
    {
        return view('dashboards.marketing');
    }

    public function analytics()
    {
        return view('dashboards.analytics');
    }

    public function hospital()
    {
        return view('dashboards.hospital');
    }

    public function finance()
    {
        return view('dashboards.finance');
    }
}

