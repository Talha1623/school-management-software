<?php

namespace App\Http\Controllers;

use App\Models\MonthlyFee;
use App\Models\Campus;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
            $studentsQuery->where('campus', $validated['campus']);
        }
        
        if ($validated['class']) {
            $studentsQuery->where('class', $validated['class']);
        }
        
        if ($validated['section']) {
            $studentsQuery->where('section', $validated['section']);
        }
        
        $students = $studentsQuery->get();
        $studentCount = $students->count();

        return redirect()
            ->route('accounting.generate-monthly-fee')
            ->with('success', "Monthly fee generated successfully for {$studentCount} student(s)!");
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

