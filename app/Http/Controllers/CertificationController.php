<?php

namespace App\Http\Controllers;

use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CertificationController extends Controller
{
    /**
     * Display the student certification page.
     */
    public function student(Request $request): View
    {
        // Get filter values
        $filterCampus = $request->get('filter_campus');
        $filterClass = $request->get('filter_class');
        $filterSection = $request->get('filter_section');
        $filterCertificateType = $request->get('filter_certificate_type');

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

        // Get sections (filtered by class if provided)
        $sectionsQuery = Section::query();
        if ($filterClass) {
            $sectionsQuery->where('class', $filterClass);
        }
        $sections = $sectionsQuery->whereNotNull('name')->distinct()->pluck('name')->sort()->values();
        
        if ($sections->isEmpty()) {
            $sections = collect(['A', 'B', 'C', 'D']);
        }

        // Get certificate types
        $certificateTypes = collect(['Character Certificate', 'Transfer Certificate', 'Bonafide Certificate', 'Admission Certificate', 'Completion Certificate', 'Merit Certificate']);

        return view('certification.student', compact(
            'campuses',
            'classes',
            'sections',
            'certificateTypes',
            'filterCampus',
            'filterClass',
            'filterSection',
            'filterCertificateType'
        ));
    }

    /**
     * Display the staff certification page.
     */
    public function staff(Request $request): View
    {
        // Get filter values
        $filterCampus = $request->get('filter_campus');
        $filterCertificateType = $request->get('filter_certificate_type');
        $filterStaffType = $request->get('filter_staff_type');

        // Get campuses for dropdown
        $campusesFromStaff = Staff::whereNotNull('campus')->distinct()->pluck('campus');
        $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
        $campuses = $campusesFromStaff->merge($campusesFromClasses)->unique()->sort()->values();
        
        if ($campuses->isEmpty()) {
            $campuses = collect(['Main Campus', 'Branch Campus 1', 'Branch Campus 2']);
        }

        // Get certificate types
        $certificateTypes = collect(['Character Certificate', 'Transfer Certificate', 'Bonafide Certificate', 'Employment Certificate', 'Experience Certificate', 'Service Certificate']);

        // Get staff types from designation field or use predefined
        $staffTypesFromDB = Staff::whereNotNull('designation')->distinct()->pluck('designation')->sort()->values();
        $staffTypes = collect(['Teacher', 'Principal', 'Vice Principal', 'Administrator', 'Accountant', 'Receptionist', 'Security', 'Cleaner']);
        
        if ($staffTypesFromDB->isNotEmpty()) {
            $staffTypes = $staffTypes->merge($staffTypesFromDB)->unique()->sort()->values();
        }

        return view('certification.staff', compact(
            'campuses',
            'certificateTypes',
            'staffTypes',
            'filterCampus',
            'filterCertificateType',
            'filterStaffType'
        ));
    }
}

