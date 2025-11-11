<?php

namespace App\Http\Controllers;

use App\Models\CustomFee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomFeeController extends Controller
{
    /**
     * Display the generate custom fee form.
     */
    public function create(): View
    {
        return view('accounting.generate-custom-fee');
    }

    /**
     * Store the generated custom fee.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string', 'max:255'],
            'class' => ['required', 'string', 'max:255'],
            'section' => ['required', 'string', 'max:255'],
            'fee_type' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        CustomFee::create($validated);

        return redirect()
            ->route('accounting.generate-custom-fee')
            ->with('success', 'Custom fee generated successfully!');
    }
}

