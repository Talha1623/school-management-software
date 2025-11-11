<?php

namespace App\Http\Controllers;

use App\Models\ClassModel;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WhatsAppController extends Controller
{
    /**
     * Display the message to parent page.
     */
    public function parent(Request $request): View
    {
        // Get campuses for dropdown
        $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
        $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
        $campuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
        
        if ($campuses->isEmpty()) {
            $campuses = collect(['Main Campus', 'Branch Campus 1', 'Branch Campus 2']);
        }

        // Get classes
        $classes = ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort()->values();
        
        if ($classes->isEmpty()) {
            $classes = collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']);
        }

        // Get sections (will be loaded via AJAX based on class)
        $sections = collect();

        return view('whatsapp.parent', compact('campuses', 'classes', 'sections'));
    }

    /**
     * Display the message to staff page.
     */
    public function staff(Request $request): View
    {
        // Get campuses for dropdown
        $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
        $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
        $campuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
        
        if ($campuses->isEmpty()) {
            $campuses = collect(['Main Campus', 'Branch Campus 1', 'Branch Campus 2']);
        }

        return view('whatsapp.staff', compact('campuses'));
    }

    /**
     * Display the message history page.
     */
    public function history(Request $request): View
    {
        // Get filter values
        $search = $request->get('search');
        
        // For now, return empty collection (can be connected to message history table later)
        $messageHistory = collect();
        
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        // Paginate empty collection for now
        $currentPage = $request->get('page', 1);
        $items = $messageHistory->slice(($currentPage - 1) * $perPage, $perPage)->values();
        
        // Create a simple paginator manually
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $messageHistory->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('whatsapp.history', compact('paginator', 'search'));
    }
}

