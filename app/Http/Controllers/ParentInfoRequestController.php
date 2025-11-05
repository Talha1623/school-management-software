<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class ParentInfoRequestController extends Controller
{
    /**
     * Display the parent info request page.
     */
    public function index(): View
    {
        // Static options for dropdowns
        $campuses = collect(['Main Campus', 'Branch Campus 1', 'Branch Campus 2']);
        $parentTypes = collect(['Father', 'Mother', 'Guardian']);
        $passValidities = collect(['1 Month', '3 Months', '6 Months', '1 Year']);
        $cardTypes = collect(['Regular', 'VIP', 'Premium']);
        
        return view('parent.info-request', compact('campuses', 'parentTypes', 'passValidities', 'cardTypes'));
    }

    /**
     * Filter data based on criteria.
     */
    public function filter(Request $request)
    {
        // This will filter data based on the form inputs
        // For now, just redirect back with filters applied
        return redirect()
            ->route('parent.info-request')
            ->with('success', 'Filter applied successfully!');
    }
}

