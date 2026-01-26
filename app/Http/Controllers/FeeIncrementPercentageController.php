<?php

namespace App\Http\Controllers;

use App\Models\FeeIncrementPercentage;
use App\Models\Accountant;
use App\Models\Campus;
use App\Models\ClassModel;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeeIncrementPercentageController extends Controller
{
    /**
     * Show the fee increment percentage form.
     */
    public function create(): View
    {
        $campuses = Campus::orderBy('campus_name', 'asc')->pluck('campus_name');
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
            $campuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
        }

        $accountants = Accountant::orderBy('name')->get();

        return view('accounting.fee-increment.percentage', compact('accountants', 'campuses'));
    }

    /**
     * Get classes by campus for fee increment percentage.
     */
    public function getClassesByCampus(Request $request)
    {
        $campus = $request->get('campus');
        $query = ClassModel::whereNotNull('class_name');
        if ($campus) {
            $query->where('campus', $campus);
        }
        $classes = $query->distinct()->pluck('class_name')->sort()->values();
        return response()->json($classes);
    }

    /**
     * Get sections by class and campus for fee increment percentage.
     */
    public function getSectionsByClass(Request $request)
    {
        $class = $request->get('class');
        $campus = $request->get('campus');
        $query = Section::whereNotNull('name');
        if ($campus) {
            $query->where('campus', $campus);
        }
        if ($class) {
            $query->where('class', $class);
        }
        $sections = $query->distinct()->pluck('name')->sort()->values();
        return response()->json($sections);
    }

    /**
     * Store a newly created fee increment percentage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['nullable', 'string', 'max:255'],
            'class' => ['nullable', 'string', 'max:255'],
            'section' => ['nullable', 'string', 'max:255'],
            'increase' => ['required', 'numeric', 'min:0'],
            'accountant' => ['nullable', 'string', 'max:255'],
            'date' => ['required', 'date'],
        ]);

        FeeIncrementPercentage::create($validated);

        return redirect()
            ->route('accounting.fee-increment.percentage')
            ->with('success', 'Fee increment by percentage recorded successfully!');
    }
}

