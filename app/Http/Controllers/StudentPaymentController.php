<?php

namespace App\Http\Controllers;

use App\Models\StudentPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentPaymentController extends Controller
{
    /**
     * Show the student payment form.
     */
    public function create(): View
    {
        return view('accounting.direct-payment.student');
    }

    /**
     * Store a newly created student payment.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['nullable', 'string', 'max:255'],
            'student_code' => ['required', 'string', 'max:255'],
            'payment_title' => ['required', 'string', 'max:255'],
            'payment_amount' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'method' => ['required', 'string', 'max:255'],
            'payment_date' => ['required', 'date'],
            'sms_notification' => ['required', 'string', 'in:Yes,No'],
        ]);

        StudentPayment::create($validated);

        return redirect()
            ->route('accounting.direct-payment.student')
            ->with('success', 'Payment recorded successfully!');
    }
}

