<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Campus;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrintStudentCardController extends Controller
{
    /**
     * Check if Student model uses soft deletes
     */
    private function usesSoftDeletes(): bool
    {
        return in_array(
            \Illuminate\Database\Eloquent\SoftDeletes::class,
            class_uses_recursive(Student::class)
        );
    }
    
    /**
     * Apply soft delete filter to query if soft deletes are enabled
     */
    private function applySoftDeleteFilter($query)
    {
        if ($this->usesSoftDeletes()) {
            $query->withoutTrashed();
        }
        return $query;
    }
    
    /**
     * Display the print student card page with dynamic data.
     */
    public function index(Request $request)
    {
        // Get campuses
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
            $campuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
        }

        // Get all classes from database
        $classes = ClassModel::whereNotNull('class_name')
            ->distinct()
            ->orderBy('numeric_no', 'asc')
            ->pluck('class_name')
            ->sort()
            ->values();
        
        // If no classes in ClassModel, get from students (only active students, not deleted)
        if ($classes->isEmpty()) {
            $studentQuery = Student::whereNotNull('class')
                ->where('class', '!=', '');
            
            // Exclude soft deleted students if soft deletes are being used
            $this->applySoftDeleteFilter($studentQuery);
            
            $classes = $studentQuery->distinct()
                ->pluck('class')
                ->sort()
                ->values();
        }
        
        // If AJAX request for sections (when class changes)
        if ($request->ajax() || $request->wantsJson()) {
            $filteredSections = collect();
            $campus = trim((string) $request->get('campus'));
            
            if ($request->filled('class') && $request->class != '') {
                // Get sections from Section model for selected class
                $sectionsFromModel = Section::where('class', $request->class)
                    ->when($campus !== '', function ($query) use ($campus) {
                        $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campus)]);
                    })
                    ->whereNotNull('name')
                    ->distinct()
                    ->pluck('name');
                
                // Get sections from Students for selected class (only active students, not deleted)
                $studentQuery = Student::where('class', $request->class)
                    ->when($campus !== '', function ($query) use ($campus) {
                        $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campus)]);
                    })
                    ->whereNotNull('section')
                    ->where('section', '!=', '');
                
                // Exclude soft deleted students if soft deletes are being used
                $this->applySoftDeleteFilter($studentQuery);
                
                $sectionsFromStudents = $studentQuery->distinct()
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
                    ->when($campus !== '', function ($query) use ($campus) {
                        $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campus)]);
                    })
                    ->distinct()
                    ->pluck('name');
                
                // Get sections from Students (only active students, not deleted)
                $studentQuery = Student::whereNotNull('section')
                    ->when($campus !== '', function ($query) use ($campus) {
                        $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campus)]);
                    })
                    ->where('section', '!=', '');
                
                // Exclude soft deleted students if soft deletes are being used
                $this->applySoftDeleteFilter($studentQuery);
                
                $sectionsFromStudents = $studentQuery->distinct()
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
        
        // Get sections from Students (only active students, not deleted)
        $studentQuery = Student::whereNotNull('section')
            ->where('section', '!=', '');
        
        // Exclude soft deleted students if soft deletes are being used
        $this->applySoftDeleteFilter($studentQuery);
        
        $sectionsFromStudents = $studentQuery->distinct()
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
        
        return view('id-card.print-student', compact('classes', 'sections', 'sessions', 'types', 'campuses'));
    }
    
    /**
     * Get filtered students based on criteria.
     */
    private function getFilteredStudents(Request $request)
    {
        $query = Student::query();
        
        // Exclude soft deleted students (if soft deletes are being used)
        // This ensures deleted students don't appear in the card
        $this->applySoftDeleteFilter($query);
        
        // Only include students with valid class (required field)
        // This ensures only active/added students with class are shown
        $query->whereNotNull('class')
              ->where('class', '!=', '');

        if ($request->filled('student_id')) {
            $query->where('id', $request->student_id);
        } elseif ($request->filled('student_code')) {
            $query->where('student_code', $request->student_code);
        }

        if ($request->filled('campus') && $request->campus != '') {
            $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($request->campus))]);
        }
        
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
            if ($request->filled('campus') && $request->campus != '') {
                $sessionQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($request->campus))]);
            }
            
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

