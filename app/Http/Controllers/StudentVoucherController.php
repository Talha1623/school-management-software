<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentVoucherController extends Controller
{
    /**
     * Show the student vouchers page with filters.
     */
    public function index(Request $request): View
    {
        $query = Student::query();
        
        // Apply filters
        if ($request->filled('class')) {
            $query->where('class', $request->class);
        }
        
        if ($request->filled('section')) {
            $query->where('section', $request->section);
        }
        
        // Type and vouchers_for are filter options, not stored in Student model
        // They will be used for voucher generation
        
        $students = $query->orderBy('student_name')->paginate(20)->withQueryString();
        
        return view('accounting.fee-voucher.student', compact('students'));
    }
}

