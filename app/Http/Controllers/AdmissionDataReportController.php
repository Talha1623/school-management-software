<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdmissionDataReportController extends Controller
{
    /**
     * Display the admission data reports with filters.
     */
    public function index(Request $request): View
    {
        // Get filter values
        $filterCampus = $request->get('filter_campus');
        $filterClass = $request->get('filter_class');
        $filterSection = $request->get('filter_section');
        $filterStatus = $request->get('filter_status');

        // Get campuses from students
        $campuses = Student::whereNotNull('campus')->distinct()->pluck('campus')->sort()->values();
        
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
            $campuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
            
            if ($campuses->isEmpty()) {
                $campuses = collect(['Main Campus', 'Branch Campus 1', 'Branch Campus 2']);
            }
        }

        // Get classes
        $classes = ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort()->values();
        if ($classes->isEmpty()) {
            $classes = collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']);
        }

        // Get sections
        $sections = Section::whereNotNull('name')->distinct()->pluck('name')->sort()->values();
        if ($sections->isEmpty()) {
            $sections = collect(['A', 'B', 'C', 'D', 'E']);
        }

        // Status options
        $statusOptions = collect(['Active', 'Inactive', 'Pending', 'Graduated', 'Transferred']);

        // Query students
        $query = Student::query();

        if ($filterCampus) {
            $query->where('campus', $filterCampus);
        }
        if ($filterClass) {
            $query->where('class', $filterClass);
        }
        if ($filterSection) {
            $query->where('section', $filterSection);
        }

        $students = $query->orderBy('student_name')->get();

        // Prepare admission records
        $admissionRecords = collect();

        foreach ($students as $student) {
            // Determine status based on student data
            // Default to Active if student has admission_date
            $status = $student->admission_date ? 'Active' : 'Pending';
            
            // Apply status filter if specified
            if ($filterStatus && $status != $filterStatus) {
                continue;
            }

            $admissionRecords->push([
                'student_code' => $student->student_code,
                'student_name' => $student->student_name,
                'surname_caste' => $student->surname_caste,
                'gender' => $student->gender,
                'date_of_birth' => $student->date_of_birth,
                'photo' => $student->photo,
                'campus' => $student->campus,
                'class' => $student->class,
                'section' => $student->section,
                'admission_date' => $student->admission_date,
                'father_name' => $student->father_name,
                'father_phone' => $student->father_phone,
                'whatsapp_number' => $student->whatsapp_number,
                'gr_number' => $student->gr_number,
                'status' => $status,
            ]);
        }

        return view('reports.admission-data', compact(
            'campuses',
            'classes',
            'sections',
            'statusOptions',
            'admissionRecords',
            'filterCampus',
            'filterClass',
            'filterSection',
            'filterStatus'
        ));
    }
}

