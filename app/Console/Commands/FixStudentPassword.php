<?php

namespace App\Console\Commands;

use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class FixStudentPassword extends Command
{
    protected $signature = 'student:fix-password {student_code}';

    protected $description = 'Fix password for a specific student';

    public function handle()
    {
        $studentCode = $this->argument('student_code');
        
        $student = Student::where('student_code', $studentCode)->first();
        
        if (!$student) {
            $this->error("Student not found!");
            return Command::FAILURE;
        }
        
        $this->info("Student: {$student->student_name}");
        $this->info("Student Code: {$student->student_code}");
        $this->info("B-Form Number: {$student->b_form_number}");
        
        if (empty($student->b_form_number)) {
            $this->error("B-Form Number is empty!");
            return Command::FAILURE;
        }
        
        // Force update password
        $student->password = $student->b_form_number;
        $student->save();
        
        // Verify
        $verified = Hash::check($student->b_form_number, $student->password);
        
        $this->info("Password updated!");
        $this->info("Verification: " . ($verified ? 'PASSED ✓' : 'FAILED ✗'));
        
        return Command::SUCCESS;
    }
}

