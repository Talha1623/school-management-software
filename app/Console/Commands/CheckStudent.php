<?php

namespace App\Console\Commands;

use App\Models\Student;
use Illuminate\Console\Command;

class CheckStudent extends Command
{
    protected $signature = 'student:check {student_code}';

    protected $description = 'Check student details by student code';

    public function handle()
    {
        $studentCode = $this->argument('student_code');
        
        $student = Student::where('student_code', $studentCode)->first();
        
        if (!$student) {
            $this->error("Student with code '{$studentCode}' not found!");
            
            // Show similar codes
            $similar = Student::where('student_code', 'like', '%' . $studentCode . '%')
                ->orWhere('student_code', 'like', substr($studentCode, 0, 6) . '%')
                ->limit(10)
                ->get(['student_code', 'student_name']);
                
            if ($similar->count() > 0) {
                $this->info("\nSimilar student codes found:");
                foreach ($similar as $s) {
                    $this->line("  - {$s->student_code}: {$s->student_name}");
                }
            }
            
            return Command::FAILURE;
        }
        
        $this->info("Student Found!");
        $this->table(
            ['Field', 'Value'],
            [
                ['Student Code', $student->student_code ?? 'N/A'],
                ['Name', $student->student_name ?? 'N/A'],
                ['B-Form Number', $student->b_form_number ?? 'N/A'],
                ['Password Set', $student->password ? 'Yes' : 'No'],
                ['Has Login Access', $student->hasLoginAccess() ? 'Yes' : 'No'],
            ]
        );
        
        return Command::SUCCESS;
    }
}

