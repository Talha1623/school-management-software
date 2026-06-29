<?php

namespace App\Services;

use App\Models\HomeworkDiary;
use App\Models\GeneralSetting;
use App\Models\ManagementExpense;
use App\Models\MobileDeviceToken;
use App\Models\ParentAccount;
use App\Models\ParentDeviceToken;
use App\Models\Staff;
use App\Models\StaffDeviceToken;
use App\Models\StaffNotification;
use App\Models\AdminRole;
use App\Models\Message;
use App\Models\Noticeboard;
use App\Models\Salary;
use App\Models\Student;
use App\Models\StudyMaterial;
use App\Models\Timetable;
use App\Models\StudentDeviceToken;
use App\Models\StudentNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Carbon\Carbon;
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
     * Notify students and parents in a class when homework diary is saved.
     *
     * @param  array<string, string>  $filters
     * @param  list<string>  $subjectNames
     * @return array<string, mixed>
     */
    public function notifyHomeworkDiaryPublished(array $filters, string $date, array $subjectNames): array
    {
        $class = trim((string) ($filters['class'] ?? ''));
        $section = trim((string) ($filters['section'] ?? ''));
        $dateFormatted = $this->formatHomeworkDate($date);
        $subjects = implode(', ', array_values(array_filter(array_map('trim', $subjectNames))));
        $classLabel = trim($class.($section !== '' ? ' - '.$section : ''));

        $title = 'New Homework';
        $body = $subjects !== ''
            ? sprintf('Homework posted for Class %s (%s) on %s.', $classLabel, $subjects, $dateFormatted)
            : sprintf('Homework posted for Class %s on %s.', $classLabel, $dateFormatted);

        $eventData = [
            'screen' => 'homework',
            'type' => 'homework_diary',
            'date' => $date,
            'campus' => trim((string) ($filters['campus'] ?? '')),
            'class' => $class,
            'section' => $section,
        ];

        $students = $this->studentsForHomeworkDiary($filters);

        return [
            'students' => $this->notifyStudentsCollection($students, $title, $body, $eventData),
            'parents' => $this->notifyParentsCollection($students, $title, $body, $eventData),
            'matched_student_ids' => $students->pluck('id')->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{count: int, student_ids: list<int>}
     */
    public function countStudentsForFilters(array $filters): array
    {
        $students = $this->studentsForHomeworkDiary($filters);

        return [
            'count' => $students->count(),
            'student_ids' => $students->pluck('id')->all(),
        ];
    }

    /**
     * @param  array<string, string>  $filters
     * @return Collection<int, Student>
     */
    public function studentsForHomeworkDiary(array $filters): Collection
    {
        $campus = trim((string) ($filters['campus'] ?? ''));
        $class = trim((string) ($filters['class'] ?? ''));
        $section = trim((string) ($filters['section'] ?? ''));

        if ($class === '' || $section === '') {
            return collect();
        }

        $diaryClassKeys = HomeworkDiary::classLookupKeys($class);
        if ($diaryClassKeys === []) {
            $diaryClassKeys = [strtolower($class)];
        }

        $diarySection = $this->normalizeSectionKey($section);

        $query = Student::query();
        if ($campus !== '') {
            $query->where(function (Builder $campusQuery) use ($campus) {
                $campusQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campus)])
                    ->orWhereNull('campus')
                    ->orWhereRaw('TRIM(COALESCE(campus, "")) = ?', ['']);
            });
        }

        return $query->get()->filter(function (Student $student) use ($diaryClassKeys, $diarySection) {
            if (! $this->studentSectionMatches($student->section, $diarySection)) {
                return false;
            }

            $studentClassKeys = HomeworkDiary::classLookupKeys($student->class);
            if ($studentClassKeys === []) {
                $studentClassKeys = [strtolower(trim((string) ($student->class ?? '')))];
            }

            return count(array_intersect($studentClassKeys, $diaryClassKeys)) > 0;
        })->values();
    }

    /**
     * @return array{recipients: int, in_app: int, push_sent: int, push_failed: int, no_tokens: int}
     */
    private function notifyStudentsCollection(Collection $students, string $title, string $body, array $eventData): array
    {
        $stats = $this->emptyStats();

        foreach ($students as $student) {
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
     * @return array{recipients: int, in_app: int, push_sent: int, push_failed: int, no_tokens: int}
     */
    private function notifyParentsCollection(Collection $students, string $title, string $body, array $eventData): array
    {
        $stats = $this->emptyStats();
        $notifiedParentIds = [];

        foreach ($students->load('parentAccount') as $student) {
            $parent = $student->parentAccount;
            if (! $parent || isset($notifiedParentIds[$parent->id])) {
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

    private function normalizeSectionKey(?string $section): string
    {
        $section = strtolower(trim((string) $section));
        $section = preg_replace('/^section\s+/i', '', $section) ?? $section;

        return $section;
    }

    private function studentSectionMatches(?string $studentSection, string $diarySection): bool
    {
        $studentKey = $this->normalizeSectionKey($studentSection);
        if ($studentKey === '' || $diarySection === '') {
            return false;
        }

        return $studentKey === $diarySection;
    }

    /**
     * Notify students and parents when study material / homework file is uploaded.
     *
     * @return array<string, mixed>
     */
    public function notifyStudyMaterialPublished(StudyMaterial $material): array
    {
        $filters = array_filter([
            'campus' => trim((string) ($material->campus ?? '')),
            'class' => trim((string) ($material->class ?? '')),
            'section' => trim((string) ($material->section ?? '')),
        ], fn ($value) => $value !== '');

        $class = trim((string) ($material->class ?? ''));
        $section = trim((string) ($material->section ?? ''));
        $subject = trim((string) ($material->subject ?? ''));
        $classLabel = trim($class.($section !== '' ? ' - '.$section : ''));
        $materialTitle = trim((string) ($material->title ?? 'Study Material'));

        $title = 'New Homework / Study Material';
        $body = $subject !== ''
            ? sprintf('"%s" uploaded for Class %s (%s).', $materialTitle, $classLabel, $subject)
            : sprintf('"%s" uploaded for Class %s.', $materialTitle, $classLabel);

        $eventData = [
            'screen' => 'study_material',
            'type' => 'study_material',
            'study_material_id' => (string) $material->id,
            'campus' => trim((string) ($material->campus ?? '')),
            'class' => $class,
            'section' => $section,
            'subject' => $subject,
        ];

        $students = $this->studentsForHomeworkDiary($filters);

        return [
            'students' => $this->notifyStudentsCollection($students, $title, $body, $eventData),
            'parents' => $this->notifyParentsCollection($students, $title, $body, $eventData),
        ];
    }

    private function formatHomeworkDate(string $date): string
    {
        try {
            return \Carbon\Carbon::parse($date)->format('d M Y');
        } catch (\Throwable) {
            return $date;
        }
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

        $this->saveStaffWebNotification($staff, $message);
    }

    /**
     * Notify all admins when a management expense is recorded with "Notify Admin" enabled.
     */
    public function notifyAdminsManagementExpense(
        ManagementExpense $expense,
        string $actorName,
        ?int $actorId = null,
        string $actorType = 'admin'
    ): void {
        $dateLabel = $expense->date
            ? Carbon::parse($expense->date)->format('d M Y')
            : Carbon::now()->format('d M Y');

        $message = sprintf(
            '%s recorded a management expense of Rs %s. Campus: %s. Category: %s. Title: %s. Method: %s. Date: %s.',
            $actorName,
            number_format((float) ($expense->amount ?? 0), 2),
            (string) ($expense->campus ?? 'N/A'),
            (string) ($expense->category ?? 'N/A'),
            (string) ($expense->title ?? 'N/A'),
            (string) ($expense->method ?? 'N/A'),
            $dateLabel
        );

        $fromType = 'accountant_notification';
        $fromId = (int) ($actorId ?? 0);

        try {
            AdminRole::query()
                ->orderBy('id')
                ->get()
                ->each(function (AdminRole $admin) use ($message, $fromType, $fromId) {
                    Message::create([
                        'from_type' => $fromType,
                        'from_id' => $fromId,
                        'to_type' => 'admin',
                        'to_id' => (int) $admin->id,
                        'text' => $message,
                        'attachment_path' => null,
                        'attachment_type' => null,
                        'read_at' => null,
                    ]);
                });
        } catch (\Throwable $e) {
            Log::warning('Admin expense notification save failed', [
                'expense_id' => $expense->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify staff when attendance is saved (Present, Absent, Leave, etc.).
     */
    public function notifyStaffAttendanceMarked(
        Staff $staff,
        string $date,
        string $status,
        ?string $lateArrival = null,
        ?string $earlyExit = null
    ): void {
        $dateLabel = Carbon::parse($date)->format('d M Y');
        $statusLabel = trim($status) !== '' ? trim($status) : 'Updated';

        $details = [];
        if ($lateArrival !== null && $lateArrival !== '' && $lateArrival !== 'No') {
            if ($lateArrival === 'Yes' || preg_match('/^\d{2}:\d{2}$/', $lateArrival)) {
                $details[] = 'Late arrival' . ($lateArrival !== 'Yes' ? ': ' . $lateArrival : '');
            }
        }
        if ($earlyExit !== null && $earlyExit !== '') {
            $details[] = 'Early exit: ' . $earlyExit;
        }

        $message = sprintf(
            'Your attendance for %s has been marked as %s%s.',
            $dateLabel,
            $statusLabel,
            $details !== [] ? ' (' . implode(', ', $details) . ')' : ''
        );

        $title = 'Attendance Notice';

        $this->notifyStaffEvent($staff, $title, $message, 'staff_attendance', [
            'attendance_date' => $date,
            'status' => $statusLabel,
            'late_arrival' => $lateArrival,
            'early_exit' => $earlyExit,
            'screen' => 'attendance',
        ]);

        $this->saveStaffWebNotification($staff, $message);
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

    private function saveStaffWebNotification(Staff $staff, string $text): void
    {
        $adminId = $this->adminActorId();
        if (! $adminId) {
            $adminId = AdminRole::query()
                ->where('super_admin', true)
                ->orderBy('id')
                ->value('id');
        }
        if (! $adminId) {
            $adminId = AdminRole::query()->orderBy('id')->value('id');
        }
        if (! $adminId) {
            Log::warning('Staff web notification skipped: no admin actor found', [
                'staff_id' => $staff->id,
            ]);

            return;
        }

        try {
            Message::create([
                'from_type' => 'admin',
                'from_id' => (int) $adminId,
                'to_type' => 'teacher',
                'to_id' => $staff->id,
                'text' => $text,
                'attachment_path' => null,
                'attachment_type' => null,
                'read_at' => null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Staff web notification save failed', [
                'staff_id' => $staff->id,
                'error' => $e->getMessage(),
            ]);
        }
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
            $classKeys = HomeworkDiary::classLookupKeys($class);
            if ($classKeys === []) {
                $classKeys = [strtolower($class)];
            }

            $query->where(function (Builder $classQuery) use ($classKeys) {
                foreach ($classKeys as $key) {
                    $classQuery->orWhereRaw('LOWER(TRIM(class)) = ?', [$key]);
                }
            });
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
                'data' => array_merge(['type' => $data['type'] ?? 'broadcast'], $data),
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
