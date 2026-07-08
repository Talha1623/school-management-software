<?php

namespace App\Console\Commands;

use App\Services\AutoStudentAttendanceService;
use Illuminate\Console\Command;

class AutoMarkStudentAbsentCommand extends Command
{
    protected $signature = 'attendance:auto-absent';

    protected $description = 'Mark students and staff absent when automation is enabled and the school cutoff time has passed';

    public function handle(AutoStudentAttendanceService $service): int
    {
        $count = $service->runIfDue();

        if ($count > 0) {
            $this->info("Auto-marked {$count} student(s) and/or staff as absent.");
        }

        return self::SUCCESS;
    }
}
