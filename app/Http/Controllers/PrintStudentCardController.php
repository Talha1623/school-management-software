<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrintStudentCardController extends Controller
{
    /**
     * Display the print student card page with dynamic data.
     */
    public function index(Request $request)
    {
        // Get all classes from database
        $classes = ClassModel::whereNotNull('class_name')
            ->distinct()
            ->orderBy('numeric_no', 'asc')
            ->pluck('class_name')
            ->sort()
            ->values();
        
        // If no classes in ClassModel, get from students
        if ($classes->isEmpty()) {
            $classes = Student::whereNotNull('class')
                ->distinct()
                ->pluck('class')
                ->sort()
                ->values();
        }
        
        // If AJAX request for sections (when class changes)
        if ($request->ajax() || $request->wantsJson()) {
            $filteredSections = collect();
            
            if ($request->filled('class') && $request->class != '') {
                // Get sections from Section model for selected class
                $sectionsFromModel = Section::where('class', $request->class)
                    ->whereNotNull('name')
                    ->distinct()
                    ->pluck('name');
                
                // Get sections from Students for selected class
                $sectionsFromStudents = Student::where('class', $request->class)
                    ->whereNotNull('section')
                    ->distinct()
                    ->pluck('section');
                
                // Combine both sources and get unique sections
                $filteredSections = $sectionsFromModel->merge($sectionsFromStudents)
                    ->unique()
                    ->sort()
                    ->values();
            } else {
                // If no class selected, return all sections
                // Get sections from Section model
                $sectionsFromModel = Section::whereNotNull('name')
                    ->distinct()
                    ->pluck('name');
                
                // Get sections from Students
                $sectionsFromStudents = Student::whereNotNull('section')
                    ->distinct()
                    ->pluck('section');
                
                // Combine both sources and get unique sections
                $filteredSections = $sectionsFromModel->merge($sectionsFromStudents)
                    ->unique()
                    ->sort()
                    ->values();
            }
            
            return response()->json([
                'sections' => $filteredSections->toArray()
            ]);
        }
        
        // Get all sections from database for initial dropdown
        // Get sections from Section model
        $sectionsFromModel = Section::whereNotNull('name')
            ->distinct()
            ->pluck('name');
        
        // Get sections from Students
        $sectionsFromStudents = Student::whereNotNull('section')
            ->distinct()
            ->pluck('section');
        
        // Combine both sources and get unique sections
        $sections = $sectionsFromModel->merge($sectionsFromStudents)
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
        
        // Student types (can be extended based on your requirements)
        // For now, using hardcoded values. If you have a student_type field, fetch from database
        $types = ['Regular', 'Scholarship', 'Merit', 'VIP'];
        
        // If AJAX request, return JSON for sections
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'sections' => $sections->toArray()
            ]);
        }
        
        return view('id-card.print-student', compact('classes', 'sections', 'sessions', 'types'));
    }
    
    /**
     * Get filtered students based on criteria.
     */
    private function getFilteredStudents(Request $request)
    {
        $query = Student::query();
        
        // Filter by Type (if you have a student_type field, use it here)
        // For now, we'll skip type filtering as it's not in the Student model
        // You can add this later if you have a student_type field
        
        // Filter by Class
        if ($request->filled('class') && $request->class != '') {
            $query->where('class', $request->class);
        }
        
        // Filter by Section
        if ($request->filled('section') && $request->section != '') {
            $query->where('section', $request->section);
        }
        
        // Filter by Session (through sections)
        // If session is provided, filter students by matching sections
        if ($request->filled('session') && $request->session != '') {
            $sessionQuery = Section::where('session', $request->session);
            
            // If class is selected, filter sections by class
            if ($request->filled('class') && $request->class != '') {
                $sessionQuery->where('class', $request->class);
            }
            
            // If section is selected, filter sections by section name
            if ($request->filled('section') && $request->section != '') {
                $sessionQuery->where('name', $request->section);
            }
            
            $matchingSections = $sessionQuery->get();
            
            if ($matchingSections->isNotEmpty()) {
                // Get unique class-section combinations
                $classSectionPairs = $matchingSections->map(function($section) {
                    return ['class' => $section->class, 'section' => $section->name];
                })->unique(function($item) {
                    return $item['class'] . '|' . $item['section'];
                });
                
                // Filter students by these class-section combinations
                $query->where(function($q) use ($classSectionPairs) {
                    foreach ($classSectionPairs as $pair) {
                        $q->orWhere(function($subQ) use ($pair) {
                            $subQ->where('class', $pair['class'])
                                 ->where('section', $pair['section']);
                        });
                    }
                });
            } else {
                // If no matching sections found, return empty result
                $query->whereRaw('1 = 0');
            }
        }
        
        return $query->orderBy('class', 'asc')
            ->orderBy('section', 'asc')
            ->orderBy('student_name', 'asc')
            ->get();
    }

    /**
     * Display print view with filtered students.
     */
    public function print(Request $request): View
    {
        // Get filtered students
        $students = $this->getFilteredStudents($request);
        
        // Get design settings from request
        $designSettings = [
            'accent_color' => $request->get('accent_color', '#5C5C5C'),
            'secondary_color' => $request->get('secondary_color', '#F08080'),
            'gradient_color1' => $request->get('gradient_color1', '#FFFFFF'),
            'gradient_color2' => $request->get('gradient_color2', '#FFFFFF'),
            'student_name_color' => $request->get('student_name_color', '#000000'),
            'student_label_color' => $request->get('student_label_color', '#000000'),
            'details_text_color' => $request->get('details_text_color', '#000000'),
            'footer_text_color' => $request->get('footer_text_color', '#FFFFFF'),
            'orientation' => $request->get('orientation', 'portrait'),
            'show_monogram' => $request->get('show_monogram', 'yes'),
            'card_style' => $request->get('card_style', 'modern'),
            'border_style' => $request->get('border_style', 'rounded'),
        ];
        
        return view('id-card.print-student-card-print', compact('students', 'designSettings'));
    }
}

