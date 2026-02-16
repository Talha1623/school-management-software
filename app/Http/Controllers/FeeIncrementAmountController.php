<?php

namespace App\Http\Controllers;

use App\Models\FeeIncrementAmount;
use App\Models\Accountant;
use App\Models\Campus;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class FeeIncrementAmountController extends Controller
{
    /**
     * Show the fee increment amount form.
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

        // Get logged in user name
        $loggedInUser = Auth::guard('admin')->user() ?? Auth::guard('accountant')->user() ?? Auth::user();
        $loggedInUserName = $loggedInUser ? ($loggedInUser->name ?? 'Admin') : 'Admin';

        return view('accounting.fee-increment.amount', compact('accountants', 'campuses', 'loggedInUserName'));
    }

    /**
     * Get classes by campus for fee increment amount.
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
     * Get sections by class and campus for fee increment amount.
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
     * Get students by filters for fee increment amount.
     */
    public function getStudents(Request $request): JsonResponse
    {
        $campus = $request->get('campus');
        $class = $request->get('class');
        $section = $request->get('section');

        $query = Student::query()
            ->whereNotNull('student_code')
            ->where('student_code', '!=', '');

        if ($campus) {
            $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        if ($class) {
            $query->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
        }
        if ($section) {
            $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($section))]);
        }

        $students = $query->orderBy('student_name')
            ->get(['id', 'student_name', 'student_code', 'father_name', 'class', 'section', 'campus', 'monthly_fee']);

        return response()->json([
            'students' => $students,
        ]);
    }

    /**
     * Store a newly created fee increment amount.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['nullable', 'string', 'max:255'],
            'class' => ['nullable', 'string', 'max:255'],
            'section' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'accountant' => ['nullable', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'selected_students' => ['required', 'array', 'min:1'],
            'selected_students.*' => ['exists:students,id'],
        ]);

        $amount = (float) $validated['amount'];
        $students = Student::whereIn('id', $validated['selected_students'])->get();
        $updatedCount = 0;

        foreach ($students as $student) {
            $currentFee = (float) ($student->monthly_fee ?? 0);
            $newFee = $currentFee + $amount;
            $student->update([
                'monthly_fee' => round($newFee, 2),
            ]);
            $updatedCount++;
        }

        FeeIncrementAmount::create($validated);

        return redirect()
            ->route('accounting.fee-increment.amount')
            ->with('success', "Fee increment applied to {$updatedCount} student(s) successfully!");
    }
}

