<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Campus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdmissionDataReportController extends Controller
{
    /**
     * Display the admission data reports with filters.
     */
    public function index(Request $request): View
    {
        // Get filter values
        $filterCampus = $request->get('filter_campus');
        $filterClass = $request->get('filter_class');
        $filterSection = $request->get('filter_section');
        $filterYears = $request->get('filter_year', []);
        if (!is_array($filterYears)) {
            $filterYears = [$filterYears];
        }
        $filterYears = array_values(array_filter($filterYears));
        // Get campuses from Campus model first, then fallback to students/classes/sections
        $campuses = Campus::orderBy('campus_name', 'asc')->pluck('campus_name');
        if ($campuses->isEmpty()) {
            $campuses = Student::whereNotNull('campus')->distinct()->pluck('campus')->sort()->values();
        }
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
            $campuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
        }

        // Get classes
        $classesQuery = ClassModel::whereNotNull('class_name');
        if ($filterCampus) {
            $classesQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim((string) $filterCampus))]);
        }
        $classes = $classesQuery->distinct()->pluck('class_name')->sort()->values();
        if ($classes->isEmpty()) {
            $classes = Student::whereNotNull('class')
                ->when($filterCampus, fn($q) => $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim((string) $filterCampus))]))
                ->distinct()->pluck('class')->sort()->values();
        }

        // Get sections
        $sectionsQuery = Section::whereNotNull('name');
        if ($filterCampus) {
            $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim((string) $filterCampus))]);
        }
        if ($filterClass) {
            $sectionsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim((string) $filterClass))]);
        }
        $sections = $sectionsQuery->distinct()->pluck('name')->sort()->values();
        if ($sections->isEmpty()) {
            $sections = \App\Models\Subject::whereNotNull('section')
                ->when($filterCampus, fn($q) => $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim((string) $filterCampus))]))
                ->when($filterClass, fn($q) => $q->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim((string) $filterClass))]))
                ->distinct()->pluck('section')->sort()->values();
        }

        // Admission years
        $years = Student::whereNotNull('admission_date')
            ->selectRaw('YEAR(admission_date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        $admissionRecords = $this->admissionRecords($request);

        return view('reports.admission-data', compact(
            'campuses',
            'classes',
            'sections',
            'years',
            'admissionRecords',
            'filterCampus',
            'filterClass',
            'filterSection',
            'filterYears'
        ));
    }

    /**
     * Get classes by campus for admission data report.
     */
    public function getClassesByCampus(Request $request)
    {
        $campus = $request->get('campus');
        $query = ClassModel::whereNotNull('class_name');
        if ($campus) {
            $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim((string) $campus))]);
        }
        $classes = $query->distinct()->pluck('class_name')->sort()->values();
        if ($classes->isEmpty()) {
            $classes = Student::whereNotNull('class')
                ->when($campus, fn($q) => $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim((string) $campus))]))
                ->distinct()->pluck('class')->sort()->values();
        }
        return response()->json($classes);
    }

    /**
     * Get sections by class and campus for admission data report.
     */
    public function getSectionsByClass(Request $request)
    {
        $class = $request->get('class');
        $campus = $request->get('campus');
        $query = Section::whereNotNull('name');
        if ($campus) {
            $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim((string) $campus))]);
        }
        if ($class) {
            $query->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim((string) $class))]);
        }
        $sections = $query->distinct()->pluck('name')->sort()->values();
        if ($sections->isEmpty()) {
            $sections = \App\Models\Subject::whereNotNull('section')
                ->when($campus, fn($q) => $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim((string) $campus))]))
                ->when($class, fn($q) => $q->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim((string) $class))]))
                ->distinct()->pluck('section')->sort()->values();
        }
        return response()->json($sections);
    }

    /**
     * Export admission data report records.
     */
    public function export(Request $request, string $format)
    {
        $admissionRecords = $this->admissionRecords($request);

        return match ($format) {
            'excel' => $this->downloadCsv($admissionRecords, 'application/vnd.ms-excel', 'admission_data_report_' . date('Y-m-d_His') . '.xls'),
            'csv' => $this->downloadCsv($admissionRecords, 'text/csv', 'admission_data_report_' . date('Y-m-d_His') . '.csv'),
            'pdf' => response($this->renderReportHtml($admissionRecords, false))
                ->header('Content-Type', 'text/html'),
            default => redirect()->route('reports.admission-data', $request->query())->with('error', 'Invalid export format'),
        };
    }

    /**
     * Show a print-friendly admission data report.
     */
    public function print(Request $request)
    {
        $admissionRecords = $this->admissionRecords($request);

        return response($this->renderReportHtml($admissionRecords, $request->boolean('auto_print')))
            ->header('Content-Type', 'text/html');
    }

    private function admissionRecords(Request $request)
    {
        $query = Student::query();
        $this->applyAdmissionFilters($query, $request);

        return $query->orderBy('student_name')->get()->map(function ($student) {
            return [
                'student_code' => $student->student_code,
                'student_name' => $student->student_name,
                'surname_caste' => $student->surname_caste,
                'campus' => $student->campus,
                'class' => $student->class,
                'section' => $student->section,
                'admission_date' => $student->admission_date,
                'father_name' => $student->father_name,
            ];
        });
    }

    private function applyAdmissionFilters(Builder $query, Request $request): void
    {
        $filterCampus = $request->get('filter_campus');
        $filterClass = $request->get('filter_class');
        $filterSection = $request->get('filter_section');
        $filterYears = $request->get('filter_year', []);

        if (!is_array($filterYears)) {
            $filterYears = [$filterYears];
        }
        $filterYears = array_values(array_filter($filterYears));

        if ($filterCampus) {
            $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim((string) $filterCampus))]);
        }
        if ($filterClass) {
            $query->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim((string) $filterClass))]);
        }
        if ($filterSection) {
            $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim((string) $filterSection))]);
        }
        if (!empty($filterYears)) {
            $query->where(function ($yearQuery) use ($filterYears) {
                foreach ($filterYears as $year) {
                    $yearQuery->orWhereYear('admission_date', $year);
                }
            });
        }
    }

    private function downloadCsv($admissionRecords, string $contentType, string $filename)
    {
        $headers = [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response()->stream(function () use ($admissionRecords) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['#', 'Student Code', 'Student', 'Parent', 'Class/Section', 'Admission Date']);

            foreach ($admissionRecords as $index => $record) {
                fputcsv($handle, [
                    $index + 1,
                    $record['student_code'] ?? 'N/A',
                    trim(($record['student_name'] ?? '') . ' ' . ($record['surname_caste'] ?? '')),
                    $record['father_name'] ?? 'N/A',
                    trim(($record['class'] ?? '') . (($record['section'] ?? null) ? ' / ' . $record['section'] : '')),
                    $this->formatAdmissionDate($record['admission_date'] ?? null),
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['Total Students:', $admissionRecords->count()]);
            fclose($handle);
        }, 200, $headers);
    }

    private function renderReportHtml($admissionRecords, bool $autoPrint): string
    {
        $rows = '';

        foreach ($admissionRecords as $index => $record) {
            $rows .= '<tr>'
                . '<td>' . ($index + 1) . '</td>'
                . '<td>' . e($record['student_code'] ?? 'N/A') . '</td>'
                . '<td>' . e(trim(($record['student_name'] ?? '') . ' ' . ($record['surname_caste'] ?? ''))) . '</td>'
                . '<td>' . e($record['father_name'] ?? 'N/A') . '</td>'
                . '<td>' . e(trim(($record['class'] ?? '') . (($record['section'] ?? null) ? ' / ' . $record['section'] : ''))) . '</td>'
                . '<td>' . e($this->formatAdmissionDate($record['admission_date'] ?? null)) . '</td>'
                . '</tr>';
        }

        if ($rows === '') {
            $rows = '<tr><td colspan="6" class="empty">No admission records found</td></tr>';
        }

        $autoPrintScript = $autoPrint ? '<script>window.addEventListener("load", function () { window.print(); });</script>' : '';

        return '<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admission Data Reports</title>
    <style>
        body { font-family: Arial, sans-serif; color: #111; margin: 24px; }
        h2 { margin: 0 0 6px; text-align: center; }
        .generated { text-align: center; font-size: 12px; margin-bottom: 18px; color: #555; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border: 1px solid #333; padding: 7px; text-align: left; }
        th { background: #f1f1f1; }
        tfoot td { font-weight: bold; background: #f8f9fa; }
        .empty { text-align: center; padding: 20px; color: #777; }
        @media print { body { margin: 10mm; } }
    </style>
</head>
<body>
    <h2>Admission Data Reports</h2>
    <div class="generated">Generated: ' . e(now()->format('d M Y, h:i A')) . '</div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Student Code</th>
                <th>Student</th>
                <th>Parent</th>
                <th>Class/Section</th>
                <th>Admission Date</th>
            </tr>
        </thead>
        <tbody>' . $rows . '</tbody>
        <tfoot>
            <tr>
                <td colspan="5" style="text-align: right;">Total Students:</td>
                <td>' . $admissionRecords->count() . '</td>
            </tr>
        </tfoot>
    </table>
    ' . $autoPrintScript . '
</body>
</html>';
    }

    private function formatAdmissionDate($date): string
    {
        return $date ? date('d M Y', strtotime($date)) : 'N/A';
    }
}

