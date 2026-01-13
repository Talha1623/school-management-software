<?php

namespace App\Http\Controllers;

use App\Models\JobInquiry;
use App\Models\Campus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
            return response()->json($job_inquiry);
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
            'salary_type' => ['nullable', 'string', 'max:255'],
            'salary_demand' => ['nullable', 'numeric', 'min:0'],
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
}

