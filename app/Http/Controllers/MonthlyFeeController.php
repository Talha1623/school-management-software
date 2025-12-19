<?php

namespace App\Http\Controllers;

use App\Models\MonthlyFee;
use App\Models\Campus;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Carbon\Carbon;

class MonthlyFeeController extends Controller
{
    /**
     * Display the generate monthly fee form.
     */
    public function create(): View
    {
        // Get campuses from Campus model
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        
        // If no campuses found, get from classes or sections
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $campusesFromSections = Section::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $allCampuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
            
            // Convert to collection of objects with campus_name property
            $campuses = collect();
            foreach ($allCampuses as $campusName) {
                $campuses->push((object)['campus_name' => $campusName]);
            }
        }
        
        // Get classes from ClassModel
        $classes = ClassModel::orderBy('class_name', 'asc')->get();
        
        // If no classes found, provide empty collection
        if ($classes->isEmpty()) {
            $classes = collect();
        }
        
        // Get sections from Section model
        $sections = Section::whereNotNull('name')
            ->distinct()
            ->orderBy('name', 'asc')
            ->pluck('name')
            ->sort()
            ->values();
        
        // Months of the year
        $months = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];
        
        // Generate years (current year - 2 to current year + 5)
        $currentYear = date('Y');
        $years = [];
        for ($y = $currentYear - 2; $y <= $currentYear + 5; $y++) {
            $years[] = $y;
        }
        
        return view('accounting.generate-monthly-fee', compact('campuses', 'classes', 'sections', 'months', 'years', 'currentYear'));
    }

    /**
     * Store the generated monthly fee.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string', 'max:255'],
            'class' => ['required', 'string', 'max:255'],
            'section' => ['required', 'string', 'max:255'],
            'fee_month' => ['required', 'string', 'max:255'],
            'fee_year' => ['required', 'string', 'max:255'],
            'due_date' => ['required', 'date'],
            'late_fee' => ['nullable', 'numeric', 'min:0'],
        ]);

        // Set default value for late_fee if it's null or empty
        if (empty($validated['late_fee']) || $validated['late_fee'] === null) {
            $validated['late_fee'] = 0;
        }

        // Create the monthly fee configuration
        $monthlyFee = MonthlyFee::create($validated);

        // Get students matching the criteria
        $studentsQuery = Student::query();
        
        if ($validated['campus']) {
            $studentsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($validated['campus']))]);
        }
        
        if ($validated['class']) {
            $studentsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($validated['class']))]);
        }
        
        if ($validated['section']) {
            $studentsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($validated['section']))]);
        }
        
        $students = $studentsQuery->get();
        $studentCount = $students->count();
        
        // Generate fee for each student
        $dueDate = Carbon::parse($validated['due_date']);
        $paymentTitle = "Monthly Fee - {$validated['fee_month']} {$validated['fee_year']}";
        $lateFeeAmount = (float) $validated['late_fee'];
        
        $generatedCount = 0;
        $skippedCount = 0;
        
        foreach ($students as $student) {
            // Skip if student doesn't have monthly_fee or student_code
            if (empty($student->monthly_fee) || $student->monthly_fee <= 0 || empty($student->student_code)) {
                $skippedCount++;
                continue;
            }
            
            // Check if fee already exists for this student, month, and year
            $existingFee = StudentPayment::where('student_code', $student->student_code)
                ->where('payment_title', $paymentTitle)
                ->first();
            
            if ($existingFee) {
                $skippedCount++;
                continue;
            }
            
            // Create fee record for this student
            StudentPayment::create([
                'campus' => $student->campus ?? $validated['campus'],
                'student_code' => $student->student_code,
                'payment_title' => $paymentTitle,
                'payment_amount' => (float) $student->monthly_fee,
                'discount' => 0,
                'method' => 'Generated', // Indicates this is a generated fee, not actual payment
                'payment_date' => $dueDate->format('Y-m-d'), // Due date (will be updated when payment is made)
                'sms_notification' => 'Yes',
                'late_fee' => 0, // Will be calculated when actual payment is made
                'accountant' => auth()->user()->name ?? 'System',
            ]);
            
            $generatedCount++;
        }

        // If no fees were generated, show warning/error message
        if ($generatedCount == 0) {
            if ($studentCount == 0) {
                // No students found in the selected class/section
                $message = "Is class mein students nahi hain. Please check the selected Campus, Class, and Section.";
            } else {
                // Students found but fees couldn't be generated
                $message = "No fees were generated. ";
                $message .= "{$studentCount} student(s) found, but ";
                if ($skippedCount > 0) {
                    $message .= "all were skipped (no monthly fee set or fees already exist for this month/year).";
                } else {
                    $message .= "none have monthly fee configured.";
                }
            }
            
            return redirect()
                ->route('accounting.generate-monthly-fee')
                ->with('error', $message);
        }

        // Success message only if fees were generated
        $message = "Monthly fee generated successfully for {$generatedCount} student(s)!";
        if ($skippedCount > 0) {
            $message .= " {$skippedCount} student(s) skipped (no monthly fee set or already exists).";
        }

        return redirect()
            ->route('accounting.generate-monthly-fee')
            ->with('success', $message);
    }

    /**
     * Get sections by class name (AJAX).
     */
    public function getSectionsByClass(Request $request)
    {
        $className = $request->get('class');
        
        if (!$className) {
            return response()->json(['sections' => []]);
        }

        // Get sections for the selected class
        $sections = Section::where('class', $className)
            ->orderBy('name', 'asc')
            ->get(['id', 'name'])
            ->map(function($section) {
                return [
                    'id' => $section->id,
                    'name' => $section->name
                ];
            });

        return response()->json(['sections' => $sections]);
    }
}

