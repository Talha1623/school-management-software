<?php

namespace App\Console\Commands;

use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class UpdateStudentPasswords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'students:update-passwords';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update passwords for existing students using their B-Form Number';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating student passwords...');

        // Get all students who have B-Form Number but no password or empty password
        $students = Student::whereNotNull('b_form_number')
            ->where(function($query) {
                $query->whereNull('password')
                      ->orWhere('password', '');
            })
            ->get();

        $updated = 0;
        $skipped = 0;

        foreach ($students as $student) {
            if (!empty($student->b_form_number)) {
                // Update password using B-Form Number
                $student->password = $student->b_form_number; // Will be hashed automatically
                $student->save();
                $updated++;
                $this->line("Updated password for: {$student->student_code} - {$student->student_name}");
            } else {
                $skipped++;
            }
        }

        $this->info("\nCompleted!");
        $this->info("Updated: {$updated} students");
        $this->info("Skipped: {$skipped} students");

        return Command::SUCCESS;
    }
}

