<?php

namespace App\Console\Commands;

use App\Models\Staff;
use Illuminate\Console\Command;

class UpdateStaffDesignation extends Command
{
    protected $signature = 'staff:update-designation {email} {designation=teacher}';
    protected $description = 'Update staff designation';

    public function handle()
    {
        $email = $this->argument('email');
        $designation = $this->argument('designation');

        $staff = Staff::where('email', $email)->first();

        if (!$staff) {
            $this->error("Staff with email '{$email}' not found!");
            return 1;
        }

        $oldDesignation = $staff->designation;
        $staff->designation = $designation;
        $staff->save();

        $this->info("Designation updated successfully!");
        $this->line("Email: {$email}");
        $this->line("Old Designation: {$oldDesignation}");
        $this->line("New Designation: {$designation}");

        return 0;
    }
}

