<?php

namespace App\Http\Controllers;

use App\Models\MonthlyFee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MonthlyFeeController extends Controller
{
    /**
     * Display the generate monthly fee form.
     */
    public function create(): View
    {
        return view('accounting.generate-monthly-fee');
    }

    /**
     * Store the generated monthly fee.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string', 'max:255'],
            'class' => ['required', 'string', 'max:255'],
            'section' => ['required', 'string', 'max:255'],
            'fee_month' => ['required', 'string', 'max:255'],
            'fee_year' => ['required', 'string', 'max:255'],
            'due_date' => ['required', 'date'],
            'late_fee' => ['nullable', 'numeric', 'min:0'],
        ]);

        MonthlyFee::create($validated);

        return redirect()
            ->route('accounting.generate-monthly-fee')
            ->with('success', 'Monthly fee generated successfully!');
    }
}

