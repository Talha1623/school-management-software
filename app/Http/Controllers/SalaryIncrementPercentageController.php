<?php

namespace App\Http\Controllers;

use App\Models\SalaryIncrementPercentage;
use App\Models\Staff;
use App\Models\Campus;
use App\Models\Accountant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class SalaryIncrementPercentageController extends Controller
{
    /**
     * Display the increment by percentage form.
     */
    public function index(): View
    {
        // Get campuses
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        
        // If no campuses found, get from staff
        if ($campuses->isEmpty()) {
            $campusesFromStaff = Staff::whereNotNull('campus')->distinct()->pluck('campus');
            $campuses = $campusesFromStaff->map(function($campus) {
                return (object)['campus_name' => $campus];
            });
        } else {
            // Convert to array format for view compatibility
            $campuses = $campuses->pluck('campus_name');
        }

        // Get logged in user name
        $loggedInUser = Auth::guard('admin')->user() ?? Auth::user();
        $loggedInUserName = $loggedInUser ? ($loggedInUser->name ?? 'Admin') : 'Admin';

        // Get current date as default
        $currentDate = date('Y-m-d');

        return view('salary-loan.increment.percentage', compact('campuses', 'loggedInUserName', 'currentDate'));
    }

    /**
     * Get staff by campus and salary type.
     */
    public function getStaffByCampus(Request $request): JsonResponse
    {
        $campus = $request->get('campus');
        $salaryType = $request->get('salary_type');
        
        if (!$campus || !$salaryType) {
            return response()->json(['staff' => []]);
        }

        // Get staff by campus and salary type
        $staff = Staff::where('campus', $campus)
            ->whereRaw('LOWER(TRIM(salary_type)) = ?', [strtolower(trim($salaryType))])
            ->orderBy('name')
            ->get(['id', 'name', 'emp_id', 'designation', 'salary', 'absent_fees', 'late_fees', 'early_exit_fees', 'salary_type']);

        return response()->json(['staff' => $staff]);
    }

    /**
     * Store the salary increment percentage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string', 'max:255'],
            'salary_type' => ['required', 'string', 'in:full time,per hour,lecture'],
            'increase' => ['required', 'numeric', 'min:0', 'max:100'],
            'absent_fees_increment' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'late_fees_increment' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'early_exit_fees_increment' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'accountant' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'selected_staff' => ['required', 'array', 'min:1'],
            'selected_staff.*' => ['exists:staff,id'],
        ]);

        $increase = (float) $validated['increase'];
        $absentFeesIncrement = isset($validated['absent_fees_increment']) && $validated['absent_fees_increment'] !== '' ? (float) $validated['absent_fees_increment'] : null;
        $lateFeesIncrement = isset($validated['late_fees_increment']) && $validated['late_fees_increment'] !== '' ? (float) $validated['late_fees_increment'] : null;
        $earlyExitFeesIncrement = isset($validated['early_exit_fees_increment']) && $validated['early_exit_fees_increment'] !== '' ? (float) $validated['early_exit_fees_increment'] : null;
        
        $selectedStaffIds = $validated['selected_staff'];
        $staff = Staff::whereIn('id', $selectedStaffIds)->get();
        $updatedCount = 0;

        foreach ($staff as $member) {
            $currentSalary = (float) ($member->salary ?? 0);
            if ($currentSalary > 0) {
                $newSalary = $currentSalary + ($currentSalary * ($increase / 100));
                
                // Only update salary - fees will only be updated if manual increment percentages are provided
                $updateData = [
                    'salary' => round($newSalary, 2),
                ];
                
                // Increment Absent Fees only if manual increment percentage is provided
                if ($absentFeesIncrement !== null && $absentFeesIncrement > 0) {
                    $currentAbsentFees = (float) ($member->absent_fees ?? null);
                    if ($currentAbsentFees !== null && $currentAbsentFees > 0) {
                        $newAbsentFees = $currentAbsentFees + ($currentAbsentFees * ($absentFeesIncrement / 100));
                        $updateData['absent_fees'] = round($newAbsentFees, 2);
                    }
                }
                
                // Increment Late Fees only if manual increment percentage is provided
                if ($lateFeesIncrement !== null && $lateFeesIncrement > 0) {
                    $currentLateFees = (float) ($member->late_fees ?? null);
                    if ($currentLateFees !== null && $currentLateFees > 0) {
                        $newLateFees = $currentLateFees + ($currentLateFees * ($lateFeesIncrement / 100));
                        $updateData['late_fees'] = round($newLateFees, 2);
                    }
                }
                
                // Increment Early Exit Fees only if manual increment percentage is provided
                if ($earlyExitFeesIncrement !== null && $earlyExitFeesIncrement > 0) {
                    $currentEarlyExitFees = (float) ($member->early_exit_fees ?? null);
                    if ($currentEarlyExitFees !== null && $currentEarlyExitFees > 0) {
                        $newEarlyExitFees = $currentEarlyExitFees + ($currentEarlyExitFees * ($earlyExitFeesIncrement / 100));
                        $updateData['early_exit_fees'] = round($newEarlyExitFees, 2);
                    }
                }
                
                $member->update($updateData);
                $updatedCount++;
            }
        }

        // Save increment record
        SalaryIncrementPercentage::create([
            'campus' => $validated['campus'],
            'increase' => $validated['increase'],
            'accountant' => $validated['accountant'],
            'date' => $validated['date'],
            'salary_type' => $validated['salary_type'],
        ]);

        return redirect()
            ->route('salary-loan.increment.percentage')
            ->with('success', "Salary increment applied to {$updatedCount} staff member(s) successfully!");
    }
}

