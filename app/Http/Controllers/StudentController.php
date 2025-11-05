<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentController extends Controller
{
    /**
     * Display a listing of students.
     */
    public function index(Request $request): View
    {
        $query = Student::query();
        
        // Search functionality - case insensitive and trim whitespace
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(student_name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(father_name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhere('father_phone', 'like', "%{$search}%")
                      ->orWhere('whatsapp_number', 'like', "%{$search}%")
                      ->orWhere('student_code', 'like', "%{$search}%")
                      ->orWhere('gr_number', 'like', "%{$search}%")
                      ->orWhereRaw('LOWER(class) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(section) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $perPage = $request->get('per_page', 10);
        // Validate per_page to prevent invalid values
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $students = $query->latest('admission_date')->paginate($perPage)->withQueryString();
        
        return view('student.information', compact('students'));
    }
}

