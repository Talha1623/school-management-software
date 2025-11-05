<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentBirthdayController extends Controller
{
    /**
     * Display the student birthdays page.
     */
    public function index(): View
    {
        // Empty array - no static data
        $students = [];

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
     * Get students data (empty for now)
     */
    private function getStudentsData()
    {
        return [];
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

