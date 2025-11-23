<?php

namespace App\Console\Commands;

use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class TestStudentLogin extends Command
{
    protected $signature = 'student:test-login {student_code} {password}';

    protected $description = 'Test student login credentials';

    public function handle()
    {
        $studentCode = $this->argument('student_code');
        $password = $this->argument('password');
        
        $student = Student::where('student_code', $studentCode)->first();
        
        if (!$student) {
            $this->error("Student not found!");
            return Command::FAILURE;
        }
        
        $this->info("Student: {$student->student_name}");
        $this->info("Student Code: {$student->student_code}");
        $this->info("B-Form Number: {$student->b_form_number}");
        $this->info("Password Hash: " . ($student->password ? substr($student->password, 0, 20) . '...' : 'NULL'));
        
        if (!$student->password) {
            $this->error("\nPassword is not set! Updating now...");
            if ($student->b_form_number) {
                $student->password = $student->b_form_number;
                $student->save();
                $this->info("Password updated!");
            }
            return Command::FAILURE;
        }
        
        $passwordMatch = Hash::check($password, $student->password);
        
        $this->info("\nPassword Check:");
        $this->info("Input Password: {$password}");
        $this->info("B-Form Number: {$student->b_form_number}");
        $this->info("Password Match: " . ($passwordMatch ? 'YES ✓' : 'NO ✗'));
        
        if (!$passwordMatch && $password === $student->b_form_number) {
            $this->warn("\nPassword doesn't match but B-Form Number matches!");
            $this->info("Re-hashing password...");
            $student->password = $student->b_form_number;
            $student->save();
            $this->info("Password re-hashed! Try login again.");
        }
        
        return $passwordMatch ? Command::SUCCESS : Command::FAILURE;
    }
}

