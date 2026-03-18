<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Models\Staff;
use App\Models\Student;
use App\Models\ParentAccount;
use App\Models\StudentAttendance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Carbon\Carbon;

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

        $oldStatus = $leave->status;
        $leave->update($validated);

        // If leave is approved and it's a student leave, update attendance to 'Leave' status
        // This will automatically reflect in teacher's class attendance
        // Updates attendance for ALL dates from from_date to to_date (inclusive)
        if ($validated['status'] === 'Approved' && !empty($validated['student_id'])) {
            $student = Student::findOrFail($validated['student_id']);
            
            // Parse dates and ensure proper format
            $fromDate = Carbon::parse($validated['from_date'])->startOfDay();
            $toDate = Carbon::parse($validated['to_date'])->endOfDay();

            // Update attendance for all dates in the leave period (from_date to to_date inclusive)
            $currentDate = $fromDate->copy();
            $updatedCount = 0;
            
            while ($currentDate->lte($toDate)) {
                StudentAttendance::updateOrCreate(
                    [
                        'student_id' => $validated['student_id'],
                        'attendance_date' => $currentDate->format('Y-m-d'),
                    ],
                    [
                        'status' => 'Leave',
                        'campus' => $student->campus,
                        'class' => $student->class,
                        'section' => $student->section,
                        'remarks' => 'Leave approved: ' . ($validated['leave_reason'] ?? ''),
                    ]
                );
                $currentDate->addDay();
                $updatedCount++;
            }
        }
        // If leave status changed from Approved to Rejected/Pending, remove Leave status from attendance
        elseif ($oldStatus === 'Approved' && $validated['status'] !== 'Approved' && !empty($validated['student_id'])) {
            $fromDate = Carbon::parse($validated['from_date']);
            $toDate = Carbon::parse($validated['to_date']);

            // Remove Leave status from attendance (set to N/A if it was only marked for leave)
            $currentDate = $fromDate->copy();
            while ($currentDate->lte($toDate)) {
                $attendance = StudentAttendance::where('student_id', $validated['student_id'])
                    ->whereDate('attendance_date', $currentDate->format('Y-m-d'))
                    ->where('status', 'Leave')
                    ->first();
                
                if ($attendance) {
                    // If attendance was only marked as Leave (no other reason), set to N/A
                    // Otherwise, keep the existing status
                    if (empty($attendance->remarks) || strpos($attendance->remarks, 'Leave approved') === 0) {
                        $attendance->update(['status' => 'N/A', 'remarks' => null]);
                    }
                }
                $currentDate->addDay();
            }
        }

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

