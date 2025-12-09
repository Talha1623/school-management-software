<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Models\Staff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveManagementController extends Controller
{
    /**
     * Display a listing of leaves.
     */
    public function index(Request $request): View
    {
        $query = Leave::with('staff');
        
        // Search functionality
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereHas('staff', function($staffQuery) use ($searchLower) {
                        $staffQuery->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"]);
                    })
                    ->orWhereRaw('LOWER(leave_reason) LIKE ?', ["%{$searchLower}%"])
                    ->orWhere('status', 'like', "%{$search}%");
                });
            }
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->whereDate('from_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('to_date', '<=', $request->to_date);
        }
        
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $leaves = $query->orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();
        
        return view('leave-management', compact('leaves'));
    }

    /**
     * Store a newly created leave.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'staff_id' => ['required', 'exists:staff,id'],
            'leave_reason' => ['required', 'string', 'max:255'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'remarks' => ['nullable', 'string'],
        ]);

        $validated['status'] = 'Pending';

        Leave::create($validated);

        return redirect()
            ->route('leave-management')
            ->with('success', 'Leave application created successfully!');
    }

    /**
     * Update the specified leave.
     */
    public function update(Request $request, Leave $leave): RedirectResponse
    {
        $validated = $request->validate([
            'staff_id' => ['required', 'exists:staff,id'],
            'leave_reason' => ['required', 'string', 'max:255'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'status' => ['required', 'in:Pending,Approved,Rejected'],
            'remarks' => ['nullable', 'string'],
        ]);

        $leave->update($validated);

        return redirect()
            ->route('leave-management')
            ->with('success', 'Leave application updated successfully!');
    }

    /**
     * Remove the specified leave.
     */
    public function destroy(Leave $leave): RedirectResponse
    {
        $leave->delete();

        return redirect()
            ->route('leave-management')
            ->with('success', 'Leave application deleted successfully!');
    }
}

