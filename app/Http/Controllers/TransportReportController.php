<?php

namespace App\Http\Controllers;

use App\Models\Transport;
use App\Models\Student;
use App\Models\StudentPayment;
use App\Models\Campus;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Carbon\Carbon;

class TransportReportController extends Controller
{
    /**
     * Display transport reports page.
     */
    public function index(): View
    {
        return view('transport.reports');
    }

    /**
     * Print All Transport Report - Shows all transport routes with details
     */
    public function printAllTransportReport(Request $request): View
    {
        $transports = Transport::query()
            ->orderBy('campus', 'asc')
            ->orderBy('route_name', 'asc')
            ->get();
        
        // Get statistics
        $totalRoutes = $transports->count();
        $totalVehicles = $transports->sum('number_of_vehicle');
        $totalStudents = Student::whereNotNull('transport_route')
            ->where('transport_route', '!=', '')
            ->count();
        
        return view('transport.print.all-transport-report', compact(
            'transports',
            'totalRoutes',
            'totalVehicles',
            'totalStudents'
        ));
    }

    /**
     * Print Transport Income Report - Shows monthly transport income
     */
    public function printTransportIncomeReport(Request $request): View
    {
        $payments = StudentPayment::where('payment_title', 'like', 'Transport Fee%')
            ->orderBy('payment_date', 'desc')
            ->get();
        
        // Group by month
        $monthlyIncome = $payments->groupBy(function($payment) {
            return Carbon::parse($payment->payment_date)->format('Y-m');
        })->map(function($monthPayments) {
            return [
                'total' => $monthPayments->sum(function($p) {
                    $amount = (float) ($p->payment_amount ?? 0);
                    $discount = (float) ($p->discount ?? 0);
                    $lateFee = (float) ($p->late_fee ?? 0);
                    return max(0, $amount - $discount) + max(0, $lateFee);
                }),
                'count' => $monthPayments->count(),
                'payments' => $monthPayments
            ];
        })->sortKeysDesc();
        
        $totalIncome = $payments->sum(function($p) {
            $amount = (float) ($p->payment_amount ?? 0);
            $discount = (float) ($p->discount ?? 0);
            $lateFee = (float) ($p->late_fee ?? 0);
            return max(0, $amount - $discount) + max(0, $lateFee);
        });
        
        return view('transport.print.transport-income-report', compact(
            'monthlyIncome',
            'totalIncome',
            'payments'
        ));
    }

    /**
     * Print Connected Students Report - Shows all students using transport
     */
    public function printConnectedStudentsReport(Request $request): View
    {
        $students = Student::whereNotNull('transport_route')
            ->where('transport_route', '!=', '')
            ->orderBy('campus', 'asc')
            ->orderBy('transport_route', 'asc')
            ->orderBy('class', 'asc')
            ->orderBy('student_name', 'asc')
            ->get();
        
        // Group by route
        $studentsByRoute = $students->groupBy('transport_route');
        
        // Get statistics
        $totalStudents = $students->count();
        $totalRoutes = $studentsByRoute->count();
        
        return view('transport.print.connected-students-report', compact(
            'students',
            'studentsByRoute',
            'totalStudents',
            'totalRoutes'
        ));
    }

    /**
     * Print Transport Passes - Individual passes for students
     */
    public function printTransportPasses(Request $request): View
    {
        $students = Student::whereNotNull('transport_route')
            ->where('transport_route', '!=', '')
            ->orderBy('campus', 'asc')
            ->orderBy('transport_route', 'asc')
            ->orderBy('class', 'asc')
            ->orderBy('student_name', 'asc')
            ->get();
        
        return view('transport.print.transport-passes', compact('students'));
    }
}
