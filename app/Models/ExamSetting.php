<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ExamSetting extends Model
{
    use HasFactory;

    protected $table = 'exam_settings';

    protected $fillable = [
        'admit_card_instructions',
        'fail_student_if',
    ];

    public static function getSettings(): self
    {
        try {
            return self::first() ?? self::create([
                'admit_card_instructions' => '',
                'fail_student_if' => '',
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle table doesn't exist or tablespace issues
            if (str_contains($e->getMessage(), "doesn't exist") || str_contains($e->getMessage(), 'Tablespace')) {
                // Log the error for admin to fix
                \Log::warning('ExamSettings table issue: ' . $e->getMessage());
                
                // Return a default model instance to prevent application crash
                // Admin should run: php artisan fix:exam-settings-table
                $settings = new self();
                $settings->id = 0;
                $settings->admit_card_instructions = '';
                $settings->fail_student_if = '';
                $settings->exists = false;
                
                return $settings;
            }
            throw $e;
        }
    }
}
