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
     * Get staff by campus.
     */
    public function getStaffByCampus(Request $request): JsonResponse
    {
        $campus = $request->get('campus');
        
        if (!$campus) {
            return response()->json(['staff' => []]);
        }

        $staff = Staff::where('campus', $campus)
            ->orderBy('name')
            ->get(['id', 'name', 'emp_id', 'designation', 'salary']);

        return response()->json(['staff' => $staff]);
    }

    /**
     * Store the salary decrement amount.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string', 'max:255'],
            'decrease' => ['required', 'numeric', 'min:0'],
            'accountant' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'selected_staff' => ['required', 'array', 'min:1'],
            'selected_staff.*' => ['exists:staff,id'],
        ]);

        $decreaseAmount = (float) $validated['decrease'];
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
                $member->update([
                    'salary' => round($newSalary, 2),
                ]);
                $updatedCount++;
            }
        }

        // Save decrement record
        SalaryDecrementAmount::create([
            'campus' => $validated['campus'],
            'decrease' => $validated['decrease'],
            'accountant' => $validated['accountant'],
            'date' => $validated['date'],
        ]);

        return redirect()
            ->route('salary-loan.decrement.amount')
            ->with('success', "Salary decrement applied to {$updatedCount} staff member(s) successfully!");
    }
}

