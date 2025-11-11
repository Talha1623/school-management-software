<?php

namespace App\Http\Controllers;

use App\Models\FeeDecrementPercentage;
use App\Models\Accountant;
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
        $accountants = Accountant::orderBy('name')->get();
        
        return view('accounting.fee-document.decrement-percentage', compact('accountants'));
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

