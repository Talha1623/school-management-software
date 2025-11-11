<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\BehaviorRecord;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgressTrackingController extends Controller
{
    /**
     * Display the progress tracking page.
     */
    public function index(Request $request): View
    {
        $student = null;
        $behaviorRecords = collect();
        
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
                    $behaviorRecords = BehaviorRecord::where('student_id', $student->id)
                        ->orderBy('date', 'desc')
                        ->get();
                }
            }
        }
        
        return view('student-behavior.progress-tracking', compact('student', 'behaviorRecords'));
    }
}

