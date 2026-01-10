<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\Campus;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrintStaffCardController extends Controller
{
    /**
     * Display the print staff card page with dynamic data.
     */
    public function index(Request $request)
    {
        // Get all campuses from database
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        
        // If no campuses found, get from staff
        if ($campuses->isEmpty()) {
            $campusesFromStaff = Staff::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            // Convert to collection of objects with campus_name property
            $campuses = collect();
            foreach ($campusesFromStaff as $campusName) {
                $campuses->push((object)['campus_name' => $campusName]);
            }
        }
        
        // Get staff types from designation field
        $staffTypesFromDB = Staff::whereNotNull('designation')
            ->distinct()
            ->pluck('designation')
            ->sort()
            ->values();
        
        // Predefined staff types
        $predefinedTypes = collect(['Teacher', 'Principal', 'Vice Principal', 'Administrator', 'Accountant', 'Receptionist', 'Security', 'Cleaner']);
        
        // Merge and get unique staff types
        $staffTypes = $predefinedTypes->merge($staffTypesFromDB)
            ->unique()
            ->sort()
            ->values();
        
        // Get sessions from sections
        $sessions = Section::whereNotNull('session')
            ->distinct()
            ->pluck('session')
            ->sort()
            ->values();
        
        // If no sessions found, use default years
        if ($sessions->isEmpty()) {
            $currentYear = date('Y');
            $sessions = collect([
                ($currentYear - 1) . '-' . $currentYear,
                $currentYear . '-' . ($currentYear + 1),
                ($currentYear + 1) . '-' . ($currentYear + 2),
                ($currentYear + 2) . '-' . ($currentYear + 3),
            ]);
        }
        
        // Card types
        $cardTypes = ['Regular', 'VIP', 'Premium'];
        
        return view('id-card.print-staff', compact('campuses', 'staffTypes', 'sessions', 'cardTypes'));
    }
    
    /**
     * Get filtered staff based on criteria.
     */
    private function getFilteredStaff(Request $request)
    {
        $query = Staff::query();
        
        // Filter by Campus (case-insensitive)
        if ($request->filled('campus') && $request->campus != '') {
            $campus = trim($request->campus);
            $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campus)]);
        }
        
        // Filter by Staff Type (designation) - case-insensitive
        if ($request->filled('staff_type') && $request->staff_type != '') {
            $staffType = trim($request->staff_type);
            $query->whereRaw('LOWER(TRIM(designation)) = ?', [strtolower($staffType)]);
        }
        
        // Note: Session and Card Type are filter options but don't exist in Staff model
        // They can be used for display purposes in the print view
        
        // If no filters applied, return all staff
        // Otherwise return filtered results
        return $query->orderBy('campus', 'asc')
            ->orderBy('designation', 'asc')
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * Display print view with filtered staff.
     */
    public function print(Request $request): View
    {
        // Get filtered staff
        $staff = $this->getFilteredStaff($request);
        
        // If no staff found and no filters applied, show all staff
        if ($staff->isEmpty() && !$request->filled('campus') && !$request->filled('staff_type')) {
            $staff = Staff::orderBy('campus', 'asc')
                ->orderBy('designation', 'asc')
                ->orderBy('name', 'asc')
                ->get();
        }
        
        // Get design settings from request
        $designSettings = [
            'accent_color' => $request->get('accent_color', '#003471'),
            'secondary_color' => $request->get('secondary_color', '#F08080'),
            'gradient_color1' => $request->get('gradient_color1', '#FFFFFF'),
            'gradient_color2' => $request->get('gradient_color2', '#F8F9FA'),
            'staff_name_color' => $request->get('staff_name_color', '#000000'),
            'details_text_color' => $request->get('details_text_color', '#000000'),
            'footer_text_color' => $request->get('footer_text_color', '#FFFFFF'),
            'orientation' => $request->get('orientation', 'portrait'),
            'show_monogram' => $request->get('show_monogram', 'yes'),
            'card_style' => $request->get('card_style', 'modern'),
            'border_style' => $request->get('border_style', 'rounded'),
        ];
        
        return view('id-card.print-staff-card-print', compact('staff', 'designSettings'));
    }
}

