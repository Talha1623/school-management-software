<?php

namespace App\Http\Controllers;

use App\Models\ParticularExamGrade;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Exam;
use App\Models\Campus;
use App\Models\GeneralSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ParticularExamGradeController extends Controller
{
    /**
     * Display a listing of particular exam grades.
     */
    public function index(Request $request): View
    {
        $filterCampus = $request->get('filter_campus');
        $filterExam = $request->get('filter_exam');
        $filterSession = $request->get('filter_session');

        // Get active campuses from Campus model first (primary source)
        $campusesFromModel = Campus::whereNotNull('campus_name')->orderBy('campus_name', 'asc')->pluck('campus_name');
        
        // Get active campuses list for filtering
        $activeCampuses = $campusesFromModel
            ->map(fn($c) => strtolower(trim($c)))
            ->filter(fn($c) => !empty($c))
            ->unique()
            ->values()
            ->toArray();
        
        // Also get campuses from ClassModel and Section, but only if they exist in Campus model
        $campusesFromClasses = ClassModel::whereNotNull('campus')
            ->whereNotNull('class_name') // Only active classes
            ->distinct()
            ->pluck('campus')
            ->filter(function($campus) use ($activeCampuses) {
                return !empty($activeCampuses) && in_array(strtolower(trim($campus)), $activeCampuses);
            });
        
        $campusesFromSections = Section::whereNotNull('campus')
            ->whereNotNull('name') // Only active sections
            ->distinct()
            ->pluck('campus')
            ->filter(function($campus) use ($activeCampuses) {
                return !empty($activeCampuses) && in_array(strtolower(trim($campus)), $activeCampuses);
            });
        
        // Merge all campuses and get unique values (only active campuses)
        $campuses = $campusesFromModel
            ->merge($campusesFromClasses)
            ->merge($campusesFromSections)
            ->unique()
            ->sort()
            ->values();
        
        // Convert to collection of objects with campus_name property
        $campuses = $campuses->map(function($campus) {
            return (object)['campus_name' => $campus];
        });

        $settings = GeneralSetting::getSettings();
        $runningSession = $settings->running_session ? trim($settings->running_session) : null;
        $sessions = Exam::whereNotNull('session')->distinct()->pluck('session')->sort()->values();
        if ($sessions->isEmpty() && $runningSession) {
            $sessions = collect([$runningSession]);
        } elseif ($runningSession && !$sessions->contains($runningSession)) {
            $sessions = $sessions->prepend($runningSession)->values();
        }

        // Exams: only for selected campus (and session). Case-insensitive match. Show exams in dropdown only when campus is selected.
        // Only show exams for active campuses
        $exams = collect();
        if ($filterCampus) {
            $campusLower = strtolower(trim($filterCampus));
            // Verify campus is active
            if (in_array($campusLower, $activeCampuses)) {
                $examsQuery = Exam::query()->whereNotNull('exam_name')
                    ->whereRaw('LOWER(TRIM(campus)) = ?', [$campusLower]);
                if ($filterSession) {
                    $examsQuery->whereRaw('LOWER(TRIM(session)) = ?', [strtolower(trim($filterSession))]);
                }
                $exams = $examsQuery->distinct()->pluck('exam_name')->sort()->values();
            }
        }

        $showResults = $filterCampus || $filterExam || $filterSession;
        $grades = collect();
        if ($showResults) {
            $gradesQuery = ParticularExamGrade::query();
            if ($filterCampus) {
                $gradesQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
            }
            if ($filterExam) {
                $gradesQuery->whereRaw('LOWER(TRIM(for_exam)) = ?', [strtolower(trim($filterExam))]);
            }
            if ($filterSession) {
                $gradesQuery->whereRaw('LOWER(TRIM(session)) = ?', [strtolower(trim($filterSession))]);
            }
            $grades = $gradesQuery->orderBy('from_percentage', 'desc')->get();
        }

        return view('exam.grades.particular', compact(
            'campuses',
            'sessions',
            'exams',
            'filterCampus',
            'filterExam',
            'filterSession',
            'grades',
            'showResults'
        ));
    }

    /**
     * Get exams by campus (and optional session) for AJAX dropdown.
     */
    public function getExamsByCampus(Request $request): JsonResponse
    {
        $campus = $request->get('campus');
        if (!$campus || !is_string($campus)) {
            return response()->json(['exams' => []]);
        }
        $campus = trim($campus);
        $campusLower = strtolower($campus);
        
        // Get active campuses list for filtering
        $activeCampuses = Campus::whereNotNull('campus_name')
            ->orderBy('campus_name', 'asc')
            ->pluck('campus_name')
            ->map(fn($c) => strtolower(trim($c)))
            ->filter(fn($c) => !empty($c))
            ->unique()
            ->values()
            ->toArray();
        
        // Verify campus is active
        if (!in_array($campusLower, $activeCampuses)) {
            return response()->json(['exams' => []]);
        }
        
        $session = $request->get('session');
        $query = Exam::query()->whereNotNull('exam_name')
            ->whereRaw('LOWER(TRIM(campus)) = ?', [$campusLower]);
        if ($session && is_string($session) && trim($session) !== '') {
            $query->whereRaw('LOWER(TRIM(session)) = ?', [strtolower(trim($session))]);
        }
        $exams = $query->distinct()->pluck('exam_name')->sort()->values();
        return response()->json(['exams' => $exams]);
    }

    /**
     * Store a newly created particular exam grade.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'from_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'to_percentage' => ['required', 'numeric', 'min:0', 'max:100', 'gte:from_percentage'],
            'grade_points' => ['required', 'numeric', 'min:0'],
            'for_exam' => ['required', 'string', 'max:255'],
            'session' => ['required', 'string', 'max:255'],
        ]);

        ParticularExamGrade::create($validated);

        return redirect()
            ->route('exam.grades.particular', [
                'filter_campus' => $validated['campus'],
                'filter_exam' => $validated['for_exam'],
                'filter_session' => $validated['session'],
            ])
            ->with('success', 'Grade created successfully!');
    }

    /**
     * Update the specified particular exam grade.
     */
    public function update(Request $request, ParticularExamGrade $particularExamGrade): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'from_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'to_percentage' => ['required', 'numeric', 'min:0', 'max:100', 'gte:from_percentage'],
            'grade_points' => ['required', 'numeric', 'min:0'],
            'for_exam' => ['required', 'string', 'max:255'],
            'session' => ['required', 'string', 'max:255'],
        ]);

        $particularExamGrade->update($validated);

        return redirect()
            ->route('exam.grades.particular', [
                'filter_campus' => $validated['campus'],
                'filter_exam' => $validated['for_exam'],
                'filter_session' => $validated['session'],
            ])
            ->with('success', 'Grade updated successfully!');
    }

    /**
     * Remove the specified particular exam grade.
     */
    public function destroy(ParticularExamGrade $particularExamGrade): RedirectResponse
    {
        $particularExamGrade->delete();

        return redirect()
            ->back()
            ->with('success', 'Grade deleted successfully!');
    }
}
