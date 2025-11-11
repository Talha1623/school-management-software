<?php

namespace App\Http\Controllers;

use App\Models\CustomPayment;
use App\Models\Accountant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomPaymentController extends Controller
{
    /**
     * Show the custom payment form.
     */
    public function create(): View
    {
        $accountants = Accountant::orderBy('name')->get();
        
        return view('accounting.direct-payment.custom', compact('accountants'));
    }

    /**
     * Store a newly created custom payment.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['nullable', 'string', 'max:255'],
            'payment_title' => ['required', 'string', 'max:255'],
            'payment_amount' => ['required', 'numeric', 'min:0'],
            'accountant' => ['nullable', 'string', 'max:255'],
            'method' => ['required', 'string', 'max:255'],
            'notify_admin' => ['required', 'string', 'in:Yes,No'],
            'payment_date' => ['required', 'date'],
        ]);

        CustomPayment::create($validated);

        return redirect()
            ->route('accounting.direct-payment.custom')
            ->with('success', 'Custom payment recorded successfully!');
    }
}

