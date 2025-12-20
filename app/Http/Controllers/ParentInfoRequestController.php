<?php

namespace App\Http\Controllers;

use App\Models\ParentAccount;
use App\Models\ParentAccountRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ParentInfoRequestController extends Controller
{
    /**
     * Display the parent info request page.
     */
    public function index(): View
    {
        // Calculate statistics
        $totalParents = ParentAccount::count();
        
        // Active parents: those who have students linked
        $activeParents = ParentAccount::has('students')->count();
        
        // Pending requests: parent account requests with pending status
        $pendingRequests = ParentAccountRequest::where(function($query) {
            $query->where('request_status', 'pending')
                  ->orWhereNull('request_status');
        })->count();
        
        // Inactive parents: those who don't have students linked
        $inactiveParents = ParentAccount::doesntHave('students')->count();
        
        // Static options for dropdowns
        $campuses = collect(['Main Campus', 'Branch Campus 1', 'Branch Campus 2']);
        $parentTypes = collect(['Father', 'Mother', 'Guardian']);
        $passValidities = collect(['1 Month', '3 Months', '6 Months', '1 Year']);
        $cardTypes = collect(['Regular', 'VIP', 'Premium']);
        
        return view('parent.info-request', compact(
            'campuses', 
            'parentTypes', 
            'passValidities', 
            'cardTypes',
            'totalParents',
            'activeParents',
            'pendingRequests',
            'inactiveParents'
        ));
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

