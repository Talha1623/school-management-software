<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
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
        $search = $request->get('search');

        // Get campuses from Campus model
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        
        // If no campuses found, get from classes or sections
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $campusesFromSections = Section::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $allCampuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
            
            // Convert to collection of objects with campus_name property
            $campuses = collect();
            foreach ($allCampuses as $campusName) {
                $campuses->push((object)['campus_name' => $campusName]);
            }
        }

        // Get classes from ClassModel (dynamic only, no static fallback)
        $classes = $this->getClassesForCampus(null);
        $filterClasses = $filterCampus ? $this->getClassesForCampus($filterCampus) : $classes;

        // Get sections (filtered by class if provided) - dynamic only, no static fallback
        $sectionsQuery = Section::whereNotNull('name');
        if ($filterClass) {
            $sectionsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
        }
        if ($filterCampus) {
            $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
        }
        $sections = $sectionsQuery->distinct()
            ->orderBy('name', 'asc')
            ->pluck('name')
            ->sort()
            ->values();

        // Get certificate types
        $certificateTypes = collect(['Character Certificate', 'School Leaving Certificate', 'Date of Birth Certificate', 'Provisional Certificate']);

        // Fetch students based on filters if certificate type is selected
        $students = collect();
        if ($filterCertificateType) {
            $studentsQuery = Student::query();
            
            if ($filterCampus) {
                $studentsQuery->where('campus', $filterCampus);
            }
            
            if ($filterClass) {
                $studentsQuery->where('class', $filterClass);
            }
            
            if ($filterSection) {
                $studentsQuery->where('section', $filterSection);
            }
            
            // Search functionality - search by student name or student code
            if ($search) {
                $searchTerm = trim($search);
                $studentsQuery->where(function($query) use ($searchTerm) {
                    $query->where('student_name', 'like', "%{$searchTerm}%")
                          ->orWhere('student_code', 'like', "%{$searchTerm}%");
                });
            }
            
            $students = $studentsQuery->orderBy('student_name', 'asc')->get();
        }

        return view('certification.student', compact(
            'campuses',
            'classes',
            'filterClasses',
            'sections',
            'certificateTypes',
            'filterCampus',
            'filterClass',
            'filterSection',
            'filterCertificateType',
            'students',
            'search'
        ));
    }

    /**
     * Get classes based on campus (AJAX).
     */
    public function getClasses(Request $request): JsonResponse
    {
        $campus = $request->get('campus');

        $classes = $this->getClassesForCampus($campus);

        return response()->json($classes->isEmpty() ? [] : $classes);
    }

    /**
     * Get sections based on class and campus (AJAX).
     */
    public function getSections(Request $request): JsonResponse
    {
        $class = $request->get('class');
        $campus = $request->get('campus');
        
        $sectionsQuery = Section::whereNotNull('name');
        
        if ($class) {
            $sectionsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
        }
        
        if ($campus) {
            $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        
        $sections = $sectionsQuery->distinct()
            ->orderBy('name', 'asc')
            ->pluck('name')
            ->sort()
            ->values();
        
        return response()->json($sections->isEmpty() ? [] : $sections);
    }

    private function getClassesForCampus(?string $campus)
    {
        $campus = trim((string) $campus);
        $campusLower = strtolower($campus);

        $classesQuery = ClassModel::whereNotNull('class_name');
        if ($campus !== '') {
            $classesQuery->whereRaw('LOWER(TRIM(campus)) = ?', [$campusLower]);
        }

        return $classesQuery->distinct()
            ->orderBy('class_name', 'asc')
            ->pluck('class_name')
            ->map(fn($class) => trim((string) $class))
            ->filter(fn($class) => $class !== '')
            ->unique()
            ->sort()
            ->values();
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

        // Get campuses from Campus model
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        
        // If no campuses found, get from staff, classes or sections
        if ($campuses->isEmpty()) {
            $campusesFromStaff = Staff::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $campusesFromClasses = ClassModel::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $campusesFromSections = Section::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $allCampuses = $campusesFromStaff->merge($campusesFromClasses)->merge($campusesFromSections)->unique()->sort()->values();
            
            // Convert to collection of objects with campus_name property
            $campuses = collect();
            foreach ($allCampuses as $campusName) {
                $campuses->push((object)['campus_name' => $campusName]);
            }
        }

        // Get certificate types - only Experience Certificate and Appreciation Certificate
        $certificateTypes = collect(['Experience Certificate', 'Appreciation Certificate']);

        // Get staff types from designation field (dynamic only, no static fallback)
        $staffTypesQuery = Staff::whereNotNull('designation');
        if ($filterCampus) {
            $staffTypesQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
        }
        $staffTypes = $staffTypesQuery->distinct()
            ->orderBy('designation', 'asc')
            ->pluck('designation')
            ->map(fn($type) => trim((string) $type))
            ->filter(fn($type) => $type !== '')
            ->unique()
            ->sort()
            ->values();

        // Get search parameter
        $search = $request->get('search');

        // Fetch staff based on filters if certificate type is selected
        $staff = collect();
        if ($filterCertificateType) {
            $staffQuery = Staff::query();
            
            if ($filterCampus) {
                $staffQuery->where('campus', $filterCampus);
            }
            
            if ($filterStaffType) {
                $staffQuery->where('designation', $filterStaffType);
            }
            
            // Search functionality - search by name or emp_id
            if ($search) {
                $searchTerm = trim($search);
                $staffQuery->where(function($query) use ($searchTerm) {
                    $query->where('name', 'like', "%{$searchTerm}%")
                          ->orWhere('emp_id', 'like', "%{$searchTerm}%");
                });
            }
            
            $staff = $staffQuery->orderBy('name', 'asc')->get();
        }

        return view('certification.staff', compact(
            'campuses',
            'certificateTypes',
            'staffTypes',
            'filterCampus',
            'filterCertificateType',
            'filterStaffType',
            'staff',
            'search'
        ));
    }

    /**
     * Get staff types based on campus (AJAX).
     */
    public function getStaffTypes(Request $request): JsonResponse
    {
        $campus = trim((string) $request->get('campus'));

        $staffTypesQuery = Staff::whereNotNull('designation');
        if ($campus !== '') {
            $staffTypesQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campus)]);
        }

        $staffTypes = $staffTypesQuery->distinct()
            ->orderBy('designation', 'asc')
            ->pluck('designation')
            ->map(fn($type) => trim((string) $type))
            ->filter(fn($type) => $type !== '')
            ->unique()
            ->sort()
            ->values();

        return response()->json($staffTypes);
    }

    /**
     * Generate certificate for a student.
     */
    public function generateCertificate(Request $request, Student $student): View
    {
        $certificateType = $request->get('type', 'Character Certificate');
        
        // Validate certificate type
        $validTypes = ['Character Certificate', 'School Leaving Certificate', 'Date of Birth Certificate', 'Provisional Certificate'];
        if (!in_array($certificateType, $validTypes)) {
            abort(404, 'Invalid certificate type');
        }
        
        // Get school information (you may want to store this in a settings table)
        $schoolName = config('app.name', 'School Management System');
        $schoolAddress = 'School Address'; // You can add this to config or database
        $currentDate = now()->format('d F Y');
        
        return view("certification.certificates.{$this->getCertificateViewName($certificateType)}", compact(
            'student',
            'certificateType',
            'schoolName',
            'schoolAddress',
            'currentDate'
        ));
    }

    /**
     * Generate certificate for a staff member.
     */
    public function generateStaffCertificate(Request $request, Staff $staff): View
    {
        $certificateType = $request->get('type', 'Experience Certificate');
        
        // Validate certificate type
        $validTypes = ['Experience Certificate', 'Appreciation Certificate'];
        if (!in_array($certificateType, $validTypes)) {
            abort(404, 'Invalid certificate type');
        }
        
        // Get school information
        $schoolName = config('app.name', 'School Management System');
        $schoolAddress = 'School Address'; // You can add this to config or database
        $currentDate = now()->format('d F Y');
        
        return view("certification.certificates.staff.{$this->getStaffCertificateViewName($certificateType)}", compact(
            'staff',
            'certificateType',
            'schoolName',
            'schoolAddress',
            'currentDate'
        ));
    }

    /**
     * Get the view name for certificate type.
     */
    private function getCertificateViewName(string $certificateType): string
    {
        return match($certificateType) {
            'Character Certificate' => 'character',
            'School Leaving Certificate' => 'school-leaving',
            'Date of Birth Certificate' => 'date-of-birth',
            'Provisional Certificate' => 'provisional',
            default => 'character',
        };
    }

    /**
     * Get the view name for staff certificate type.
     */
    private function getStaffCertificateViewName(string $certificateType): string
    {
        return match($certificateType) {
            'Experience Certificate' => 'experience',
            'Appreciation Certificate' => 'appreciation',
            default => 'experience',
        };
    }
}

