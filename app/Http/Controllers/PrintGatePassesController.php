<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use App\Models\GeneralSetting;
use App\Models\ParentAccount;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrintGatePassesController extends Controller
{
    /**
     * Display the print gate passes page with dynamic data.
     */
    public function index(Request $request): View
    {
        // Get all campuses from database
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        
        // Parent types (can be extended based on your requirements)
        $parentTypes = ['Father', 'Mother', 'Guardian'];
        
        // Pass validity options
        $passValidities = ['1 Month', '3 Months', '6 Months', '1 Year'];
        
        // Card types (only QR cards)
        $cardTypes = ['With QR Code'];
        
        // Get filtered parents for gate passes
        $parents = collect();
        
        if ($request->has('campus') || $request->has('parent_type') || $request->has('pass_validity') || $request->has('card_type')) {
            $parents = $this->getFilteredParents($request);
        }
        
        return view('parent.print-gate-passes', compact('campuses', 'parentTypes', 'passValidities', 'cardTypes', 'parents'));
    }
    
    /**
     * Get filtered parents based on criteria.
     */
    private function getFilteredParents(Request $request)
    {
        $query = ParentAccount::query();
        
        // Filter by campus (through students)
        if ($request->filled('campus') && $request->campus != '') {
            $campus = $request->campus;
            $studentIds = Student::where('campus', $campus)
                ->whereNotNull('parent_account_id')
                ->pluck('parent_account_id')
                ->unique();
            $query->whereIn('id', $studentIds);
        }
        
        // Get all parents that have at least one student
        $parents = $query->whereHas('students')->get();
        
        // If no parents found with students, get all parents
        if ($parents->isEmpty() && !$request->filled('campus')) {
            $parents = ParentAccount::all();
        }
        
        // Add additional data for each parent
        return $parents->map(function($parent) use ($request) {
            // Get student for this parent to get campus
            $student = Student::where('parent_account_id', $parent->id)->first();
            
            // Determine campus
            $campus = 'All campuses';
            if ($request->filled('campus') && $request->campus != '') {
                $campus = $request->campus;
            } elseif ($student && $student->campus) {
                $campus = $student->campus;
            }
            
            return [
                'id' => $parent->id,
                'name' => $parent->name,
                'email' => $parent->email,
                'phone' => $parent->phone,
                'campus' => $campus,
                'parent_type' => $request->parent_type ?: 'Father',
                'pass_validity' => $request->pass_validity ?: '6 Months',
                'card_type' => $request->card_type ?: 'Regular',
                'issue_date' => now()->format('d-m-Y'),
            ];
        });
    }

    private function getDesignSettings(Request $request): array
    {
        return [
            'accent_color' => $request->get('accent_color', '#003471'),
            'secondary_color' => $request->get('secondary_color', '#004a9e'),
            'gradient_color1' => $request->get('gradient_color1', '#FFFFFF'),
            'gradient_color2' => $request->get('gradient_color2', '#F8F9FA'),
            'parent_name_color' => $request->get('parent_name_color', '#1f2a44'),
            'details_text_color' => $request->get('details_text_color', '#333333'),
            'footer_text_color' => $request->get('footer_text_color', '#FFFFFF'),
        ];
    }

    /**
     * Display print view with filtered parents.
     */
    public function print(Request $request): View
    {
        $settings = GeneralSetting::getSettings();
        $designSettings = $this->getDesignSettings($request);

        if ($request->filled('parent_id')) {
            $parent = ParentAccount::find($request->parent_id);
            $parents = collect();
            if ($parent) {
                $student = Student::where('parent_account_id', $parent->id)->first();
                $parents = collect([[
                    'id' => $parent->id,
                    'name' => $parent->name,
                    'email' => $parent->email,
                    'phone' => $parent->phone,
                    'campus' => $student ? ($student->campus ?? 'All campuses') : 'All campuses',
                    'parent_type' => $request->get('parent_type', 'Father'),
                    'pass_validity' => $request->get('pass_validity', '6 Months'),
                    'card_type' => 'With QR Code',
                    'issue_date' => now()->format('d-m-Y'),
                ]]);
            }

            return view('parent.print-gate-passes-print', compact('parents', 'designSettings', 'settings'));
        }

        // Get filtered parents
        $parents = collect();
        
        if ($request->has('campus') || $request->has('parent_type') || $request->has('pass_validity') || $request->has('card_type')) {
            $parents = $this->getFilteredParents($request);
        } else {
            // If no filter, get all parents
            $parents = ParentAccount::all()->map(function($parent) {
                $student = Student::where('parent_account_id', $parent->id)->first();
                return [
                    'id' => $parent->id,
                    'name' => $parent->name,
                    'email' => $parent->email,
                    'phone' => $parent->phone,
                    'campus' => $student ? ($student->campus ?? 'All campuses') : 'All campuses',
                    'parent_type' => 'Father',
                    'pass_validity' => '6 Months',
                    'card_type' => 'Regular',
                    'issue_date' => now()->format('d-m-Y'),
                ];
            });
        }
        
        return view('parent.print-gate-passes-print', compact('parents', 'designSettings', 'settings'));
    }
}

