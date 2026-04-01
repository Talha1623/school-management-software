<?php

namespace App\Http\Controllers;

use App\Models\JobInquiry;
use App\Models\Campus;
use App\Models\Staff;
use App\Models\GeneralSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class JobInquiryController extends Controller
{
    /**
     * Display a listing of job inquiries.
     */
    public function index(Request $request): View
    {
        $query = JobInquiry::query();
        
        // Search functionality
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(email) LIKE ?', ["%{$searchLower}%"])
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhereRaw('LOWER(qualification) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(applied_for_designation) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(campus) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $inquiries = $query->latest()->paginate($perPage)->withQueryString();
        
        // Get campuses for dropdown
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        
        // Job Inquiry Statistics - Dynamic based on filtered query (search results)
        $totalInquiries = (clone $query)->count();
        $fullTimeInquiries = (clone $query)->whereRaw('LOWER(salary_type) LIKE ?', ['%full time%'])->count();
        $partTimeInquiries = (clone $query)->whereRaw('LOWER(salary_type) LIKE ?', ['%part time%'])->count();
        
        return view('staff.job-inquiry', compact('inquiries', 'campuses', 'totalInquiries', 'fullTimeInquiries', 'partTimeInquiries'));
    }

    /**
     * Print job inquiries list (dedicated print page)
     */
    public function print(Request $request): View
    {
        $query = JobInquiry::query();

        // Search functionality (match index behavior)
        if ($request->filled('search')) {
            $search = trim((string) $request->get('search'));
            if ($search !== '') {
                $searchLower = strtolower($search);
                $query->where(function ($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"])
                        ->orWhereRaw('LOWER(email) LIKE ?', ["%{$searchLower}%"])
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhereRaw('LOWER(qualification) LIKE ?', ["%{$searchLower}%"])
                        ->orWhereRaw('LOWER(applied_for_designation) LIKE ?', ["%{$searchLower}%"])
                        ->orWhereRaw('LOWER(campus) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }

        $inquiries = $query->latest()->get();
        $settings = GeneralSetting::getSettings();

        return view('staff.job-inquiry-print', compact('inquiries', 'settings'));
    }

    /**
     * Store a newly created job inquiry.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'father_husband_name' => ['nullable', 'string', 'max:255'],
            'campus' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'in:Male,Female,Other'],
            'phone' => ['nullable', 'string', 'max:20'],
            'qualification' => ['nullable', 'string', 'max:255'],
            'birthday' => ['nullable', 'date'],
            'marital_status' => ['nullable', 'string', 'in:Single,Married,Divorced,Widowed'],
            'applied_for_designation' => ['nullable', 'string', 'max:255'],
            'salary_type' => ['nullable', 'string', 'max:255'],
            'salary_demand' => ['nullable', 'numeric', 'min:0'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'absent_fees' => ['nullable', 'numeric', 'min:0'],
            'late_fees' => ['nullable', 'numeric', 'min:0'],
            'early_exit_fees' => ['nullable', 'numeric', 'min:0'],
            'free_absent' => ['nullable', 'integer', 'min:0', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'home_address' => ['nullable', 'string'],
            'cv_resume' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ]);

        // Handle file upload
        if ($request->hasFile('cv_resume')) {
            $validated['cv_resume'] = $request->file('cv_resume')->store('job-inquiries/cv', 'public');
        }

        JobInquiry::create($validated);

        return redirect()
            ->route('staff.job-inquiry')
            ->with('success', 'Job inquiry added successfully!');
    }

    /**
     * Show the specified job inquiry details.
     */
    public function show(Request $request, JobInquiry $job_inquiry)
    {
        // If request wants JSON (for edit modal), return JSON
        if ($request->wantsJson() || $request->ajax()) {
            // Format dates properly for the edit form
            $inquiryData = $job_inquiry->toArray();
            
            // Format birthday date (YYYY-MM-DD format for date input)
            if ($job_inquiry->birthday) {
                try {
                    $inquiryData['birthday'] = \Carbon\Carbon::parse($job_inquiry->birthday)->format('Y-m-d');
                } catch (\Exception $e) {
                    $inquiryData['birthday'] = null;
                }
            } else {
                $inquiryData['birthday'] = null;
            }
            
            return response()->json($inquiryData);
        }
        
        // Otherwise return view
        return view('staff.job-inquiry-view', compact('job_inquiry'));
    }

    /**
     * Update the specified job inquiry.
     */
    public function update(Request $request, JobInquiry $job_inquiry): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'father_husband_name' => ['nullable', 'string', 'max:255'],
            'campus' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'in:Male,Female,Other'],
            'phone' => ['nullable', 'string', 'max:20'],
            'qualification' => ['nullable', 'string', 'max:255'],
            'birthday' => ['nullable', 'date'],
            'marital_status' => ['nullable', 'string', 'in:Single,Married,Divorced,Widowed'],
            'applied_for_designation' => ['nullable', 'string', 'max:255'],
            'salary_type' => ['nullable', 'string', 'in:full time,per hour,lecture'],
            'salary_demand' => ['nullable', 'numeric', 'min:0'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'absent_fees' => ['nullable', 'numeric', 'min:0'],
            'late_fees' => ['nullable', 'numeric', 'min:0'],
            'early_exit_fees' => ['nullable', 'numeric', 'min:0'],
            'free_absent' => ['nullable', 'integer', 'min:0', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'home_address' => ['nullable', 'string'],
            'cv_resume' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ]);

        // Handle file upload
        if ($request->hasFile('cv_resume')) {
            // Delete old CV if exists
            if ($job_inquiry->cv_resume) {
                Storage::disk('public')->delete($job_inquiry->cv_resume);
            }
            $validated['cv_resume'] = $request->file('cv_resume')->store('job-inquiries/cv', 'public');
        }

        $job_inquiry->update($validated);

        return redirect()
            ->route('staff.job-inquiry')
            ->with('success', 'Job inquiry updated successfully!');
    }

    /**
     * Remove the specified job inquiry.
     */
    public function destroy(JobInquiry $job_inquiry): RedirectResponse
    {
        // Delete associated file
        if ($job_inquiry->cv_resume) {
            Storage::disk('public')->delete($job_inquiry->cv_resume);
        }

        $job_inquiry->delete();

        return redirect()
            ->route('staff.job-inquiry')
            ->with('success', 'Job inquiry deleted successfully!');
    }

    /**
     * Delete all job inquiries.
     */
    public function deleteAll(): RedirectResponse
    {
        // Delete all associated files
        $allInquiries = JobInquiry::all();
        foreach ($allInquiries as $inquiry) {
            if ($inquiry->cv_resume) {
                Storage::disk('public')->delete($inquiry->cv_resume);
            }
        }

        JobInquiry::truncate();

        return redirect()
            ->route('staff.job-inquiry')
            ->with('success', 'All job inquiries deleted successfully!');
    }

    /**
     * Export job inquiries data.
     */
    public function export(Request $request, $format)
    {
        $query = JobInquiry::query();
        
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(email) LIKE ?', ["%{$searchLower}%"])
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }
        }

        $inquiries = $query->latest()->get();

        switch ($format) {
            case 'excel':
                return redirect()->back()->with('info', 'Excel export will be implemented');
                
            case 'csv':
                $filename = 'job_inquiries_' . date('Y-m-d_His') . '.csv';
                $headers = [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => "attachment; filename=\"$filename\"",
                ];
                
                $callback = function() use ($inquiries) {
                    $file = fopen('php://output', 'w');
                    
                    // Add headers
                    fputcsv($file, [
                        'Name', 'Father/Husband Name', 'Campus', 'Gender', 'Phone',
                        'Qualification', 'Birthday', 'Marital Status', 'Applied For Designation',
                        'Salary Type', 'Salary Demand', 'Email', 'Home Address'
                    ]);
                    
                    // Add data
                    foreach ($inquiries as $inquiry) {
                        fputcsv($file, [
                            $inquiry->name ?? '',
                            $inquiry->father_husband_name ?? '',
                            $inquiry->campus ?? '',
                            $inquiry->gender ?? '',
                            $inquiry->phone ?? '',
                            $inquiry->qualification ?? '',
                            $inquiry->birthday ?? '',
                            $inquiry->marital_status ?? '',
                            $inquiry->applied_for_designation ?? '',
                            $inquiry->salary_type ?? '',
                            $inquiry->salary_demand ?? '',
                            $inquiry->email ?? '',
                            $inquiry->home_address ?? '',
                        ]);
                    }
                    
                    fclose($file);
                };
                
                return response()->stream($callback, 200, $headers);
                
            case 'pdf':
                $html = view('staff.job-inquiry-pdf', compact('inquiries'))->render();
                return response($html)
                    ->header('Content-Type', 'text/html');
                
            default:
                return redirect()->back()->with('error', 'Invalid export format');
        }
    }

    /**
     * Appoint a job inquiry candidate as staff member.
     */
    public function appoint(JobInquiry $job_inquiry): RedirectResponse
    {
        try {
            // Check if this inquiry has already been appointed (check by name and phone/email)
            $existingStaff = null;
            if ($job_inquiry->email) {
                $existingStaff = Staff::where('email', $job_inquiry->email)->first();
            }
            if (!$existingStaff && $job_inquiry->phone) {
                $existingStaff = Staff::where('phone', $job_inquiry->phone)
                    ->where('name', $job_inquiry->name)
                    ->first();
            }
            
            if ($existingStaff) {
                // If staff already exists, delete the inquiry and redirect
                $inquiryName = $job_inquiry->name;
                if ($job_inquiry->cv_resume) {
                    Storage::disk('public')->delete($job_inquiry->cv_resume);
                }
                $job_inquiry->delete();
                
                return redirect()
                    ->route('staff.job-inquiry')
                    ->with('info', $inquiryName . ' is already in Staff Management. The inquiry has been removed from Job Inquiry/CV Bank.');
            }

            // Generate Employee ID based on campus
            $campus = $job_inquiry->campus ?? null;
            
            // Extract campus number from campus name
            $campusNumber = '1'; // Default to 1 if no campus
            if ($campus) {
                if (preg_match('/(\d+)/', $campus, $matches)) {
                    $campusNumber = $matches[1];
                } else {
                    $campusLower = strtolower(trim($campus));
                    if (strpos($campusLower, 'main') !== false || strpos($campusLower, 'primary') !== false) {
                        $campusNumber = '1';
                    } elseif (strpos($campusLower, 'secondary') !== false || strpos($campusLower, 'branch') !== false) {
                        $campusNumber = '2';
                    } else {
                        $firstChar = strtoupper(substr($campus, 0, 1));
                        if (is_numeric($firstChar)) {
                            $campusNumber = $firstChar;
                        } else {
                            $campusNumber = ord($firstChar) - ord('A') + 1;
                            if ($campusNumber < 1 || $campusNumber > 9) {
                                $campusNumber = '1';
                            }
                        }
                    }
                }
            }

            // Get the last employee ID for this campus
            $prefix = 'EMP' . $campusNumber . '-';
            $lastStaff = Staff::whereNotNull('emp_id')
                ->where('emp_id', 'like', $prefix . '%')
                ->orderByRaw('CAST(SUBSTRING(emp_id, ' . (strlen($prefix) + 1) . ') AS UNSIGNED) DESC')
                ->first();

            if ($lastStaff && $lastStaff->emp_id) {
                $lastNumber = (int) substr($lastStaff->emp_id, strlen($prefix));
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }
            $empId = 'EMP' . $campusNumber . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

            // Auto-generate email if not provided
            $email = $job_inquiry->email;
            if (empty($email)) {
                $namePart = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $job_inquiry->name));
                $email = $namePart . $empId . '@school.local';
            }

            // Map salary type from job inquiry to staff format
            $salaryType = null;
            if ($job_inquiry->salary_type) {
                $salaryTypeLower = strtolower(trim($job_inquiry->salary_type));
                if (strpos($salaryTypeLower, 'full time') !== false || strpos($salaryTypeLower, 'fulltime') !== false) {
                    $salaryType = 'full time';
                } elseif (strpos($salaryTypeLower, 'part time') !== false || strpos($salaryTypeLower, 'parttime') !== false || strpos($salaryTypeLower, 'per hour') !== false) {
                    $salaryType = 'per hour';
                } elseif (strpos($salaryTypeLower, 'lecture') !== false || strpos($salaryTypeLower, 'per lecture') !== false) {
                    $salaryType = 'lecture';
                }
            }

            // Map job inquiry data to staff data
            $staffData = [
                'name' => $job_inquiry->name,
                'father_husband_name' => $job_inquiry->father_husband_name,
                'campus' => $job_inquiry->campus,
                'designation' => $job_inquiry->applied_for_designation,
                'gender' => $job_inquiry->gender,
                'emp_id' => $empId,
                'phone' => $job_inquiry->phone,
                'whatsapp' => $job_inquiry->phone, // Use phone as whatsapp if not separate
                'qualification' => $job_inquiry->qualification,
                'birthday' => $job_inquiry->birthday,
                'joining_date' => now(), // Set joining date as today
                'marital_status' => $job_inquiry->marital_status,
                'salary_type' => $salaryType,
                'salary' => $job_inquiry->salary ?? 0, // Use only salary field, not salary_demand
                'absent_fees' => $job_inquiry->absent_fees ?? null,
                'late_fees' => $job_inquiry->late_fees ?? null,
                'early_exit_fees' => $job_inquiry->early_exit_fees ?? null,
                'free_absent' => $job_inquiry->free_absent ?? 0,
                'email' => $email,
                'password' => 'staff', // Default password
                'home_address' => $job_inquiry->home_address,
                'status' => 'Active',
            ];

            // Copy CV/Resume if exists
            if ($job_inquiry->cv_resume) {
                $sourcePath = storage_path('app/public/' . $job_inquiry->cv_resume);
                if (file_exists($sourcePath)) {
                    $destinationPath = 'staff/cv/' . basename($job_inquiry->cv_resume);
                    Storage::disk('public')->copy($job_inquiry->cv_resume, $destinationPath);
                    $staffData['cv_resume'] = $destinationPath;
                }
            }

            // Use database transaction to ensure atomicity
            DB::beginTransaction();
            
            try {
                // Create staff member
                $staff = Staff::create($staffData);

                // Store inquiry name and ID for deletion
                $inquiryName = $job_inquiry->name;
                $inquiryId = $job_inquiry->id;
                $cvResumePath = $job_inquiry->cv_resume;

                // Delete the job inquiry after successful appointment
                // Note: CV file is already copied to staff folder, so we can delete the original
                $job_inquiry->delete();
                
                // Delete CV file if exists
                if ($cvResumePath) {
                    Storage::disk('public')->delete($cvResumePath);
                }
                
                // Commit transaction
                DB::commit();
                
                // Verify deletion - if still exists, force delete
                $deletedInquiry = JobInquiry::find($inquiryId);
                if ($deletedInquiry) {
                    DB::table('job_inquiries')->where('id', $inquiryId)->delete();
                }

                // Redirect to Staff Management page with success message
                return redirect()
                    ->route('staff.management')
                    ->with('success', $inquiryName . ' has been successfully appointed as staff member and removed from Job Inquiry/CV Bank!');
                    
            } catch (\Exception $e) {
                // Rollback transaction on error
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return redirect()
                ->route('staff.job-inquiry')
                ->with('error', 'Failed to appoint candidate: ' . $e->getMessage());
        }
    }
}

