<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

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

