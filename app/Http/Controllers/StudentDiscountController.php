<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentDiscount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentDiscountController extends Controller
{
    public function index(Request $request): View
    {
        $query = StudentDiscount::with('student')->orderBy('id', 'desc');

        if ($request->filled('search')) {
            $search = trim($request->search);
            if ($search !== '') {
                $searchLower = strtolower($search);
                $query->where(function ($q) use ($searchLower) {
                    $q->whereRaw('LOWER(student_code) LIKE ?', ["%{$searchLower}%"])
                        ->orWhereRaw('LOWER(discount_title) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }

        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;

        $discounts = $query->paginate($perPage)->withQueryString();

        return view('accounting.parent-wallet.discount-student', compact('discounts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_code' => ['required', 'string', 'max:255'],
            'discount_title' => ['required', 'string', 'max:255'],
            'discount_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $student = Student::where('student_code', $validated['student_code'])->first();
        if (!$student) {
            return redirect()
                ->route('accounting.parent-wallet.discount-student')
                ->with('error', 'Student not found with this code.');
        }

        StudentDiscount::create([
            'student_code' => $validated['student_code'],
            'discount_title' => $validated['discount_title'],
            'discount_amount' => $validated['discount_amount'],
            'created_by' => auth()->check() ? (auth()->user()->name ?? null) : null,
        ]);

        return redirect()
            ->route('accounting.parent-wallet.discount-student')
            ->with('success', 'Discount added successfully.');
    }

    public function update(Request $request, StudentDiscount $studentDiscount): RedirectResponse
    {
        $validated = $request->validate([
            'student_code' => ['required', 'string', 'max:255'],
            'discount_title' => ['required', 'string', 'max:255'],
            'discount_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $student = Student::where('student_code', $validated['student_code'])->first();
        if (!$student) {
            return redirect()
                ->route('accounting.parent-wallet.discount-student')
                ->with('error', 'Student not found with this code.');
        }

        $studentDiscount->update($validated);

        return redirect()
            ->route('accounting.parent-wallet.discount-student')
            ->with('success', 'Discount updated successfully.');
    }

    public function destroy(StudentDiscount $studentDiscount): RedirectResponse
    {
        $studentDiscount->delete();

        return redirect()
            ->route('accounting.parent-wallet.discount-student')
            ->with('success', 'Discount deleted successfully.');
    }
}
