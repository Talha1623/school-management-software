<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Campus;
use App\Models\ClassModel;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class StudentPromotionController extends Controller
{
    /**
     * Display the student promotion page.
     */
    public function index(Request $request): View
    {
        // Check if students table exists and has required columns
        $hasCampus = Schema::hasColumn('students', 'campus');
        $hasClass = Schema::hasColumn('students', 'class');
        $hasSection = Schema::hasColumn('students', 'section');
        
        // Get campuses from Campus model (Manage Campuses)
        $campuses = Campus::orderBy('campus_name', 'asc')->pluck('campus_name');
        
        if ($campuses->isEmpty()) {
            // Fallback to classes or sections if Campus table is empty
            $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
            $campuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
            
            if ($campuses->isEmpty()) {
                // Fallback to students table if ClassModel/Section don't have campuses
                if ($hasCampus) {
                    try {
                        $campuses = Student::whereNotNull('campus')->distinct()->pluck('campus')->sort()->values();
                    } catch (\Exception $e) {
                        $campuses = collect(['Main Campus', 'Branch Campus 1', 'Branch Campus 2']);
                    }
                } else {
                    $campuses = collect(['Main Campus', 'Branch Campus 1', 'Branch Campus 2']);
                }
            }
        }
        
        // Get classes from ClassModel
        $classes = ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort()->values();
        if ($classes->isEmpty()) {
            // Fallback to students table if ClassModel is empty
            if ($hasClass) {
                try {
                    $classes = Student::distinct()->pluck('class')->sort()->values();
                } catch (\Exception $e) {
                    $classes = collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']);
                }
            } else {
                $classes = collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']);
            }
        }
        
        // Get sections based on selected class
        $sections = collect();
        if ($request->filled('from_class')) {
            // Get sections from Section model for the selected class
            try {
                $sections = Section::where('class', $request->from_class)
                    ->whereNotNull('name')
                    ->distinct()
                    ->pluck('name')
                    ->sort()
                    ->values();
            } catch (\Exception $e) {
                // Fallback to students table
                if ($hasSection) {
                    try {
                        $sections = Student::where('class', $request->from_class)
                            ->whereNotNull('section')
                            ->distinct()
                            ->pluck('section')
                            ->sort()
                            ->values();
                    } catch (\Exception $e2) {
                        $sections = collect(['A', 'B', 'C', 'D', 'E']);
                    }
                } else {
                    $sections = collect(['A', 'B', 'C', 'D', 'E']);
                }
            }
        } else {
            // Get all sections from Section model
            try {
                $sections = Section::whereNotNull('name')->distinct()->pluck('name')->sort()->values();
            } catch (\Exception $e) {
                // Fallback to students table
                if ($hasSection) {
                    try {
                        $sections = Student::whereNotNull('section')->distinct()->pluck('section')->sort()->values();
                    } catch (\Exception $e2) {
                        $sections = collect(['A', 'B', 'C', 'D', 'E']);
                    }
                } else {
                    $sections = collect(['A', 'B', 'C', 'D', 'E']);
                }
            }
        }
        
        if ($sections->isEmpty()) {
            $sections = collect(['A', 'B', 'C', 'D', 'E']);
        }
        
        // Filter students based on request
        $students = collect();
        $hasFilters = false;
        
        if ($request->filled('campus') || $request->filled('from_class') || $request->filled('from_section')) {
            $hasFilters = true;
            $query = Student::query();
            
            if ($request->filled('campus') && $hasCampus) {
                $query->where('campus', $request->campus);
            }
            
            if ($request->filled('from_class') && $hasClass) {
                $query->where('class', $request->from_class);
            }
            
            if ($request->filled('from_section') && $hasSection) {
                $query->where('section', $request->from_section);
            }
            
            $perPage = $request->get('per_page', 10);
            $students = $query->orderBy('student_name')->paginate($perPage)->withQueryString();
        }
        
        return view('student.promotion', compact('campuses', 'classes', 'sections', 'students', 'hasFilters'));
    }

    /**
     * Handle the promotion process.
     */
    public function promote(Request $request)
    {
        $validated = $request->validate([
            'campus' => ['nullable', 'string', 'max:255'],
            'from_class' => ['required', 'string', 'max:255'],
            'from_section' => ['nullable', 'string', 'max:255'],
            'to_class' => ['required', 'string', 'max:255'],
            'to_section' => ['nullable', 'string', 'max:255'],
        ], [
            'from_class.required' => 'Please select Promotion From Class',
            'to_class.required' => 'Please select Promotion To Class',
        ]);

        // Check if required columns exist
        if (!Schema::hasColumn('students', 'class')) {
            return redirect()
                ->route('student.promotion')
                ->with('error', 'Class column not found in database. Please run migrations: php artisan migrate');
        }

        $query = Student::query();

        // Filter by campus if provided and column exists
        if (Schema::hasColumn('students', 'campus') && $request->filled('campus') && !empty($request->campus)) {
            $query->where('campus', $request->campus);
        }

        // Filter by from class
        $query->where('class', $validated['from_class']);

        // Filter by from section if provided and column exists
        if (Schema::hasColumn('students', 'section') && !empty($validated['from_section'])) {
            $query->where('section', $validated['from_section']);
        }

        // Update students
        $updateData = ['class' => $validated['to_class']];
        
        // Only add section if column exists
        if (Schema::hasColumn('students', 'section')) {
            $updateData['section'] = $validated['to_section'] ?? null;
        }
        
        try {
            $updated = $query->update($updateData);
        } catch (\Exception $e) {
            return redirect()
                ->route('student.promotion')
                ->with('error', 'Error updating students: ' . $e->getMessage());
        }

        return redirect()
            ->route('student.promotion')
            ->with('success', "Successfully promoted {$updated} student(s) from {$validated['from_class']} to {$validated['to_class']}!");
    }

    /**
     * Get sections for a specific class (AJAX)
     */
    public function getSections(Request $request)
    {
        $class = $request->get('class');
        
        if (!$class) {
            return response()->json(['sections' => []]);
        }
        
        // First try to get sections from Section model
        try {
            $sections = Section::where('class', $class)
                ->whereNotNull('name')
                ->distinct()
                ->pluck('name')
                ->sort()
                ->values();
        } catch (\Exception $e) {
            // Fallback to students table
            $hasSection = Schema::hasColumn('students', 'section');
            if ($hasSection) {
                try {
                    $sections = Student::where('class', $class)
                        ->whereNotNull('section')
                        ->distinct()
                        ->pluck('section')
                        ->sort()
                        ->values();
                } catch (\Exception $e2) {
                    $sections = collect(['A', 'B', 'C', 'D', 'E']);
                }
            } else {
                $sections = collect(['A', 'B', 'C', 'D', 'E']);
            }
        }
        
        if ($sections->isEmpty()) {
            $sections = collect(['A', 'B', 'C', 'D', 'E']);
        }
        
        return response()->json(['sections' => $sections]);
    }
}

