<?php

namespace App\Http\Controllers;

use App\Models\ClassModel;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GenerateSalaryController extends Controller
{
    /**
     * Display the generate salary form.
     */
    public function index(): View
    {
        // Get campuses from classes or sections
        $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
        $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
        $campuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
        
        // If no data exists, provide defaults
        if ($campuses->isEmpty()) {
            $campuses = collect(['Main Campus', 'Branch Campus 1', 'Branch Campus 2']);
        }

        // Get current month and year as defaults
        $currentMonth = date('m');
        $currentYear = date('Y');

        return view('salary-loan.generate-salary', compact('campuses', 'currentMonth', 'currentYear'));
    }

    /**
     * Process the salary generation.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string', 'max:255'],
            'month' => ['required', 'string', 'max:255'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'deduction_per_late_arrival' => ['nullable', 'numeric', 'min:0'],
        ]);

        // TODO: Implement salary generation logic here
        // This is where you would process the salary generation based on the form data
        
        return redirect()
            ->route('salary-loan.generate-salary')
            ->with('success', 'Salary generation process initiated successfully!');
    }
}

