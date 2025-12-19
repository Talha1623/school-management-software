<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OnlineClass;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeacherOnlineClassController extends Controller
{
    /**
     * Helper: get classes and sections assigned to this teacher based on Subject + Section tables.
     */
    protected function getAssignedClassesAndSections($teacher): array
    {
        $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
            ->get();

        $assignedSectionsModels = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
            ->get();

        $assignedClasses = $assignedSubjects->pluck('class')
            ->merge($assignedSectionsModels->pluck('class'))
            ->map(function ($class) {
                return trim($class);
            })
            ->filter(function ($class) {
                return !empty($class);
            })
            ->unique()
            ->values();

        $assignedSections = $assignedSubjects->pluck('section')
            ->merge($assignedSectionsModels->pluck('name'))
            ->map(function ($section) {
                return trim($section);
            })
            ->filter(function ($section) {
                return !empty($section);
            })
            ->unique()
            ->values();

        return [$assignedClasses, $assignedSections];
    }

    /**
     * List online classes visible to this teacher (only their assigned classes).
     *
     * GET /api/teacher/online-classes
     * Optional filters: class, section, date_from, date_to
     */
    public function index(Request $request): JsonResponse
    {
        $teacher = $request->user();

        if (!$teacher || strtolower(trim($teacher->designation ?? '')) !== 'teacher') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Only teachers can access online classes.',
            ], 403);
        }

        [$assignedClasses, $assignedSections] = $this->getAssignedClassesAndSections($teacher);

        $query = OnlineClass::query();

        // Restrict to teacher-assigned classes
        if ($assignedClasses->isNotEmpty()) {
            $query->where(function ($q) use ($assignedClasses) {
                foreach ($assignedClasses as $class) {
                    $q->orWhereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
                }
            });
        } else {
            $query->whereRaw('1 = 0');
        }

        // Optional filters
        if ($request->filled('class')) {
            $class = trim($request->class);
            if ($assignedClasses->contains(function ($c) use ($class) {
                return strtolower(trim($c)) === strtolower(trim($class));
            })) {
                $query->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($class)]);
            }
        }

        if ($request->filled('section')) {
            $section = trim($request->section);
            if ($assignedSections->contains(function ($s) use ($section) {
                return strtolower(trim($s)) === strtolower(trim($section));
            })) {
                $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($section)]);
            }
        }

        if ($request->filled('date')) {
            $query->whereDate('start_date', $request->date);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('start_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('start_date', '<=', $request->date_to);
        }

        $perPage = $request->get('per_page', 10);
        $perPage = in_array((int)$perPage, [10, 25, 50, 100], true) ? (int)$perPage : 10;

        $classes = $query->orderBy('start_date', 'desc')
            ->orderBy('start_time', 'asc')
            ->paginate($perPage);

        $data = $classes->map(function (OnlineClass $class) {
            return [
                'id' => $class->id,
                'campus' => $class->campus,
                'class' => $class->class,
                'section' => $class->section,
                'class_topic' => $class->class_topic,
                'start_date' => $class->start_date?->format('Y-m-d'),
                'start_time' => $class->start_time,
                'timing' => $class->timing,
                'password' => $class->password,
                'created_by' => $class->created_by,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Online classes loaded successfully.',
            'data' => [
                'classes' => $data,
                'pagination' => [
                    'current_page' => $classes->currentPage(),
                    'last_page' => $classes->lastPage(),
                    'per_page' => $classes->perPage(),
                    'total' => $classes->total(),
                    'from' => $classes->firstItem(),
                    'to' => $classes->lastItem(),
                ],
            ],
        ], 200);
    }

    /**
     * Create a new online class from teacher app.
     *
     * POST /api/teacher/online-classes
     */
    public function store(Request $request): JsonResponse
    {
        $teacher = $request->user();

        if (!$teacher || strtolower(trim($teacher->designation ?? '')) !== 'teacher') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Only teachers can create online classes.',
            ], 403);
        }

        $validated = $request->validate([
            'campus' => ['required', 'string', 'max:255'],
            'class' => ['required', 'string', 'max:255'],
            'section' => ['required', 'string', 'max:255'],
            'class_topic' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'start_time' => ['nullable', 'string'],
            'timing' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:4'],
        ]);

        [$assignedClasses, $assignedSections] = $this->getAssignedClassesAndSections($teacher);

        $class = trim($validated['class']);
        $section = trim($validated['section']);

        if (!$assignedClasses->contains(function ($c) use ($class) {
            return strtolower(trim($c)) === strtolower(trim($class));
        })) {
            return response()->json([
                'success' => false,
                'message' => 'This class is not assigned to you.',
            ], 403);
        }

        if (!$assignedSections->contains(function ($s) use ($section) {
            return strtolower(trim($s)) === strtolower(trim($section));
        })) {
            return response()->json([
                'success' => false,
                'message' => 'This section is not assigned to you.',
            ], 403);
        }

        $onlineClass = OnlineClass::create([
            'campus' => $validated['campus'],
            'class' => $class,
            'section' => $section,
            'class_topic' => $validated['class_topic'],
            'start_date' => $validated['start_date'],
            'start_time' => $validated['start_time'] ?? null,
            'timing' => $validated['timing'],
            'password' => $validated['password'],
            'created_by' => $teacher->name ?? $teacher->email ?? 'Teacher API',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Online class created successfully.',
            'data' => [
                'id' => $onlineClass->id,
            ],
        ], 201);
    }
}


