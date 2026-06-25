<?php

namespace App\Services;

use App\Models\Campus;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentPayment;

/**
 * Campus-scoped code prefixes (ST*) and sequences (student + employee IDs).
 */
class CampusCodeService
{
    /**
     * Student code prefix for a campus (e.g. ST1, ST3) — same rules as admission.
     */
    public function resolveCampusCodePrefix(?string $campus): string
    {
        $campus = trim((string) $campus);
        if ($campus === '') {
            return 'ST';
        }

        $campusRecord = Campus::whereRaw('LOWER(TRIM(campus_name)) = ?', [strtolower($campus)])->first();
        if ($campusRecord && ! empty($campusRecord->code_prefix)) {
            return strtoupper(trim($campusRecord->code_prefix));
        }

        if (preg_match('/(\d+)/', $campus, $matches)) {
            $prefix = 'ST'.$matches[1];
            if ($campusRecord) {
                $campusRecord->code_prefix = $prefix;
                $campusRecord->save();
            }

            return strtoupper($prefix);
        }

        $usedCampusNumbers = [];

        $campusesWithPrefix = Campus::whereNotNull('code_prefix')
            ->where('code_prefix', 'like', 'ST%')
            ->get();

        foreach ($campusesWithPrefix as $campusWithPrefix) {
            if (preg_match('/^ST(\d+)$/i', $campusWithPrefix->code_prefix, $matches)) {
                $usedCampusNumbers[] = (int) $matches[1];
            }
        }

        $studentCodes = Student::whereNotNull('student_code')
            ->where('student_code', 'like', 'ST%-%')
            ->pluck('student_code')
            ->toArray();

        $paymentCodes = StudentPayment::whereNotNull('student_code')
            ->where('student_code', 'like', 'ST%-%')
            ->distinct()
            ->pluck('student_code')
            ->toArray();

        foreach (array_unique(array_merge($studentCodes, $paymentCodes)) as $code) {
            $code = trim((string) $code);
            if ($code === '') {
                continue;
            }
            if (preg_match('/^ST(\d+)-(\d+)$/i', $code, $matches)) {
                $campusNum = (int) $matches[1];
                if ($campusNum > 0 && ! in_array($campusNum, $usedCampusNumbers, true)) {
                    $usedCampusNumbers[] = $campusNum;
                }
            }
        }

        sort($usedCampusNumbers);

        $nextCampusNumber = 1;
        if ($usedCampusNumbers !== []) {
            $nextCampusNumber = max($usedCampusNumbers) + 1;
        }

        $prefix = 'ST'.$nextCampusNumber;

        if ($campusRecord) {
            $campusRecord->code_prefix = $prefix;
            $campusRecord->save();
        }

        return strtoupper($prefix);
    }

    /**
     * Employee ID prefix for a campus (e.g. ST3 → EMP3).
     */
    public function employeePrefixForCampus(?string $campus): string
    {
        $studentPrefix = $this->resolveCampusCodePrefix($campus);

        if (preg_match('/^ST(\d+)$/i', $studentPrefix, $matches)) {
            return 'EMP'.$matches[1];
        }

        if (preg_match('/(\d+)/', $studentPrefix, $matches)) {
            return 'EMP'.$matches[1];
        }

        return 'EMP1';
    }

    /**
     * Next employee ID for a campus (e.g. EMP3-007).
     *
     * @param  array<int, string>  $exclude
     */
    public function generateNextEmployeeId(?string $campus, array $exclude = []): string
    {
        $prefix = $this->employeePrefixForCampus($campus);
        $campus = trim((string) $campus);

        $existingCodes = Staff::query()
            ->whereNotNull('emp_id')
            ->where('emp_id', 'like', $prefix.'-%')
            ->pluck('emp_id')
            ->toArray();

        $exclude = array_filter($exclude, fn ($code) => stripos((string) $code, $prefix.'-') === 0);
        $allCodes = array_unique(array_merge($existingCodes, $exclude));

        if ($allCodes === []) {
            return $prefix.'-001';
        }

        $maxNumber = 0;
        $pattern = '/^'.preg_quote($prefix, '/').'-(\d+)$/i';
        foreach ($allCodes as $code) {
            if (preg_match($pattern, (string) $code, $matches)) {
                $maxNumber = max($maxNumber, (int) $matches[1]);
            }
        }

        return $prefix.'-'.str_pad((string) ($maxNumber + 1), 3, '0', STR_PAD_LEFT);
    }
}
