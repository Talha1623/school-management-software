<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'campus',
        'quiz_name',
        'description',
        'for_class',
        'section',
        'total_questions',
        'start_date_time',
        'duration_minutes',
    ];

    protected $casts = [
        'start_date_time' => 'datetime',
        'duration_minutes' => 'integer',
        'total_questions' => 'integer',
    ];

    public function questions()
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('question_number');
    }

    /**
     * School timezone from general settings.
     */
    public static function schoolTimezone(): string
    {
        try {
            $settings = GeneralSetting::getSettings();
            $tz = trim((string) ($settings->timezone ?? ''));
            if ($tz !== '') {
                return $tz;
            }
        } catch (\Throwable $e) {
            // Use app default.
        }

        return (string) config('app.timezone', 'UTC');
    }

    /**
     * Start moment: DB stores wall-clock time in school timezone (not UTC).
     */
    public function startAtLocal(): ?Carbon
    {
        $raw = $this->getRawOriginal('start_date_time');
        if ($raw === null || $raw === '') {
            return null;
        }

        if ($raw instanceof Carbon) {
            $raw = $raw->format('Y-m-d H:i:s');
        }

        $value = substr((string) $raw, 0, 19);
        $tz = static::schoolTimezone();

        try {
            return Carbon::createFromFormat('Y-m-d H:i:s', $value, $tz);
        } catch (\Throwable $e) {
            return Carbon::parse($value, $tz);
        }
    }

    public function nowLocal(): Carbon
    {
        return Carbon::now(static::schoolTimezone());
    }

    public function hasStarted(): bool
    {
        $start = $this->startAtLocal();
        if (!$start) {
            return false;
        }

        return $this->nowLocal()->greaterThanOrEqualTo($start);
    }

    public function hasEnded(): bool
    {
        $start = $this->startAtLocal();
        if (!$start) {
            return false;
        }

        $duration = (int) ($this->duration_minutes ?? 60);

        return $this->nowLocal()->greaterThan($start->copy()->addMinutes($duration));
    }

    public function questionsAreEditable(): bool
    {
        $start = $this->startAtLocal();
        if (!$start) {
            return true;
        }

        return $this->nowLocal()->lt($start);
    }
}
