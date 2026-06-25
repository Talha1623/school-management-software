<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomeworkDiary extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_id',
        'campus',
        'class',
        'section',
        'date',
        'homework_content',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * @return array<int, string>
     */
    public static function classLookupKeys(?string $class): array
    {
        $class = strtolower(trim((string) $class));
        if ($class === '') {
            return [];
        }

        $groups = [
            ['nursery'],
            ['kg', 'k.g', 'k g', 'kindergarten'],
            ['one', '1', '1st', 'first', 'class one', 'grade 1', 'grade one'],
            ['two', '2', '2nd', 'second', 'class two', 'grade 2', 'grade two'],
            ['three', '3', '3rd', 'third', 'class three', 'grade 3', 'grade three'],
            ['four', '4', '4th', 'fourth', 'class four', 'grade 4', 'grade four'],
            ['five', '5', '5th', 'fifth', 'class five', 'grade 5', 'grade five'],
            ['six', '6', '6th', 'sixth', 'class six', 'grade 6', 'grade six'],
            ['seven', '7', '7th', 'seventh', 'class seven', 'grade 7', 'grade seven'],
            ['eight', '8', '8th', 'eighth', 'class eight', 'grade 8', 'grade eight'],
            ['nine', '9', '9th', 'ninth', 'class nine', 'grade 9', 'grade nine'],
            ['ten', '10', '10th', 'tenth', 'class ten', 'grade 10', 'grade ten'],
            ['eleven', '11', '11th', 'eleventh', 'class eleven', 'grade 11', 'grade eleven'],
            ['twelve', '12', '12th', 'twelfth', 'class twelve', 'grade 12', 'grade twelve'],
        ];

        foreach ($groups as $keys) {
            if (in_array($class, $keys, true)) {
                return array_values(array_unique($keys));
            }
        }

        return [$class];
    }

    public function scopeWithContent(Builder $query): Builder
    {
        return $query->whereNotNull('homework_content')
            ->where('homework_content', '!=', '')
            ->whereRaw('TRIM(homework_content) != ?', ['']);
    }

    /**
     * Match homework for a student — flexible class aliases + subject timetable rows.
     */
    public function scopeForStudent(Builder $query, Student $student): Builder
    {
        $campus = strtolower(trim((string) ($student->campus ?? '')));
        $section = strtolower(trim((string) ($student->section ?? '')));
        $classKeys = self::classLookupKeys($student->class);

        if ($section === '' || $classKeys === []) {
            return $query->whereRaw('1 = 0');
        }

        $subjectIds = Subject::query()
            ->whereRaw('LOWER(TRIM(section)) = ?', [$section])
            ->where(function (Builder $classQuery) use ($classKeys) {
                foreach ($classKeys as $key) {
                    $classQuery->orWhereRaw('LOWER(TRIM(class)) = ?', [$key]);
                }
            })
            ->when($campus !== '', function (Builder $campusQuery) use ($campus) {
                $campusQuery->where(function (Builder $q) use ($campus) {
                    $q->whereRaw('LOWER(TRIM(campus)) = ?', [$campus])
                        ->orWhereNull('campus')
                        ->orWhereRaw('TRIM(campus) = ?', ['']);
                });
            })
            ->pluck('id');

        return $query->where(function (Builder $outer) use ($campus, $section, $classKeys, $subjectIds) {
            $outer->where(function (Builder $direct) use ($campus, $section, $classKeys) {
                $direct->whereRaw('LOWER(TRIM(section)) = ?', [$section])
                    ->where(function (Builder $classQuery) use ($classKeys) {
                        foreach ($classKeys as $key) {
                            $classQuery->orWhereRaw('LOWER(TRIM(class)) = ?', [$key]);
                        }
                    });

                if ($campus !== '') {
                    $direct->where(function (Builder $campusQuery) use ($campus) {
                        $campusQuery->whereRaw('LOWER(TRIM(campus)) = ?', [$campus])
                            ->orWhereNull('campus')
                            ->orWhereRaw('TRIM(campus) = ?', ['']);
                    });
                }
            });

            if ($subjectIds->isNotEmpty()) {
                $outer->orWhereIn('subject_id', $subjectIds);
            }
        });
    }

    public function scopeOnDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('date', $date);
    }
}
