<?php

namespace App\Http\Controllers;

use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Campus;
use App\Models\StudyMaterial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class StudyMaterialController extends Controller
{
    /**
     * Display the study material LMS page.
     */
    public function lms(Request $request): View
    {
        // Get filter values
        $filterCampus = $request->get('filter_campus');
        $filterClass = $request->get('filter_class');
        $filterSection = $request->get('filter_section');
        $filterType = $request->get('filter_type');

        // Check if staff is logged in and is a teacher
        $staff = Auth::guard('staff')->user();
        $isTeacher = $staff && $staff->isTeacher();
        $teacherName = $isTeacher ? strtolower(trim($staff->name ?? '')) : null;
        
        // Get campuses for dropdown - filter by teacher's assigned campuses if teacher
        if ($isTeacher && $teacherName) {
            // Get campuses from teacher's assigned subjects
            $teacherCampuses = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [$teacherName])
                ->whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->merge(
                    Section::whereRaw('LOWER(TRIM(teacher)) = ?', [$teacherName])
                        ->whereNotNull('campus')
                        ->distinct()
                        ->pluck('campus')
                )
                ->map(fn($c) => trim($c))
                ->filter(fn($c) => !empty($c))
                ->unique()
                ->sort()
                ->values();
            
            // Filter Campus model results to only show assigned campuses
            if ($teacherCampuses->isNotEmpty()) {
                $campuses = Campus::orderBy('campus_name', 'asc')
                    ->get()
                    ->filter(function($campus) use ($teacherCampuses) {
                        return $teacherCampuses->contains(strtolower(trim($campus->campus_name ?? '')));
                    });
                
                // If no campuses found in Campus model, create objects from teacher campuses
                if ($campuses->isEmpty()) {
                    $campuses = $teacherCampuses->map(function($campus) {
                        return (object)['campus_name' => $campus];
                    });
                }
            } else {
                // If teacher has no assigned campuses, show empty
                $campuses = collect();
            }
        } else {
            // For non-teachers (admin, staff, etc.), get all campuses
            $campuses = Campus::orderBy('campus_name', 'asc')->get();
            if ($campuses->isEmpty()) {
                $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
                $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
                $allCampuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort();
                $campuses = $allCampuses->map(function($campus) {
                    return (object)['campus_name' => $campus];
                });
            }
        }

        // Get classes - filter by teacher's assigned classes if teacher
        $classes = $this->getClassesForCampus(null, $staff);
        $filterClasses = $filterCampus ? $this->getClassesForCampus($filterCampus, $staff) : $classes;

        // Get sections (filtered by class if provided)
        $sections = collect();
        if ($filterClass) {
            $sectionsQuery = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))])
                ->whereNotNull('name');
            if ($filterCampus) {
                $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
            }
            $sections = $sectionsQuery->distinct()->pluck('name')->sort()->values();
            
            if ($sections->isEmpty()) {
                $subjectsQuery = Subject::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))])
                    ->whereNotNull('section');
                if ($filterCampus) {
                    $subjectsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
                }
                $sections = $subjectsQuery->distinct()->pluck('section')->sort()->values();
            }
        }

        // Get material types
        $materialTypes = collect(['picture', 'video', 'documents']);

        // Get subjects for modal (will be loaded dynamically via AJAX)
        $subjects = collect();

        // Query study materials based on filters
        $studyMaterials = collect();
        if ($filterCampus || $filterClass || $filterSection || $filterType) {
            $query = StudyMaterial::query();
            
            $campusName = is_object($filterCampus) ? ($filterCampus->campus_name ?? '') : $filterCampus;
            if ($campusName) {
                $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campusName))]);
            }
            if ($filterClass) {
                $query->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
            }
            if ($filterSection) {
                $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))]);
            }
            if ($filterType) {
                $query->where('file_type', $filterType);
            }
            
            $studyMaterials = $query->orderBy('created_at', 'desc')->get();
        }

        return view('study-material.lms', compact(
            'campuses',
            'classes',
            'filterClasses',
            'sections',
            'subjects',
            'materialTypes',
            'studyMaterials',
            'filterCampus',
            'filterClass',
            'filterSection',
            'filterType'
        ));
    }

    /**
     * Store a newly created study material.
     */
    public function store(Request $request): RedirectResponse
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'campus' => ['required', 'string', 'max:255'],
            'class' => ['required', 'string', 'max:255'],
            'section' => ['nullable', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'file_type' => ['required', 'in:picture,video,documents'],
        ];

        // Conditional validation based on file type
        if ($request->file_type === 'video') {
            $rules['youtube_url'] = ['required', 'url', 'max:500'];
        } else {
            $rules['file'] = ['required', 'file', 'max:10240']; // Max 10MB
        }

        $validated = $request->validate($rules);

        $filePath = null;
        $youtubeUrl = null;

        if ($validated['file_type'] === 'video') {
            // For video, use YouTube URL
            $youtubeUrl = $validated['youtube_url'] ?? null;
        } else {
            // For picture or documents, upload file
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('study-materials', $fileName, 'public');
            }
        }

        StudyMaterial::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'campus' => $validated['campus'],
            'class' => $validated['class'],
            'section' => $validated['section'] ?? null,
            'subject' => $validated['subject'] ?? null,
            'file_type' => $validated['file_type'],
            'file_path' => $filePath,
            'youtube_url' => $youtubeUrl,
        ]);

        return redirect()
            ->route('study-material.lms', [
                'filter_campus' => $validated['campus'],
                'filter_class' => $validated['class'],
                'filter_section' => $validated['section'] ?? '',
                'filter_type' => $validated['file_type']
            ])
            ->with('success', 'Study material created successfully!');
    }

    /**
     * Get sections based on class (AJAX).
     * Filter by teacher's assigned subjects if teacher.
     */
    public function getSectionsByClass(Request $request): JsonResponse
    {
        $class = $request->get('class');
        $campus = $request->get('campus');
        if (!$class) {
            return response()->json(['sections' => []]);
        }
        
        $staff = Auth::guard('staff')->user();
        $sections = collect();
        
        // Filter by teacher's assigned subjects and sections if teacher
        if ($staff && $staff->isTeacher()) {
            // Get sections from teacher's assigned subjects for this class
            $assignedSubjectsQuery = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
            if ($campus) {
                $assignedSubjectsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            $assignedSubjects = $assignedSubjectsQuery->get();
            
            // Get sections from teacher's assigned sections for this class
            $assignedSectionsQuery = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
            if ($campus) {
                $assignedSectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            $assignedSections = $assignedSectionsQuery->get();
            
            // Merge sections from both sources
            $sections = $assignedSubjects->pluck('section')
                ->merge($assignedSections->pluck('name'))
                ->map(function($section) {
                    return trim($section);
                })
                ->filter(function($section) {
                    return !empty($section);
                })
                ->unique()
                ->sort()
                ->values();
        } else {
            // For non-teachers, get all sections
            $sectionsQuery = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                ->whereNotNull('name');
            if ($campus) {
                $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            $sections = $sectionsQuery->distinct()->pluck('name')->sort()->values();
            
            if ($sections->isEmpty()) {
                $subjectsQuery = Subject::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                    ->whereNotNull('section');
                if ($campus) {
                    $subjectsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
                }
                $sections = $subjectsQuery->distinct()->pluck('section')->sort()->values();
            }
        }
        
        return response()->json(['sections' => $sections]);
    }

    /**
     * Get classes based on campus (AJAX).
     * Filter by teacher's assigned subjects if teacher.
     */
    public function getClassesByCampus(Request $request): JsonResponse
    {
        $campus = trim((string) $request->get('campus'));
        if ($campus === '') {
            return response()->json(['classes' => []]);
        }

        $staff = Auth::guard('staff')->user();
        $classes = $this->getClassesForCampus($campus, $staff);

        return response()->json(['classes' => $classes]);
    }

    private function getClassesForCampus(?string $campus, $staff)
    {
        $campus = trim((string) $campus);
        $campusLower = strtolower($campus);
        $isTeacher = $staff && $staff->isTeacher();
        $teacherName = $isTeacher ? strtolower(trim($staff->name ?? '')) : null;

        $classes = collect();

        if ($isTeacher && $teacherName) {
            $subjectsQuery = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [$teacherName]);
            $sectionsQuery = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [$teacherName]);
            if ($campus) {
                $subjectsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [$campusLower]);
                $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [$campusLower]);
            }

            $classes = $subjectsQuery->pluck('class')
                ->merge($sectionsQuery->pluck('class'))
                ->map(fn($class) => trim((string) $class))
                ->filter(fn($class) => $class !== '')
                ->unique()
                ->sort()
                ->values();
        }

        if ($classes->isEmpty()) {
            $classQuery = ClassModel::whereNotNull('class_name');
            if ($campus) {
                $classQuery->whereRaw('LOWER(TRIM(campus)) = ?', [$campusLower]);
            }
            $classModelClasses = $classQuery->distinct()->pluck('class_name')
                ->map(fn($class) => trim((string) $class))
                ->filter(fn($class) => $class !== '')
                ->unique()
                ->sort()
                ->values();

            // If ClassModel has classes for the campus, trust it and skip fallbacks.
            if ($classModelClasses->isNotEmpty()) {
                return $classModelClasses->values();
            }

            $sectionClasses = Section::whereNotNull('class');
            $subjectClasses = Subject::whereNotNull('class');
            if ($campus) {
                $sectionClasses->whereRaw('LOWER(TRIM(campus)) = ?', [$campusLower]);
                $subjectClasses->whereRaw('LOWER(TRIM(campus)) = ?', [$campusLower]);
            }

            $classes = $classModelClasses
                ->merge($sectionClasses->distinct()->pluck('class'))
                ->merge($subjectClasses->distinct()->pluck('class'))
                ->map(fn($class) => trim((string) $class))
                ->filter(fn($class) => $class !== '')
                ->unique()
                ->sort()
                ->values();

            if ($classes->isEmpty() && !$campus) {
                $classes = collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']);
            }
        }

        return $classes->values();
    }

    /**
     * Get subjects based on class and section (AJAX).
     * Filter by teacher's assigned subjects if teacher.
     */
    public function getSubjectsByClassSection(Request $request): JsonResponse
    {
        $class = $request->get('class');
        $section = $request->get('section');
        
        $staff = Auth::guard('staff')->user();
        $subjectsQuery = Subject::query();
        
        // Filter by teacher's assigned subjects if teacher
        if ($staff && $staff->isTeacher()) {
            $subjectsQuery->whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))]);
        }
        
        if ($class) {
            $subjectsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
        }
        if ($section) {
            $subjectsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($section))]);
        }
        
        $subjects = $subjectsQuery->whereNotNull('subject_name')
            ->distinct()
            ->pluck('subject_name')
            ->sort()
            ->values();
        
        return response()->json(['subjects' => $subjects]);
    }

    /**
     * View/Download study material file.
     */
    public function viewFile(StudyMaterial $studyMaterial)
    {
        if (!$studyMaterial->file_path) {
            abort(404, 'File not found');
        }

        $filePath = storage_path('app/public/' . $studyMaterial->file_path);
        
        if (!file_exists($filePath)) {
            abort(404, 'File not found');
        }

        return response()->file($filePath);
    }

    /**
     * Delete a study material.
     */
    public function destroy(StudyMaterial $studyMaterial): RedirectResponse
    {
        // Delete file if exists
        if ($studyMaterial->file_path && Storage::disk('public')->exists($studyMaterial->file_path)) {
            Storage::disk('public')->delete($studyMaterial->file_path);
        }

        $studyMaterial->delete();

        return redirect()
            ->route('study-material.lms')
            ->with('success', 'Study material deleted successfully!');
    }
}

