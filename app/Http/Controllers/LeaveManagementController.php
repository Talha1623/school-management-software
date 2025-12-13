<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Models\Staff;
use App\Models\Student;
use App\Models\ParentAccount;
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
        $query = Leave::with(['staff', 'student']);
        
        // Search functionality
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereHas('staff', function($staffQuery) use ($searchLower) {
                        $staffQuery->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"]);
                    })
                    ->orWhereHas('student', function($studentQuery) use ($searchLower) {
                        $studentQuery->whereRaw('LOWER(student_name) LIKE ?', ["%{$searchLower}%"]);
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
        // Validate either staff_id or student_id is required
        $validated = $request->validate([
            'staff_id' => ['nullable', 'required_without:student_id', 'exists:staff,id'],
            'student_id' => ['nullable', 'required_without:staff_id', 'exists:students,id'],
            'leave_reason' => ['required', 'string', 'max:255'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'remarks' => ['nullable', 'string'],
        ], [
            'staff_id.required_without' => 'Either staff or student must be selected.',
            'student_id.required_without' => 'Either staff or student must be selected.',
        ]);

        // Ensure at least one of staff_id or student_id is provided
        if (empty($validated['staff_id']) && empty($validated['student_id'])) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Please select either a staff member or a student.');
        }

        $validated['status'] = 'Pending';

        Leave::create($validated);

        return redirect()
            ->route('leave-management')
            ->with('success', 'Leave application created successfully!');
    }

    /**
     * Store a student leave request from parent.
     */
    public function storeStudentLeave(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'leave_reason' => ['required', 'string', 'max:255'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
        ]);

        // Verify student belongs to the authenticated parent (if parent is logged in)
        if (auth()->check() && auth()->user() instanceof ParentAccount) {
            $parent = auth()->user();
            $student = Student::findOrFail($validated['student_id']);
            
            // Check if student belongs to this parent
            if ($student->parent_account_id != $parent->id) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', 'You are not authorized to request leave for this student.');
            }
        }

        $validated['status'] = 'Pending';

        Leave::create($validated);

        return redirect()
            ->back()
            ->with('success', 'Leave request submitted successfully! It will be reviewed by the admin.');
    }

    /**
     * Get students by parent phone number (AJAX).
     */
    public function getStudentsByParentPhone(Request $request)
    {
        $phone = $request->get('phone');
        
        if (empty($phone)) {
            return response()->json(['students' => []]);
        }

        // Find parent by phone
        $parent = ParentAccount::where('phone', $phone)->first();
        
        if (!$parent) {
            return response()->json(['students' => []]);
        }

        // Get all students for this parent
        $students = Student::where('parent_account_id', $parent->id)
            ->select('id', 'student_code', 'student_name', 'class', 'section', 'campus')
            ->orderBy('student_name')
            ->get()
            ->map(function($student) {
                return [
                    'id' => $student->id,
                    'code' => $student->student_code ?? 'N/A',
                    'name' => $student->student_name,
                    'class' => $student->class ?? 'N/A',
                    'section' => $student->section ?? 'N/A',
                    'campus' => $student->campus ?? 'N/A',
                ];
            });

        return response()->json(['students' => $students]);
    }

    /**
     * Update the specified leave.
     */
    public function update(Request $request, Leave $leave): RedirectResponse
    {
        $validated = $request->validate([
            'staff_id' => ['nullable', 'required_without:student_id', 'exists:staff,id'],
            'student_id' => ['nullable', 'required_without:staff_id', 'exists:students,id'],
            'leave_reason' => ['required', 'string', 'max:255'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'status' => ['required', 'in:Pending,Approved,Rejected'],
            'remarks' => ['nullable', 'string'],
        ]);

        // Ensure at least one of staff_id or student_id is provided
        if (empty($validated['staff_id']) && empty($validated['student_id'])) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Please select either a staff member or a student.');
        }

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

