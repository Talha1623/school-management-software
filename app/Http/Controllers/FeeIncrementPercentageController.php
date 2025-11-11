<?php

namespace App\Http\Controllers;

use App\Models\FeeIncrementPercentage;
use App\Models\Accountant;
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
        $accountants = Accountant::orderBy('name')->get();
        
        return view('accounting.fee-increment.percentage', compact('accountants'));
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

