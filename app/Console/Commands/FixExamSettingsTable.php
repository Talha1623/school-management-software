<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FixExamSettingsTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:exam-settings-table';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix exam_settings table tablespace issue';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fixing exam_settings table...');
        
        try {
            // Check if table exists
            $tableExists = DB::select("SHOW TABLES LIKE 'exam_settings'");
            
            if (!empty($tableExists)) {
                $this->info('Table exists, attempting to fix tablespace issue...');
                
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                
                try {
                    DB::unprepared('ALTER TABLE exam_settings DISCARD TABLESPACE');
                    $this->info('Tablespace discarded successfully.');
                } catch (\Exception $e) {
                    $this->warn('Could not discard tablespace: ' . $e->getMessage());
                }
                
                DB::statement('DROP TABLE exam_settings');
                $this->info('Table dropped successfully.');
                
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            }
            
            // Create the table using raw SQL to avoid tablespace issues
            if (!Schema::hasTable('exam_settings')) {
                // Use raw SQL to create table
                DB::statement("CREATE TABLE `exam_settings` (
                    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    `admit_card_instructions` TEXT NULL,
                    `fail_student_if` VARCHAR(191) NULL,
                    `created_at` TIMESTAMP NULL,
                    `updated_at` TIMESTAMP NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                
                $this->info('Table created successfully.');
                
                // Create default record
                DB::table('exam_settings')->insert([
                    'admit_card_instructions' => '',
                    'fail_student_if' => '',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                $this->info('Default settings record created.');
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
                $this->line('   ALTER TABLE exam_settings DISCARD TABLESPACE;');
                $this->line('   DROP TABLE IF EXISTS exam_settings;');
                $this->line('   SET FOREIGN_KEY_CHECKS=1;');
                $this->line('');
                $this->line('4. Then run this command again:');
                $this->line('   php artisan fix:exam-settings-table');
                $this->line('');
                $this->warn('Or you can manually create the table using:');
                $this->line('   CREATE TABLE exam_settings (');
                $this->line('       id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,');
                $this->line('       admit_card_instructions TEXT NULL,');
                $this->line('       fail_student_if VARCHAR(191) NULL,');
                $this->line('       created_at TIMESTAMP NULL,');
                $this->line('       updated_at TIMESTAMP NULL');
                $this->line('   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
            } else {
                $this->error('Error: ' . $e->getMessage());
            }
            return 1;
        }
    }
}
