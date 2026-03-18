<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FixParticularTestGradesTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:particular-test-grades-table';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix particular_test_grades table tablespace issue';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fixing particular_test_grades table...');
        
        try {
            // Check if table exists
            $tableExists = DB::select("SHOW TABLES LIKE 'particular_test_grades'");
            
            if (!empty($tableExists)) {
                $this->info('Table exists, attempting to fix tablespace issue...');
                
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                
                try {
                    DB::unprepared('ALTER TABLE particular_test_grades DISCARD TABLESPACE');
                    $this->info('Tablespace discarded successfully.');
                } catch (\Exception $e) {
                    $this->warn('Could not discard tablespace: ' . $e->getMessage());
                }
                
                DB::statement('DROP TABLE particular_test_grades');
                $this->info('Table dropped successfully.');
                
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            }
            
            // Create the table using raw SQL to avoid tablespace issues
            if (!Schema::hasTable('particular_test_grades')) {
                // Use raw SQL to create table
                DB::statement("CREATE TABLE `particular_test_grades` (
                    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    `campus` VARCHAR(191) NOT NULL,
                    `name` VARCHAR(191) NOT NULL,
                    `from_percentage` DECIMAL(5, 2) NOT NULL,
                    `to_percentage` DECIMAL(5, 2) NOT NULL,
                    `for_test` VARCHAR(191) NOT NULL,
                    `class` VARCHAR(191) NOT NULL,
                    `section` VARCHAR(191) NULL,
                    `subject` VARCHAR(191) NULL,
                    `session` VARCHAR(191) NOT NULL,
                    `created_at` TIMESTAMP NULL,
                    `updated_at` TIMESTAMP NULL,
                    INDEX `particular_test_grades_campus_index` (`campus`),
                    INDEX `particular_test_grades_for_test_index` (`for_test`),
                    INDEX `particular_test_grades_class_index` (`class`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                
                $this->info('Table created successfully.');
            } else {
                $this->info('Table already exists and is accessible.');
            }
            
            $this->info('Done!');
            return 0;
            
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'Tablespace')) {
                $this->error('Tablespace issue detected. Please run these MySQL commands manually:');
                $this->line('');
                $this->line('1. Connect to MySQL:');
                $this->line('   mysql -u your_username -p');
                $this->line('');
                $this->line('2. Select database:');
                $this->line('   USE school;');
                $this->line('');
                $this->line('3. Fix tablespace:');
                $this->line('   SET FOREIGN_KEY_CHECKS=0;');
                $this->line('   ALTER TABLE particular_test_grades DISCARD TABLESPACE;');
                $this->line('   DROP TABLE IF EXISTS particular_test_grades;');
                $this->line('   SET FOREIGN_KEY_CHECKS=1;');
                $this->line('');
                $this->line('4. Then run this command again:');
                $this->line('   php artisan fix:particular-test-grades-table');
            } else {
                $this->error('Error: ' . $e->getMessage());
            }
            return 1;
        }
    }
}
