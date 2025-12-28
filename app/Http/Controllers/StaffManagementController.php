<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\Campus;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class StaffManagementController extends Controller
{
    /**
     * Display a listing of staff members.
     */
    public function index(Request $request): View
    {
        $query = Staff::query();
        
        // Search functionality
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(email) LIKE ?', ["%{$searchLower}%"])
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('whatsapp', 'like', "%{$search}%")
                      ->orWhere('emp_id', 'like', "%{$search}%")
                      ->orWhere('cnic', 'like', "%{$search}%")
                      ->orWhereRaw('LOWER(designation) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(campus) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $staff = $query->latest()->paginate($perPage)->withQueryString();
        
        // Summary statistics
        $totalTeachers = Staff::count();
        $presentToday = 0; // This would come from attendance system
        $absentToday = $totalTeachers - $presentToday; // Placeholder
        
        // Get campuses for dropdown
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        
        return view('staff.management', compact('staff', 'totalTeachers', 'presentToday', 'absentToday', 'campuses'));
    }

    /**
     * Generate unique Employee ID
     */
    private function generateEmployeeId(): string
    {
        // Get the last employee ID
        $lastStaff = Staff::whereNotNull('emp_id')
            ->where('emp_id', 'like', 'EMP%')
            ->orderByRaw('CAST(SUBSTRING(emp_id, 4) AS UNSIGNED) DESC')
            ->first();

        if ($lastStaff && $lastStaff->emp_id) {
            // Extract the number part and increment
            $lastNumber = (int) substr($lastStaff->emp_id, 3);
            $newNumber = $lastNumber + 1;
        } else {
            // Start from 1 if no employee ID exists
            $newNumber = 1;
        }

        // Format as EMP001, EMP002, etc.
        return 'EMP' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Get next Employee ID (for AJAX request)
     */
    public function getNextEmployeeId()
    {
        return response()->json(['emp_id' => $this->generateEmployeeId()]);
    }

    /**
     * Store a newly created staff member.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'father_husband_name' => ['nullable', 'string', 'max:255'],
            'campus' => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'in:Male,Female,Other'],
            'emp_id' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'cnic' => ['nullable', 'string', 'max:255'],
            'qualification' => ['nullable', 'string', 'max:255'],
            'birthday' => ['nullable', 'date'],
            'joining_date' => ['nullable', 'date'],
            'marital_status' => ['nullable', 'string', 'in:Single,Married,Divorced,Widowed'],
            'salary_type' => ['nullable', 'string', 'in:full time,per hour,lecture'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'email' => ['nullable', 'email', 'max:255', 'unique:staff,email'],
            'password' => ['nullable', 'string', 'min:6'],
            'home_address' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:2048'],
            'cv_resume' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ]);

        // Auto-generate Employee ID if not provided
        $empId = $validated['emp_id'] ?? $this->generateEmployeeId();
        $validated['emp_id'] = $empId;

        // Auto-generate email if not provided (for dashboard access)
        if (empty($validated['email'])) {
            $namePart = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $validated['name']));
            $validated['email'] = $namePart . $empId . '@school.local';
        }

        // Auto-generate password if not provided (for dashboard access)
        if (empty($validated['password'])) {
            $validated['password'] = $empId . '@123'; // Default password: EMP001@123
        }
        // Password will be hashed automatically by Staff model's setPasswordAttribute mutator

        // Handle file uploads
        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('staff/photos', 'public');
        }

        if ($request->hasFile('cv_resume')) {
            $validated['cv_resume'] = $request->file('cv_resume')->store('staff/cv', 'public');
        }

        Staff::create($validated);

        return redirect()
            ->route('staff.management')
            ->with('success', 'Staff member created successfully!');
    }

    /**
     * Show the specified staff member details.
     */
    public function show(Request $request, Staff $staff)
    {
        // If request wants JSON (for edit modal), return JSON
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($staff);
        }
        
        // Otherwise return view
        return view('staff.view', compact('staff'));
    }

    /**
     * Update the specified staff member.
     */
    public function update(Request $request, Staff $staff): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'father_husband_name' => ['nullable', 'string', 'max:255'],
            'campus' => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'in:Male,Female,Other'],
            'emp_id' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'cnic' => ['nullable', 'string', 'max:255'],
            'qualification' => ['nullable', 'string', 'max:255'],
            'birthday' => ['nullable', 'date'],
            'joining_date' => ['nullable', 'date'],
            'marital_status' => ['nullable', 'string', 'in:Single,Married,Divorced,Widowed'],
            'salary_type' => ['nullable', 'string', 'in:full time,per hour,lecture'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'email' => ['nullable', 'email', 'max:255', 'unique:staff,email,' . $staff->id],
            'password' => ['nullable', 'string', 'min:6'],
            'home_address' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:2048'],
            'cv_resume' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ]);

        // If password is empty, don't update it
        if (empty($validated['password'])) {
            unset($validated['password']);
        }
        // Password will be hashed automatically by Staff model's setPasswordAttribute mutator if provided

        // Handle file uploads
        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($staff->photo) {
                Storage::disk('public')->delete($staff->photo);
            }
            $validated['photo'] = $request->file('photo')->store('staff/photos', 'public');
        }

        if ($request->hasFile('cv_resume')) {
            // Delete old CV if exists
            if ($staff->cv_resume) {
                Storage::disk('public')->delete($staff->cv_resume);
            }
            $validated['cv_resume'] = $request->file('cv_resume')->store('staff/cv', 'public');
        }

        $staff->update($validated);

        return redirect()
            ->route('staff.management')
            ->with('success', 'Staff member updated successfully!');
    }

    /**
     * Remove the specified staff member.
     */
    public function destroy(Staff $staff): RedirectResponse
    {
        // Get staff name before deletion for removing from sections and subjects
        $staffName = trim($staff->name ?? '');
        $isTeacher = strtolower(trim($staff->designation ?? '')) === 'teacher';

        // Remove staff member from all sections where they are assigned (for ALL staff, not just teachers)
        if (!empty($staffName)) {
            $normalizedStaffName = strtolower(trim($staffName));
            
            // Remove staff from all sections where they are assigned (case-insensitive match)
            // Using DB raw query to handle case-insensitive comparison
            DB::table('sections')
                ->whereRaw('LOWER(TRIM(teacher)) = ?', [$normalizedStaffName])
                ->whereNotNull('teacher')
                ->where('teacher', '!=', '')
                ->update(['teacher' => null]);
            
            // Also try exact match and LIKE match as fallback
            Section::where('teacher', $staffName)
                ->update(['teacher' => null]);
            
            Section::where('teacher', 'like', $staffName)
                ->whereNotNull('teacher')
                ->where('teacher', '!=', '')
                ->update(['teacher' => null]);
        }

        // If this is a teacher, also remove them from all subjects
        if ($isTeacher && !empty($staffName)) {
            $normalizedStaffName = strtolower(trim($staffName));
            
            // Remove teacher from all subjects where they are assigned (case-insensitive match)
            DB::table('subjects')
                ->whereRaw('LOWER(TRIM(teacher)) = ?', [$normalizedStaffName])
                ->whereNotNull('teacher')
                ->where('teacher', '!=', '')
                ->update(['teacher' => null]);
            
            // Also try exact match and LIKE match as fallback
            Subject::where('teacher', $staffName)
                ->update(['teacher' => null]);
            
            Subject::where('teacher', 'like', $staffName)
                ->whereNotNull('teacher')
                ->where('teacher', '!=', '')
                ->update(['teacher' => null]);
        }

        // Delete associated files
        if ($staff->photo) {
            Storage::disk('public')->delete($staff->photo);
        }
        if ($staff->cv_resume) {
            Storage::disk('public')->delete($staff->cv_resume);
        }

        $staff->delete();

        $message = 'Staff member deleted successfully!';
        if (!empty($staffName)) {
            $message .= ' Staff has been removed from all assigned sections.';
        }
        if ($isTeacher) {
            $message .= ' Teacher has also been removed from all assigned subjects.';
        }

        return redirect()
            ->route('staff.management')
            ->with('success', $message);
    }

    /**
     * Delete all staff members.
     */
    public function deleteAll(): RedirectResponse
    {
        // Delete all associated files
        $allStaff = Staff::all();
        foreach ($allStaff as $staffMember) {
            if ($staffMember->photo) {
                Storage::disk('public')->delete($staffMember->photo);
            }
            if ($staffMember->cv_resume) {
                Storage::disk('public')->delete($staffMember->cv_resume);
            }
        }

        // Remove all staff from sections before deleting
        DB::table('sections')
            ->whereNotNull('teacher')
            ->where('teacher', '!=', '')
            ->update(['teacher' => null]);

        // Remove all teachers from subjects before deleting
        DB::table('subjects')
            ->whereNotNull('teacher')
            ->where('teacher', '!=', '')
            ->update(['teacher' => null]);

        Staff::truncate();

        return redirect()
            ->route('staff.management')
            ->with('success', 'All staff members deleted successfully! All staff have been removed from sections and subjects.');
    }

    /**
     * Export staff data.
     */
    public function export(Request $request, $format)
    {
        $query = Staff::query();
        
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(email) LIKE ?', ["%{$searchLower}%"])
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('emp_id', 'like', "%{$search}%");
                });
            }
        }

        $staff = $query->latest()->get();

        switch ($format) {
            case 'excel':
                // Excel export logic here
                return redirect()->back()->with('info', 'Excel export will be implemented');
                
            case 'csv':
                $filename = 'staff_' . date('Y-m-d_His') . '.csv';
                $headers = [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => "attachment; filename=\"$filename\"",
                ];
                
                $callback = function() use ($staff) {
                    $file = fopen('php://output', 'w');
                    
                    // Add headers
                    fputcsv($file, [
                        'Name', 'Father/Husband Name', 'Campus', 'Designation', 'Gender',
                        'Emp. ID', 'Phone', 'WhatsApp', 'CNIC', 'Qualification',
                        'Birthday', 'Joining Date', 'Marital Status', 'Salary Type', 'Salary',
                        'Email', 'Home Address'
                    ]);
                    
                    // Add data
                    foreach ($staff as $member) {
                        fputcsv($file, [
                            $member->name ?? '',
                            $member->father_husband_name ?? '',
                            $member->campus ?? '',
                            $member->designation ?? '',
                            $member->gender ?? '',
                            $member->emp_id ?? '',
                            $member->phone ?? '',
                            $member->whatsapp ?? '',
                            $member->cnic ?? '',
                            $member->qualification ?? '',
                            $member->birthday ?? '',
                            $member->joining_date ?? '',
                            $member->marital_status ?? '',
                            $member->salary_type ?? '',
                            $member->salary ?? '',
                            $member->email ?? '',
                            $member->home_address ?? '',
                        ]);
                    }
                    
                    fclose($file);
                };
                
                return response()->stream($callback, 200, $headers);
                
            case 'pdf':
                $html = view('staff.management-pdf', compact('staff'))->render();
                return response($html)
                    ->header('Content-Type', 'text/html');
                
            default:
                return redirect()->back()->with('error', 'Invalid export format');
        }
    }

    /**
     * Toggle staff status (Active/Inactive)
     */
    public function toggleStatus(Request $request, Staff $staff)
    {
        try {
            // Check if user is super admin
            if (!Auth::guard('admin')->check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Please login.',
                ], 403);
            }

            $admin = Auth::guard('admin')->user();
            if (!$admin || !$admin->isSuperAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Super Admin access required.',
                ], 403);
            }

            // Get current status (default to Active if null or empty)
            $currentStatus = $staff->status;
            if (empty($currentStatus) || is_null($currentStatus)) {
                $currentStatus = 'Active';
            }
            
            // Toggle status
            $newStatus = (strtolower(trim($currentStatus)) === 'active') ? 'Inactive' : 'Active';
            
            // Update status
            $staff->status = $newStatus;
            $result = $staff->save();

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update status. Please try again.',
                ], 500);
            }

            // Create success message
            $message = $newStatus === 'Active' 
                ? $staff->name . ' is now Active.' 
                : $staff->name . ' is now Inactive.';

            // Return JSON response
            return response()->json([
                'success' => true,
                'message' => $message,
                'status' => $staff->status,
            ], 200);

        } catch (\Exception $e) {
            // Log error for debugging
            \Log::error('Toggle status error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
                'error_details' => config('app.debug') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }
}

