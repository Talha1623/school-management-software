<?php

namespace App\Http\Controllers;

use App\Models\Timetable;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Campus;
use App\Models\Subject;
use App\Models\Staff;
use App\Models\GeneralSetting;
use App\Services\MobilePushNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TimetableController extends Controller
{
    public function __construct(
        private readonly MobilePushNotificationService $pushNotifications,
    ) {
    }

    /**
     * Static timetable subjects that are not stored in Subject table.
     */
    private function getStaticSubjects(): array
    {
        return [
            '[Assembly]',
            '[Lunch Break]',
            '[Free Time]',
            '[Lab Active]',
            '[physicial/sports/activity]',
            '[singing class]',
            '[material arts class]',
            '[Library Activity]',
            '[chilligraphy class]',
            '[other fun activities]',
        ];
    }
    /**
     * Display the add timetable form.
     */
    public function add(): View
    {
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
        
        // Get classes from ClassModel (only existing classes, not deleted ones)
        $classes = ClassModel::whereNotNull('class_name')
            ->where('class_name', '!=', '')
            ->orderBy('class_name', 'asc')
            ->get();
        
        // If no classes found, provide empty collection
        if ($classes->isEmpty()) {
            $classes = collect();
        }
        
        // Get sections from Section model
        $sections = Section::whereNotNull('name')
            ->distinct()
            ->orderBy('name', 'asc')
            ->pluck('name')
            ->sort()
            ->values();
        
        // Get subjects from Subject model (only active/non-deleted subjects)
        // Filter out subjects with deleted classes to ensure only valid subjects are shown
        $existingClassNames = ClassModel::whereNotNull('class_name')
            ->where('class_name', '!=', '')
            ->pluck('class_name')
            ->map(function($name) {
                return strtolower(trim($name));
            })->toArray();
        
        $subjects = Subject::whereNotNull('subject_name')
            ->when(!empty($existingClassNames), function($query) use ($existingClassNames) {
                return $query->where(function($q) use ($existingClassNames) {
                    foreach ($existingClassNames as $className) {
                        $q->orWhereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($className))]);
                    }
                });
            })
            ->distinct()
            ->orderBy('subject_name', 'asc')
            ->pluck('subject_name')
            ->sort()
            ->values();
        
        // Days of the week
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        
        return view('timetable.add', compact('campuses', 'classes', 'sections', 'subjects', 'days'));
    }

    /**
     * Display a listing of timetables with filters.
     */
    public function index(Request $request): View
    {
        // Get campuses from Campus model
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        
        // If no campuses found, get from classes, sections, or timetables
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
            
            $campusesFromTimetables = Timetable::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $allCampuses = $campusesFromClasses->merge($campusesFromSections)->merge($campusesFromTimetables)->unique()->sort()->values();
            
            // Convert to collection of objects with campus_name property
            $campuses = collect();
            foreach ($allCampuses as $campusName) {
                $campuses->push((object)['campus_name' => $campusName]);
            }
        }
        
        // Get classes from ClassModel (only existing classes, not deleted ones)
        $classes = ClassModel::whereNotNull('class_name')
            ->where('class_name', '!=', '')
            ->orderBy('class_name', 'asc')
            ->get();
        
        // Get existing class names for filtering timetables
        $existingClassNames = $classes->pluck('class_name')->map(function($name) {
            return strtolower(trim($name));
        })->unique()->values()->toArray();
        
        // Get existing subject names from Subject table (only active/non-deleted subjects)
        $existingSubjectNames = Subject::whereNotNull('subject_name')
            ->when(!empty($existingClassNames), function($query) use ($existingClassNames) {
                return $query->where(function($q) use ($existingClassNames) {
                    foreach ($existingClassNames as $className) {
                        $q->orWhereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($className))]);
                    }
                });
            })
            ->distinct()
            ->pluck('subject_name')
            ->map(function($name) {
                return strtolower(trim($name));
            })
            ->unique()
            ->values()
            ->toArray();

        // Add static subjects so they also appear in timetable management list
        $staticSubjectNames = collect($this->getStaticSubjects())
            ->map(fn($name) => strtolower(trim($name)))
            ->toArray();
        $existingSubjectNames = array_values(array_unique(array_merge($existingSubjectNames, $staticSubjectNames)));
        
        // Filter timetables to only show those with existing classes and existing subjects
        if (!empty($existingClassNames)) {
            // Only query if at least one filter is applied
            if ($request->filled('filter_campus') || $request->filled('filter_class') || $request->filled('filter_section') || $request->filled('filter_day')) {
                $query = Timetable::query();
                
                // Filter by existing classes only
                $query->where(function($q) use ($existingClassNames) {
                    foreach ($existingClassNames as $className) {
                        $q->orWhereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($className))]);
                    }
                });
                
                // Filter out timetables with deleted subjects (subjects that no longer exist in Subject table)
                if (!empty($existingSubjectNames)) {
                    $query->where(function($q) use ($existingSubjectNames) {
                        foreach ($existingSubjectNames as $subjectName) {
                            $q->orWhereRaw('LOWER(TRIM(subject)) = ?', [strtolower(trim($subjectName))]);
                        }
                    });
                } else {
                    // If no subjects exist, show no timetables
                    $query->whereRaw('1 = 0');
                }
                
                // Apply other filters
                if ($request->filled('filter_campus')) {
                    $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($request->filter_campus))]);
                }
                
                if ($request->filled('filter_class')) {
                    $query->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($request->filter_class))]);
                }
                
                if ($request->filled('filter_section')) {
                    $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($request->filter_section))]);
                }
                
                if ($request->filled('filter_day')) {
                    $query->whereRaw('LOWER(TRIM(day)) = ?', [strtolower(trim($request->filter_day))]);
                }
                
                $perPage = $request->get('per_page', 10);
                $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
                
                // Order by day sequence (Monday to Sunday) and then by starting time
                $timetables = $query->orderByRaw("
                    CASE day
                        WHEN 'Monday' THEN 1
                        WHEN 'Tuesday' THEN 2
                        WHEN 'Wednesday' THEN 3
                        WHEN 'Thursday' THEN 4
                        WHEN 'Friday' THEN 5
                        WHEN 'Saturday' THEN 6
                        WHEN 'Sunday' THEN 7
                        ELSE 8
                    END
                ")
                ->orderBy('starting_time')
                ->paginate($perPage)->withQueryString();
                
                // Load assigned teachers for each timetable
                $timetables->getCollection()->transform(function($timetable) {
                    // Skip static subjects (like [Assembly], [Lunch Break], etc.)
                    if (strpos($timetable->subject, '[') === 0) {
                        $timetable->assigned_teacher = null;
                        return $timetable;
                    }
                    
                    $resolved = $this->resolveAssignedTeacher(
                        (string) $timetable->subject,
                        $timetable->campus,
                        $timetable->class,
                        $timetable->section
                    );
                    $timetable->assigned_teacher = $resolved['teacher'];
                    
                    return $timetable;
                });
            } else {
                // Return empty paginator when no filters are applied
                $timetables = new \Illuminate\Pagination\LengthAwarePaginator(
                    collect(),
                    0,
                    10,
                    1,
                    ['path' => $request->url(), 'query' => $request->query()]
                );
            }
        } else {
            // If no existing classes, return empty paginator
            $timetables = new \Illuminate\Pagination\LengthAwarePaginator(
                collect(),
                0,
                10,
                1,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        }
        
        // Get sections from Section model based on selected class
        $sections = collect();
        if ($request->filled('filter_class')) {
            $sections = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($request->filter_class))])
                ->whereNotNull('name')
                ->distinct()
                ->orderBy('name', 'asc')
                ->pluck('name')
                ->sort()
                ->values();
            
            // Also filter by campus if provided
            if ($request->filled('filter_campus')) {
                $sections = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($request->filter_class))])
                    ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($request->filter_campus))])
                    ->whereNotNull('name')
                    ->distinct()
                    ->orderBy('name', 'asc')
                    ->pluck('name')
                    ->sort()
                    ->values();
            }
        } else {
            // If no class selected, get all sections from existing classes only
            if (!empty($existingClassNames)) {
                $sectionsQuery = Section::where(function($q) use ($existingClassNames) {
                    foreach ($existingClassNames as $className) {
                        $q->orWhereRaw('LOWER(TRIM(COALESCE(class, ""))) = ?', [strtolower(trim($className))]);
                    }
                });
                
                // Filter by campus if provided
                if ($request->filled('filter_campus')) {
                    $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($request->filter_campus))]);
                }
                
                $sections = $sectionsQuery
                    ->whereNotNull('name')
                    ->distinct()
                    ->orderBy('name', 'asc')
                    ->pluck('name')
                    ->sort()
                    ->values();
            }
        }
        
        // Get subjects from Subject model (only active/non-deleted subjects)
        // Filter out subjects with deleted classes to ensure only valid subjects are shown
        $existingClassNames = ClassModel::whereNotNull('class_name')
            ->where('class_name', '!=', '')
            ->pluck('class_name')
            ->map(function($name) {
                return strtolower(trim($name));
            })->toArray();
        
        $subjects = Subject::whereNotNull('subject_name')
            ->when(!empty($existingClassNames), function($query) use ($existingClassNames) {
                return $query->where(function($q) use ($existingClassNames) {
                    foreach ($existingClassNames as $className) {
                        $q->orWhereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($className))]);
                    }
                });
            })
            ->distinct()
            ->orderBy('subject_name', 'asc')
            ->pluck('subject_name')
            ->sort()
            ->values();
        
        // Days of the week
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        
        return view('timetable.manage', compact('timetables', 'campuses', 'classes', 'sections', 'subjects', 'days'));
    }

    /**
     * Store a newly created timetable.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string', 'max:255'],
            'class' => ['required', 'string', 'max:255'],
            'section' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'day' => ['required', 'string', 'max:255'],
            'starting_time' => ['required', 'string'],
            'ending_time' => ['required', 'string'],
        ]);

        $hasConflict = Timetable::where('campus', $validated['campus'])
            ->where('class', $validated['class'])
            ->where('section', $validated['section'])
            ->where('day', $validated['day'])
            ->where('starting_time', $validated['starting_time'])
            ->exists();

        if ($hasConflict) {
            $message = 'Same time already exists for this class and section.';
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'errors' => [
                        'starting_time' => [$message],
                    ],
                ], 422);
            }

            return back()
                ->withErrors(['starting_time' => $message])
                ->withInput();
        }

        $timetable = Timetable::create($validated);
        $this->notifyTeacherForTimetable($timetable);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Timetable created successfully!',
            ], 201);
        }

        return redirect()
            ->route('timetable.add')
            ->with('success', 'Timetable created successfully!');
    }

    /**
     * Show the form for editing the specified timetable.
     */
    public function edit(Timetable $timetable): View
    {
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
        
        // Get classes from ClassModel (only existing classes, not deleted ones)
        $classes = ClassModel::whereNotNull('class_name')
            ->where('class_name', '!=', '')
            ->orderBy('class_name', 'asc')
            ->get();
        
        // If no classes found, provide empty collection
        if ($classes->isEmpty()) {
            $classes = collect();
        }
        
        // Get sections for the selected class
        $sections = Section::where('class', $timetable->class)
            ->whereNotNull('name')
            ->distinct()
            ->orderBy('name', 'asc')
            ->pluck('name')
            ->sort()
            ->values();
        
        // Get subjects from Subject model (only active/non-deleted subjects)
        // Filter out subjects with deleted classes to ensure only valid subjects are shown
        $existingClassNames = ClassModel::whereNotNull('class_name')
            ->where('class_name', '!=', '')
            ->pluck('class_name')
            ->map(function($name) {
                return strtolower(trim($name));
            })->toArray();
        
        $subjects = Subject::whereNotNull('subject_name')
            ->when(!empty($existingClassNames), function($query) use ($existingClassNames) {
                return $query->where(function($q) use ($existingClassNames) {
                    foreach ($existingClassNames as $className) {
                        $q->orWhereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($className))]);
                    }
                });
            })
            ->distinct()
            ->orderBy('subject_name', 'asc')
            ->pluck('subject_name')
            ->sort()
            ->values();
        
        // Days of the week
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        
        return view('timetable.edit', compact('timetable', 'campuses', 'classes', 'sections', 'subjects', 'days'));
    }

    /**
     * Update the specified timetable in storage.
     */
    public function update(Request $request, Timetable $timetable): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string', 'max:255'],
            'class' => ['required', 'string', 'max:255'],
            'section' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'day' => ['required', 'string', 'max:255'],
            'starting_time' => ['required', 'string'],
            'ending_time' => ['required', 'string'],
        ]);

        $hasConflict = Timetable::where('campus', $validated['campus'])
            ->where('class', $validated['class'])
            ->where('section', $validated['section'])
            ->where('day', $validated['day'])
            ->where('starting_time', $validated['starting_time'])
            ->where('id', '!=', $timetable->id)
            ->exists();

        if ($hasConflict) {
            return back()
                ->withErrors(['starting_time' => 'Same time already exists for this class and section.'])
                ->withInput();
        }

        $timetable->update($validated);
        $this->notifyTeacherForTimetable($timetable->fresh());

        return redirect()
            ->route('timetable.manage')
            ->with('success', 'Timetable updated successfully!');
    }

    private function notifyTeacherForTimetable(Timetable $timetable): void
    {
        try {
            $staff = $this->findStaffForTimetable($timetable);
            if (!$staff) {
                Log::info('Timetable notification skipped: teacher not resolved', [
                    'timetable_id' => $timetable->id,
                    'subject' => $timetable->subject,
                    'campus' => $timetable->campus,
                    'class' => $timetable->class,
                    'section' => $timetable->section,
                ]);

                return;
            }

            $this->pushNotifications->notifyStaffTimetableAssigned($staff, $timetable);

            Log::info('Timetable notification dispatched', [
                'timetable_id' => $timetable->id,
                'staff_id' => $staff->id,
                'staff_name' => $staff->name,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function findStaffForTimetable(Timetable $timetable): ?Staff
    {
        if (in_array((string) $timetable->subject, $this->getStaticSubjects(), true)) {
            return null;
        }

        $teacherNames = [];
        $resolved = $this->resolveAssignedTeacher(
            (string) $timetable->subject,
            $timetable->campus,
            $timetable->class,
            $timetable->section
        );

        if (!empty($resolved['teacher'])) {
            $teacherNames[] = trim((string) $resolved['teacher']);
        }

        $subjectNorm = strtolower(trim((string) $timetable->subject));
        $campusNorm = strtolower(trim((string) ($timetable->campus ?? '')));
        $classNorm = strtolower(trim((string) ($timetable->class ?? '')));
        $sectionNorm = strtolower(trim((string) ($timetable->section ?? '')));

        $directQuery = Subject::query()
            ->whereRaw('LOWER(TRIM(subject_name)) = ?', [$subjectNorm])
            ->whereNotNull('teacher')
            ->where('teacher', '!=', '');

        if ($classNorm !== '') {
            $directQuery->whereRaw('LOWER(TRIM(class)) = ?', [$classNorm]);
        }
        if ($sectionNorm !== '') {
            $directQuery->whereRaw('LOWER(TRIM(section)) = ?', [$sectionNorm]);
        }
        if ($campusNorm !== '') {
            $directQuery->where(function ($q) use ($campusNorm) {
                $q->whereRaw('LOWER(TRIM(campus)) = ?', [$campusNorm])
                    ->orWhereNull('campus')
                    ->orWhereRaw("TRIM(COALESCE(campus, '')) = ''");
            });
        }

        foreach ($directQuery->pluck('teacher') as $name) {
            $name = trim((string) $name);
            if ($name !== '') {
                $teacherNames[] = $name;
            }
        }

        foreach (array_unique($teacherNames) as $teacherName) {
            $staff = $this->findStaffByTeacherName($teacherName);
            if ($staff) {
                return $staff;
            }
        }

        return null;
    }

    private function findStaffByTeacherName(string $teacherName): ?Staff
    {
        $normalized = strtolower(trim($teacherName));
        if ($normalized === '') {
            return null;
        }

        return Staff::query()
            ->where(function ($q) use ($normalized) {
                $q->whereRaw('LOWER(TRIM(name)) = ?', [$normalized])
                    ->orWhereRaw('LOWER(TRIM(email)) = ?', [$normalized])
                    ->orWhereRaw('LOWER(TRIM(emp_id)) = ?', [$normalized]);
            })
            ->first();
    }

    /**
     * Remove the specified timetable from storage.
     */
    public function destroy(Timetable $timetable): RedirectResponse|JsonResponse
    {
        $timetable->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'id' => $timetable->id,
            ]);
        }

        return redirect()
            ->route('timetable.manage')
            ->with('success', 'Timetable deleted successfully!');
    }

    /**
     * Export timetables to PDF
     */
    public function export(Request $request, string $format)
    {
        $query = Timetable::query();
        
        // Apply filters if present
        if ($request->filled('filter_campus')) {
            $query->where('campus', $request->filter_campus);
        }
        
        if ($request->filled('filter_class')) {
            $query->where('class', $request->filter_class);
        }
        
        if ($request->filled('filter_section')) {
            $query->where('section', $request->filter_section);
        }
        
        $timetables = $query->orderBy('day')->orderBy('starting_time')->get();
        
        switch ($format) {
            case 'pdf':
                return $this->exportPDF($timetables);
            default:
                return redirect()->route('timetable.manage')
                    ->with('error', 'Invalid export format!');
        }
    }

    /**
     * Print timetables (dedicated print page)
     */
    public function print(Request $request): View
    {
        $query = Timetable::query();

        if ($request->filled('filter_campus')) {
            $query->where('campus', $request->filter_campus);
        }

        if ($request->filled('filter_class')) {
            $query->where('class', $request->filter_class);
        }

        if ($request->filled('filter_section')) {
            $query->where('section', $request->filter_section);
        }

        if ($request->filled('filter_day')) {
            $query->where('day', $request->filter_day);
        }

        $timetables = $query
            ->orderByRaw("
                CASE day
                    WHEN 'Monday' THEN 1
                    WHEN 'Tuesday' THEN 2
                    WHEN 'Wednesday' THEN 3
                    WHEN 'Thursday' THEN 4
                    WHEN 'Friday' THEN 5
                    WHEN 'Saturday' THEN 6
                    WHEN 'Sunday' THEN 7
                    ELSE 8
                END
            ")
            ->orderBy('starting_time')
            ->get();

        // Load assigned teacher (same behavior as manage list)
        $timetables->transform(function ($timetable) {
            if (strpos((string) $timetable->subject, '[') === 0) {
                $timetable->assigned_teacher = null;
                return $timetable;
            }

            $resolved = $this->resolveAssignedTeacher(
                (string) $timetable->subject,
                $timetable->campus,
                $timetable->class,
                $timetable->section
            );
            $timetable->assigned_teacher = $resolved['teacher'];

            return $timetable;
        });

        $settings = GeneralSetting::getSettings();

        return view('timetable.manage-print', [
            'timetables' => $timetables,
            'settings' => $settings,
            'printedAt' => now()->format('d M Y, h:i A'),
        ]);
    }

    /**
     * Get sections by class name (AJAX).
     */
    /**
     * Resolve staff for a subject — always scoped to campus when campus is known.
     *
     * @return array{teacher: ?string, note: ?string}
     */
    private function resolveAssignedTeacher(
        string $subject,
        ?string $campus = null,
        ?string $class = null,
        ?string $section = null,
    ): array {
        if (in_array($subject, $this->getStaticSubjects(), true)) {
            return ['teacher' => null, 'note' => null];
        }

        $subjectNorm = strtolower(trim($subject));
        $campusNorm = $campus !== null ? strtolower(trim($campus)) : '';
        $classNorm = $class !== null ? strtolower(trim($class)) : '';
        $sectionNorm = $section !== null ? strtolower(trim($section)) : '';

        $scopedQuery = function () use ($subjectNorm, $campusNorm) {
            $q = Subject::query()
                ->whereRaw('LOWER(TRIM(subject_name)) = ?', [$subjectNorm])
                ->whereNotNull('teacher')
                ->where('teacher', '!=', '');

            if ($campusNorm !== '') {
                $q->where(function ($inner) use ($campusNorm) {
                    $inner->whereRaw('LOWER(TRIM(campus)) = ?', [$campusNorm])
                        ->orWhereNull('campus')
                        ->orWhereRaw("TRIM(COALESCE(campus, '')) = ''");
                });
            }

            return $q;
        };

        if ($campusNorm !== '' && $classNorm !== '' && $sectionNorm !== '') {
            $exact = $scopedQuery()
                ->whereRaw('LOWER(TRIM(class)) = ?', [$classNorm])
                ->whereRaw('LOWER(TRIM(section)) = ?', [$sectionNorm])
                ->first();

            if ($exact?->teacher) {
                return ['teacher' => $exact->teacher, 'note' => null];
            }
        }

        if ($campusNorm !== '' && $classNorm !== '') {
            $byClass = $scopedQuery()
                ->whereRaw('LOWER(TRIM(class)) = ?', [$classNorm])
                ->first();

            if ($byClass?->teacher) {
                return [
                    'teacher' => $byClass->teacher,
                    'note' => 'Assigned to this class (different section)',
                ];
            }
        }

        if ($campusNorm !== '') {
            $byCampus = $scopedQuery()->first();

            if ($byCampus?->teacher) {
                return [
                    'teacher' => $byCampus->teacher,
                    'note' => 'Assigned in this campus (different class/section)',
                ];
            }

            return ['teacher' => null, 'note' => null];
        }

        // Legacy: no campus on request — still avoid cross-campus by preferring class/section match first.
        $query = Subject::query()
            ->whereRaw('LOWER(TRIM(subject_name)) = ?', [$subjectNorm])
            ->whereNotNull('teacher')
            ->where('teacher', '!=', '');

        if ($classNorm !== '') {
            $query->whereRaw('LOWER(TRIM(class)) = ?', [$classNorm]);
        }
        if ($sectionNorm !== '') {
            $query->whereRaw('LOWER(TRIM(section)) = ?', [$sectionNorm]);
        }

        $match = $query->first();
        if ($match?->teacher) {
            return ['teacher' => $match->teacher, 'note' => null];
        }

        return ['teacher' => null, 'note' => null];
    }

    /**
     * Get teacher assigned to a subject (AJAX)
     */
    public function getTeacherBySubject(Request $request): JsonResponse
    {
        $subject = trim((string) $request->get('subject', ''));
        $campus = $request->get('campus');
        $class = $request->get('class');
        $section = $request->get('section');

        if ($subject === '') {
            return response()->json(['teacher' => null]);
        }

        if (in_array($subject, $this->getStaticSubjects(), true)) {
            return response()->json(['teacher' => null, 'message' => 'Static subject - no teacher assigned']);
        }

        $resolved = $this->resolveAssignedTeacher($subject, $campus, $class, $section);

        if ($resolved['teacher']) {
            return response()->json([
                'teacher' => $resolved['teacher'],
                'note' => $resolved['note'],
            ]);
        }

        return response()->json([
            'teacher' => null,
            'message' => $campus
                ? 'No teacher assigned for this subject in the selected campus.'
                : 'No teacher assigned',
        ]);
    }

    public function getSectionsByClass(Request $request)
    {
        $className = $request->get('class');
        $campus = $request->get('campus');
        
        if (!$className) {
            return response()->json(['sections' => []]);
        }
        
        $sectionsQuery = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($className))]);
        if ($campus) {
            $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        $sections = $sectionsQuery
            ->whereNotNull('name')
            ->distinct()
            ->orderBy('name', 'asc')
            ->pluck('name')
            ->sort()
            ->values();
        
        return response()->json(['sections' => $sections]);
    }

    /**
     * Get subjects by campus, class, and section (AJAX).
     */
    public function getSubjects(Request $request): JsonResponse
    {
        $campus = $request->get('campus');
        $class = $request->get('class');
        $section = $request->get('section');
        
        // Get existing class names for filtering
        $existingClassNames = ClassModel::whereNotNull('class_name')
            ->where('class_name', '!=', '')
            ->pluck('class_name')
            ->map(function($name) {
                return strtolower(trim($name));
            })->toArray();
        
        $subjectsQuery = Subject::whereNotNull('subject_name');
        
        // Filter by campus if provided
        if ($campus) {
            $subjectsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        
        // Filter by class if provided
        if ($class) {
            $subjectsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
        }
        
        // Filter by section if provided
        if ($section) {
            $subjectsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($section))]);
        }
        
        // Filter out subjects with deleted classes
        if (!empty($existingClassNames)) {
            $subjectsQuery->where(function($q) use ($existingClassNames) {
                foreach ($existingClassNames as $className) {
                    $q->orWhereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($className))]);
                }
            });
        } else {
            // If no classes exist, return empty
            return response()->json(['subjects' => []]);
        }
        
        $subjects = $subjectsQuery
            ->distinct()
            ->orderBy('subject_name', 'asc')
            ->pluck('subject_name')
            ->sort()
            ->values();
        
        // Add static subjects
        $staticSubjects = collect($this->getStaticSubjects());
        $allSubjects = $subjects->merge($staticSubjects)->unique()->values();
        
        return response()->json(['subjects' => $allSubjects]);
    }

    /**
     * Export to PDF
     */
    private function exportPDF($timetables)
    {
        $html = view('timetable-pdf', compact('timetables'))->render();
        
        return response($html)
            ->header('Content-Type', 'text/html');
    }

    /**
     * Print a single timetable row (A4 letterhead from General Settings — not thermal).
     */
    public function terminalPrint(Timetable $timetable)
    {
        if (strpos((string) $timetable->subject, '[') === 0) {
            $timetable->assigned_teacher = null;
        } else {
            $resolved = $this->resolveAssignedTeacher(
                (string) $timetable->subject,
                $timetable->campus,
                $timetable->class,
                $timetable->section
            );
            $timetable->assigned_teacher = $resolved['teacher'];
        }

        return view('timetable.terminal-print', [
            'timetable' => $timetable,
            'settings' => GeneralSetting::getSettings(),
            'printedAt' => now()->format('d M Y, h:i A'),
        ]);
    }
}
