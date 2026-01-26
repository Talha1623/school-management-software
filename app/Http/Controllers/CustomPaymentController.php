<?php

namespace App\Http\Controllers;

use App\Models\CustomPayment;
use App\Models\Accountant;
use App\Models\Campus;
use App\Models\ClassModel;
use App\Models\Section;
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
        $campuses = Campus::orderBy('campus_name', 'asc')->pluck('campus_name');
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
            $campuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
        }

        $methods = ['Cash Payment', 'Bank Transfer', 'Cheque', 'Online Payment', 'Card Payment'];
        $accountants = Accountant::orderBy('name')->get();
        
        return view('accounting.direct-payment.custom', compact('campuses', 'methods', 'accountants'));
    }

    /**
     * Get accountants by campus for custom payment.
     */
    public function getAccountantsByCampus(Request $request)
    {
        $campus = $request->get('campus');
        $query = Accountant::query()->orderBy('name', 'asc');
        if ($campus) {
            $query->where('campus', $campus);
        }
        $accountants = $query->get(['id', 'name', 'campus']);

        return response()->json($accountants);
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

