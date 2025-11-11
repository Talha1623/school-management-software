<?php

namespace App\Http\Controllers;

use App\Models\SalaryIncrementAmount;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Accountant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalaryIncrementAmountController extends Controller
{
    /**
     * Display the increment by amount form.
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

        return view('salary-loan.increment.amount', compact('campuses', 'accountants', 'currentDate'));
    }

    /**
     * Store the salary increment amount.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string', 'max:255'],
            'increase' => ['required', 'numeric', 'min:0'],
            'accountant' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
        ]);

        SalaryIncrementAmount::create($validated);

        return redirect()
            ->route('salary-loan.increment.amount')
            ->with('success', 'Salary increment by amount created successfully!');
    }
}

