<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\Salary;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class StaffSalaryController extends Controller
{
    /**
     * Get Staff Salary Report
     * Returns complete year's salary details with monthly breakdown
     * Token-based authentication - staff can only view their own salary
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function salaryReport(Request $request): JsonResponse
    {
        try {
            $staff = $request->user();
            
            if (!$staff) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'data' => null,
                    'token' => null,
                ], 404);
            }

            // Validate required parameter - year
            if (!$request->filled('year')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Year is required (e.g., 2026)',
                    'data' => null,
                    'token' => null,
                ], 400);
            }

            // Get year from request
            $year = (int) $request->year;

            // Validate year (reasonable range)
            if ($year < 2000 || $year > 2100) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid year. Year must be between 2000 and 2100',
                    'data' => null,
                    'token' => null,
                ], 400);
            }

            // Use authenticated staff (from token)
            $targetStaff = $staff;
            $targetStaffId = $staff->id;

            // Month names
            $monthNames = [
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
            ];

            // Get all salary records for this staff for the selected year
            $salaries = Salary::where('staff_id', $targetStaffId)
                ->where('year', $year)
                ->get()
                ->keyBy('salary_month');

            // Prepare monthly data for all 12 months
            $monthlyData = [];
            $yearlyTotals = [
                'present' => 0,
                'absent' => 0,
                'late' => 0,
                'leaves' => 0,
                'holidays' => 0,
                'sundays' => 0,
                'basic_salary' => 0,
                'salary_generated' => 0,
                'amount_paid' => 0,
                'loan_repayment' => 0,
            ];

            foreach ($monthNames as $monthNum => $monthName) {
                $salary = $salaries->get($monthNum);
                
                $monthData = [
                    'month' => $monthName,
                    'month_num' => $monthNum,
                    'present' => $salary ? (int) $salary->present : 0,
                    'absent' => $salary ? (int) $salary->absent : 0,
                    'late' => $salary ? (int) $salary->late : 0,
                    'leaves' => $salary ? (int) $salary->leaves : 0,
                    'holidays' => $salary ? (int) $salary->holidays : 0,
                    'sundays' => $salary ? (int) $salary->sundays : 0,
                    'basic_salary' => $salary ? (float) $salary->basic : (float) ($targetStaff->salary ?? 0),
                    'salary_generated' => $salary ? (float) $salary->salary_generated : 0,
                    'amount_paid' => $salary ? (float) $salary->amount_paid : 0,
                    'loan_repayment' => $salary ? (float) $salary->loan_repayment : 0,
                    'status' => $salary ? $salary->status : null,
                ];

                // Add to yearly totals
                $yearlyTotals['present'] += $monthData['present'];
                $yearlyTotals['absent'] += $monthData['absent'];
                $yearlyTotals['late'] += $monthData['late'];
                $yearlyTotals['leaves'] += $monthData['leaves'];
                $yearlyTotals['holidays'] += $monthData['holidays'];
                $yearlyTotals['sundays'] += $monthData['sundays'];
                $yearlyTotals['salary_generated'] += $monthData['salary_generated'];
                $yearlyTotals['amount_paid'] += $monthData['amount_paid'];
                $yearlyTotals['loan_repayment'] += $monthData['loan_repayment'];
                // Basic salary is same for all months, so use the last non-zero value
                if ($monthData['basic_salary'] > 0) {
                    $yearlyTotals['basic_salary'] = $monthData['basic_salary'];
                }

                $monthlyData[] = $monthData;
            }

            // Staff information
            $staffData = [
                'id' => $targetStaff->id,
                'name' => $targetStaff->name,
                'emp_id' => $targetStaff->emp_id ?? null,
                'designation' => $targetStaff->designation,
                'campus' => $targetStaff->campus,
                'photo' => $targetStaff->photo ?? null,
            ];

            // Return response with complete year's salary data
            return response()->json([
                'success' => true,
                'message' => 'Staff salary report retrieved successfully',
                'data' => [
                    'year' => $year,
                    'staff' => $staffData,
                    'monthly_data' => $monthlyData,
                    'yearly_totals' => [
                        'total_present' => $yearlyTotals['present'],
                        'total_absent' => $yearlyTotals['absent'],
                        'total_late' => $yearlyTotals['late'],
                        'total_leaves' => $yearlyTotals['leaves'],
                        'total_holidays' => $yearlyTotals['holidays'],
                        'total_sundays' => $yearlyTotals['sundays'],
                        'basic_salary' => $yearlyTotals['basic_salary'],
                        'total_salary_generated' => $yearlyTotals['salary_generated'],
                        'total_amount_paid' => $yearlyTotals['amount_paid'],
                        'total_loan_repayment' => $yearlyTotals['loan_repayment'],
                    ],
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving salary report: ' . $e->getMessage(),
                'data' => null,
                'token' => null,
            ], 500);
        }
    }
}
