<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailAlertsController extends Controller
{
    /**
     * Display the message to specific email page.
     */
    public function specific(Request $request): View
    {
        return view('email-alerts.specific');
    }

    /**
     * Display the email history page.
     */
    public function history(Request $request): View
    {
        // Get filter values
        $search = $request->get('search');
        
        // For now, return empty collection (can be connected to email history table later)
        $emailHistory = collect();
        
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        // Paginate empty collection for now
        $currentPage = $request->get('page', 1);
        $items = $emailHistory->slice(($currentPage - 1) * $perPage, $perPage)->values();
        
        // Create a simple paginator manually
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $emailHistory->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('email-alerts.history', compact('paginator', 'search'));
    }
}

