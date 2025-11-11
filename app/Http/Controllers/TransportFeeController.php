<?php

namespace App\Http\Controllers;

use App\Models\TransportFee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransportFeeController extends Controller
{
    /**
     * Display the generate transport fee form.
     */
    public function create(): View
    {
        return view('accounting.generate-transport-fee');
    }

    /**
     * Store the generated transport fee.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string', 'max:255'],
            'class' => ['required', 'string', 'max:255'],
            'section' => ['required', 'string', 'max:255'],
            'fee_month' => ['required', 'string', 'max:255'],
            'fee_year' => ['required', 'string', 'max:255'],
        ]);

        TransportFee::create($validated);

        return redirect()
            ->route('accounting.generate-transport-fee')
            ->with('success', 'Transport fee generated successfully!');
    }
}

