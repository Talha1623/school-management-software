<?php

namespace App\Http\Controllers;

use App\Models\ClassToShow;
use App\Models\Campus;
use App\Models\ClassModel;
use App\Models\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassToShowController extends Controller
{
    /**
     * Display the Classes to Show page.
     */
    public function index(Request $request): View
    {
        $query = ClassToShow::query();
        
        // Search functionality
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function($q) use ($search) {
                $q->where('campus', 'like', "%{$search}%")
                  ->orWhere('class', 'like', "%{$search}%")
                  ->orWhere('section', 'like', "%{$search}%");
            });
        }
        
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        $classes = $query->orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();
        
        // Get campuses for dropdown
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromSections = \App\Models\Section::whereNotNull('campus')->distinct()->pluck('campus');
            $allCampuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort();
            $campuses = $allCampuses->map(function($campus) {
                return (object)['campus_name' => $campus];
            });
        }
        
        return view('website-management.classes-show', compact('classes', 'campuses'));
    }

    /**
     * Store a newly created class.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'campus' => ['nullable', 'string', 'max:255'],
                'class' => ['nullable', 'string', 'max:255'],
                'section' => ['nullable', 'string', 'max:255'],
                'class_timing_from' => ['nullable', 'string', 'max:255'],
                'class_timing_to' => ['nullable', 'string', 'max:255'],
                'student_age_limit_from' => ['nullable', 'string', 'max:255'],
                'student_age_limit_to' => ['nullable', 'string', 'max:255'],
                'class_tuition_fee' => ['nullable', 'string', 'max:255'],
                'show_on_website_main_page' => ['required', 'in:Yes,No'],
            ]);

            $classToShow = ClassToShow::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Class added successfully!',
                'class' => $classToShow
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add class: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified class.
     */
    public function update(Request $request, ClassToShow $classToShow): JsonResponse
    {
        try {
            $validated = $request->validate([
                'campus' => ['nullable', 'string', 'max:255'],
                'class' => ['nullable', 'string', 'max:255'],
                'section' => ['nullable', 'string', 'max:255'],
                'class_timing_from' => ['nullable', 'string', 'max:255'],
                'class_timing_to' => ['nullable', 'string', 'max:255'],
                'student_age_limit_from' => ['nullable', 'string', 'max:255'],
                'student_age_limit_to' => ['nullable', 'string', 'max:255'],
                'class_tuition_fee' => ['nullable', 'string', 'max:255'],
                'show_on_website_main_page' => ['required', 'in:Yes,No'],
            ]);

            $classToShow->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Class updated successfully!',
                'class' => $classToShow->fresh()
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update class: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified class.
     */
    public function destroy(Request $request, ClassToShow $classToShow): JsonResponse
    {
        $classToShow->delete();

        return response()->json([
            'success' => true,
            'message' => 'Class deleted successfully!'
        ]);
    }

    /**
     * Get a single class for editing.
     */
    public function show(ClassToShow $classToShow): JsonResponse
    {
        return response()->json([
            'success' => true,
            'class' => $classToShow
        ]);
    }

    public function getClassesByCampus(Request $request): JsonResponse
    {
        $campus = $request->get('campus');
        $query = ClassModel::whereNotNull('class_name')
            ->where('class_name', '!=', '');
        if ($campus) {
            $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        $classes = $query->distinct()->orderBy('class_name')->pluck('class_name')->values();

        return response()->json(['classes' => $classes]);
    }

    public function getSectionsByClass(Request $request): JsonResponse
    {
        $campus = $request->get('campus');
        $class = $request->get('class');

        $query = Section::whereNotNull('name');
        if ($campus) {
            $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        if ($class) {
            $query->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
        }

        $sections = $query->distinct()->orderBy('name')->pluck('name')->values();

        return response()->json(['sections' => $sections]);
    }
}
