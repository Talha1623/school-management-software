<?php

namespace App\Http\Controllers;

use App\Models\CustomFee;
use App\Models\Campus;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\FeeType;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomFeeController extends Controller
{
    /**
     * Display the generate custom fee form.
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
        
        // Get fee types from FeeType model
        $feeTypes = FeeType::whereNotNull('fee_name')
            ->distinct()
            ->orderBy('fee_name', 'asc')
            ->pluck('fee_name')
            ->sort()
            ->values();
        
        return view('accounting.generate-custom-fee', compact('campuses', 'classes', 'sections', 'feeTypes'));
    }

    /**
     * Store the generated custom fee.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string', 'max:255'],
            'class' => ['required', 'string', 'max:255'],
            'section' => ['required', 'string', 'max:255'],
            'fee_type' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        // Create the custom fee configuration
        $customFee = CustomFee::create($validated);

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
            ->route('accounting.generate-custom-fee')
            ->with('success', "Custom fee generated successfully for {$studentCount} student(s)!");
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

