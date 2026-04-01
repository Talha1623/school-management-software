<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\Salary;
use App\Models\StaffAttendance;
use App\Models\SalarySetting;
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

            // Build yearly flow summary by salary type using attendance records (web-like flow detail)
            $salaryTypeRaw = strtolower(trim((string) ($targetStaff->salary_type ?? '')));
            $isPerLecture = $salaryTypeRaw === 'lecture';
            $isPerHour = $salaryTypeRaw === 'per hour';
            $normalizedSalaryType = $isPerLecture ? 'lecture' : ($isPerHour ? 'per hour' : 'full time');
            $rate = (float) ($targetStaff->salary ?? 0);

            $yearAttendance = StaffAttendance::where('staff_id', $targetStaffId)
                ->whereYear('attendance_date', $year)
                ->get();

            $attendancePresent = 0;
            $attendanceAbsent = 0;
            $attendanceLeave = 0;
            $totalLectures = 0;
            $totalMinutes = 0;

            foreach ($yearAttendance as $record) {
                $statusNormalized = strtolower(trim((string) ($record->status ?? '')));
                $isPresentLike = in_array($statusNormalized, ['present', 'half day'], true);

                if ($isPresentLike) {
                    $attendancePresent++;

                    if ($isPerLecture) {
                        $conducted = (int) ($record->conducted_lectures ?? 0);
                        $totalLectures += $conducted > 0 ? $conducted : 1;
                    }

                    if ($isPerHour && !empty($record->start_time) && !empty($record->end_time)) {
                        try {
                            $dateStr = $record->attendance_date ? $record->attendance_date->format('Y-m-d') : Carbon::now()->format('Y-m-d');
                            $startTime = Carbon::parse($dateStr . ' ' . $record->start_time);
                            $endTime = Carbon::parse($dateStr . ' ' . $record->end_time);
                            if ($endTime->greaterThan($startTime)) {
                                $totalMinutes += $startTime->diffInMinutes($endTime);
                            }
                        } catch (\Exception $e) {
                            // Ignore invalid times
                        }
                    }
                } elseif ($statusNormalized === 'absent') {
                    $attendanceAbsent++;
                } elseif ($statusNormalized === 'leave') {
                    $attendanceLeave++;
                }
            }

            $typeSummary = [
                'salary_type' => $normalizedSalaryType,
                'rate' => $rate,
                'full_time' => [
                    'active' => $normalizedSalaryType === 'full time',
                    'present_count' => $attendancePresent,
                    'absent_count' => $attendanceAbsent,
                    'leave_count' => $attendanceLeave,
                    'salary_generated' => round((float) $yearlyTotals['salary_generated'], 2),
                ],
                'per_hour' => [
                    'active' => $normalizedSalaryType === 'per hour',
                    'total_minutes' => $totalMinutes,
                    'total_hours' => round($totalMinutes / 60, 2),
                    'salary_generated' => $normalizedSalaryType === 'per hour'
                        ? round($rate * ($totalMinutes / 60), 2)
                        : 0,
                ],
                'per_lecture' => [
                    'active' => $normalizedSalaryType === 'lecture',
                    'lecture_count' => $totalLectures,
                    'salary_generated' => $normalizedSalaryType === 'lecture'
                        ? round($rate * $totalLectures, 2)
                        : 0,
                ],
            ];

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
                    'salary_type_summary' => $typeSummary,
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

    /**
     * Staff salary calculation flow (per hour / per lecture / full time)
     * Uses StaffAttendance for the given month/year (does not rely on Salary table).
     *
     * Query params:
     * - year (e.g., 2026)
     * - month (1-12)
     */
    public function salaryCalculation(Request $request): JsonResponse
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

            if (!$request->filled('year')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Year is required (e.g., 2026)',
                    'data' => null,
                    'token' => null,
                ], 400);
            }

            if (!$request->filled('month')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Month is required (1-12)',
                    'data' => null,
                    'token' => null,
                ], 400);
            }

            $year = (int) $request->year;
            $month = (int) $request->month;

            if ($year < 2000 || $year > 2100) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid year. Year must be between 2000 and 2100',
                    'data' => null,
                    'token' => null,
                ], 400);
            }

            if ($month < 1 || $month > 12) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid month. Month must be between 1 and 12',
                    'data' => null,
                    'token' => null,
                ], 400);
            }

            $settings = SalarySetting::getSettings();
            $lateArrivalTime = $settings->late_arrival_time ?? '09:00:00';
            $earlyExitTime = $settings->early_exit_time ?? null;
            $freeAbsentsSetting = (int) ($settings->free_absents ?? 0);
            $leaveDeduction = strtolower(trim($settings->leave_deduction ?? 'no')) === 'yes';

            $records = StaffAttendance::where('staff_id', $staff->id)
                ->whereYear('attendance_date', $year)
                ->whereMonth('attendance_date', $month)
                ->get();

            $salaryType = strtolower(trim((string) ($staff->salary_type ?? '')));
            $isPerLecture = $salaryType === 'lecture';
            $isPerHour = $salaryType === 'per hour';
            $isFullTime = empty($salaryType) || $salaryType === 'full time';

            $present = 0;
            $absent = 0;
            $leave = 0;
            $late = 0;
            $earlyExit = 0;
            $holidayCount = 0;
            $sundayCount = 0;
            $totalLectures = 0;
            $totalMinutes = 0;

            $lecturesFromAttendance = 0;
            $presentDaysForLectures = 0;

            // For per-hour staff: leave should be ignored, and late/early rules are skipped
            if ($isPerHour) {
                $leave = 0;
            }

            foreach ($records as $record) {
                $status = (string) ($record->status ?? '');
                $statusNormalized = strtolower(trim($status));

                // Treat Half Day as Present for salary logic
                $isPresentLike = in_array($statusNormalized, ['present', 'half day'], true);

                if ($isPresentLike) {
                    $present++;

                    if ($isPerLecture) {
                        $conducted = (int) ($record->conducted_lectures ?? 0);
                        if ($conducted > 0) {
                            $lecturesFromAttendance += $conducted;
                        }
                        $presentDaysForLectures++;
                    }

                    if ($isPerHour) {
                        if (!empty($record->start_time) && !empty($record->end_time)) {
                            try {
                                $dateStr = $record->attendance_date ? $record->attendance_date->format('Y-m-d') : Carbon::now()->format('Y-m-d');
                                $startTime = Carbon::parse($dateStr . ' ' . $record->start_time);
                                $endTime = Carbon::parse($dateStr . ' ' . $record->end_time);
                                if ($endTime->greaterThan($startTime)) {
                                    $totalMinutes += $startTime->diffInMinutes($endTime);
                                }
                            } catch (\Exception $e) {
                                // ignore invalid times
                            }
                        }
                    }
                } elseif ($statusNormalized === 'absent') {
                    $absent++;
                } elseif ($statusNormalized === 'leave') {
                    if (!$isPerHour) {
                        $leave++;
                    }
                } elseif ($statusNormalized === 'holiday') {
                    $holidayCount++;
                } elseif ($statusNormalized === 'sunday') {
                    $sundayCount++;
                }

                if (!$isPerHour) {
                    // Late arrival
                    $lateFlag = false;
                    if (!empty($record->start_time)) {
                        try {
                            $dateStr = $record->attendance_date ? $record->attendance_date->format('Y-m-d') : Carbon::now()->format('Y-m-d');
                            $startTime = Carbon::parse($dateStr . ' ' . $record->start_time);
                            $standardTime = Carbon::parse($dateStr . ' ' . $lateArrivalTime);
                            if ($startTime->greaterThan($standardTime)) {
                                $lateFlag = true;
                            }
                        } catch (\Exception $e) {
                            // ignore invalid times
                        }
                    }
                    if (!$lateFlag && !empty($record->remarks) && stripos((string) $record->remarks, 'Late Arrival') !== false) {
                        $lateFlag = true;
                    }
                    if ($lateFlag) {
                        $late++;
                    }

                    // Early exit
                    $earlyExitFlag = false;
                    if (!empty($record->end_time) && !empty($earlyExitTime)) {
                        try {
                            $dateStr = $record->attendance_date ? $record->attendance_date->format('Y-m-d') : Carbon::now()->format('Y-m-d');
                            $endTime = Carbon::parse($dateStr . ' ' . $record->end_time);
                            $standardExitTime = Carbon::parse($dateStr . ' ' . $earlyExitTime);
                            if ($endTime->lessThan($standardExitTime)) {
                                $earlyExitFlag = true;
                            }
                        } catch (\Exception $e) {
                            // ignore invalid times
                        }
                    }
                    if (!$earlyExitFlag && !empty($record->remarks) && stripos((string) $record->remarks, 'Early Exit') !== false) {
                        $earlyExitFlag = true;
                    }
                    if ($earlyExitFlag) {
                        $earlyExit++;
                    }
                }
            }

            // Full-time: count Holiday + Sunday as present
            if ($isFullTime) {
                $present += $holidayCount + $sundayCount;
            }

            // Per lecture: if conducted_lectures isn't available, use present days as lectures
            if ($isPerLecture) {
                if ($lecturesFromAttendance > 0) {
                    $totalLectures = $lecturesFromAttendance;
                } else {
                    $totalLectures = $presentDaysForLectures;
                }
            }

            $rate = (float) ($staff->salary ?? 0);
            $salaryGenerated = 0.0;
            $salaryBreakdown = [];

            if ($isPerHour) {
                $totalHours = $totalMinutes / 60;
                $salaryGenerated = $rate * $totalHours;
                $salaryBreakdown = [
                    'salary_type' => 'per hour',
                    'rate' => $rate,
                    'total_minutes' => $totalMinutes,
                    'total_hours' => round($totalHours, 2),
                ];
            } elseif ($isPerLecture) {
                $salaryGenerated = $totalLectures * $rate;
                $salaryBreakdown = [
                    'salary_type' => 'lecture',
                    'rate' => $rate,
                    'total_lectures' => $totalLectures,
                ];
            } else {
                $staffFreeAbsents = $staff->free_absent ?? null;
                $freeAbsents = $staffFreeAbsents !== null && (int) $staffFreeAbsents >= 0 ? (int) $staffFreeAbsents : $freeAbsentsSetting;

                $totalAbsents = $absent + ($leaveDeduction ? $leave : 0);
                $deductibleAbsents = max(0, $totalAbsents - $freeAbsents);
                $freeAbsentsUsed = min($freeAbsents, $absent);

                $daysInMonth = 30;
                try {
                    $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;
                } catch (\Exception $e) {
                    $daysInMonth = 30;
                }

                $dailyRate = $daysInMonth > 0 ? ($rate / $daysInMonth) : 0;
                $baseSalary = $dailyRate * ($present + $freeAbsentsUsed + $leave);

                $staffLateFees = (float) ($staff->late_fees ?? null);
                $lateFeePerLate = $staffLateFees !== null && $staffLateFees >= 0 ? $staffLateFees : 500;
                $lateDeduction = $lateFeePerLate * $late;

                $staffEarlyExitFees = (float) ($staff->early_exit_fees ?? null);
                $earlyExitFeePerExit = $staffEarlyExitFees !== null && $staffEarlyExitFees >= 0 ? $staffEarlyExitFees : 1000;
                $earlyExitDeduction = $earlyExitFeePerExit * $earlyExit;

                $staffAbsentFees = (float) ($staff->absent_fees ?? null);
                if ($staffAbsentFees !== null && $staffAbsentFees >= 0) {
                    $absentDeduction = $staffAbsentFees * $deductibleAbsents;
                } else {
                    $absentDeduction = $dailyRate * $deductibleAbsents;
                }

                $salaryGenerated = round(max(0, $baseSalary - $absentDeduction - $lateDeduction - $earlyExitDeduction), 2);

                $salaryBreakdown = [
                    'salary_type' => 'full time',
                    'rate' => $rate,
                    'days_in_month' => $daysInMonth,
                    'daily_rate' => round($dailyRate, 2),
                    'present' => $present,
                    'absent' => $absent,
                    'leave' => $leave,
                    'free_absents' => $freeAbsents,
                    'free_absents_used' => $freeAbsentsUsed,
                    'leave_deduction_enabled' => $leaveDeduction,
                    'deductible_absents' => $deductibleAbsents,
                    'late_count' => $late,
                    'early_exit_count' => $earlyExit,
                    'late_fee_per_late' => $lateFeePerLate,
                    'early_exit_fee_per_exit' => $earlyExitFeePerExit,
                    'absent_fees' => $staffAbsentFees,
                    'base_salary' => round($baseSalary, 2),
                    'late_deduction' => round($lateDeduction, 2),
                    'early_exit_deduction' => round($earlyExitDeduction, 2),
                    'absent_deduction' => round($absentDeduction, 2),
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Staff salary calculation retrieved successfully',
                'data' => [
                    'year' => $year,
                    'month' => $month,
                    'staff' => [
                        'id' => $staff->id,
                        'name' => $staff->name,
                        'salary_type' => $staff->salary_type,
                        'salary_rate' => $staff->salary,
                        'campus' => $staff->campus,
                        'designation' => $staff->designation,
                    ],
                    'attendance_summary' => [
                        'present' => $present,
                        'absent' => $absent,
                        'leave' => $leave,
                        'late' => $late,
                        'early_exit' => $earlyExit,
                        'total_lectures' => $totalLectures,
                        'total_minutes' => $totalMinutes,
                        'total_hours' => $isPerHour ? round($totalMinutes / 60, 2) : null,
                    ],
                    'salary_calculation' => [
                        'salary_generated' => round((float) $salaryGenerated, 2),
                        'breakdown' => $salaryBreakdown,
                    ],
                    'settings' => [
                        'late_arrival_time' => $lateArrivalTime,
                        'early_exit_time' => $earlyExitTime,
                        'free_absents_default' => $freeAbsentsSetting,
                        'leave_deduction' => $leaveDeduction ? 'yes' : 'no',
                    ],
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while calculating salary: ' . $e->getMessage(),
                'data' => null,
                'token' => null,
            ], 500);
        }
    }
}
