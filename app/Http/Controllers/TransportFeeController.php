<?php

namespace App\Http\Controllers;

use App\Models\TransportFee;
use App\Models\Campus;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentPayment;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransportFeeController extends Controller
{
    /**
     * Display the generate transport fee form.
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
        
        return view('accounting.generate-transport-fee', compact('campuses', 'classes', 'sections', 'months', 'years', 'currentYear'));
    }

    /**
     * Store the generated transport fee.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string', 'max:255'],
            'class' => ['required', 'string', 'max:255'],
            'section' => ['required', 'string', 'max:255'],
            'fee_month' => ['required', 'string', 'max:255'],
            'fee_year' => ['required', 'string', 'max:255'],
            'selected_students' => ['nullable', 'array'],
            'selected_students.*' => ['exists:students,id'],
        ]);

        // Check if students are selected
        $selectedStudentIds = $request->input('selected_students', []);

        if (empty($selectedStudentIds)) {
            return redirect()
                ->route('accounting.generate-transport-fee')
                ->with('error', 'Please select at least one student to generate fees.');
        }

        // Create the transport fee configuration
        $transportFee = TransportFee::create($validated);

        // Get selected students
        $students = Student::whereIn('id', $selectedStudentIds)->get();

        // Generate fee for each selected student
        $paymentTitle = "Transport Fee - {$validated['fee_month']} {$validated['fee_year']}";
        $dueDate = Carbon::now()->addDays(15);

        $generatedCount = 0;
        $skippedCount = 0;

        foreach ($students as $student) {
            // Skip if student doesn't have transport fare or student_code
            if (empty($student->transport_fare) || $student->transport_fare <= 0 || empty($student->student_code)) {
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

            // Get accountant name based on guard
            $accountantName = 'System';
            if (auth()->guard('accountant')->check()) {
                $accountantName = auth()->guard('accountant')->user()->name ?? 'System';
            } elseif (auth()->guard('admin')->check()) {
                $accountantName = auth()->guard('admin')->user()->name ?? 'System';
            }

            // Create fee record for this student
            StudentPayment::create([
                'campus' => $student->campus ?? $validated['campus'],
                'student_code' => $student->student_code,
                'payment_title' => $paymentTitle,
                'payment_amount' => (float) $student->transport_fare,
                'discount' => 0,
                'method' => 'Generated',
                'payment_date' => $dueDate->format('Y-m-d'),
                'sms_notification' => 'Yes',
                'late_fee' => 0,
                'accountant' => $accountantName,
            ]);

            $generatedCount++;
        }

        if ($generatedCount == 0) {
            $message = "No transport fees were generated. ";
            if ($skippedCount > 0) {
                $message .= "All selected students were skipped (no transport fare set or fees already exist).";
            } else {
                $message .= "Selected students don't have transport fare configured.";
            }

            return redirect()
                ->route('accounting.generate-transport-fee')
                ->with('error', $message);
        }

        $message = "Transport fee generated successfully for {$generatedCount} student(s)!";
        if ($skippedCount > 0) {
            $message .= " {$skippedCount} student(s) skipped (no transport fare set or already exists).";
        }

        return redirect()
            ->route('accounting.generate-transport-fee')
            ->with('success', $message);
    }

    /**
     * Get sections by class name (AJAX).
     */
    public function getSectionsByClass(Request $request)
    {
        $className = $request->get('class');
        $campus = $request->get('campus');
        
        if (!$className) {
            return response()->json(['sections' => []]);
        }

        // Get sections for the selected class
        $sectionsQuery = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($className))]);
        if ($campus) {
            $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        $sections = $sectionsQuery->orderBy('name', 'asc')
            ->get(['id', 'name'])
            ->map(function($section) {
                return [
                    'id' => $section->id,
                    'name' => $section->name
                ];
            });

        return response()->json(['sections' => $sections]);
    }

    public function getStudentsWithFeeStatus(Request $request)
    {
        $campus = $request->get('campus');
        $class = $request->get('class');
        $section = $request->get('section');
        $feeMonth = $request->get('fee_month');
        $feeYear = $request->get('fee_year');

        if (!$campus || !$class || !$section) {
            return response()->json(['students' => []]);
        }

        // Get students matching the criteria
        $studentsQuery = Student::query();

        if ($campus) {
            $studentsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }

        if ($class) {
            $studentsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
        }

        if ($section) {
            $studentsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($section))]);
        }

        $students = $studentsQuery->orderBy('student_code', 'asc')->get();

        // Build payment title if month and year are provided
        $paymentTitle = null;
        if ($feeMonth && $feeYear) {
            $paymentTitle = "Transport Fee - {$feeMonth} {$feeYear}";
        }

        // Map students with fee status
        $studentsWithStatus = $students->map(function ($student) use ($paymentTitle) {
            $hasFeeGenerated = false;

            if ($paymentTitle && $student->student_code) {
                $existingFee = StudentPayment::where('student_code', $student->student_code)
                    ->where('payment_title', $paymentTitle)
                    ->first();

                $hasFeeGenerated = (bool) $existingFee;
            }

            $parentName = $student->father_name ?? '';

            return [
                'id' => $student->id,
                'student_code' => $student->student_code ?? '',
                'student_name' => $student->student_name ?? '',
                'parent_name' => $parentName,
                'has_fee_generated' => $hasFeeGenerated,
                'status' => $hasFeeGenerated ? 'Generated' : 'Ready',
            ];
        });

        return response()->json(['students' => $studentsWithStatus]);
    }
}

