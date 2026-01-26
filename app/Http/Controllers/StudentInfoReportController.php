<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class StudentInfoReportController extends Controller
{
    /**
     * Check if Student model uses soft deletes.
     */
    private function usesSoftDeletes(): bool
    {
        return in_array(
            \Illuminate\Database\Eloquent\SoftDeletes::class,
            class_uses_recursive(Student::class)
        );
    }

    /**
     * Apply common filters for "current" students.
     */
    private function applyCurrentStudentsFilter($query)
    {
        if ($this->usesSoftDeletes()) {
            $query->withoutTrashed();
        }

        // Ensure only valid students with class are included
        $query->whereNotNull('class')
            ->where('class', '!=', '');

        return $query;
    }
    /**
     * Print student info report by type.
     */
    public function print(Request $request): View
    {
        $type = $request->get('type', 'all-active');
        $query = $this->applyCurrentStudentsFilter(Student::query());

        $title = 'Student Info Report';
        $subtitle = '';
        $grouped = false;
        $groupedStudents = collect();

        switch ($type) {
            case 'all-active':
                $title = 'All Active Students';
                $subtitle = 'List of all active students';
                $query->whereNotNull('admission_date');
                break;
            case 'all-inactive':
                $title = 'All Inactive Students';
                $subtitle = 'List of all inactive students';
                $query->whereNull('admission_date');
                break;
            case 'class-wise':
                $title = 'Class Wise Student Report';
                $subtitle = 'Students grouped by class';
                $grouped = true;
                $groupedStudents = $this->applyCurrentStudentsFilter(Student::query())
                    ->orderBy('class')
                    ->orderBy('section')
                    ->orderBy('student_name')
                    ->get()
                    ->groupBy(function ($student) {
                        return trim($student->class ?? 'N/A');
                    });
                break;
            case 'all-passout':
                $title = 'All Passout Students';
                $subtitle = 'List of all passout students';
                $this->applyPassoutFilter($query);
                break;
            case 'free-students':
                $title = 'Free Students Report';
                $subtitle = 'Students with free fees or discounted students';
                $query->where(function ($q) {
                    $q->where('discounted_student', true)
                      ->orWhere('monthly_fee', '<=', 0);
                });
                break;
            case 'monthly-passout':
                $title = 'Monthly Passout Students Report';
                $subtitle = 'Passout students for current month';
                $this->applyPassoutFilter($query);
                $query->whereMonth('admission_date', Carbon::now()->month)
                    ->whereYear('admission_date', Carbon::now()->year);
                break;
            case 'daily-passout':
                $title = 'Daily Passout Students Report';
                $subtitle = 'Passout students for today';
                $this->applyPassoutFilter($query);
                $query->whereDate('admission_date', Carbon::today());
                break;
            case 'gender-wise':
                $title = 'Gender Wise Student Report';
                $subtitle = 'Students grouped by gender';
                $grouped = true;
                $groupedStudents = $this->applyCurrentStudentsFilter(Student::query())
                    ->orderBy('gender')
                    ->orderBy('student_name')
                    ->get()
                    ->groupBy(function ($student) {
                        return ucfirst($student->gender ?? 'N/A');
                    });
                break;
            default:
                $title = 'All Active Students';
                $subtitle = 'List of all active students';
                $query->whereNotNull('admission_date');
                break;
        }

        $students = $grouped ? collect() : $query->orderBy('class')
            ->orderBy('section')
            ->orderBy('student_name')
            ->get();

        return view('student.info-report-print', [
            'type' => $type,
            'title' => $title,
            'subtitle' => $subtitle,
            'students' => $students,
            'grouped' => $grouped,
            'groupedStudents' => $groupedStudents,
            'printedAt' => Carbon::now()->format('d-m-Y H:i'),
        ]);
    }

    /**
     * Apply passout filter based on class value.
     * Adjust this list if your passout marker is stored differently.
     */
    private function applyPassoutFilter($query): void
    {
        $passoutClasses = [
            'passout',
            'pass out',
            'passed out',
            'passedout',
            'graduated',
            'graduate',
            'alumni',
        ];

        if (Schema::hasColumn('students', 'class')) {
            $query->whereIn(DB::raw('LOWER(TRIM(class))'), $passoutClasses);
        } else {
            // If class column is missing, return empty set
            $query->whereRaw('1 = 0');
        }
    }
}
