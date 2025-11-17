<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\BehaviorRecord;
use App\Models\Campus;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Carbon\Carbon;

class ProgressTrackingController extends Controller
{
    /**
     * Display the progress tracking page.
     */
    public function index(Request $request): View
    {
        $student = null;
        $behaviorRecords = collect();
        $behaviorSummary = [];
        $currentYearPoints = 0;
        $lastYearPoints = 0;
        $campus = null;
        
        // Search for student
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $student = Student::where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(student_name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhere('student_code', 'like', "%{$search}%")
                      ->orWhere('gr_number', 'like', "%{$search}%");
                })->first();
                
                // If student found, get their behavior records
                if ($student) {
                    // Get all behavior records
                    $behaviorRecords = BehaviorRecord::where('student_id', $student->id)
                        ->orderBy('date', 'desc')
                        ->get();
                    
                    // Calculate current year and last year points
                    $currentYear = Carbon::now()->year;
                    $lastYear = $currentYear - 1;
                    
                    $currentYearRecords = $behaviorRecords->filter(function($record) use ($currentYear) {
                        return Carbon::parse($record->date)->year == $currentYear;
                    });
                    
                    $lastYearRecords = $behaviorRecords->filter(function($record) use ($lastYear) {
                        return Carbon::parse($record->date)->year == $lastYear;
                    });
                    
                    $currentYearPoints = $currentYearRecords->sum('points');
                    $lastYearPoints = $lastYearRecords->sum('points');
                    
                    // Group by type for summary table
                    $behaviorSummary = $behaviorRecords->groupBy('type')->map(function($records, $type) {
                        return [
                            'type' => $type,
                            'points' => $records->sum('points'),
                            'count' => $records->count()
                        ];
                    })->values()->toArray();
                    
                    // Get campus information
                    if ($student->campus) {
                        $campus = Campus::where('campus_name', $student->campus)->first();
                    }
                }
            }
        }
        
        return view('student-behavior.progress-tracking', compact(
            'student', 
            'behaviorRecords', 
            'behaviorSummary',
            'currentYearPoints',
            'lastYearPoints',
            'campus'
        ));
    }
}

