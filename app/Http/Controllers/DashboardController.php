<?php

namespace App\Http\Controllers;

use App\Models\StudentPayment;
use App\Models\Student;
use App\Models\ManagementExpense;
use App\Models\ParentAccount;
use App\Models\Staff;
use App\Models\StudentAttendance;
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
            'presentStudentsToday',
            'attendancePercentage'
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

