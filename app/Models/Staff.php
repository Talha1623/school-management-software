<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class Staff extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $table = 'staff';

    protected $fillable = [
        'name',
        'father_husband_name',
        'campus',
        'designation',
        'gender',
        'emp_id',
        'phone',
        'whatsapp',
        'cnic',
        'qualification',
        'birthday',
        'joining_date',
        'marital_status',
        'salary_type',
        'salary',
        'absent_fees',
        'late_fees',
        'early_exit_fees',
        'free_absent',
        'email',
        'password',
        'home_address',
        'photo',
        'cv_resume',
        'api_token',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'api_token',
    ];

    protected $casts = [
        'birthday' => 'date',
        'joining_date' => 'date',
        'absent_fees' => 'decimal:2',
        'late_fees' => 'decimal:2',
        'early_exit_fees' => 'decimal:2',
        'free_absent' => 'integer',
    ];

    /**
     * Set the password attribute (hash it)
     */
    public function setPasswordAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['password'] = Hash::make($value);
        }
    }

    /**
     * Check if staff has dashboard access (has email and password)
     */
    public function hasDashboardAccess(): bool
    {
        $status = strtolower(trim($this->status ?? ''));
        $isActive = $status === '' || $status === 'active';

        return $isActive && !empty($this->email) && !empty($this->password);
    }

    public function profilePhotoUrl(?string $default = null): string
    {
        $default ??= asset('assets/images/admin.png');
        $photo = trim((string) ($this->photo ?? ''));

        if ($photo === '') {
            return $default;
        }

        if (filter_var($photo, FILTER_VALIDATE_URL)) {
            return $photo;
        }

        if (str_starts_with($photo, 'storage/')) {
            return asset($photo);
        }

        return Storage::disk('public')->exists($photo)
            ? Storage::url($photo)
            : $default;
    }

    /**
     * Check if staff is a teacher (designation contains "teacher")
     */
    public function isTeacher(): bool
    {
        $designation = strtolower(trim($this->designation ?? ''));
        return strpos($designation, 'teacher') !== false;
    }

    /**
     * Trimmed campus for this staff member (null if unset).
     */
    public function scopeCampusName(): ?string
    {
        $campus = trim((string) ($this->campus ?? ''));

        return $campus !== '' ? $campus : null;
    }

    /**
     * Values that may appear in Subjects/Sections "teacher" field for this staff member.
     *
     * @return array<int, string>
     */
    public function teacherIdentityKeys(): array
    {
        $keys = [];

        foreach ([$this->name, $this->emp_id] as $value) {
            $normalized = strtolower(trim((string) $value));
            if ($normalized !== '') {
                $keys[] = $normalized;
            }
        }

        return array_values(array_unique($keys));
    }

    /**
     * Restrict query to rows assigned to this staff member (by name or employee ID).
     */
    public function scopeQueryToTeacherAssignments(Builder $query): Builder
    {
        $keys = $this->teacherIdentityKeys();
        if ($keys === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $q) use ($keys) {
            foreach ($keys as $key) {
                $q->orWhereRaw('LOWER(TRIM(teacher)) = ?', [$key]);
            }
        });
    }

    /**
     * Campus filter for assignment lookups (matches campus or rows with no campus set).
     */
    public function scopeQueryToFlexibleCampus(Builder $query, ?string $campus): Builder
    {
        if ($campus === null || trim($campus) === '') {
            return $query;
        }

        $campusKey = strtolower(trim($campus));

        return $query->where(function (Builder $q) use ($campusKey) {
            $q->whereRaw('LOWER(TRIM(COALESCE(campus, ""))) = ?', [$campusKey])
                ->orWhereRaw('TRIM(COALESCE(campus, "")) = ?', ['']);
        });
    }

    /**
     * Campus used when resolving teaching/attendance assignments.
     */
    public function campusForTeachingAssignments(?string $selectedCampus = null): ?string
    {
        if ($this->scopeCampusName()) {
            return $this->campus;
        }

        $selectedCampus = trim((string) ($selectedCampus ?? ''));

        return $selectedCampus !== '' ? $selectedCampus : null;
    }

    /**
     * Compare class labels ("Class 3" vs "class 3" vs "3").
     */
    public static function normalizeClassKey(string $class): string
    {
        $normalized = strtolower(trim($class));
        $normalized = preg_replace('/^class\s+/i', '', $normalized) ?? $normalized;

        return trim($normalized);
    }

    /**
     * Class names assigned in Manage Section / Manage Subjects.
     */
    public function assignedTeachingClassNames(?string $selectedCampus = null): Collection
    {
        if ($this->teacherIdentityKeys() === []) {
            return collect();
        }

        $campus = $this->campusForTeachingAssignments($selectedCampus);
        $classes = $this->mergeAssignedClassesFromQueries($campus);

        if ($classes->isEmpty() && $campus !== null) {
            $classes = $this->mergeAssignedClassesFromQueries(null);
        }

        return $classes;
    }

    /**
     * Class names from Manage Subjects only (subject-teacher assignments).
     */
    public function assignedSubjectClassNames(?string $selectedCampus = null): Collection
    {
        if ($this->teacherIdentityKeys() === []) {
            return collect();
        }

        $campus = $this->campusForTeachingAssignments($selectedCampus);

        $subjectsQuery = Subject::query();
        $this->scopeQueryToTeacherAssignments($subjectsQuery);
        $this->scopeQueryToFlexibleCampus($subjectsQuery, $campus);

        $classes = $subjectsQuery->pluck('class')
            ->map(fn ($class) => trim((string) $class))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        if ($classes->isEmpty() && $campus !== null) {
            $fallbackQuery = Subject::query();
            $this->scopeQueryToTeacherAssignments($fallbackQuery);

            $classes = $fallbackQuery->pluck('class')
                ->map(fn ($class) => trim((string) $class))
                ->filter()
                ->unique()
                ->sort()
                ->values();
        }

        return $classes;
    }

    /**
     * Subject names this staff may enter marks or declare results for (Manage Subjects).
     */
    public function uploadableSubjectNamesForMarks(
        ?string $campus = null,
        ?string $class = null,
        ?string $section = null
    ): Collection {
        if ($this->teacherIdentityKeys() === []) {
            return collect();
        }

        $requestedCampus = trim((string) ($campus ?? ''));
        $effectiveCampus = $requestedCampus !== ''
            ? $requestedCampus
            : $this->campusForTeachingAssignments(null);

        $buildQuery = function (?string $campusFilter) use ($class, $section) {
            $query = Subject::query();
            $this->scopeQueryToTeacherAssignments($query);
            $this->scopeQueryToFlexibleCampus($query, $campusFilter);

            if ($class !== null && trim((string) $class) !== '') {
                $className = trim((string) $class);
                $classKey = self::normalizeClassKey($className);
                $query->where(function (Builder $q) use ($className, $classKey) {
                    $q->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($className)])
                        ->orWhereRaw('LOWER(TRIM(class)) = ?', [$classKey])
                        ->orWhereRaw("LOWER(TRIM(REPLACE(REPLACE(class, 'Class ', ''), 'class ', ''))) = ?", [$classKey]);
                });
            }

            if ($section !== null && trim((string) $section) !== '') {
                $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim((string) $section))]);
            }

            return $query;
        };

        $subjects = $buildQuery($effectiveCampus)
            ->whereNotNull('subject_name')
            ->pluck('subject_name')
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        if ($subjects->isEmpty() && $effectiveCampus !== null) {
            $subjects = $buildQuery(null)
                ->whereNotNull('subject_name')
                ->pluck('subject_name')
                ->map(fn ($name) => trim((string) $name))
                ->filter()
                ->unique()
                ->sort()
                ->values();
        }

        return $subjects;
    }

    /**
     * Whether this staff member may declare/reset results for a test row.
     */
    public function canDeclareResultForTest(
        ?string $campus,
        ?string $class,
        ?string $section,
        ?string $subject
    ): bool {
        $subjectName = trim((string) $subject);
        if ($subjectName === '') {
            return false;
        }

        return $this->uploadableSubjectNamesForMarks($campus, $class, $section)
            ->contains(fn ($name) => strcasecmp(trim((string) $name), $subjectName) === 0);
    }

    /**
     * Whether this staff member may upload marks or enter exam remarks for a subject.
     */
    public function canUploadMarksForSubject(
        ?string $campus,
        ?string $class,
        ?string $section,
        ?string $subject
    ): bool {
        return $this->canDeclareResultForTest($campus, $class, $section, $subject);
    }

    /**
     * Whether this staff member can edit final result remarks for a class/section.
     * Only allowed for class teacher of that class/section (and campus when set).
     */
    public function canEditFinalResultRemarks(
        ?string $campus,
        ?string $class,
        ?string $section = null
    ): bool {
        $className = trim((string) ($class ?? ''));
        if ($className === '') {
            return false;
        }

        $classKey = self::normalizeClassKey($className);
        $campusFilter = $this->campusForTeachingAssignments($campus);

        $sectionsQuery = Section::query();
        $this->scopeQueryToTeacherAssignments($sectionsQuery);
        $this->scopeQueryToFlexibleCampus($sectionsQuery, $campusFilter);

        $sectionsQuery->where(function (Builder $q) use ($className, $classKey) {
            $q->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($className))])
                ->orWhereRaw('LOWER(TRIM(class)) = ?', [$classKey])
                ->orWhereRaw("LOWER(TRIM(REPLACE(REPLACE(class, 'Class ', ''), 'class ', ''))) = ?", [$classKey]);
        });

        if ($section !== null && trim((string) $section) !== '') {
            $sectionsQuery->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim((string) $section))]);
        }

        return $sectionsQuery->exists();
    }

    /**
     * Whether this staff member can edit combined result remarks (class teacher only).
     */
    public function canEditCombinedResultRemarks(
        ?string $campus,
        ?string $class,
        ?string $section = null
    ): bool {
        return $this->canEditFinalResultRemarks($campus, $class, $section);
    }

    /**
     * Sections in a class where this staff member teaches (Manage Subjects + Manage Section).
     */
    public function assignedTeachingSectionsForClass(string $class, ?string $selectedCampus = null): Collection
    {
        if ($this->teacherIdentityKeys() === []) {
            return collect();
        }

        $classKey = self::normalizeClassKey($class);
        if ($classKey === '') {
            return collect();
        }

        $campus = $this->campusForTeachingAssignments($selectedCampus);

        $applyClassFilter = function (Builder $query) use ($class, $classKey) {
            $query->where(function (Builder $q) use ($class, $classKey) {
                $q->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                    ->orWhereRaw('LOWER(TRIM(class)) = ?', [$classKey])
                    ->orWhereRaw("LOWER(TRIM(REPLACE(REPLACE(class, 'Class ', ''), 'class ', ''))) = ?", [$classKey]);
            });
        };

        $subjectsQuery = Subject::query();
        $this->scopeQueryToTeacherAssignments($subjectsQuery);
        $this->scopeQueryToFlexibleCampus($subjectsQuery, $campus);
        $applyClassFilter($subjectsQuery);

        $sectionsFromSubjects = $subjectsQuery
            ->whereNotNull('section')
            ->pluck('section')
            ->map(fn ($section) => trim((string) $section))
            ->filter();

        $sectionsQuery = Section::query();
        $this->scopeQueryToTeacherAssignments($sectionsQuery);
        $this->scopeQueryToFlexibleCampus($sectionsQuery, $campus);
        $applyClassFilter($sectionsQuery);

        $sectionsFromClassTeacher = $sectionsQuery
            ->whereNotNull('name')
            ->pluck('name')
            ->map(fn ($section) => trim((string) $section))
            ->filter();

        $sections = $sectionsFromSubjects
            ->merge($sectionsFromClassTeacher)
            ->unique()
            ->sort()
            ->values();

        if ($sections->isEmpty() && $campus !== null) {
            $fallbackSubjectsQuery = Subject::query();
            $this->scopeQueryToTeacherAssignments($fallbackSubjectsQuery);
            $applyClassFilter($fallbackSubjectsQuery);

            $fallbackSectionsQuery = Section::query();
            $this->scopeQueryToTeacherAssignments($fallbackSectionsQuery);
            $applyClassFilter($fallbackSectionsQuery);

            $sections = $fallbackSubjectsQuery
                ->whereNotNull('section')
                ->pluck('section')
                ->merge(
                    $fallbackSectionsQuery
                        ->whereNotNull('name')
                        ->pluck('name')
                )
                ->map(fn ($section) => trim((string) $section))
                ->filter()
                ->unique()
                ->sort()
                ->values();
        }

        return $sections;
    }

    /**
     * Classes where this staff member is class teacher (Manage Section).
     */
    public function assignedAttendanceClassNames(?string $selectedCampus = null): Collection
    {
        if ($this->teacherIdentityKeys() === []) {
            return collect();
        }

        $campus = $this->campusForTeachingAssignments($selectedCampus);

        $sectionsQuery = Section::query();
        $this->scopeQueryToTeacherAssignments($sectionsQuery);
        $this->scopeQueryToFlexibleCampus($sectionsQuery, $campus);

        $classes = $sectionsQuery->pluck('class')
            ->map(fn ($class) => trim((string) $class))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        if ($classes->isEmpty() && $campus !== null) {
            $fallbackQuery = Section::query();
            $this->scopeQueryToTeacherAssignments($fallbackQuery);

            $classes = $fallbackQuery->pluck('class')
                ->map(fn ($class) => trim((string) $class))
                ->filter()
                ->unique()
                ->sort()
                ->values();
        }

        return $classes;
    }

    /**
     * Whether this teacher is assigned to the given class.
     */
    public function isAssignedToClass(string $class, ?string $selectedCampus = null): bool
    {
        $classKey = self::normalizeClassKey($class);
        if ($classKey === '') {
            return false;
        }

        return $this->assignedAttendanceClassNames($selectedCampus)
            ->contains(fn ($assigned) => self::normalizeClassKey((string) $assigned) === $classKey);
    }

    /**
     * Sections for attendance in an assigned class.
     */
    public function assignedAttendanceSectionsForClass(string $class, ?string $selectedCampus = null): Collection
    {
        if (!$this->isAssignedToClass($class, $selectedCampus)) {
            return collect();
        }

        $classKey = self::normalizeClassKey($class);
        $campus = $this->campusForTeachingAssignments($selectedCampus);

        $sectionsQuery = Section::where(function (Builder $q) use ($class, $classKey) {
            $q->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                ->orWhereRaw('LOWER(TRIM(class)) = ?', [$classKey])
                ->orWhereRaw("LOWER(TRIM(REPLACE(REPLACE(class, 'Class ', ''), 'class ', ''))) = ?", [$classKey]);
        });
        $this->scopeQueryToFlexibleCampus($sectionsQuery, $campus);

        $sections = $sectionsQuery
            ->whereNotNull('name')
            ->distinct()
            ->pluck('name')
            ->map(fn ($section) => trim((string) $section))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        if ($sections->isNotEmpty()) {
            return $sections;
        }

        $fromStudents = Student::where(function (Builder $q) use ($class, $classKey) {
            $q->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                ->orWhereRaw('LOWER(TRIM(class)) = ?', [$classKey])
                ->orWhereRaw("LOWER(TRIM(REPLACE(REPLACE(class, 'Class ', ''), 'class ', ''))) = ?", [$classKey]);
        });

        if ($campus !== null) {
            $fromStudents->whereRaw('LOWER(TRIM(COALESCE(campus, ""))) = ?', [strtolower(trim($campus))]);
        }

        return $fromStudents
            ->whereNotNull('section')
            ->distinct()
            ->pluck('section')
            ->map(fn ($section) => trim((string) $section))
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }

    /**
     * Whether this staff member may mark attendance for the given student.
     */
    public function mayMarkAttendanceForStudent(Student $student): bool
    {
        if ($this->scopeCampusName()) {
            if (strcasecmp(trim((string) ($student->campus ?? '')), trim($this->campus)) !== 0) {
                return false;
            }
        }

        $assignedClasses = $this->assignedAttendanceClassNames();
        if ($assignedClasses->isEmpty()) {
            return false;
        }

        $classKey = self::normalizeClassKey((string) ($student->class ?? ''));
        if ($classKey === '') {
            return false;
        }

        return $assignedClasses
            ->contains(fn ($assigned) => self::normalizeClassKey((string) $assigned) === $classKey);
    }

    /**
     * @return Collection<int, string>
     */
    private function mergeAssignedClassesFromQueries(?string $campus): Collection
    {
        $subjectsQuery = Subject::query();
        $this->scopeQueryToTeacherAssignments($subjectsQuery);
        $this->scopeQueryToFlexibleCampus($subjectsQuery, $campus);

        $sectionsQuery = Section::query();
        $this->scopeQueryToTeacherAssignments($sectionsQuery);
        $this->scopeQueryToFlexibleCampus($sectionsQuery, $campus);

        return $subjectsQuery->pluck('class')
            ->merge($sectionsQuery->pluck('class'))
            ->map(fn ($class) => trim((string) $class))
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }

    /**
     * Get allowed routes based on designation
     */
    public function getAllowedRoutes(): array
    {
        // Default routes for all staff with dashboard access
        $defaultRoutes = [
            'dashboard',
            'student-list',
            'attendance.student',
            'student-behavior.recording',
        ];

        // Add more routes based on designation if needed
        if ($this->isTeacher()) {
            return $defaultRoutes;
        }

        return $defaultRoutes;
    }

    /**
     * Get the salaries for the staff member.
     */
    public function salaries()
    {
        return $this->hasMany(Salary::class);
    }

    /**
     * Get the loans for the staff member.
     */
    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    /**
     * Teacher/staff in-app notifications (used by mobile teacher app).
     */
    public function notifications()
    {
        return $this->hasMany(StaffNotification::class, 'staff_id');
    }
}

