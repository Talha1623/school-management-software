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
            // Handle both string and integer year formats
            $salaries = Salary::where('staff_id', $targetStaffId)
                ->where(function($query) use ($year) {
                    $query->where('year', $year)
                          ->orWhere('year', (string) $year);
                })
                ->get()
                ->keyBy('salary_month'); // Key by month name (e.g., "March", "April")

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
                // Database stores salary_month as month name (e.g., "March"), not month number
                // So we need to lookup by month name, not month number
                $salary = $salaries->get($monthName);
                
                // Also try with month number formats for backward compatibility
                // (in case some records use month numbers)
                if (!$salary) {
                    $monthNumStr = (string) $monthNum;
                    $salary = $salaries->get($monthNumStr);
                    
                    // Try without leading zero (e.g., "1" instead of "01")
                    if (!$salary && strlen($monthNumStr) == 2 && substr($monthNumStr, 0, 1) == '0') {
                        $salary = $salaries->get((string)(int)$monthNumStr);
                    }
                    
                    // Try with leading zero (e.g., "01" instead of "1")
                    if (!$salary && strlen($monthNumStr) == 1) {
                        $salary = $salaries->get(str_pad($monthNumStr, 2, '0', STR_PAD_LEFT));
                    }
                }
                
                // Always fetch fresh from database for each month to ensure latest data
                // This is important because salary payments can be updated at any time
                $salary = Salary::where('staff_id', $targetStaffId)
                    ->where(function($query) use ($year) {
                        $query->where('year', $year)
                              ->orWhere('year', (string) $year);
                    })
                    ->where(function($query) use ($monthName, $monthNum) {
                        $query->where('salary_month', $monthName)
                              ->orWhere('salary_month', $monthNum)
                              ->orWhere('salary_month', str_pad($monthNum, 2, '0', STR_PAD_LEFT))
                              ->orWhere('salary_month', (string)(int)$monthNum);
                    })
                    ->first();
                
                // Calculate status based on amount_paid and salary_generated
                // If salary is generated but not paid, status should be "Pending"
                // If salary is paid (amount_paid >= salary_generated), status should be "Paid"
                $status = null;
                if ($salary) {
                    $salaryGenerated = (float) $salary->salary_generated;
                    $amountPaid = (float) $salary->amount_paid;
                    
                    // Always calculate status based on payment, even if status field exists
                    // This ensures status is always accurate based on current payment state
                    if ($salaryGenerated > 0) {
                        // Round to 2 decimals for comparison to avoid floating point issues
                        $salaryGeneratedRounded = round($salaryGenerated, 2);
                        $amountPaidRounded = round($amountPaid, 2);
                        
                        if ($amountPaidRounded >= $salaryGeneratedRounded) {
                            $status = 'Paid';
                        } elseif ($amountPaidRounded > 0) {
                            $status = 'Issued'; // Partial payment
                        } else {
                            $status = 'Pending'; // Generated but not paid
                        }
                    } elseif ($amountPaid > 0) {
                        // If amount_paid > 0 but salary_generated = 0, it's likely a payment without generation
                        $status = 'Issued';
                    } else {
                        $status = null; // Not generated yet
                    }
                }
                
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
                    'status' => $status,
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
