<?php

namespace App\Http\Controllers;

use App\Models\Test;
use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Campus;
use App\Models\StudentMark;
use App\Models\AdminRole;
use App\Models\Message;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class MarksEntryController extends Controller
{
    private function notifyAdminsAboutStaffMarks(array $validated, int $savedCount): void
    {
        $staff = Auth::guard('staff')->user();
        if (!$staff || $savedCount <= 0) {
            return;
        }

        $classSection = trim((string) $validated['class'] . (!empty($validated['section']) ? ' - ' . $validated['section'] : ''));
        $text = sprintf(
            '%s entered test marks for %d student(s). Test: %s. Subject: %s. Campus: %s. Class: %s.',
            $staff->name ?? 'Staff',
            $savedCount,
            $validated['test_id'],
            $validated['subject'] ?? 'N/A',
            $validated['campus'],
            $classSection
        );

        AdminRole::query()
            ->select('id')
            ->orderBy('id')
            ->get()
            ->each(function (AdminRole $admin) use ($staff, $text) {
                Message::create([
                    'from_type' => 'staff_notification',
                    'from_id' => $staff->id,
                    'to_type' => 'admin',
                    'to_id' => $admin->id,
                    'text' => $text,
                    'attachment_path' => null,
                    'attachment_type' => null,
                    'read_at' => null,
                ]);
            });
    }

    /**
     * Display the marks entry page with filters.
     */
    public function index(Request $request): View
    {
        // Get filter values
        $filterCampus = $request->get('filter_campus');
        $filterClass = $request->get('filter_class');
        $filterSection = $request->get('filter_section');
        $filterTest = $request->get('filter_test');
        $filterSubject = $request->get('filter_subject');

        $classes = collect();
        $staff = Auth::guard('staff')->user();
        $isStaffMarksUser = $this->isStaffMarksSession();

        // Staff: campuses/classes from Manage Subjects assignment only
        if ($isStaffMarksUser && $staff) {
            $campusName = is_object($filterCampus) ? ($filterCampus->campus_name ?? '') : $filterCampus;
            $assignedCampusesQuery = Subject::query();
            $staff->scopeQueryToTeacherAssignments($assignedCampusesQuery);
            $staff->scopeQueryToFlexibleCampus($assignedCampusesQuery, $staff->campusForTeachingAssignments($campusName));

            $teacherCampuses = $assignedCampusesQuery
                ->whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->map(fn ($c) => trim((string) $c))
                ->filter()
                ->unique()
                ->sort()
                ->values();

            if ($teacherCampuses->isNotEmpty()) {
                $campuses = Campus::orderBy('campus_name', 'asc')
                    ->get()
                    ->filter(fn ($campus) => $teacherCampuses->contains(strtolower(trim($campus->campus_name ?? ''))));

                if ($campuses->isEmpty()) {
                    $campuses = $teacherCampuses->map(fn ($campus) => (object) ['campus_name' => $campus]);
                }
            } else {
                $campuses = collect();
            }

            $classes = $staff->assignedTeachingClassNames($campusName);
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
            $classes = ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort()->values();

            if ($classes->isEmpty()) {
                $classes = collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']);
            }
        }

        $sections = collect();
        if ($filterClass) {
            if ($isStaffMarksUser && $staff) {
                $campusName = is_object($filterCampus) ? ($filterCampus->campus_name ?? '') : $filterCampus;
                $classKey = strtolower(trim($filterClass));

                $assignedSubjectsQuery = Subject::query();
                $staff->scopeQueryToTeacherAssignments($assignedSubjectsQuery);
                $staff->scopeQueryToFlexibleCampus($assignedSubjectsQuery, $staff->campusForTeachingAssignments($campusName));
                $assignedSubjectsQuery->whereRaw('LOWER(TRIM(class)) = ?', [$classKey]);

                $sections = $assignedSubjectsQuery
                    ->pluck('section')
                    ->map(fn ($section) => trim((string) $section))
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values();
            } else {
                // For non-teachers, get all sections
                $sections = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))])
                    ->whereNotNull('name')
                    ->distinct()
                    ->pluck('name')
                    ->sort()
                    ->values();
                
                if ($sections->isEmpty()) {
                    $sections = Subject::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))])
                        ->whereNotNull('section')
                        ->distinct()
                        ->pluck('section')
                        ->sort()
                        ->values();
                }
            }
        }

        // Get tests (show before/after declaration).
        $testsQuery = Test::query();
        
        $campusName = is_object($filterCampus) ? ($filterCampus->campus_name ?? '') : $filterCampus;
        if ($campusName) {
            $testsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campusName))]);
        }
        if ($filterClass) {
            $testsQuery->whereRaw('LOWER(TRIM(for_class)) = ?', [strtolower(trim($filterClass))]);
        }
        if ($filterSection) {
            $sectionLower = strtolower(trim($filterSection));
            // Keep tests visible even if section wasn't saved on test row.
            $testsQuery->where(function ($q) use ($sectionLower) {
                $q->whereNull('section')
                    ->orWhereRaw('TRIM(section) = ?', [''])
                    ->orWhereRaw('LOWER(TRIM(section)) = ?', [$sectionLower]);
            });
        }
        if ($filterSubject) {
            $subjectLower = strtolower(trim($filterSubject));
            // Keep tests visible even if subject wasn't saved on test row.
            $testsQuery->where(function ($q) use ($subjectLower) {
                $q->whereNull('subject')
                    ->orWhereRaw('TRIM(subject) = ?', [''])
                    ->orWhereRaw('LOWER(TRIM(subject)) = ?', [$subjectLower]);
            });
        }
        
        $tests = $testsQuery->whereNotNull('test_name')
            ->distinct()
            ->pluck('test_name')
            ->sort()
            ->values();

        $campusName = is_object($filterCampus) ? ($filterCampus->campus_name ?? '') : $filterCampus;
        $subjects = $filterClass
            ? $this->resolveSubjectNamesForMarksEntry($campusName, $filterClass, $filterSection)
            : collect();

        // Query students based on filters
        $students = collect();
        $existingMarks = collect();
        
        if ($filterCampus || $filterClass || $filterSection) {
            $studentsQuery = Student::query();
            
            $campusName = is_object($filterCampus) ? ($filterCampus->campus_name ?? '') : $filterCampus;
            if ($campusName) {
                $studentsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campusName))]);
            }
            if ($filterClass) {
                $studentsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
            }
            if ($filterSection) {
                $studentsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))]);
            }
            
            $students = $studentsQuery->orderBy('student_name')->get();
            
            // Load existing marks if test is selected
            if ($filterTest && $students->isNotEmpty()) {
                $studentIds = $students->pluck('id');
                
                $marksQuery = StudentMark::where('test_name', $filterTest)
                    ->whereIn('student_id', $studentIds);
                
                $campusName = is_object($filterCampus) ? ($filterCampus->campus_name ?? '') : $filterCampus;
                if ($campusName) {
                    $marksQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campusName))]);
                }
                if ($filterClass) {
                    $marksQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
                }
                if ($filterSection) {
                    $marksQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))]);
                }
                if ($filterSubject) {
                    $marksQuery->whereRaw('LOWER(TRIM(subject)) = ?', [strtolower(trim($filterSubject))]);
                }
                
                $existingMarks = $marksQuery->get()->keyBy('student_id');
            }
        }

        $uploadableSubjects = $this->resolveUploadableSubjectsForMarks(
            $campusName,
            $filterClass,
            $filterSection,
            $subjects
        );
        $canUploadMarks = !$isStaffMarksUser || (
            $filterSubject
            && $staff
            && method_exists($staff, 'canUploadMarksForSubject')
            && $staff->canUploadMarksForSubject($campusName, $filterClass, $filterSection, $filterSubject)
        );

        return view('test.marks-entry', compact(
            'campuses',
            'classes',
            'sections',
            'tests',
            'subjects',
            'students',
            'existingMarks',
            'filterCampus',
            'filterClass',
            'filterSection',
            'filterTest',
            'filterSubject',
            'isStaffMarksUser',
            'uploadableSubjects',
            'canUploadMarks'
        ));
    }

    /**
     * Get sections for marks entry (AJAX).
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

        if ($this->isStaffMarksSession() && $staff) {
            $assignedSubjectsQuery = Subject::query();
            $staff->scopeQueryToTeacherAssignments($assignedSubjectsQuery);
            $staff->scopeQueryToFlexibleCampus($assignedSubjectsQuery, $staff->campusForTeachingAssignments($campus));
            $assignedSubjectsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);

            $sections = $assignedSubjectsQuery
                ->pluck('section')
                ->map(fn ($section) => trim((string) $section))
                ->filter()
                ->unique()
                ->sort()
                ->values();
        } else {
            // For non-teachers, get all sections
            $sections = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                ->whereNotNull('name')
                ->distinct()
                ->pluck('name')
                ->sort()
                ->values();
                
            if ($sections->isEmpty()) {
                $sections = Subject::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                    ->whereNotNull('section')
                    ->distinct()
                    ->pluck('section')
                    ->sort()
                    ->values();
            }
        }
        
        return response()->json(['sections' => $sections]);
    }

    /**
     * Get tests for marks entry (AJAX).
     */
    public function getTests(Request $request): JsonResponse
    {
        $campus = $request->get('campus');
        $class = $request->get('class');
        $section = $request->get('section');
        $subject = $request->get('subject');
        
        $testsQuery = Test::query();
        
        if ($campus) {
            $testsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        if ($class) {
            $testsQuery->whereRaw('LOWER(TRIM(for_class)) = ?', [strtolower(trim($class))]);
        }
        if ($section) {
            $sectionLower = strtolower(trim($section));
            $testsQuery->where(function ($q) use ($sectionLower) {
                $q->whereNull('section')
                    ->orWhereRaw('TRIM(section) = ?', [''])
                    ->orWhereRaw('LOWER(TRIM(section)) = ?', [$sectionLower]);
            });
        }
        if ($subject) {
            $subjectLower = strtolower(trim($subject));
            $testsQuery->where(function ($q) use ($subjectLower) {
                $q->whereNull('subject')
                    ->orWhereRaw('TRIM(subject) = ?', [''])
                    ->orWhereRaw('LOWER(TRIM(subject)) = ?', [$subjectLower]);
            });
        }
        
        $tests = $testsQuery->whereNotNull('test_name')
            ->distinct()
            ->pluck('test_name')
            ->sort()
            ->values();
        
        return response()->json(['tests' => $tests]);
    }

    /**
     * Get subjects for marks entry (AJAX).
     */
    public function getSubjects(Request $request): JsonResponse
    {
        $campus = $request->get('campus');
        $class = $request->get('class');
        $section = $request->get('section');

        if (!$class) {
            return response()->json(['subjects' => [], 'uploadable_subjects' => []]);
        }

        try {
            $subjects = $this->resolveSubjectNamesForMarksEntry($campus, $class, $section);
            $uploadableSubjects = $this->resolveUploadableSubjectsForMarks($campus, $class, $section, $subjects);

            return response()->json([
                'subjects' => $subjects->values()->all(),
                'uploadable_subjects' => $uploadableSubjects,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'subjects' => [],
                'uploadable_subjects' => [],
                'message' => 'Unable to load subjects.',
            ], 500);
        }
    }

    /**
     * @return array<int, string>
     */
    private function resolveUploadableSubjectsForMarks(
        ?string $campus,
        ?string $class,
        ?string $section,
        Collection $allSubjects
    ): array {
        $staff = Auth::guard('staff')->user();
        if (!$staff || !$staff->isTeacher()) {
            return $allSubjects->values()->all();
        }

        try {
            if (method_exists($staff, 'uploadableSubjectNamesForMarks')) {
                return $staff->uploadableSubjectNamesForMarks($campus, $class, $section)->values()->all();
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return $allSubjects->values()->all();
    }

    /**
     * Resolve subject names for class with campus/section fallbacks (avoids empty dropdown).
     *
     * @return Collection<int, string>
     */
    private function resolveSubjectNamesForMarksEntry(?string $campus, ?string $class, ?string $section): Collection
    {
        $class = trim((string) ($class ?? ''));
        if ($class === '') {
            return collect();
        }

        $classKey = strtolower($class);
        $sectionTrim = trim((string) ($section ?? ''));
        $campusTrim = trim((string) ($campus ?? ''));

        $baseQuery = function () use ($classKey, $class): Builder {
            return Subject::query()
                ->whereNotNull('subject_name')
                ->where(function (Builder $q) use ($classKey, $class) {
                    $q->whereRaw('LOWER(TRIM(class)) = ?', [$classKey])
                        ->orWhereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
                });
        };

        $attempts = [];
        if ($campusTrim !== '' && $sectionTrim !== '') {
            $attempts[] = ['campus' => $campusTrim, 'section' => $sectionTrim, 'flexCampus' => true];
        }
        if ($campusTrim !== '') {
            $attempts[] = ['campus' => $campusTrim, 'section' => null, 'flexCampus' => true];
        }
        if ($sectionTrim !== '') {
            $attempts[] = ['campus' => null, 'section' => $sectionTrim, 'flexCampus' => false];
        }
        $attempts[] = ['campus' => null, 'section' => null, 'flexCampus' => false];

        foreach ($attempts as $filters) {
            $query = $baseQuery();
            if (!empty($filters['campus'])) {
                if ($filters['flexCampus']) {
                    $this->applyFlexibleCampusFilterToSubjectQuery($query, $filters['campus']);
                } else {
                    $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($filters['campus'])]);
                }
            }
            if (!empty($filters['section'])) {
                $sectionLower = strtolower($filters['section']);
                $query->where(function (Builder $q) use ($sectionLower) {
                    $q->whereRaw('LOWER(TRIM(section)) = ?', [$sectionLower])
                        ->orWhereRaw('TRIM(COALESCE(section, "")) = ?', ['']);
                });
            }

            $names = $query
                ->distinct()
                ->pluck('subject_name')
                ->map(fn ($name) => trim((string) $name))
                ->filter()
                ->unique()
                ->sort()
                ->values();

            if ($names->isNotEmpty()) {
                return $names;
            }
        }

        return collect();
    }

    private function applyFlexibleCampusFilterToSubjectQuery(Builder $query, string $campus): void
    {
        $campusKey = strtolower(trim($campus));
        $query->where(function (Builder $q) use ($campusKey) {
            $q->whereRaw('LOWER(TRIM(COALESCE(campus, ""))) = ?', [$campusKey])
                ->orWhereRaw('TRIM(COALESCE(campus, "")) = ?', ['']);
        });
    }

    /**
     * Save marks for students.
     */
    public function save(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'test_id' => ['required', 'string'],
            'campus' => ['required', 'string'],
            'class' => ['required', 'string'],
            'section' => ['nullable', 'string'],
            'subject' => ['nullable', 'string'],
            'marks' => ['required', 'array'],
            'marks.*.obtained' => ['nullable', 'numeric', 'min:0'],
            'marks.*.total' => ['nullable', 'numeric', 'min:0'],
            'marks.*.passing' => ['nullable', 'numeric', 'min:0'],
        ]);

        $staff = Auth::guard('staff')->user();
        if ($this->isStaffMarksSession() && $staff && method_exists($staff, 'canUploadMarksForSubject')) {
            $subject = trim((string) ($validated['subject'] ?? ''));
            if ($subject === '' || !$staff->canUploadMarksForSubject(
                $validated['campus'],
                $validated['class'],
                $validated['section'] ?? null,
                $subject
            )) {
                return redirect()
                    ->route('test.marks-entry', [
                        'filter_campus' => $validated['campus'],
                        'filter_class' => $validated['class'],
                        'filter_section' => $validated['section'] ?? '',
                        'filter_test' => $validated['test_id'],
                        'filter_subject' => $subject,
                    ])
                    ->with('error', 'You can only enter marks for subjects assigned to you in Manage Subjects.');
            }
        }

        $savedCount = 0;
        // Save or update marks for each student
        foreach ($validated['marks'] as $studentId => $markData) {
            StudentMark::updateOrCreate(
                [
                    'student_id' => $studentId,
                    'test_name' => $validated['test_id'],
                    'campus' => $validated['campus'],
                    'class' => $validated['class'],
                    'section' => $validated['section'] ?? null,
                    'subject' => $validated['subject'] ?? null,
                ],
                [
                    'marks_obtained' => $markData['obtained'] ?? null,
                    'total_marks' => $markData['total'] ?? null,
                    'passing_marks' => $markData['passing'] ?? null,
                ]
            );
            $savedCount++;
        }

        $this->notifyAdminsAboutStaffMarks($validated, $savedCount);
        
        return redirect()
            ->route('test.marks-entry', [
                'filter_campus' => $validated['campus'],
                'filter_class' => $validated['class'],
                'filter_section' => $validated['section'] ?? '',
                'filter_test' => $validated['test_id'],
                'filter_subject' => $validated['subject'] ?? '',
            ])
            ->with('success', 'Marks saved successfully!');
    }

    private function isStaffMarksSession(): bool
    {
        return Auth::guard('staff')->check();
    }
}

