<?php

namespace App\Services;

use App\Models\GeneralSetting;
use App\Models\MobileDeviceToken;
use App\Models\ParentAccount;
use App\Models\ParentDeviceToken;
use App\Models\Staff;
use App\Models\StaffDeviceToken;
use App\Models\StaffNotification;
use App\Models\Noticeboard;
use App\Models\Salary;
use App\Models\Student;
use App\Models\Timetable;
use App\Models\StudentDeviceToken;
use App\Models\StudentNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MobilePushNotificationService
{
    public function __construct(
        private readonly FirebasePushService $firebase,
    ) {
    }

    /**
     * @return array{recipients: int, in_app: int, push_sent: int, push_failed: int, no_tokens: int}
     */
    public function sendToStudents(array $filters, string $title, string $bodyTemplate): array
    {
        $stats = $this->emptyStats();
        $schoolName = $this->schoolName();

        foreach ($this->studentsQuery($filters)->cursor() as $student) {
            $stats['recipients']++;
            $resolvedTitle = $this->replaceStudentTags($title, $student, $schoolName);
            $resolvedBody = $this->replaceStudentTags($bodyTemplate, $student, $schoolName);

            if ($this->saveStudentInApp($student, $resolvedTitle, $resolvedBody, 'admin_broadcast')) {
                $stats['in_app']++;
            }

            $tokens = $this->activeStudentTokens((int) $student->id);
            if ($tokens === []) {
                $stats['no_tokens']++;
                continue;
            }

            $push = $this->firebase->sendToTokens($tokens, $resolvedTitle, $resolvedBody, [
                'screen' => 'notifications',
                'type' => 'broadcast',
                'audience' => 'student',
                'student_id' => (string) $student->id,
            ]);
            $stats['push_sent'] += $push['sent'];
            $stats['push_failed'] += $push['failed'];
        }

        return $stats;
    }

    /**
     * @return array{recipients: int, in_app: int, push_sent: int, push_failed: int, no_tokens: int}
     */
    public function sendToParents(array $filters, string $title, string $bodyTemplate): array
    {
        $stats = $this->emptyStats();
        $schoolName = $this->schoolName();
        $notifiedParentIds = [];

        foreach ($this->studentsQuery($filters)->with('parentAccount')->cursor() as $student) {
            $parent = $student->parentAccount;
            if (!$parent) {
                continue;
            }

            if (isset($notifiedParentIds[$parent->id])) {
                continue;
            }
            $notifiedParentIds[$parent->id] = true;
            $stats['recipients']++;

            $resolvedTitle = $this->replaceParentTags($title, $student, $parent, $schoolName);
            $resolvedBody = $this->replaceParentTags($bodyTemplate, $student, $parent, $schoolName);

            $tokens = $this->activeParentTokens((int) $parent->id);
            if ($tokens === []) {
                $stats['no_tokens']++;
                continue;
            }

            $push = $this->firebase->sendToTokens($tokens, $resolvedTitle, $resolvedBody, [
                'screen' => 'notifications',
                'type' => 'broadcast',
                'audience' => 'parent',
                'parent_id' => (string) $parent->id,
            ]);
            $stats['push_sent'] += $push['sent'];
            $stats['push_failed'] += $push['failed'];
        }

        return $stats;
    }

    /**
     * @return array{recipients: int, in_app: int, push_sent: int, push_failed: int, no_tokens: int}
     */
    public function sendToStaff(array $filters, string $title, string $bodyTemplate): array
    {
        $stats = $this->emptyStats();
        $schoolName = $this->schoolName();

        foreach ($this->staffQuery($filters)->cursor() as $staff) {
            $stats['recipients']++;
            $resolvedTitle = $this->replaceStaffTags($title, $staff, $schoolName);
            $resolvedBody = $this->replaceStaffTags($bodyTemplate, $staff, $schoolName);

            if ($this->saveStaffInApp($staff, $resolvedTitle, $resolvedBody, 'admin_broadcast')) {
                $stats['in_app']++;
            }

            $tokens = $this->activeStaffTokens((int) $staff->id);
            if ($tokens === []) {
                $stats['no_tokens']++;
                continue;
            }

            $push = $this->firebase->sendToTokens($tokens, $resolvedTitle, $resolvedBody, [
                'screen' => 'notifications',
                'type' => 'broadcast',
                'audience' => 'staff',
                'staff_id' => (string) $staff->id,
            ]);
            $stats['push_sent'] += $push['sent'];
            $stats['push_failed'] += $push['failed'];
        }

        return $stats;
    }

    /**
     * Notify students, parents, and staff when a school notice is published for mobile.
     *
     * @return array<string, mixed>
     */
    public function notifyNoticeboardPublished(Noticeboard $noticeboard): array
    {
        $showOn = trim((string) ($noticeboard->show_on ?? ''));
        if (!$this->isNoticeVisibleOnMobile($showOn)) {
            return ['skipped' => true, 'reason' => 'not_visible_on_mobile'];
        }

        $title = 'New School Notice';
        $body = $this->noticeboardMessage($noticeboard);
        $filters = $this->noticeboardFilters($noticeboard);
        $eventData = [
            'noticeboard_id' => (string) $noticeboard->id,
            'screen' => 'noticeboard',
            'type' => 'noticeboard',
        ];

        $stats = [
            'students' => $this->notifyStudentsNoticeboard($filters, $title, $body, $eventData),
            'parents' => $this->notifyParentsNoticeboard($filters, $title, $body, $eventData),
        ];

        if ($showOn === 'Yes') {
            $stats['staff'] = $this->notifyStaffNoticeboard($filters, $title, $body, $eventData);
        }

        return $stats;
    }

    /**
     * Notify staff/teacher when salary is generated (Pending).
     */
    public function notifyStaffSalaryGenerated(Staff $staff, Salary $salary): void
    {
        $amount = number_format((float) ($salary->salary_generated ?? 0), 0);
        $title = 'Salary Generated';
        $message = sprintf(
            'Your salary for %s %s has been generated. Net payable: Rs %s. Status: %s.',
            (string) ($salary->salary_month ?? ''),
            (string) ($salary->year ?? ''),
            $amount,
            (string) ($salary->status ?? 'Pending')
        );

        $this->notifyStaffEvent($staff, $title, $message, 'salary_generated', [
            'salary_id' => (int) $salary->id,
            'salary_month' => (string) ($salary->salary_month ?? ''),
            'year' => (string) ($salary->year ?? ''),
            'salary_generated' => (float) ($salary->salary_generated ?? 0),
            'status' => (string) ($salary->status ?? 'Pending'),
            'screen' => 'salary',
        ]);
    }

    /**
     * Notify teacher when a timetable period is saved for their subject.
     */
    public function notifyStaffTimetableAssigned(Staff $staff, Timetable $timetable): void
    {
        $startTime = $this->formatTimetableTime((string) ($timetable->starting_time ?? ''));
        $endTime = $this->formatTimetableTime((string) ($timetable->ending_time ?? ''));
        $title = 'Timetable Assigned';
        $message = sprintf(
            'Your timetable has been assigned: %s on %s (%s - %s) for Class %s, Section %s.',
            (string) ($timetable->subject ?? ''),
            (string) ($timetable->day ?? ''),
            $startTime,
            $endTime,
            (string) ($timetable->class ?? ''),
            (string) ($timetable->section ?? '')
        );

        $this->notifyStaffEvent($staff, $title, $message, 'timetable_assigned', [
            'timetable_id' => (int) $timetable->id,
            'campus' => (string) ($timetable->campus ?? ''),
            'class' => (string) ($timetable->class ?? ''),
            'section' => (string) ($timetable->section ?? ''),
            'subject' => (string) ($timetable->subject ?? ''),
            'day' => (string) ($timetable->day ?? ''),
            'starting_time' => $startTime,
            'ending_time' => $endTime,
            'screen' => 'timetable',
        ]);
    }

    private function formatTimetableTime(string $time): string
    {
        $time = trim($time);
        if ($time === '') {
            return 'N/A';
        }

        try {
            return \Carbon\Carbon::parse($time)->format('h:i A');
        } catch (\Throwable) {
            return $time;
        }
    }

    /**
     * Notify staff/teacher when salary payment is recorded (Issued/Paid).
     */
    public function notifyStaffSalaryPaid(Staff $staff, Salary $salary): void
    {
        $paid = number_format((float) ($salary->amount_paid ?? 0), 0);
        $title = 'Salary Payment';
        $message = sprintf(
            'Your salary for %s %s has been updated. Paid: Rs %s. Status: %s.',
            (string) ($salary->salary_month ?? ''),
            (string) ($salary->year ?? ''),
            $paid,
            (string) ($salary->status ?? 'Pending')
        );

        $this->notifyStaffEvent($staff, $title, $message, 'salary_payment', [
            'salary_id' => (int) $salary->id,
            'salary_month' => (string) ($salary->salary_month ?? ''),
            'year' => (string) ($salary->year ?? ''),
            'amount_paid' => (float) ($salary->amount_paid ?? 0),
            'status' => (string) ($salary->status ?? 'Pending'),
            'screen' => 'salary',
        ]);
    }

    /**
     * @param array<string, mixed> $filters
     * @param array<string, string> $eventData
     * @return array{recipients: int, in_app: int, push_sent: int, push_failed: int, no_tokens: int}
     */
    private function notifyStudentsNoticeboard(array $filters, string $title, string $body, array $eventData): array
    {
        $stats = $this->emptyStats();

        foreach ($this->studentsQuery($filters)->cursor() as $student) {
            $stats['recipients']++;

            if ($this->saveStudentInApp($student, $title, $body, 'system', $eventData)) {
                $stats['in_app']++;
            }

            $tokens = $this->activeStudentTokens((int) $student->id);
            if ($tokens === []) {
                $stats['no_tokens']++;
                continue;
            }

            $push = $this->firebase->sendToTokens($tokens, $title, $body, array_merge([
                'audience' => 'student',
                'student_id' => (string) $student->id,
            ], $eventData));
            $stats['push_sent'] += $push['sent'];
            $stats['push_failed'] += $push['failed'];
        }

        return $stats;
    }

    /**
     * @param array<string, mixed> $filters
     * @param array<string, string> $eventData
     * @return array{recipients: int, in_app: int, push_sent: int, push_failed: int, no_tokens: int}
     */
    private function notifyParentsNoticeboard(array $filters, string $title, string $body, array $eventData): array
    {
        $stats = $this->emptyStats();
        $notifiedParentIds = [];

        foreach ($this->studentsQuery($filters)->with('parentAccount')->cursor() as $student) {
            $parent = $student->parentAccount;
            if (!$parent || isset($notifiedParentIds[$parent->id])) {
                continue;
            }

            $notifiedParentIds[$parent->id] = true;
            $stats['recipients']++;

            $tokens = $this->activeParentTokens((int) $parent->id);
            if ($tokens === []) {
                $stats['no_tokens']++;
                continue;
            }

            $push = $this->firebase->sendToTokens($tokens, $title, $body, array_merge([
                'audience' => 'parent',
                'parent_id' => (string) $parent->id,
            ], $eventData));
            $stats['push_sent'] += $push['sent'];
            $stats['push_failed'] += $push['failed'];
        }

        return $stats;
    }

    /**
     * @param array<string, mixed> $filters
     * @param array<string, string> $eventData
     * @return array{recipients: int, in_app: int, push_sent: int, push_failed: int, no_tokens: int}
     */
    private function notifyStaffNoticeboard(array $filters, string $title, string $body, array $eventData): array
    {
        $stats = $this->emptyStats();

        foreach ($this->staffQuery($filters)->cursor() as $staff) {
            $stats['recipients']++;

            if ($this->saveStaffInApp($staff, $title, $body, 'system', $eventData)) {
                $stats['in_app']++;
            }

            $tokens = $this->activeStaffTokens((int) $staff->id);
            if ($tokens === []) {
                $stats['no_tokens']++;
                continue;
            }

            $push = $this->firebase->sendToTokens($tokens, $title, $body, array_merge([
                'audience' => 'staff',
                'staff_id' => (string) $staff->id,
            ], $eventData));
            $stats['push_sent'] += $push['sent'];
            $stats['push_failed'] += $push['failed'];
        }

        return $stats;
    }

    private function isNoticeVisibleOnMobile(string $showOn): bool
    {
        if (strcasecmp($showOn, 'No') === 0) {
            return false;
        }

        if ($showOn === '' || strcasecmp($showOn, 'Yes') === 0) {
            return true;
        }

        return str_contains($showOn, 'mobile_app');
    }

    private function noticeboardMessage(Noticeboard $noticeboard): string
    {
        $title = trim((string) ($noticeboard->title ?? ''));
        $notice = trim(strip_tags((string) ($noticeboard->notice ?? '')));

        if ($title === '') {
            return 'A new notice has been published.';
        }

        if ($notice === '') {
            return $title;
        }

        return $title . ': ' . Str::limit($notice, 120);
    }

    /** @return array<string, string> */
    private function noticeboardFilters(Noticeboard $noticeboard): array
    {
        $campus = trim((string) ($noticeboard->campus ?? ''));

        return $campus !== '' ? ['campus' => $campus] : [];
    }

    private function notifyStaffEvent(Staff $staff, string $title, string $message, string $eventType, array $data): void
    {
        $saved = $this->saveStaffInApp($staff, $title, $message, 'system', array_merge(['type' => $eventType], $data));

        $tokens = $this->activeStaffTokens((int) $staff->id);
        if ($tokens === []) {
            Log::info('Staff notification saved without push token', [
                'event_type' => $eventType,
                'staff_id' => $staff->id,
                'in_app_saved' => $saved,
            ]);

            return;
        }

        $result = $this->firebase->sendToTokens($tokens, $title, $message, array_merge([
            'screen' => 'notifications',
            'type' => $eventType,
            'audience' => 'staff',
            'staff_id' => (string) $staff->id,
        ], $data));

        Log::info('Staff push notification result', [
            'event_type' => $eventType,
            'staff_id' => $staff->id,
            'in_app_saved' => $saved,
            'push' => $result,
        ]);
    }

    private function studentsQuery(array $filters): Builder
    {
        $query = Student::query();

        $campus = trim((string) ($filters['campus'] ?? ''));
        $class = trim((string) ($filters['class'] ?? ''));
        $section = trim((string) ($filters['section'] ?? ''));

        if ($campus !== '') {
            $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campus)]);
        }
        if ($class !== '') {
            $query->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($class)]);
        }
        if ($section !== '') {
            $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($section)]);
        }

        return $query->orderBy('id');
    }

    private function staffQuery(array $filters): Builder
    {
        $query = Staff::query()
            ->where(function ($q) {
                $q->whereNull('status')
                    ->orWhereRaw('LOWER(TRIM(status)) = ?', ['active']);
            });

        $campus = trim((string) ($filters['campus'] ?? ''));
        if ($campus !== '') {
            $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campus)]);
        }

        return $query->orderBy('name');
    }

    private function replaceStudentTags(string $text, Student $student, string $schoolName): string
    {
        return str_replace(
            ['$student_name', '$roll_number', '$class_name', '$section_name', '$campus_name', '$school_name'],
            [
                (string) ($student->student_name ?? ''),
                (string) ($student->student_code ?? $student->id),
                (string) ($student->class ?? ''),
                (string) ($student->section ?? ''),
                (string) ($student->campus ?? ''),
                $schoolName,
            ],
            $text
        );
    }

    private function replaceParentTags(string $text, Student $student, ParentAccount $parent, string $schoolName): string
    {
        return str_replace(
            ['$student_name', '$parent_name', '$roll_number', '$class_name', '$section_name', '$campus_name', '$school_name'],
            [
                (string) ($student->student_name ?? ''),
                (string) ($parent->name ?? $student->father_name ?? ''),
                (string) ($student->student_code ?? $student->id),
                (string) ($student->class ?? ''),
                (string) ($student->section ?? ''),
                (string) ($student->campus ?? ''),
                $schoolName,
            ],
            $text
        );
    }

    private function replaceStaffTags(string $text, Staff $staff, string $schoolName): string
    {
        return str_replace(
            ['$staff_name', '$emp_id', '$designation', '$campus_name', '$school_name'],
            [
                (string) ($staff->name ?? ''),
                (string) ($staff->emp_id ?? $staff->id),
                (string) ($staff->designation ?? ''),
                (string) ($staff->campus ?? ''),
                $schoolName,
            ],
            $text
        );
    }

    private function saveStudentInApp(Student $student, string $title, string $message, string $createdByType, array $data = []): bool
    {
        if (!Schema::hasTable('student_notifications')) {
            return false;
        }

        try {
            StudentNotification::create([
                'student_id' => $student->id,
                'title' => $title,
                'message' => $message,
                'data' => array_merge(['type' => 'broadcast'], $data),
                'created_by_type' => $createdByType,
                'created_by_id' => $this->adminActorId(),
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::warning('Student in-app notification save failed', [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function saveStaffInApp(Staff $staff, string $title, string $message, string $createdByType, array $data = []): bool
    {
        if (!Schema::hasTable('staff_notifications')) {
            return false;
        }

        try {
            StaffNotification::create([
                'staff_id' => $staff->id,
                'title' => $title,
                'message' => $message,
                'data' => array_merge(['type' => 'broadcast'], $data),
                'created_by_type' => $createdByType,
                'created_by_id' => $this->adminActorId(),
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::warning('Staff in-app notification save failed', [
                'staff_id' => $staff->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /** @return list<string> */
    private function activeStudentTokens(int $studentId): array
    {
        if (!Schema::hasTable('student_device_tokens')) {
            return [];
        }

        return StudentDeviceToken::query()
            ->where('student_id', $studentId)
            ->where('is_active', true)
            ->pluck('fcm_token')
            ->filter()
            ->values()
            ->all();
    }

    /** @return list<string> */
    private function activeParentTokens(int $parentId): array
    {
        if (!Schema::hasTable('parent_device_tokens')) {
            return [];
        }

        return ParentDeviceToken::query()
            ->where('parent_id', $parentId)
            ->where('is_active', true)
            ->pluck('fcm_token')
            ->filter()
            ->values()
            ->all();
    }

    /** @return list<string> */
    private function activeStaffTokens(int $staffId): array
    {
        $tokens = [];

        if (Schema::hasTable('staff_device_tokens')) {
            $tokens = array_merge(
                $tokens,
                StaffDeviceToken::query()
                    ->where('staff_id', $staffId)
                    ->where('is_active', true)
                    ->pluck('fcm_token')
                    ->filter()
                    ->values()
                    ->all()
            );
        }

        if (Schema::hasTable('mobile_device_tokens')) {
            $tokens = array_merge(
                $tokens,
                MobileDeviceToken::query()
                    ->where('user_type', 'teacher')
                    ->where('user_id', $staffId)
                    ->whereNotNull('fcm_token')
                    ->pluck('fcm_token')
                    ->filter()
                    ->values()
                    ->all()
            );
        }

        return array_values(array_unique(array_filter($tokens)));
    }

    private function schoolName(): string
    {
        if (Schema::hasTable('general_settings')) {
            $name = GeneralSetting::query()->value('school_name');
            if (is_string($name) && trim($name) !== '') {
                return trim($name);
            }
        }

        return (string) config('app.name', 'School');
    }

    private function adminActorId(): ?int
    {
        $admin = Auth::guard('admin')->user();

        return $admin ? (int) $admin->id : null;
    }

    /** @return array{recipients: int, in_app: int, push_sent: int, push_failed: int, no_tokens: int} */
    private function emptyStats(): array
    {
        return [
            'recipients' => 0,
            'in_app' => 0,
            'push_sent' => 0,
            'push_failed' => 0,
            'no_tokens' => 0,
        ];
    }
}
