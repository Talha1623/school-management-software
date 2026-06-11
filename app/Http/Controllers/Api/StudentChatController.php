<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminRole;
use App\Models\Message;
use App\Models\ParentAccount;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class StudentChatController extends Controller
{
    /**
     * Directory for chat: super admin, your parent(s), and class teachers.
     * Same shape as GET /api/teacher/chat/contacts (teacher has students; student has teachers + parents).
     *
     * GET /api/student/chat/contacts
     */
    public function contacts(Request $request): JsonResponse
    {
        $student = $this->resolveAuthenticatedStudent($request);
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Only students can access this resource.',
                'token' => null,
            ], 403);
        }

        $limit = (int) $request->get('limit', 500);
        $limit = max(1, min($limit, 1000));

        $student->loadMissing('parentAccount');

        $superAdmin = AdminRole::where('super_admin', true)->first() ?? AdminRole::orderBy('id')->first();
        $superAdminPayload = $superAdmin ? $this->formatAdminContactRow($superAdmin) : null;

        $teachers = $this->teachersForStudent($student)->sortBy('name')->values();
        $teachersPayload = $teachers->map(fn (Staff $s) => $this->formatTeacherContactRow($s));

        $parentsPayload = collect();
        if ($student->parentAccount) {
            $parentsPayload->push($this->formatParentContactRow($student->parentAccount, $student));
        }

        return response()->json([
            'success' => true,
            'message' => 'Chat contacts loaded successfully.',
            'data' => [
                'viewer' => $this->formatViewerRow($student),
                'student' => $this->formatSelfStudentRow($student),
                'super_admin' => $superAdminPayload,
                'parents' => $parentsPayload->values()->all(),
                'parent' => $parentsPayload->first(),
                'teachers' => $teachersPayload->values()->all(),
                'parents_truncated' => false,
                'teachers_truncated' => $teachers->count() >= $limit,
                'limit' => $limit,
            ],
            'token' => $request->user()?->currentAccessToken()?->token ?? null,
        ], 200);
    }

    /**
     * GET /api/student/chat/contacts/{type}/{id}
     * type: admin | teacher | parent
     */
    public function contactShow(Request $request, string $type, string $id): JsonResponse
    {
        try {
            return $this->resolveContactShow($request, $type, $id);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load contact: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }

    private function resolveContactShow(Request $request, string $type, string $id): JsonResponse
    {
        $student = $this->resolveAuthenticatedStudent($request);
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Only students can access this resource.',
                'token' => null,
            ], 403);
        }

        if (!ctype_digit((string) $id)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid id.',
                'token' => null,
            ], 422);
        }

        $numericId = (int) $id;
        $type = strtolower(trim($type));
        if (!in_array($type, ['admin', 'teacher', 'parent'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid type. Allowed: admin, teacher, parent.',
                'token' => null,
            ], 422);
        }

        switch ($type) {
            case 'admin':
                $admin = AdminRole::find($numericId);
                if (!$admin) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Contact not found.',
                        'token' => null,
                    ], 404);
                }
                $contact = $this->formatAdminContactRow($admin);
                break;

            case 'parent':
                if (!$this->assertStudentOwnsParent($student, $numericId)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Contact not found.',
                        'token' => null,
                    ], 404);
                }
                $contact = $this->formatParentContactRow(
                    ParentAccount::find($numericId),
                    $student
                );
                break;

            case 'teacher':
                if (!$this->assertStudentMayChatWithTeacher($student, $numericId)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Contact not found.',
                        'token' => null,
                    ], 404);
                }
                $contact = $this->formatTeacherContactRow(Staff::find($numericId));
                break;
        }

        $messagesQuery = null;
        $adminInboxHint = null;

        if ($type === 'admin') {
            $messagesQuery = [
                'peer_type' => 'admin',
                'peer_id' => $numericId,
                'send' => [
                    'method' => 'POST',
                    'endpoint' => '/api/student/chat/messages',
                    'peer_type' => 'admin',
                    'peer_id' => $numericId,
                    'admin_id' => $numericId,
                ],
            ];
        } elseif ($type === 'teacher') {
            $messagesQuery = [
                'peer_type' => 'teacher',
                'peer_id' => $numericId,
                'send' => [
                    'method' => 'POST',
                    'endpoint' => '/api/student/chat/messages',
                    'peer_type' => 'teacher',
                    'peer_id' => $numericId,
                    'teacher_id' => $numericId,
                ],
            ];
        } elseif ($type === 'parent') {
            $messagesQuery = [
                'peer_type' => 'parent',
                'peer_id' => $numericId,
                'send' => [
                    'method' => 'POST',
                    'endpoint' => '/api/student/chat/messages',
                    'peer_type' => 'parent',
                    'peer_id' => $numericId,
                    'parent_id' => $numericId,
                ],
            ];
        }

        $messageBlock = $this->contactShowMessagesBlock($request, $student, $type, $numericId);

        return response()->json([
            'success' => true,
            'message' => 'Contact loaded successfully.',
            'data' => [
                'type' => $type,
                'id' => $numericId,
                'viewer' => $this->formatViewerRow($student),
                'ui_hints' => [
                    'my_message_align' => 'right',
                    'peer_message_align' => 'left',
                    'check_field' => 'is_mine',
                    'fallback_fields' => ['isMine', 'show_on_right', 'align', 'direction'],
                    'contact_id' => $numericId,
                ],
                'contact' => $contact,
                'messages_query' => $messagesQuery,
                'admin_inbox_hint' => $adminInboxHint,
                'messages' => $messageBlock['messages'],
                'unread_count' => $messageBlock['unread_count'],
                'messages_limit' => $messageBlock['messages_limit'],
                'messages_truncated' => $messageBlock['messages_truncated'],
            ],
            'token' => $request->user()?->currentAccessToken()?->token ?? null,
        ], 200);
    }

    /**
     * GET /api/student/chat/messages
     * Query: peer_type (admin|teacher|parent), peer_id
     * Same shape as GET /api/teacher/chat/messages
     */
    public function index(Request $request): JsonResponse
    {
        $student = $this->resolveAuthenticatedStudent($request);
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Student authentication required.',
                'token' => null,
            ], 403);
        }

        $resolved = $this->resolvePeerFromRequest($request, $student);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        [$peerType, $peerId] = $resolved;

        $messagesBase = Message::query();
        $unreadBase = Message::query();
        $this->applyConversationFilters($messagesBase, $student, $peerType, $peerId);
        $this->applyUnreadFilters($unreadBase, $student, $peerType, $peerId);

        $perPage = (int) $request->get('per_page', 20);
        $perPage = in_array($perPage, [10, 20, 50, 100], true) ? $perPage : 20;

        $unreadCount = (clone $unreadBase)->count();
        $messages = (clone $messagesBase)->orderBy('created_at', 'asc')->paginate($perPage);

        $data = $messages->map(fn (Message $message) => $this->formatMessageRow(
            $message,
            $student,
            $peerType,
            $peerId
        ));

        return response()->json([
            'success' => true,
            'message' => 'Chat messages loaded successfully.',
            'data' => [
                'peer_type' => $peerType,
                'peer_id' => $peerId,
                'viewer' => $this->formatViewerRow($student),
                'ui_hints' => [
                    'my_message_align' => 'right',
                    'peer_message_align' => 'left',
                    'check_field' => 'is_mine',
                    'fallback_fields' => ['isMine', 'show_on_right', 'align', 'direction'],
                    'contact_id' => $peerId,
                ],
                'messages' => $data,
                'unread_count' => $unreadCount,
                'pagination' => [
                    'current_page' => $messages->currentPage(),
                    'last_page' => $messages->lastPage(),
                    'per_page' => $messages->perPage(),
                    'total' => $messages->total(),
                    'from' => $messages->firstItem(),
                    'to' => $messages->lastItem(),
                ],
            ],
            'token' => $request->user()?->currentAccessToken()?->token ?? null,
        ], 200);
    }

    /**
     * POST /api/student/chat/messages
     */
    public function store(Request $request): JsonResponse
    {
        $student = $this->resolveAuthenticatedStudent($request);
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Student authentication required.',
                'token' => null,
            ], 403);
        }

        $this->normalizeTextInput($request);

        $validated = $request->validate([
            'text' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,txt', 'max:5120'],
            'peer_type' => ['nullable', 'string', 'in:admin,teacher,parent'],
            'peer_id' => ['nullable', 'integer', 'min:1'],
        ]);

        if (empty($validated['text']) && !$request->hasFile('attachment')) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide message text or an attachment.',
                'token' => null,
            ], 422);
        }

        $resolved = $this->resolvePeerFromRequest($request, $student);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        [$peerType, $peerId] = $resolved;
        [$toType, $toId] = $this->resolveMessageTarget($peerType, $peerId);

        $attachmentPath = null;
        $attachmentType = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $folder = match ($toType) {
                'teacher' => 'chat-attachments/student-teacher',
                'parent' => 'chat-attachments/student-parent',
                default => 'chat-attachments/students',
            };
            $storedPath = $file->store($folder, 'public');
            $attachmentPath = $storedPath;

            $mime = $file->getClientMimeType();
            if (str_starts_with($mime, 'image/')) {
                $attachmentType = 'image';
            } elseif ($mime === 'application/pdf') {
                $attachmentType = 'pdf';
            } else {
                $attachmentType = 'document';
            }
        }

        $message = Message::create([
            'from_type' => 'student',
            'from_id' => $student->id,
            'to_type' => $toType,
            'to_id' => $toId,
            'text' => $validated['text'] ?? null,
            'attachment_path' => $attachmentPath,
            'attachment_type' => $attachmentType,
            'read_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully.',
            'data' => $this->formatMessageRow($message, $student, $peerType, $peerId),
            'token' => $request->user()?->currentAccessToken()?->token ?? null,
        ], 201);
    }

    /**
     * POST /api/student/chat/messages/read
     */
    public function markRead(Request $request): JsonResponse
    {
        $student = $this->resolveAuthenticatedStudent($request);
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Student authentication required.',
                'token' => null,
            ], 403);
        }

        $resolved = $this->resolvePeerFromRequest($request, $student);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        [$peerType, $peerId] = $resolved;

        $q = Message::query()->whereNull('read_at');

        if ($peerType === 'teacher') {
            $q->where('from_type', 'teacher')
                ->where('from_id', $peerId)
                ->where('to_type', 'student')
                ->where('to_id', $student->id);
        } elseif ($peerType === 'parent') {
            $q->where('from_type', 'parent')
                ->where('from_id', $peerId)
                ->where('to_type', 'student')
                ->where('to_id', $student->id);
        } else {
            $q->where('from_type', 'admin')
                ->where('from_id', $peerId)
                ->where('to_type', 'student')
                ->where('to_id', $student->id);
        }

        $q->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Messages marked as read.',
            'data' => [
                'peer_type' => $peerType,
                'peer_id' => $peerId,
            ],
            'token' => $request->user()?->currentAccessToken()?->token ?? null,
        ], 200);
    }

    /**
     * @return Collection<int, Staff>
     */
    private function teachersForStudent(Student $student): Collection
    {
        $class = strtolower(trim((string) $student->class));
        $section = strtolower(trim((string) $student->section));
        $campus = strtolower(trim((string) $student->campus));

        $teacherNames = collect();

        if ($class !== '') {
            $subjectsQuery = Subject::query()
                ->whereRaw('LOWER(TRIM(class)) = ?', [$class]);
            if ($section !== '') {
                $subjectsQuery->where(function ($q) use ($section) {
                    $q->whereRaw('LOWER(TRIM(section)) = ?', [$section])
                        ->orWhereNull('section')
                        ->orWhereRaw('TRIM(COALESCE(section, "")) = ?', ['']);
                });
            }
            if ($campus !== '') {
                $subjectsQuery->whereRaw('LOWER(TRIM(COALESCE(campus, ""))) = ?', [$campus]);
            }
            $teacherNames = $teacherNames->merge(
                $subjectsQuery->pluck('teacher')
                    ->map(fn ($n) => trim((string) $n))
                    ->filter()
            );

            $sectionsQuery = Section::query()
                ->whereRaw('LOWER(TRIM(class)) = ?', [$class]);
            if ($section !== '') {
                $sectionsQuery->whereRaw('LOWER(TRIM(name)) = ?', [$section]);
            }
            if ($campus !== '') {
                $sectionsQuery->whereRaw('LOWER(TRIM(COALESCE(campus, ""))) = ?', [$campus]);
            }
            $teacherNames = $teacherNames->merge(
                $sectionsQuery->pluck('teacher')
                    ->map(fn ($n) => trim((string) $n))
                    ->filter()
            );
        }

        $uniqueNames = $teacherNames
            ->filter()
            ->unique(fn ($name) => strtolower($name))
            ->values();

        $baseQuery = Staff::query()
            ->whereRaw('LOWER(COALESCE(designation, "")) LIKE ?', ['%teacher%'])
            ->where(function ($q) {
                $q->whereNull('status')
                    ->orWhereRaw('LOWER(TRIM(status)) = ?', ['active']);
            });

        if ($uniqueNames->isNotEmpty()) {
            $baseQuery->where(function ($q) use ($uniqueNames) {
                foreach ($uniqueNames as $name) {
                    $q->orWhereRaw('LOWER(TRIM(name)) = ?', [strtolower($name)]);
                }
            });
        } elseif ($campus !== '') {
            $baseQuery->whereRaw('LOWER(TRIM(campus)) = ?', [$campus]);
        } else {
            return collect();
        }

        return $baseQuery->orderBy('name')->get();
    }

    /**
     * @return array{messages: array, unread_count: int, messages_limit: int, messages_truncated: bool}
     */
    private function contactShowMessagesBlock(Request $request, Student $student, string $type, int $numericId): array
    {
        $empty = [
            'messages' => [],
            'unread_count' => 0,
            'messages_limit' => 0,
            'messages_truncated' => false,
        ];

        $limit = (int) $request->query('messages_limit', 200);
        $limit = max(1, min($limit, 500));

        if ($type === 'teacher' && !$this->assertStudentMayChatWithTeacher($student, $numericId)) {
            return array_merge($empty, ['messages_limit' => $limit]);
        }

        if ($type === 'parent' && !$this->assertStudentOwnsParent($student, $numericId)) {
            return array_merge($empty, ['messages_limit' => $limit]);
        }

        if ($type === 'admin' && !AdminRole::find($numericId)) {
            return array_merge($empty, ['messages_limit' => $limit]);
        }

        $messagesBase = Message::query();
        $unreadBase = Message::query();
        $this->applyConversationFilters($messagesBase, $student, $type, $numericId);
        $this->applyUnreadFilters($unreadBase, $student, $type, $numericId);

        $totalCount = (clone $messagesBase)->count();
        $unreadCount = (clone $unreadBase)->count();
        $messages = (clone $messagesBase)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->sortBy('created_at')
            ->values();

        return [
            'messages' => $messages->map(fn (Message $message) => $this->formatMessageRow(
                $message,
                $student,
                $type,
                $numericId
            ))->values()->all(),
            'unread_count' => $unreadCount,
            'messages_limit' => $limit,
            'messages_truncated' => $totalCount > $limit,
        ];
    }

    private function formatSelfStudentRow(Student $student): array
    {
        $student->loadMissing('parentAccount');

        $parent = null;
        if ($student->parentAccount) {
            $parent = [
                'chat_peer' => ['type' => 'parent', 'id' => $student->parentAccount->id],
                'parent_account_id' => $student->parentAccount->id,
                'name' => $student->parentAccount->name,
                'phone' => $student->parentAccount->phone,
                'whatsapp' => $student->parentAccount->whatsapp,
                'has_login_access' => $student->parentAccount->hasLoginAccess(),
            ];
        }

        return [
            'chat_peer' => ['type' => 'student', 'id' => $student->id],
            'id' => $student->id,
            'student_code' => $student->student_code,
            'student_name' => $student->student_name,
            'class' => $student->class,
            'section' => $student->section,
            'campus' => $student->campus,
            'photo' => $student->photo ? asset('storage/' . $student->photo) : null,
            'parent' => $parent,
        ];
    }

    private function applyConversationFilters(Builder $messagesBase, Student $student, string $peerType, int $peerId): void
    {
        $studentId = (int) $student->id;

        if ($peerType === 'teacher') {
            $messagesBase->where(function ($q) use ($studentId, $peerId) {
                $q->where(function ($q2) use ($studentId, $peerId) {
                    $q2->where('from_type', 'student')->where('from_id', $studentId)
                        ->where('to_type', 'teacher')->where('to_id', $peerId);
                })->orWhere(function ($q2) use ($studentId, $peerId) {
                    $q2->where('from_type', 'teacher')->where('from_id', $peerId)
                        ->where('to_type', 'student')->where('to_id', $studentId);
                });
            });

            return;
        }

        if ($peerType === 'parent') {
            $messagesBase->where(function ($q) use ($studentId, $peerId) {
                $q->where(function ($q2) use ($studentId, $peerId) {
                    $q2->where('from_type', 'student')->where('from_id', $studentId)
                        ->where('to_type', 'parent')->where('to_id', $peerId);
                })->orWhere(function ($q2) use ($studentId, $peerId) {
                    $q2->where('from_type', 'parent')->where('from_id', $peerId)
                        ->where('to_type', 'student')->where('to_id', $studentId);
                });
            });

            return;
        }

        $messagesBase->where(function ($q) use ($studentId, $peerId) {
            $q->where(function ($q2) use ($studentId, $peerId) {
                $q2->where('from_type', 'student')->where('from_id', $studentId)
                    ->where('to_type', 'admin')->where('to_id', $peerId);
            })->orWhere(function ($q2) use ($studentId, $peerId) {
                $q2->where('from_type', 'admin')->where('from_id', $peerId)
                    ->where('to_type', 'student')->where('to_id', $studentId);
            });
        });
    }

    private function applyUnreadFilters(Builder $unreadBase, Student $student, string $peerType, int $peerId): void
    {
        if ($peerType === 'teacher') {
            $unreadBase->where('from_type', 'teacher')
                ->where('from_id', $peerId)
                ->where('to_type', 'student')
                ->where('to_id', $student->id)
                ->whereNull('read_at');

            return;
        }

        if ($peerType === 'parent') {
            $unreadBase->where('from_type', 'parent')
                ->where('from_id', $peerId)
                ->where('to_type', 'student')
                ->where('to_id', $student->id)
                ->whereNull('read_at');

            return;
        }

        $unreadBase->where('from_type', 'admin')
            ->where('from_id', $peerId)
            ->where('to_type', 'student')
            ->where('to_id', $student->id)
            ->whereNull('read_at');
    }

    /**
     * @return array{0: string, 1: int}|JsonResponse
     */
    private function resolvePeerFromRequest(Request $request, Student $student): array|JsonResponse
    {
        $this->normalizePeerInput($request);

        $peerType = strtolower(trim((string) $request->input('peer_type', 'admin')));
        if ($peerType === '') {
            $peerType = 'admin';
        }

        if (!in_array($peerType, ['admin', 'teacher', 'parent'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid peer. Use peer_type=admin, peer_type=teacher, or peer_type=parent with peer_id.',
                'token' => null,
            ], 422);
        }

        $peerIdRaw = $request->input('peer_id');
        $peerId = ($peerIdRaw !== null && $peerIdRaw !== '' && ctype_digit((string) $peerIdRaw))
            ? (int) $peerIdRaw
            : null;

        if ($peerType === 'admin' && $peerId) {
            if (!AdminRole::find($peerId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin not found.',
                    'token' => null,
                ], 404);
            }
        }

        if ($peerType === 'admin' && !$peerId) {
            $admin = AdminRole::where('super_admin', true)->first() ?? AdminRole::first();
            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'No admin user found to receive this message.',
                    'token' => null,
                ], 500);
            }
            $peerId = (int) $admin->id;
        }

        if ($peerType === 'teacher') {
            if (!$peerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'peer_id is required when peer_type is teacher.',
                    'token' => null,
                ], 422);
            }
            if (!$this->assertStudentMayChatWithTeacher($student, $peerId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not allowed to chat with this user.',
                    'token' => null,
                ], 403);
            }
        }

        if ($peerType === 'parent') {
            if (!$peerId) {
                $peerId = (int) ($student->parent_account_id ?? 0);
                if ($peerId <= 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No parent account linked to this student.',
                        'token' => null,
                    ], 404);
                }
            }
            if (!$this->assertStudentOwnsParent($student, $peerId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not allowed to chat with this parent.',
                    'token' => null,
                ], 403);
            }
        }

        return [$peerType, $peerId];
    }

    /** @return array{0: string, 1: int} */
    private function resolveMessageTarget(string $peerType, int $peerId): array
    {
        return match ($peerType) {
            'teacher' => ['teacher', $peerId],
            'parent' => ['parent', $peerId],
            default => ['admin', $peerId],
        };
    }

    private function assertStudentMayChatWithTeacher(Student $student, int $staffId): bool
    {
        return $this->teachersForStudent($student)->contains(fn (Staff $teacher) => (int) $teacher->id === $staffId);
    }

    private function assertStudentOwnsParent(Student $student, int $parentId): bool
    {
        return (int) ($student->parent_account_id ?? 0) === $parentId
            && ParentAccount::where('id', $parentId)->exists();
    }

    private function formatMessageRow(
        Message $message,
        ?Student $viewer = null,
        ?string $contactType = null,
        ?int $contactId = null,
    ): array {
        $row = [
            'id' => $message->id,
            'from_type' => $message->from_type,
            'from_id' => (int) $message->from_id,
            'to_type' => $message->to_type,
            'to_id' => (int) $message->to_id,
            'text' => $message->text,
            'attachment_url' => $message->attachment_path
                ? (str_starts_with($message->attachment_path, 'storage/')
                    ? asset($message->attachment_path)
                    : asset('storage/' . $message->attachment_path))
                : null,
            'attachment_type' => $message->attachment_type,
            'read_at' => $message->read_at ? $message->read_at->format('Y-m-d H:i:s') : null,
            'created_at' => $message->created_at?->format('Y-m-d H:i:s'),
        ];

        if ($viewer) {
            $viewerId = (int) $viewer->id;
            $fromType = strtolower(trim((string) $message->from_type));
            $isMine = $fromType === 'student' && (int) $message->from_id === $viewerId;

            $row['viewer_student_id'] = $viewerId;
            $row['viewer_staff_id'] = $viewerId;
            $row['is_mine'] = $isMine;
            $row['isMine'] = $isMine;
            $row['is_sent_by_me'] = $isMine;
            $row['isSentByMe'] = $isMine;
            $row['is_received_by_me'] = !$isMine;
            $row['isReceivedByMe'] = !$isMine;
            $row['direction'] = $isMine ? 'outgoing' : 'incoming';
            $row['display_as'] = $isMine ? 'sent' : 'received';
            $row['align'] = $isMine ? 'right' : 'left';
            $row['bubble_align'] = $isMine ? 'end' : 'start';
            $row['show_on_right'] = $isMine;
            $row['show_on_left'] = !$isMine;
            $row['sender'] = [
                'type' => $message->from_type,
                'id' => (int) $message->from_id,
            ];
            $row['receiver'] = [
                'type' => $message->to_type,
                'id' => (int) $message->to_id,
            ];

            if ($contactType && $contactId) {
                $row['contact_peer'] = [
                    'type' => $contactType,
                    'id' => $contactId,
                ];
                $row['is_from_contact'] = !$isMine;
                $row['isFromContact'] = !$isMine;
                $row['is_from_admin'] = $fromType === 'admin';
                $row['isFromAdmin'] = $fromType === 'admin';
                $row['message_belongs_to'] = $isMine ? 'viewer' : match ($fromType) {
                    'admin' => 'admin',
                    'parent' => 'parent',
                    'teacher', 'staff' => 'staff',
                    default => 'other',
                };

                $row['db_from_id'] = (int) $message->from_id;
                $row['db_to_id'] = (int) $message->to_id;
                if ($isMine) {
                    $row['from_id'] = $viewerId;
                    $row['to_id'] = $contactId;
                }
            }
        }

        return $row;
    }

    private function formatViewerRow(Student $student): array
    {
        return [
            'type' => 'student',
            'id' => (int) $student->id,
            'student_id' => (int) $student->id,
            'staff_id' => (int) $student->id,
            'name' => $student->student_name,
        ];
    }

    private function formatAdminContactRow(AdminRole $admin): array
    {
        return [
            'chat_peer' => ['type' => 'admin', 'id' => $admin->id],
            'id' => $admin->id,
            'name' => $admin->name,
            'phone' => $admin->phone,
            'is_super_admin' => (bool) $admin->super_admin,
        ];
    }

    private function formatParentContactRow(?ParentAccount $parent, Student $student): ?array
    {
        if (!$parent) {
            return null;
        }

        return [
            'chat_peer' => ['type' => 'parent', 'id' => $parent->id],
            'id' => $parent->id,
            'parent_account_id' => (int) $parent->id,
            'name' => $parent->name,
            'phone' => $parent->phone,
            'whatsapp' => $parent->whatsapp,
            'relation' => 'parent',
            'has_login_access' => $parent->hasLoginAccess(),
            'student' => [
                'id' => (int) $student->id,
                'student_name' => $student->student_name,
                'student_code' => $student->student_code,
            ],
        ];
    }

    private function formatTeacherContactRow(Staff $s): array
    {
        $photoUrl = null;
        if ($s->photo) {
            $photoUrl = Storage::url($s->photo);
            if (!filter_var($photoUrl, FILTER_VALIDATE_URL)) {
                $photoUrl = url($photoUrl);
            }
        }

        return [
            'chat_peer' => ['type' => 'teacher', 'id' => $s->id],
            'id' => $s->id,
            'name' => $s->name,
            'phone' => $s->phone,
            'whatsapp' => $s->whatsapp,
            'designation' => $s->designation,
            'campus' => $s->campus,
            'photo' => $photoUrl,
        ];
    }

    private function normalizeTextInput(Request $request): void
    {
        if ($request->filled('text')) {
            return;
        }

        foreach (['message', 'body', 'content'] as $alias) {
            if ($request->filled($alias)) {
                $request->merge(['text' => $request->input($alias)]);
                break;
            }
        }
    }

    private function normalizePeerInput(Request $request): void
    {
        if ($request->filled('peer_type')) {
            $peerType = strtolower(trim((string) $request->input('peer_type')));
            if ($peerType === 'staff') {
                $peerType = 'teacher';
            } elseif ($peerType === 'super_admin') {
                $peerType = 'admin';
            }
            $request->merge(['peer_type' => $peerType]);
        }

        if (!$request->filled('peer_type')) {
            foreach (['recipient_type', 'to_type', 'receiver_type'] as $alias) {
                if ($request->filled($alias)) {
                    $peerType = strtolower(trim((string) $request->input($alias)));
                    if ($peerType === 'staff') {
                        $peerType = 'teacher';
                    } elseif ($peerType === 'super_admin') {
                        $peerType = 'admin';
                    }
                    $request->merge(['peer_type' => $peerType]);
                    break;
                }
            }
        }

        if (!$request->filled('peer_id')) {
            if ($request->filled('parent_id')) {
                $request->merge([
                    'peer_id' => $request->input('parent_id'),
                    'peer_type' => $request->input('peer_type', 'parent'),
                ]);
            } else {
                foreach (['admin_id', 'staff_id', 'teacher_id', 'to_staff_id', 'recipient_id', 'to_id', 'receiver_id'] as $alias) {
                    if ($request->filled($alias)) {
                        $defaultPeerType = $alias === 'admin_id' ? 'admin' : 'teacher';
                        $request->merge([
                            'peer_id' => $request->input($alias),
                            'peer_type' => $request->input('peer_type', $defaultPeerType),
                        ]);
                        break;
                    }
                }
            }
        }
    }

    private function resolveAuthenticatedStudent(Request $request): ?Student
    {
        $user = $request->user();

        return $user instanceof Student ? $user : null;
    }
}
