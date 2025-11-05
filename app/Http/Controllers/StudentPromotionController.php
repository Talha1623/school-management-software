<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class StudentPromotionController extends Controller
{
    /**
     * Display the student promotion page.
     */
    public function index(): View
    {
        // Check if students table exists and has required columns
        $hasCampus = Schema::hasColumn('students', 'campus');
        $hasClass = Schema::hasColumn('students', 'class');
        $hasSection = Schema::hasColumn('students', 'section');
        
        // Get unique values for dropdowns
        if ($hasCampus) {
            try {
                $campuses = Student::whereNotNull('campus')->distinct()->pluck('campus')->sort()->values();
            } catch (\Exception $e) {
                $campuses = collect();
            }
        } else {
            $campuses = collect();
        }
        
        if ($hasClass) {
            try {
                $classes = Student::distinct()->pluck('class')->sort()->values();
            } catch (\Exception $e) {
                $classes = collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']);
            }
        } else {
            // Default classes if column doesn't exist
            $classes = collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']);
        }
        
        if ($hasSection) {
            try {
                $sections = Student::whereNotNull('section')->distinct()->pluck('section')->sort()->values();
            } catch (\Exception $e) {
                $sections = collect(['A', 'B', 'C', 'D', 'E']);
            }
        } else {
            // Default sections if column doesn't exist
            $sections = collect(['A', 'B', 'C', 'D', 'E']);
        }
        
        return view('student.promotion', compact('campuses', 'classes', 'sections'));
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
}

