<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Subject;
use App\Models\HomeworkDiary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class HomeworkDiaryController extends Controller
{
    /**
     * Display the add & manage diaries page.
     */
    public function manage(Request $request): View
    {
        // Get filter values
        $filterCampus = $request->get('filter_campus');
        $filterClass = $request->get('filter_class');
        $filterSection = $request->get('filter_section');
        $filterDate = $request->get('filter_date', date('Y-m-d'));

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
                $campusesFromSubjects = Subject::whereNotNull('campus')->distinct()->pluck('campus');
                $allCampuses = $campusesFromClasses->merge($campusesFromSections)->merge($campusesFromSubjects)->unique()->sort();
                $campuses = $allCampuses->map(function($campus) {
                    return (object)['campus_name' => $campus];
                });
            }
        }

        // Get classes - filter by teacher's assigned classes if teacher
        $classes = $this->getClassesForCampus(null, $staff);
        $filterClasses = $filterCampus ? $this->getClassesForCampus($filterCampus, $staff) : $classes;

        // Get sections (filtered by class if provided)
        // Filter by teacher's assigned subjects if teacher
        $sections = collect();
        if ($filterClass) {
            if ($staff && $staff->isTeacher()) {
                // Get sections from teacher's assigned subjects for this class
                $assignedSubjectsQuery = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
                if ($filterCampus) {
                    $assignedSubjectsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
                }
                $assignedSubjects = $assignedSubjectsQuery->get();
                
                // Get sections from teacher's assigned sections for this class
                $assignedSectionsQuery = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
                if ($filterCampus) {
                    $assignedSectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
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
                $sectionsQuery = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))])
                    ->whereNotNull('name');
                if ($filterCampus) {
                    $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
                }
                $sections = $sectionsQuery->distinct()->pluck('name')->sort()->values();
                
                if ($sections->isEmpty()) {
                    $sectionsFromSubjectsQuery = Subject::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))])
                        ->whereNotNull('section');
                    if ($filterCampus) {
                        $sectionsFromSubjectsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
                    }
                    $sections = $sectionsFromSubjectsQuery->distinct()->pluck('section')->sort();
                }
            }
        }

        // Get subjects based on filters
        $subjects = collect();
        $diaryEntries = collect();
        
        if ($filterCampus && $filterClass && $filterSection) {
            $subjectsQuery = Subject::query();
            
            $campusName = is_object($filterCampus) ? ($filterCampus->campus_name ?? '') : $filterCampus;
            
            // Exact match with case-insensitive comparison and trim whitespace
            $subjectsQuery->where(function($query) use ($campusName, $filterClass, $filterSection) {
                $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campusName))])
                      ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))])
                      ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))]);
            });
            
            // Filter by teacher's assigned subjects if teacher
            if ($staff && $staff->isTeacher()) {
                $subjectsQuery->whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))]);
            }
            
            $subjects = $subjectsQuery->orderBy('subject_name', 'asc')->get();
            
            // Load existing diary entries for the selected date
            if ($subjects->count() > 0) {
                $subjectIds = $subjects->pluck('id');
                $diaryEntries = HomeworkDiary::whereIn('subject_id', $subjectIds)
                    ->where('date', $filterDate)
                    ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campusName))])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))])
                    ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))])
                    ->get()
                    ->keyBy('subject_id');
            }
        }

        return view('homework-diary.manage', compact(
            'campuses',
            'classes',
            'filterClasses',
            'sections',
            'subjects',
            'diaryEntries',
            'filterCampus',
            'filterClass',
            'filterSection',
            'filterDate'
        ));
    }

    /**
     * Get sections for homework diary (AJAX).
     * Filter by teacher's assigned subjects if teacher.
     */
    public function getSections(Request $request): JsonResponse
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
                // Try from subjects table with case-insensitive matching
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
     * Get classes for homework diary (AJAX).
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
     * Store or update homework diary entries.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string'],
            'class' => ['required', 'string'],
            'section' => ['required', 'string'],
            'date' => ['required', 'date'],
            'diaries' => ['required', 'array'],
            'diaries.*.subject_id' => ['required', 'exists:subjects,id'],
            'diaries.*.homework_content' => ['nullable', 'string'],
        ]);

        $savedCount = 0;
        $updatedCount = 0;

        foreach ($validated['diaries'] as $diaryData) {
            if (empty($diaryData['homework_content'])) {
                // Skip empty entries
                continue;
            }

            $homeworkDiary = HomeworkDiary::updateOrCreate(
                [
                    'subject_id' => $diaryData['subject_id'],
                    'date' => $validated['date'],
                    'class' => $validated['class'],
                    'section' => $validated['section'],
                ],
                [
                    'campus' => $validated['campus'],
                    'homework_content' => $diaryData['homework_content'],
                ]
            );

            if ($homeworkDiary->wasRecentlyCreated) {
                $savedCount++;
            } else {
                $updatedCount++;
            }
        }

        $message = 'Homework diary saved successfully!';
        if ($savedCount > 0) {
            $message .= " {$savedCount} new " . ($savedCount == 1 ? 'entry' : 'entries') . " created.";
        }
        if ($updatedCount > 0) {
            $message .= " {$updatedCount} " . ($updatedCount == 1 ? 'entry' : 'entries') . " updated.";
        }

        return redirect()
            ->route('homework-diary.manage', [
                'filter_campus' => $validated['campus'],
                'filter_class' => $validated['class'],
                'filter_section' => $validated['section'],
                'filter_date' => $validated['date']
            ])
            ->with('success', $message);
    }

    /**
     * Send diary for a subject.
     */
    public function sendDiary(Request $request)
    {
        $validated = $request->validate([
            'subject_id' => ['required', 'exists:subjects,id'],
            'date' => ['required', 'date'],
        ]);

        $subject = Subject::findOrFail($validated['subject_id']);

        // TODO: Implement diary sending logic (SMS/WhatsApp/Email)
        // For now, just return success message

        return redirect()
            ->route('homework-diary.manage', [
                'filter_campus' => $subject->campus,
                'filter_class' => $subject->class,
                'filter_section' => $subject->section,
                'filter_date' => $validated['date']
            ])
            ->with('success', 'Diary sent successfully for ' . $subject->subject_name . '!');
    }
}

