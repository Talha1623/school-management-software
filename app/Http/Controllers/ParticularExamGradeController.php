<?php

namespace App\Http\Controllers;

use App\Models\ParticularExamGrade;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Exam;
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

        $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
        $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
        $campuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
        if ($campuses->isEmpty()) {
            $campuses = collect(['Main Campus', 'Branch Campus 1', 'Branch Campus 2']);
        }

        $sessions = Exam::whereNotNull('session')->distinct()->pluck('session')->sort()->values();
        if ($sessions->isEmpty()) {
            $sessions = collect(['2024-2025', '2025-2026', '2026-2027']);
        }

        $examsQuery = Exam::query();
        if ($filterCampus) {
            $examsQuery->where('campus', $filterCampus);
        }
        if ($filterSession) {
            $examsQuery->where('session', $filterSession);
        }
        $exams = $examsQuery->whereNotNull('exam_name')->distinct()->pluck('exam_name')->sort()->values();

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
