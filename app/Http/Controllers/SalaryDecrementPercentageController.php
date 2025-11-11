<?php

namespace App\Http\Controllers;

use App\Models\SalaryDecrementPercentage;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Accountant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalaryDecrementPercentageController extends Controller
{
    /**
     * Display the decrement by percentage form.
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

        // Get accountants
        $accountants = Accountant::orderBy('name')->get();

        // Get current date as default
        $currentDate = date('Y-m-d');

        return view('salary-loan.decrement.percentage', compact('campuses', 'accountants', 'currentDate'));
    }

    /**
     * Store the salary decrement percentage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string', 'max:255'],
            'decrease' => ['required', 'numeric', 'min:0', 'max:100'],
            'accountant' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
        ]);

        SalaryDecrementPercentage::create($validated);

        return redirect()
            ->route('salary-loan.decrement.percentage')
            ->with('success', 'Salary decrement by percentage created successfully!');
    }
}

