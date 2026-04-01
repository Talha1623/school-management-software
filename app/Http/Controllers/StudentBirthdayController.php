<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentNotification;
use App\Models\GeneralSetting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class StudentBirthdayController extends Controller
{
    /**
     * Display the student birthdays page.
     */
    public function index(): View
    {
        $students = $this->getStudentsData();

        return view('student.birthday', compact('students'));
    }

    /**
     * Export student birthdays to Excel, CSV, or PDF
     */
    public function export(Request $request, string $format)
    {
        $students = $this->getStudentsData();
        
        switch ($format) {
            case 'excel':
                return $this->exportExcel($students);
            case 'csv':
                return $this->exportCSV($students);
            case 'pdf':
                return $this->exportPDF($students);
            default:
                return redirect()->route('student.birthday')
                    ->with('error', 'Invalid export format!');
        }
    }

    /**
     * Print student birthdays list (dedicated print page like Stock/Inquiries)
     */
    public function print(Request $request): View
    {
        $students = $this->getStudentsData();
        $settings = GeneralSetting::getSettings();

        return view('student.birthday-print', compact('students', 'settings'));
    }

    /**
     * Print birthday card for a student.
     */
    public function printBirthdayCard(Student $student): View
    {
        return view('student.birthday-card-print', [
            'student' => $student,
            'printedAt' => now()->format('d-m-Y H:i'),
            'autoPrint' => request()->get('auto_print'),
        ]);
    }

    /**
     * Send birthday wish notification to a single student (in-app notification).
     * POST /student/birthday/{student}/wish
     */
    public function wish(Request $request, Student $student): JsonResponse
    {
        // Basic auth guard: allow admin/staff session only
        if (!Auth::guard('admin')->check() && !Auth::guard('staff')->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $title = $request->input('title') ?: 'Happy Birthday!';
        $message = $request->input('message') ?: ('Happy Birthday ' . ($student->student_name ?? 'Student') . '! 🎉');

        $creatorType = Auth::guard('admin')->check() ? 'admin' : 'staff';
        $creatorId = Auth::guard('admin')->check()
            ? (Auth::guard('admin')->user()->id ?? null)
            : (Auth::guard('staff')->user()->id ?? null);

        StudentNotification::create([
            'student_id' => $student->id,
            'title' => $title,
            'message' => $message,
            'data' => [
                'type' => 'birthday_wish',
                'student_id' => $student->id,
            ],
            'created_by_type' => $creatorType,
            'created_by_id' => $creatorId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Wish sent successfully',
        ]);
    }

    /**
     * Send birthday wishes to multiple students (in-app notifications).
     * POST /student/birthday/wish-all
     * Body: { student_ids?: [1,2,3], title?: string, message?: string }
     */
    public function wishAll(Request $request): JsonResponse
    {
        if (!Auth::guard('admin')->check() && !Auth::guard('staff')->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['integer'],
            'title' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $title = $validated['title'] ?? 'Happy Birthday!';
        $messageTemplate = $validated['message'] ?? 'Happy Birthday {name}! 🎉';

        $creatorType = Auth::guard('admin')->check() ? 'admin' : 'staff';
        $creatorId = Auth::guard('admin')->check()
            ? (Auth::guard('admin')->user()->id ?? null)
            : (Auth::guard('staff')->user()->id ?? null);

        $query = Student::query()->whereNotNull('date_of_birth');

        // If IDs provided, target only those. Otherwise, target "today birthdays".
        if (!empty($validated['student_ids'])) {
            $query->whereIn('id', $validated['student_ids']);
        } else {
            $today = Carbon::today();
            $query->whereRaw("DATE_FORMAT(date_of_birth, '%m-%d') = ?", [$today->format('m-d')]);
        }

        $students = $query->get(['id', 'student_name']);

        if ($students->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No students found to send wishes.',
            ], 200);
        }

        $rows = $students->map(function (Student $s) use ($title, $messageTemplate, $creatorType, $creatorId) {
            $name = $s->student_name ?: 'Student';
            $message = str_replace('{name}', $name, $messageTemplate);

            return [
                'student_id' => $s->id,
                'title' => $title,
                'message' => $message,
                'data' => json_encode([
                    'type' => 'birthday_wish',
                    'student_id' => $s->id,
                ]),
                'read_at' => null,
                'created_by_type' => $creatorType,
                'created_by_id' => $creatorId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->values()->all();

        // Bulk insert for performance
        \DB::table('student_notifications')->insert($rows);

        return response()->json([
            'success' => true,
            'message' => 'Wishes sent successfully',
            'data' => [
                'count' => count($rows),
            ],
        ]);
    }

    /**
     * Get students data (empty for now)
     */
    private function getStudentsData()
    {
        $today = Carbon::today();

        $students = Student::whereNotNull('date_of_birth')
            ->whereNotNull('class')
            ->where('class', '!=', '')
            ->orderByRaw("DATE_FORMAT(date_of_birth, '%m-%d') asc")
            ->get();

        return $students->map(function (Student $student) use ($today) {
            $birthday = $student->date_of_birth ? Carbon::parse($student->date_of_birth) : null;
            $status = 'N/A';
            $wish = 'Pending';
            $birthdayCard = 'Pending';

            if ($birthday) {
                $birthdayThisYear = $birthday->copy()->year($today->year);
                if ($birthdayThisYear->isSameDay($today)) {
                    $status = 'Today';
                    $wish = 'Sent';
                    $birthdayCard = 'Sent';
                } elseif ($birthdayThisYear->isAfter($today)) {
                    $status = 'Upcoming';
                } else {
                    $status = 'Past';
                }
            }

            return [
                'id' => $student->id,
                'roll' => $student->student_code ?? $student->gr_number ?? 'N/A',
                'student' => $student->student_name ?? 'N/A',
                'parent' => $student->father_name ?? 'N/A',
                'class' => $student->class ?? 'N/A',
                'section' => $student->section ?? 'N/A',
                'birthday' => $student->date_of_birth ? $student->date_of_birth->format('Y-m-d') : null,
                'status' => $status,
                'birthday_card' => $birthdayCard,
                'wish' => $wish,
                'picture' => $student->photo ? Storage::url($student->photo) : null,
                'parent_phone' => $student->whatsapp_number ?: $student->father_phone,
            ];
        })->values();
    }

    /**
     * Export to Excel (CSV format for Excel compatibility)
     */
    private function exportExcel($students)
    {
        $filename = 'student_birthdays_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($students) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for Excel UTF-8 compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Add headers
            fputcsv($file, ['Roll', 'Student', 'Parent', 'Class', 'Section', 'Birthday', 'Status', 'Birthday Card', 'Wish']);
            
            // Add data rows
            foreach ($students as $student) {
                fputcsv($file, [
                    $student['roll'],
                    $student['student'],
                    $student['parent'],
                    $student['class'],
                    $student['section'],
                    $student['birthday'],
                    $student['status'],
                    $student['birthday_card'],
                    $student['wish'],
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to CSV
     */
    private function exportCSV($students)
    {
        $filename = 'student_birthdays_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($students) {
            $file = fopen('php://output', 'w');
            
            // Add headers
            fputcsv($file, ['Roll', 'Student', 'Parent', 'Class', 'Section', 'Birthday', 'Status', 'Birthday Card', 'Wish']);
            
            // Add data rows
            foreach ($students as $student) {
                fputcsv($file, [
                    $student['roll'],
                    $student['student'],
                    $student['parent'],
                    $student['class'],
                    $student['section'],
                    $student['birthday'],
                    $student['status'],
                    $student['birthday_card'],
                    $student['wish'],
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to PDF
     */
    private function exportPDF($students)
    {
        $html = view('student.birthday-pdf', compact('students'))->render();
        
        return response($html)
            ->header('Content-Type', 'text/html');
    }
}

