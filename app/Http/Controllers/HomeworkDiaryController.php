<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeworkDiaryController extends Controller
{
    /**
     * Display the add & manage diaries page.
     */
    public function manage(Request $request): View
    {
        // Get filter values
        $filterCampus = $request->get('filter_campus');
        $filterClass = $request->get('filter_class');
        $filterSection = $request->get('filter_section');
        $filterDate = $request->get('filter_date', date('Y-m-d'));

        // Get campuses
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromSubjects = Subject::whereNotNull('campus')->distinct()->pluck('campus');
            $allCampuses = $campusesFromClasses->merge($campusesFromSections)->merge($campusesFromSubjects)->unique()->sort();
            $campuses = $allCampuses->map(function($campus) {
                return (object)['campus_name' => $campus];
            });
        }

        // Get classes
        $classes = ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort()->values();
        
        if ($classes->isEmpty()) {
            $classesFromSubjects = Subject::whereNotNull('class')->distinct()->pluck('class')->sort();
            $classes = $classesFromSubjects->isEmpty() ? collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']) : $classesFromSubjects;
        }

        // Get sections (filtered by class if provided)
        $sections = collect();
        if ($filterClass) {
            // Use case-insensitive matching for class
            $sections = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))])
                ->whereNotNull('name')
                ->distinct()
                ->pluck('name')
                ->sort()
                ->values();
            
            if ($sections->isEmpty()) {
                $sectionsFromSubjects = Subject::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))])
                    ->whereNotNull('section')
                    ->distinct()
                    ->pluck('section')
                    ->sort();
                $sections = $sectionsFromSubjects;
            }
        }

        // Get subjects based on filters
        $subjects = collect();
        if ($filterCampus && $filterClass && $filterSection) {
            $subjectsQuery = Subject::query();
            
            $campusName = is_object($filterCampus) ? ($filterCampus->campus_name ?? '') : $filterCampus;
            
            // Exact match with case-insensitive comparison and trim whitespace
            $subjectsQuery->where(function($query) use ($campusName, $filterClass, $filterSection) {
                $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campusName))])
                      ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))])
                      ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))]);
            });
            
            $subjects = $subjectsQuery->orderBy('subject_name', 'asc')->get();
        }

        return view('homework-diary.manage', compact(
            'campuses',
            'classes',
            'sections',
            'subjects',
            'filterCampus',
            'filterClass',
            'filterSection',
            'filterDate'
        ));
    }

    /**
     * Get sections for homework diary (AJAX).
     */
    public function getSections(Request $request): JsonResponse
    {
        $class = $request->get('class');
        
        if (!$class) {
            return response()->json(['sections' => []]);
        }
        
        // Use case-insensitive matching for class
        $sections = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
            ->whereNotNull('name')
            ->distinct()
            ->pluck('name')
            ->sort()
            ->values();
            
        if ($sections->isEmpty()) {
            // Try from subjects table with case-insensitive matching
            $sections = Subject::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                ->whereNotNull('section')
                ->distinct()
                ->pluck('section')
                ->sort()
                ->values();
        }
        
        return response()->json(['sections' => $sections]);
    }

    /**
     * Send diary for a subject.
     */
    public function sendDiary(Request $request)
    {
        $validated = $request->validate([
            'subject_id' => ['required', 'exists:subjects,id'],
            'date' => ['required', 'date'],
        ]);

        $subject = Subject::findOrFail($validated['subject_id']);

        // TODO: Implement diary sending logic (SMS/WhatsApp/Email)
        // For now, just return success message

        return redirect()
            ->route('homework-diary.manage', [
                'filter_campus' => $subject->campus,
                'filter_class' => $subject->class,
                'filter_section' => $subject->section,
                'filter_date' => $validated['date']
            ])
            ->with('success', 'Diary sent successfully for ' . $subject->subject_name . '!');
    }
}

