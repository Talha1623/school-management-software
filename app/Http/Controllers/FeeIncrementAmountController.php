<?php

namespace App\Http\Controllers;

use App\Models\FeeIncrementAmount;
use App\Models\Accountant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeeIncrementAmountController extends Controller
{
    /**
     * Show the fee increment amount form.
     */
    public function create(): View
    {
        $accountants = Accountant::orderBy('name')->get();
        
        return view('accounting.fee-increment.amount', compact('accountants'));
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
        ]);

        FeeIncrementAmount::create($validated);

        return redirect()
            ->route('accounting.fee-increment.amount')
            ->with('success', 'Fee increment by amount recorded successfully!');
    }
}

