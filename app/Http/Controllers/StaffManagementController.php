<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        
        return view('staff.management', compact('staff', 'totalTeachers', 'presentToday', 'absentToday'));
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
            'salary_type' => ['nullable', 'string', 'max:255'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'email' => ['nullable', 'email', 'max:255', 'unique:staff,email'],
            'password' => ['nullable', 'string', 'min:6'],
            'home_address' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:2048'],
            'cv_resume' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

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
     * Show the specified staff member for editing.
     */
    public function show(Staff $staff)
    {
        return response()->json($staff);
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
            'salary_type' => ['nullable', 'string', 'max:255'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'email' => ['nullable', 'email', 'max:255', 'unique:staff,email,' . $staff->id],
            'password' => ['nullable', 'string', 'min:6'],
            'home_address' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:2048'],
            'cv_resume' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

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
        // Delete associated files
        if ($staff->photo) {
            Storage::disk('public')->delete($staff->photo);
        }
        if ($staff->cv_resume) {
            Storage::disk('public')->delete($staff->cv_resume);
        }

        $staff->delete();

        return redirect()
            ->route('staff.management')
            ->with('success', 'Staff member deleted successfully!');
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

        Staff::truncate();

        return redirect()
            ->route('staff.management')
            ->with('success', 'All staff members deleted successfully!');
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
}

