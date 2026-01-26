<?php

namespace App\Http\Controllers;

use App\Models\FeeDecrementPercentage;
use App\Models\Accountant;
use App\Models\Campus;
use App\Models\ClassModel;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeeDecrementPercentageController extends Controller
{
    /**
     * Show the fee decrement percentage form.
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
        
        return view('accounting.fee-document.decrement-percentage', compact('accountants', 'campuses'));
    }

    /**
     * Get classes by campus for fee decrement percentage.
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
     * Get sections by class and campus for fee decrement percentage.
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
     * Get accountants by campus for fee decrement percentage.
     */
    public function getAccountantsByCampus(Request $request)
    {
        $campus = $request->get('campus');
        $query = Accountant::orderBy('name', 'asc');
        if ($campus) {
            $query->where('campus', $campus);
        }
        return response()->json($query->get(['id', 'name', 'campus']));
    }

    /**
     * Store a newly created fee decrement percentage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['nullable', 'string', 'max:255'],
            'class' => ['nullable', 'string', 'max:255'],
            'section' => ['nullable', 'string', 'max:255'],
            'decrement' => ['required', 'numeric', 'min:0'],
            'accountant' => ['nullable', 'string', 'max:255'],
            'date' => ['required', 'date'],
        ]);

        FeeDecrementPercentage::create($validated);

        return redirect()
            ->route('accounting.fee-document.decrement-percentage')
            ->with('success', 'Fee decrement by percentage recorded successfully!');
    }
}

