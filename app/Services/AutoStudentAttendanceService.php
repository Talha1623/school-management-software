<?php

namespace App\Services;

use App\Models\GeneralSetting;
use App\Models\SalarySetting;
use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\Student;
use App\Models\StudentAttendance;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;

class AutoStudentAttendanceService
{
    public function isAutomationEnabled(): bool
    {
        $automation = GeneralSetting::getSettings()->automation_settings ?? [];

        return ! empty($automation['auto_attendance']);
    }

    public function graceMinutes(): int
    {
        $automation = GeneralSetting::getSettings()->automation_settings ?? [];

        return max(0, (int) ($automation['attendance_time_limit'] ?? 0));
    }

    public function timezone(): string
    {
        $tz = trim((string) (GeneralSetting::getSettings()->timezone ?? ''));

        return $tz !== '' ? $tz : (string) config('app.timezone', 'Asia/Karachi');
    }

    /**
     * School day start from Salary Setting → Late Arrival Time.
     */
    public function schoolStartTime(?Carbon $onDate = null): ?Carbon
    {
        $raw = trim((string) (SalarySetting::getSettings()->late_arrival_time ?? ''));
        if ($raw === '') {
            return null;
        }

        $tz = $this->timezone();
        $day = ($onDate ?? Carbon::now($tz))->copy()->timezone($tz);

        try {
            if (preg_match('/(\d{1,2}):(\d{2})\s*(AM|PM)/i', $raw, $matches)) {
                $parsed = Carbon::createFromFormat(
                    'g:i A',
                    $matches[1] . ':' . $matches[2] . ' ' . strtoupper($matches[3]),
                    $tz
                );
            } else {
                $timePart = preg_match('/^\d{1,2}:\d{2}$/', $raw) ? $raw . ':00' : $raw;
                $parsed = Carbon::parse($day->toDateString() . ' ' . $timePart, $tz);
            }

            return $parsed->setDate($day->year, $day->month, $day->day);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Cutoff = school start + Attendance Time Limit (minutes).
     */
    public function absentCutoffAt(?Carbon $onDate = null): ?Carbon
    {
        $start = $this->schoolStartTime($onDate);
        if ($start === null) {
            return null;
        }

        return $start->copy()->addMinutes($this->graceMinutes());
    }

    public function isPastCutoff(?Carbon $at = null): bool
    {
        $cutoff = $this->absentCutoffAt($at);
        if ($cutoff === null) {
            return false;
        }

        $tz = $this->timezone();
        $now = ($at ?? Carbon::now($tz))->copy()->timezone($tz);

        return $now->greaterThanOrEqualTo($cutoff);
    }

    /**
     * Auto-mark absent for today when automation is enabled and cutoff has passed.
     */
    public function runIfDue(): int
    {
        if (! $this->isAutomationEnabled()) {
            return 0;
        }

        $tz = $this->timezone();
        $today = Carbon::now($tz);

        if (! $this->isPastCutoff($today)) {
            return 0;
        }

        $date = $today->copy()->startOfDay();

        return $this->markUnmarkedStudentsAbsent($date) + $this->markUnmarkedStaffAbsent($date);
    }

    public function markUnmarkedStudentsAbsent(Carbon $date): int
    {
        $passoutClasses = ['passout', 'pass out', 'passed out', 'passedout', 'graduated', 'graduate', 'alumni'];

        $students = Student::query()
            ->whereNotNull('student_code')
            ->where('student_code', '!=', '')
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhereRaw('LOWER(TRIM(status)) = ?', ['active'])
                    ->orWhere('status', '=', '');
            })
            ->where(function ($query) use ($passoutClasses) {
                $query->whereNotNull('class')
                    ->where('class', '!=', '')
                    ->whereRaw(
                        "LOWER(TRIM(COALESCE(class, ''))) NOT IN ('" . implode("', '", $passoutClasses) . "')"
                    );
            })
            ->get();

        if ($students->isEmpty()) {
            return 0;
        }

        $studentIds = $students->pluck('id');
        $attendancesByStudent = StudentAttendance::query()
            ->whereIn('student_id', $studentIds)
            ->whereDate('attendance_date', $date->toDateString())
            ->get()
            ->keyBy('student_id');

        $marked = 0;
        foreach ($students as $student) {
            $attendance = $attendancesByStudent->get($student->id);

            if ($attendance !== null && ! $this->shouldAutoAbsentStudent($attendance)) {
                continue;
            }

            if ($attendance === null) {
                try {
                    StudentAttendance::create([
                        'student_id' => $student->id,
                        'attendance_date' => $date->toDateString(),
                        'status' => 'Absent',
                        'campus' => $student->campus,
                        'class' => $student->class,
                        'section' => $student->section,
                        'remarks' => 'Auto-marked absent (automation)',
                    ]);
                } catch (UniqueConstraintViolationException) {
                    $attendance = StudentAttendance::query()
                        ->where('student_id', $student->id)
                        ->whereDate('attendance_date', $date->toDateString())
                        ->first();

                    if ($attendance !== null && $this->shouldAutoAbsentStudent($attendance)) {
                        $attendance->update([
                            'status' => 'Absent',
                            'campus' => $student->campus,
                            'class' => $student->class,
                            'section' => $student->section,
                            'remarks' => trim(($attendance->remarks ?? '') . ' Auto-marked absent (automation)'),
                        ]);
                    }
                }
            } else {
                $attendance->update([
                    'status' => 'Absent',
                    'campus' => $student->campus,
                    'class' => $student->class,
                    'section' => $student->section,
                    'remarks' => trim(($attendance->remarks ?? '') . ' Auto-marked absent (automation)'),
                ]);
            }

            $marked++;
        }

        return $marked;
    }

    /**
     * Students with no row or placeholder N/A are treated as not marked on time.
     */
    private function shouldAutoAbsentStudent(StudentAttendance $attendance): bool
    {
        $status = strtolower(trim((string) ($attendance->status ?? '')));

        return $status === '' || $status === 'n/a';
    }

    public function markUnmarkedStaffAbsent(Carbon $date): int
    {
        // Only full-time staff (not per hour / lecture).
        $staffMembers = Staff::query()
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhereRaw('LOWER(TRIM(status)) = ?', ['active'])
                    ->orWhere('status', '=', '');
            })
            ->where(function ($query) {
                $query->whereRaw('LOWER(TRIM(COALESCE(salary_type, ""))) = ?', ['full time'])
                    ->orWhereRaw('LOWER(TRIM(COALESCE(salary_type, ""))) = ?', ['fulltime'])
                    ->orWhereRaw('LOWER(TRIM(COALESCE(salary_type, ""))) LIKE ?', ['full time%']);
            })
            ->get();

        if ($staffMembers->isEmpty()) {
            return 0;
        }

        $staffIds = $staffMembers->pluck('id');
        $attendancesByStaff = StaffAttendance::query()
            ->whereIn('staff_id', $staffIds)
            ->whereDate('attendance_date', $date->toDateString())
            ->get()
            ->keyBy('staff_id');

        $marked = 0;
        foreach ($staffMembers as $staff) {
            $attendance = $attendancesByStaff->get($staff->id);

            if ($attendance !== null && ! $this->shouldAutoAbsentStaff($attendance)) {
                continue;
            }

            if ($attendance === null) {
                try {
                    StaffAttendance::create([
                        'staff_id' => $staff->id,
                        'attendance_date' => $date->toDateString(),
                        'status' => 'Absent',
                        'campus' => $staff->campus,
                        'designation' => $staff->designation,
                        'remarks' => 'Auto-marked absent (automation)',
                    ]);
                } catch (UniqueConstraintViolationException) {
                    $attendance = StaffAttendance::query()
                        ->where('staff_id', $staff->id)
                        ->whereDate('attendance_date', $date->toDateString())
                        ->first();

                    if ($attendance !== null && $this->shouldAutoAbsentStaff($attendance)) {
                        $attendance->update([
                            'status' => 'Absent',
                            'campus' => $staff->campus,
                            'designation' => $staff->designation,
                            'remarks' => trim(($attendance->remarks ?? '') . ' Auto-marked absent (automation)'),
                        ]);
                    }
                }
            } else {
                $attendance->update([
                    'status' => 'Absent',
                    'campus' => $staff->campus,
                    'designation' => $staff->designation,
                    'remarks' => trim(($attendance->remarks ?? '') . ' Auto-marked absent (automation)'),
                ]);
            }

            $marked++;
        }

        return $marked;
    }

    private function shouldAutoAbsentStaff(StaffAttendance $attendance): bool
    {
        $status = strtolower(trim((string) ($attendance->status ?? '')));

        return $status === '' || $status === 'n/a';
    }

    public function formattedSchoolStartTime(): ?string
    {
        $start = $this->schoolStartTime();
        if ($start === null) {
            return null;
        }

        return $start->format('h:i A');
    }

    public function formattedCutoffTime(): ?string
    {
        $cutoff = $this->absentCutoffAt();
        if ($cutoff === null) {
            return null;
        }

        return $cutoff->format('h:i A');
    }
}
