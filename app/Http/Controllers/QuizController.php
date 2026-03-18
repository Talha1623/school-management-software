<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Campus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Schema;

class QuizController extends Controller
{
    /**
     * Display a listing of quizzes.
     */
    public function index(Request $request): View
    {
        $query = Quiz::query();
        
        // Search functionality
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(quiz_name) LIKE ?', ["%{$searchLower}"])
                      ->orWhereRaw('LOWER(campus) LIKE ?', ["%{$searchLower}"])
                      ->orWhereRaw('LOWER(for_class) LIKE ?', ["%{$searchLower}"])
                      ->orWhereRaw('LOWER(section) LIKE ?', ["%{$searchLower}"]);
                });
            }
        }
        
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $quizzes = $query->orderBy('start_date_time', 'desc')->paginate($perPage)->withQueryString();

        // Get campuses for dropdown - First from Campus model, then fallback
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
            $allCampuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort();
            $campuses = $allCampuses->map(function($campus) {
                return (object)['campus_name' => $campus];
            });
        }

        // Get classes (dynamic only, no static fallback)
        $classes = $this->getClassesForCampus(null);

        // Sections will be loaded dynamically via AJAX based on class selection
        $sections = collect();
        
        return view('quiz.manage', compact('quizzes', 'campuses', 'classes', 'sections'));
    }

    /**
     * Store a newly created quiz.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string', 'max:255'],
            'quiz_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'for_class' => ['required', 'string', 'max:255'],
            'section' => ['required', 'string', 'max:255'],
            'total_questions' => ['required', 'integer', 'min:1'],
            'start_date_time' => ['required', 'date'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
        ]);

        Quiz::create($validated);

        return redirect()
            ->route('quiz.manage')
            ->with('success', 'Quiz created successfully!');
    }

    /**
     * Update the specified quiz.
     */
    public function update(Request $request, Quiz $quiz): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string', 'max:255'],
            'quiz_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'for_class' => ['required', 'string', 'max:255'],
            'section' => ['required', 'string', 'max:255'],
            'total_questions' => ['required', 'integer', 'min:1'],
            'start_date_time' => ['required', 'date'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
        ]);

        $quiz->update($validated);

        return redirect()
            ->route('quiz.manage')
            ->with('success', 'Quiz updated successfully!');
    }

    /**
     * Remove the specified quiz.
     */
    public function destroy(Quiz $quiz): RedirectResponse
    {
        $quiz->delete();

        return redirect()
            ->route('quiz.manage')
            ->with('success', 'Quiz deleted successfully!');
    }

    /**
     * Export quizzes to Excel, CSV, or PDF
     */
    public function export(Request $request, string $format)
    {
        $query = Quiz::query();
        
        // Apply search filter if present
        if ($request->has('search') && $request->search) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(quiz_name) LIKE ?', ["%{$searchLower}"])
                      ->orWhereRaw('LOWER(campus) LIKE ?', ["%{$searchLower}"])
                      ->orWhereRaw('LOWER(for_class) LIKE ?', ["%{$searchLower}"])
                      ->orWhereRaw('LOWER(section) LIKE ?', ["%{$searchLower}"]);
                });
            }
        }
        
        $quizzes = $query->orderBy('start_date_time', 'desc')->get();
        
        switch ($format) {
            case 'excel':
                return $this->exportExcel($quizzes);
            case 'pdf':
                return $this->exportPDF($quizzes);
            default:
                return redirect()->route('quiz.manage')
                    ->with('error', 'Invalid export format!');
        }
    }

    /**
     * Export to Excel (CSV format for Excel compatibility)
     */
    private function exportExcel($quizzes)
    {
        $filename = 'quizzes_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($quizzes) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fputs($file, "\xEF\xBB\xBF");
            
            // Headers
            fputcsv($file, ['#', 'Campus', 'Quiz Name', 'Description', 'For Class', 'Section', 'Total Questions', 'Start Date & Time']);
            
            // Data
            foreach ($quizzes as $index => $quiz) {
                fputcsv($file, [
                    $index + 1,
                    $quiz->campus,
                    $quiz->quiz_name,
                    $quiz->description ?? '',
                    $quiz->for_class,
                    $quiz->section,
                    $quiz->total_questions,
                    $quiz->start_date_time->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to PDF
     */
    private function exportPDF($quizzes)
    {
        $html = view('quiz.manage-pdf', compact('quizzes'))->render();
        
        // Simple PDF generation (you can use DomPDF or similar package)
        return response($html)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="quizzes_' . date('Y-m-d_His') . '.pdf"');
    }

    /**
     * Get sections based on class (AJAX).
     */
    public function getSectionsByClass(Request $request): JsonResponse
    {
        $class = $request->get('class');
        $campus = $request->get('campus');
        if (!$class) {
            return response()->json(['sections' => []]);
        }
        
        $sectionsQuery = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
            ->whereNotNull('name');
        if ($campus) {
            $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        $sections = $sectionsQuery->distinct()
            ->pluck('name')
            ->sort()
            ->values();
        
        if ($sections->isEmpty()) {
            $subjectsQuery = Subject::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                ->whereNotNull('section');
            if ($campus) {
                $subjectsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            $sections = $subjectsQuery->distinct()
                ->pluck('section')
                ->sort()
                ->values();
        }
        
        return response()->json(['sections' => $sections]);
    }

    /**
     * Get classes based on campus (AJAX).
     */
    public function getClassesByCampus(Request $request): JsonResponse
    {
        $campus = $request->get('campus');
        $classes = $this->getClassesForCampus($campus);

        return response()->json(['classes' => $classes]);
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
     * Get quiz questions (AJAX).
     */
    public function getQuestions(Quiz $quiz): JsonResponse
    {
        try {
            if (!Schema::hasTable('quiz_questions')) {
                return response()->json(['questions' => []]);
            }
            
            $questions = $quiz->questions()->orderBy('question_number')->get();
            
            return response()->json([
                'questions' => $questions->map(function($q) {
                    return [
                        'question_number' => $q->question_number,
                        'question' => $q->question,
                        'answer1' => $q->answer1,
                        'marks1' => $q->marks1,
                        'answer2' => $q->answer2,
                        'marks2' => $q->marks2,
                        'answer3' => $q->answer3,
                        'marks3' => $q->marks3,
                    ];
                })
            ]);
        } catch (\Exception $e) {
            return response()->json(['questions' => []]);
        }
    }

    /**
     * Update quiz questions.
     */
    public function updateQuestions(Request $request, Quiz $quiz): RedirectResponse
    {
        $request->validate([
            'questions' => ['required', 'array'],
            'questions.*.question' => ['nullable', 'string'],
            'questions.*.answer1' => ['nullable', 'string'],
            'questions.*.marks1' => ['nullable', 'integer', 'min:0'],
            'questions.*.answer2' => ['nullable', 'string'],
            'questions.*.marks2' => ['nullable', 'integer', 'min:0'],
            'questions.*.answer3' => ['nullable', 'string'],
            'questions.*.marks3' => ['nullable', 'integer', 'min:0'],
        ]);

        // Ensure table exists, create if it doesn't
        if (!Schema::hasTable('quiz_questions')) {
            try {
                $this->createQuizQuestionsTable();
            } catch (\Exception $e) {
                return redirect()
                    ->route('quiz.manage')
                    ->with('error', 'Quiz questions table does not exist. Please run: php artisan fix:quiz-questions-table');
            }
        }

        // Delete existing questions (only if table exists)
        try {
            if (Schema::hasTable('quiz_questions')) {
                $quiz->questions()->delete();
            }
        } catch (\Exception $e) {
            // Table doesn't exist, continue without deleting
        }

        // Create new questions
        foreach ($request->questions as $questionNumber => $questionData) {
            if (!empty($questionData['question']) || !empty($questionData['answer1']) || !empty($questionData['answer2']) || !empty($questionData['answer3'])) {
                try {
                    QuizQuestion::create([
                        'quiz_id' => $quiz->id,
                        'question_number' => (int) $questionNumber,
                        'question' => $questionData['question'] ?? '',
                        'answer1' => $questionData['answer1'] ?? null,
                        'marks1' => (int) ($questionData['marks1'] ?? 0),
                        'answer2' => $questionData['answer2'] ?? null,
                        'marks2' => (int) ($questionData['marks2'] ?? 0),
                        'answer3' => $questionData['answer3'] ?? null,
                        'marks3' => (int) ($questionData['marks3'] ?? 0),
                    ]);
                } catch (\Exception $e) {
                    // If table still doesn't exist, show error
                    if (str_contains($e->getMessage(), "doesn't exist")) {
                        return redirect()
                            ->route('quiz.manage')
                            ->with('error', 'Quiz questions table does not exist. Please run: php artisan fix:quiz-questions-table');
                    }
                    throw $e;
                }
            }
        }

        return redirect()
            ->route('quiz.manage')
            ->with('success', 'Quiz questions updated successfully!');
    }

    /**
     * Get quiz result (marks) for display.
     */
    public function getResult(Quiz $quiz): JsonResponse
    {
        try {
            if (!Schema::hasTable('quiz_questions')) {
                return response()->json(['questions' => []]);
            }
            
            $questions = $quiz->questions()->orderBy('question_number')->get();
            
            return response()->json([
                'questions' => $questions->map(function($q) {
                    return [
                        'question_number' => $q->question_number,
                        'question' => $q->question,
                        'answer1' => $q->answer1,
                        'marks1' => $q->marks1,
                        'answer2' => $q->answer2,
                        'marks2' => $q->marks2,
                        'answer3' => $q->answer3,
                        'marks3' => $q->marks3,
                    ];
                })
            ]);
        } catch (\Exception $e) {
            return response()->json(['questions' => []]);
        }
    }

    /**
     * Create quiz_questions table if it doesn't exist.
     */
    private function createQuizQuestionsTable(): void
    {
        if (Schema::hasTable('quiz_questions')) {
            return;
        }

        try {
            // Try to drop any orphaned table reference first
            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0');
            \Illuminate\Support\Facades\DB::statement('DROP TABLE IF EXISTS quiz_questions');
            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1');
        } catch (\Exception $e) {
            // Ignore drop errors
        }

        // Create the table
        Schema::create('quiz_questions', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quiz_id');
            $table->integer('question_number');
            $table->text('question')->nullable();
            $table->string('answer1')->nullable();
            $table->integer('marks1')->default(0);
            $table->string('answer2')->nullable();
            $table->integer('marks2')->default(0);
            $table->string('answer3')->nullable();
            $table->integer('marks3')->default(0);
            $table->timestamps();
        });

        // Add foreign key separately
        try {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE quiz_questions ADD CONSTRAINT quiz_questions_quiz_id_foreign FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE');
        } catch (\Exception $e) {
            // Foreign key might already exist or fail, continue
        }
    }
}

