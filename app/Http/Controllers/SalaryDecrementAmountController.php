<?php

namespace App\Http\Controllers;

use App\Models\SalaryDecrementAmount;
use App\Models\Staff;
use App\Models\Campus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class SalaryDecrementAmountController extends Controller
{
    /**
     * Display the decrement by amount form.
     */
    public function index(): View
    {
        // Get campuses
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        
        // If no campuses found, get from staff
        if ($campuses->isEmpty()) {
            $campusesFromStaff = Staff::whereNotNull('campus')->distinct()->pluck('campus');
            $campuses = $campusesFromStaff;
        } else {
            // Convert to array format for view compatibility
            $campuses = $campuses->pluck('campus_name');
        }

        // Get logged in user name
        $loggedInUser = Auth::guard('admin')->user() ?? Auth::user();
        $loggedInUserName = $loggedInUser ? ($loggedInUser->name ?? 'Admin') : 'Admin';

        // Get current date as default
        $currentDate = date('Y-m-d');

        return view('salary-loan.decrement.amount', compact('campuses', 'loggedInUserName', 'currentDate'));
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
     * Store the salary decrement amount.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string', 'max:255'],
            'salary_type' => ['required', 'string', 'in:full time,per hour,lecture'],
            'decrease' => ['required', 'numeric', 'min:0'],
            'absent_fees_decrement' => ['nullable', 'numeric', 'min:0'],
            'late_fees_decrement' => ['nullable', 'numeric', 'min:0'],
            'early_exit_fees_decrement' => ['nullable', 'numeric', 'min:0'],
            'accountant' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'selected_staff' => ['required', 'array', 'min:1'],
            'selected_staff.*' => ['exists:staff,id'],
        ]);

        $decreaseAmount = (float) $validated['decrease'];
        $absentFeesDecrement = isset($validated['absent_fees_decrement']) && $validated['absent_fees_decrement'] !== '' ? (float) $validated['absent_fees_decrement'] : null;
        $lateFeesDecrement = isset($validated['late_fees_decrement']) && $validated['late_fees_decrement'] !== '' ? (float) $validated['late_fees_decrement'] : null;
        $earlyExitFeesDecrement = isset($validated['early_exit_fees_decrement']) && $validated['early_exit_fees_decrement'] !== '' ? (float) $validated['early_exit_fees_decrement'] : null;
        
        $selectedStaffIds = $validated['selected_staff'];
        $staff = Staff::whereIn('id', $selectedStaffIds)->get();
        $updatedCount = 0;

        foreach ($staff as $member) {
            $currentSalary = (float) ($member->salary ?? 0);
            if ($currentSalary > 0) {
                // Decrease salary by amount
                $newSalary = $currentSalary - $decreaseAmount;
                // Ensure salary doesn't go below 0
                $newSalary = max(0, $newSalary);
                
                // Only update salary - fees will only be updated if manual decrement amounts are provided
                $updateData = [
                    'salary' => round($newSalary, 2),
                ];
                
                // Decrement Absent Fees only if manual decrement amount is provided
                if ($absentFeesDecrement !== null && $absentFeesDecrement > 0) {
                    $currentAbsentFees = (float) ($member->absent_fees ?? null);
                    if ($currentAbsentFees !== null && $currentAbsentFees > 0) {
                        $newAbsentFees = $currentAbsentFees - $absentFeesDecrement;
                        $newAbsentFees = max(0, $newAbsentFees);
                        $updateData['absent_fees'] = round($newAbsentFees, 2);
                    }
                }
                
                // Decrement Late Fees only if manual decrement amount is provided
                if ($lateFeesDecrement !== null && $lateFeesDecrement > 0) {
                    $currentLateFees = (float) ($member->late_fees ?? null);
                    if ($currentLateFees !== null && $currentLateFees > 0) {
                        $newLateFees = $currentLateFees - $lateFeesDecrement;
                        $newLateFees = max(0, $newLateFees);
                        $updateData['late_fees'] = round($newLateFees, 2);
                    }
                }
                
                // Decrement Early Exit Fees only if manual decrement amount is provided
                if ($earlyExitFeesDecrement !== null && $earlyExitFeesDecrement > 0) {
                    $currentEarlyExitFees = (float) ($member->early_exit_fees ?? null);
                    if ($currentEarlyExitFees !== null && $currentEarlyExitFees > 0) {
                        $newEarlyExitFees = $currentEarlyExitFees - $earlyExitFeesDecrement;
                        $newEarlyExitFees = max(0, $newEarlyExitFees);
                        $updateData['early_exit_fees'] = round($newEarlyExitFees, 2);
                    }
                }
                
                $member->update($updateData);
                $updatedCount++;
            }
        }

        // Save decrement record
        SalaryDecrementAmount::create([
            'campus' => $validated['campus'],
            'decrease' => $validated['decrease'],
            'accountant' => $validated['accountant'],
            'date' => $validated['date'],
            'salary_type' => $validated['salary_type'],
        ]);

        return redirect()
            ->route('salary-loan.decrement.amount')
            ->with('success', "Salary decrement applied to {$updatedCount} staff member(s) successfully!");
    }
}

