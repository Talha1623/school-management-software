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

class ParentChatController extends Controller
{
    /**
     * Chat directory: school admin, teachers (children's class), and your students.
     *
     * GET /api/parent/chat/contacts
     */
    public function contacts(Request $request): JsonResponse
    {
        $parent = $this->resolveAuthenticatedParent($request);
        if (!$parent) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Only parents can access this resource.',
                'token' => null,
            ], 403);
        }

        $limit = (int) $request->get('limit', 500);
        $limit = max(1, min($limit, 1000));

        $students = Student::query()
            ->with('parentAccount')
            ->where('parent_account_id', $parent->id)
            ->orderBy('student_name')
            ->limit($limit)
            ->get();

        $studentsPayload = $students->map(
            fn (Student $student) => $this->formatStudentContactRow($student, $parent)
        );

        $superAdmin = AdminRole::where('super_admin', true)->first() ?? AdminRole::orderBy('id')->first();
        $superAdminPayload = $superAdmin ? $this->formatAdminContactRow($superAdmin) : null;

        $teachersById = [];
        foreach ($students as $student) {
            foreach ($this->teachersForStudent($student) as $teacher) {
                if ($this->assertParentMayChatWithTeacher($parent, $teacher->id)) {
                    $teachersById[$teacher->id] = $teacher;
                }
            }
        }

        $teachersPayload = collect($teachersById)
            ->sortBy('name')
            ->values()
            ->map(fn (Staff $s) => $this->formatTeacherContactRow($s));

        return response()->json([
            'success' => true,
            'message' => 'Chat contacts loaded successfully.',
            'data' => [
                'super_admin' => $superAdminPayload,
                'teachers' => $teachersPayload,
                'students' => $studentsPayload,
                'students_truncated' => $students->count() >= $limit,
                'limit' => $limit,
            ],
        ], 200);
    }

    /**
     * GET /api/parent/chat/contacts/{type}/{id}
     * type: admin | teacher | student
     */
    public function contactShow(Request $request, string $type, string $id): JsonResponse
    {
        $parent = $this->resolveAuthenticatedParent($request);
        if (!$parent) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Only parents can access this resource.',
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
        if (!in_array($type, ['admin', 'teacher', 'student'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid type. Allowed: admin, teacher, student.',
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

            case 'teacher':
                $teacher = Staff::find($numericId);
                if (!$teacher || !$this->assertParentMayChatWithTeacher($parent, $teacher->id)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Contact not found.',
                        'token' => null,
                    ], 404);
                }
                $contact = $this->formatTeacherContactRow($teacher);
                break;

            case 'student':
                $student = Student::with('parentAccount')->find($numericId);
                if (!$student || (int) $student->parent_account_id !== (int) $parent->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Contact not found.',
                        'token' => null,
                    ], 404);
                }
                $contact = $this->formatStudentContactRow($student, $parent);
                break;
        }

        $messagesQuery = null;
        $adminInboxHint = null;

        if ($type === 'admin') {
            $messagesQuery = [
                'peer_type' => 'admin',
                'peer_id' => $numericId,
            ];
        } elseif ($type === 'teacher') {
            $messagesQuery = [
                'peer_type' => 'teacher',
                'peer_id' => $numericId,
                'send' => [
                    'method' => 'POST',
                    'endpoint' => '/api/parent/chat/messages',
                    'peer_type' => 'teacher',
                    'peer_id' => $numericId,
                ],
            ];
        } elseif ($type === 'student') {
            $messagesQuery = [
                'peer_type' => 'student',
                'peer_id' => $numericId,
                'send' => [
                    'method' => 'POST',
                    'endpoint' => '/api/parent/chat/messages',
                    'peer_type' => 'student',
                    'peer_id' => $numericId,
                    'student_id' => $numericId,
                ],
            ];
        }

        $messageBlock = $this->contactShowMessagesBlock($request, $parent, $type, $numericId);

        return response()->json([
            'success' => true,
            'message' => 'Contact loaded successfully.',
            'data' => [
                'type' => $type,
                'id' => $numericId,
                'viewer' => $this->formatViewerRow($parent),
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
        ], 200);
    }

    /**
     * GET /api/parent/chat/messages
     * Query: peer_type (admin|teacher|student), peer_id
     * Same shape as GET /api/staff/chat/messages
     */
    public function index(Request $request): JsonResponse
    {
        $parent = $this->resolveAuthenticatedParent($request);
        if (!$parent) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Parent authentication required.',
                'token' => null,
            ], 403);
        }

        $resolved = $this->resolvePeerFromRequest($request, $parent);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        [$peerType, $peerId] = $resolved;
        $messages = $this->loadConversationMessages($parent, $peerType, $peerId)
            ->map(fn (Message $message) => $this->formatMessageRow(
                $message,
                $parent,
                $peerType,
                $peerId
            ))
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'message' => 'Chat messages loaded successfully.',
            'data' => [
                'peer_type' => $peerType,
                'peer_id' => $peerId,
                'viewer' => $this->formatViewerRow($parent),
                'ui_hints' => [
                    'my_message_align' => 'right',
                    'peer_message_align' => 'left',
                    'check_field' => 'is_mine',
                ],
                'messages' => $messages,
            ],
            'token' => $request->user()?->currentAccessToken()?->token ?? null,
        ], 200);
    }

    /**
     * POST /api/parent/chat/messages
     * Same shape as POST /api/staff/chat/messages
     */
    public function store(Request $request): JsonResponse
    {
        $parent = $this->resolveAuthenticatedParent($request);
        if (!$parent) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Parent authentication required.',
                'token' => null,
            ], 403);
        }

        $this->normalizeTextInput($request);

        $validated = $request->validate([
            'text' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,txt', 'max:5120'],
            'peer_type' => ['nullable', 'string', 'in:admin,teacher,student'],
            'peer_id' => ['nullable', 'integer', 'min:1'],
        ]);

        if (empty($validated['text']) && !$request->hasFile('attachment')) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide message text or an attachment.',
                'token' => null,
            ], 422);
        }

        $resolved = $this->resolvePeerFromRequest($request, $parent);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        [$peerType, $peerId] = $resolved;
        $target = $this->resolveMessageTarget($peerType, $peerId);
        if ($target instanceof JsonResponse) {
            return $target;
        }
        [$toType, $toId] = $target;

        $attachmentPath = null;
        $attachmentType = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $folder = match ($toType) {
                'teacher' => 'chat-attachments/parent-teacher',
                'student' => 'chat-attachments/parent-student',
                default => 'chat-attachments/parents',
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
            'from_type' => 'parent',
            'from_id' => $parent->id,
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
            'data' => $this->formatMessageRow($message, $parent, $peerType, $peerId),
            'token' => $request->user()?->currentAccessToken()?->token ?? null,
        ], 201);
    }

    /**
     * POST /api/parent/chat/messages/read
     * Same shape as POST /api/staff/chat/messages/read
     */
    public function markRead(Request $request): JsonResponse
    {
        $parent = $this->resolveAuthenticatedParent($request);
        if (!$parent) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Parent authentication required.',
                'token' => null,
            ], 403);
        }

        $resolved = $this->resolvePeerFromRequest($request, $parent);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        [$peerType, $peerId] = $resolved;

        $q = Message::query()->whereNull('read_at');

        if ($peerType === 'teacher') {
            $q->where('from_type', 'teacher')
                ->where('from_id', $peerId)
                ->where('to_type', 'parent')
                ->where('to_id', $parent->id);
        } elseif ($peerType === 'student') {
            $teacherIds = $this->teacherIdsForStudentPeer($parent, $peerId);
            $q->where(function ($outer) use ($parent, $peerId, $teacherIds) {
                $outer->where(function ($q2) use ($parent) {
                    $q2->where('from_type', 'admin')
                        ->where('to_type', 'parent')
                        ->where('to_id', $parent->id);
                })->orWhere(function ($q2) use ($parent, $peerId) {
                    $q2->where('from_type', 'student')
                        ->where('from_id', $peerId)
                        ->where('to_type', 'parent')
                        ->where('to_id', $parent->id);
                });
                if ($teacherIds->isNotEmpty()) {
                    $outer->orWhere(function ($q2) use ($parent, $teacherIds) {
                        $q2->where('from_type', 'teacher')
                            ->whereIn('from_id', $teacherIds->all())
                            ->where('to_type', 'parent')
                            ->where('to_id', $parent->id);
                    });
                }
            });
        } else {
            $q->where('from_type', 'admin')
                ->where('from_id', $peerId)
                ->where('to_type', 'parent')
                ->where('to_id', $parent->id);
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
    private function contactShowMessagesBlock(Request $request, ParentAccount $parent, string $type, int $numericId): array
    {
        $empty = [
            'messages' => [],
            'unread_count' => 0,
            'messages_limit' => 0,
            'messages_truncated' => false,
        ];

        $limit = (int) $request->query('messages_limit', 200);
        $limit = max(1, min($limit, 500));
        $parentId = $parent->id;

        $messagesBase = Message::query();
        $unreadBase = Message::query();

        if ($type === 'admin') {
            if (!AdminRole::find($numericId)) {
                return array_merge($empty, ['messages_limit' => $limit]);
            }
            $messagesBase->where(function ($q) use ($parentId, $numericId) {
                $q->where(function ($q2) use ($parentId, $numericId) {
                    $q2->where('from_type', 'parent')->where('from_id', $parentId)
                        ->where('to_type', 'admin')->where('to_id', $numericId);
                })->orWhere(function ($q2) use ($parentId, $numericId) {
                    $q2->where('from_type', 'admin')->where('from_id', $numericId)
                        ->where('to_type', 'parent')->where('to_id', $parentId);
                });
            });
            $unreadBase->where('from_type', 'admin')
                ->where('from_id', $numericId)
                ->where('to_type', 'parent')
                ->where('to_id', $parentId)
                ->whereNull('read_at');
        } elseif ($type === 'teacher') {
            if (!$this->assertParentMayChatWithTeacher($parent, $numericId)) {
                return array_merge($empty, ['messages_limit' => $limit]);
            }
            $tid = $numericId;
            $messagesBase->where(function ($q) use ($parentId, $tid) {
                $q->where(function ($q2) use ($parentId, $tid) {
                    $q2->where('from_type', 'parent')->where('from_id', $parentId)
                        ->where('to_type', 'teacher')->where('to_id', $tid);
                })->orWhere(function ($q2) use ($parentId, $tid) {
                    $q2->where('from_type', 'teacher')->where('from_id', $tid)
                        ->where('to_type', 'parent')->where('to_id', $parentId);
                });
            });
            $unreadBase->where('from_type', 'teacher')
                ->where('from_id', $tid)
                ->where('to_type', 'parent')
                ->where('to_id', $parentId)
                ->whereNull('read_at');
        } elseif ($type === 'student') {
            if (!$this->assertParentOwnsStudent($parent, $numericId)) {
                return array_merge($empty, ['messages_limit' => $limit]);
            }
            $this->applyParentStudentContactMessages($messagesBase, $parentId, $numericId);
            $teacherIds = $this->teacherIdsForStudentPeer($parent, $numericId);
            $unreadBase->where(function ($outer) use ($parentId, $numericId, $teacherIds) {
                $outer->where(function ($q2) use ($parentId) {
                    $q2->where('from_type', 'admin')
                        ->where('to_type', 'parent')
                        ->where('to_id', $parentId);
                })->orWhere(function ($q2) use ($parentId, $numericId) {
                    $q2->where('from_type', 'student')
                        ->where('from_id', $numericId)
                        ->where('to_type', 'parent')
                        ->where('to_id', $parentId);
                });
                if ($teacherIds->isNotEmpty()) {
                    $outer->orWhere(function ($q2) use ($parentId, $teacherIds) {
                        $q2->where('from_type', 'teacher')
                            ->whereIn('from_id', $teacherIds->all())
                            ->where('to_type', 'parent')
                            ->where('to_id', $parentId);
                    });
                }
            })->whereNull('read_at');
        } else {
            return array_merge($empty, ['messages_limit' => $limit]);
        }

        $totalCount = (clone $messagesBase)->count();
        $unreadCount = (clone $unreadBase)->count();
        $messages = (clone $messagesBase)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->sortBy('created_at')
            ->values();

        return [
            'messages' => $messages->map(fn (Message $m) => $this->formatMessageRow(
                $m,
                $parent,
                $type,
                $numericId
            ))->values()->all(),
            'unread_count' => $unreadCount,
            'messages_limit' => $limit,
            'messages_truncated' => $totalCount > $limit,
        ];
    }

    private function formatMessageRow(
        Message $message,
        ?ParentAccount $viewer = null,
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
            $isMine = $fromType === 'parent' && (int) $message->from_id === $viewerId;

            $row['viewer_parent_id'] = $viewerId;
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

    /**
     * Same keys as teacher chat viewer (staff_id kept for mobile app compatibility).
     */
    private function formatViewerRow(ParentAccount $parent): array
    {
        return [
            'type' => 'parent',
            'id' => (int) $parent->id,
            'staff_id' => (int) $parent->id,
            'parent_id' => (int) $parent->id,
            'name' => $parent->name,
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

    private function formatStudentContactRow(Student $student, ParentAccount $parent): array
    {
        return [
            'chat_peer' => ['type' => 'student', 'id' => $student->id],
            'id' => $student->id,
            'student_code' => $student->student_code,
            'student_name' => $student->student_name,
            'class' => $student->class,
            'section' => $student->section,
            'campus' => $student->campus,
            'photo' => $student->photo ? asset('storage/' . $student->photo) : null,
            'parent' => [
                'chat_peer' => ['type' => 'parent', 'id' => $parent->id],
                'parent_account_id' => $parent->id,
                'name' => $parent->name,
                'phone' => $parent->phone,
                'whatsapp' => $parent->whatsapp,
                'has_login_access' => $parent->hasLoginAccess(),
                'is_you' => true,
            ],
        ];
    }

    /**
     * @return Collection<int, Message>
     */
    private function loadConversationMessages(ParentAccount $parent, string $peerType, int $peerId): Collection
    {
        $parentId = $parent->id;
        $messagesBase = Message::query();

        if ($peerType === 'teacher') {
            $messagesBase->where(function ($q) use ($parentId, $peerId) {
                $q->where(function ($q2) use ($parentId, $peerId) {
                    $q2->where('from_type', 'parent')->where('from_id', $parentId)
                        ->where('to_type', 'teacher')->where('to_id', $peerId);
                })->orWhere(function ($q2) use ($parentId, $peerId) {
                    $q2->where('from_type', 'teacher')->where('from_id', $peerId)
                        ->where('to_type', 'parent')->where('to_id', $parentId);
                });
            });
        } elseif ($peerType === 'student') {
            $this->applyParentStudentContactMessages($messagesBase, $parentId, $peerId);
        } else {
            $messagesBase->where(function ($q) use ($parentId, $peerId) {
                $q->where(function ($q2) use ($parentId, $peerId) {
                    $q2->where('from_type', 'parent')->where('from_id', $parentId)
                        ->where('to_type', 'admin')->where('to_id', $peerId);
                })->orWhere(function ($q2) use ($parentId, $peerId) {
                    $q2->where('from_type', 'admin')->where('from_id', $peerId)
                        ->where('to_type', 'parent')->where('to_id', $parentId);
                });
            });
        }

        return $messagesBase->orderBy('created_at', 'asc')->get();
    }

    /**
     * @return array{0: string, 1: int}|JsonResponse
     */
    private function resolvePeerFromRequest(Request $request, ParentAccount $parent): array|JsonResponse
    {
        $this->normalizePeerInput($request);

        $peerType = strtolower(trim((string) $request->input('peer_type', 'admin')));
        if ($peerType === '') {
            $peerType = 'admin';
        }

        if (!in_array($peerType, ['admin', 'teacher', 'student'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid peer. Use peer_type=admin, peer_type=teacher, or peer_type=student with peer_id.',
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
            if (!$this->assertParentMayChatWithTeacher($parent, $peerId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not allowed to chat with this user.',
                    'token' => null,
                ], 403);
            }
        }

        if ($peerType === 'student') {
            if (!$peerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'peer_id is required when peer_type is student.',
                    'token' => null,
                ], 422);
            }
            if (!$this->assertParentOwnsStudent($parent, $peerId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not allowed to chat with this student.',
                    'token' => null,
                ], 403);
            }
        }

        return [$peerType, $peerId];
    }

    /**
     * Student contact thread for parent: admin/parent/teacher messages for this child.
     */
    private function applyParentStudentContactMessages(Builder $messagesBase, int $parentId, int $studentId): void
    {
        $student = Student::find($studentId);
        if (!$student) {
            $messagesBase->whereRaw('1 = 0');

            return;
        }

        $teacherIds = $this->teachersForStudent($student)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $messagesBase->where(function ($q) use ($parentId, $studentId, $teacherIds) {
            $q->where(function ($stu) use ($studentId) {
                $stu->where(function ($q2) use ($studentId) {
                    $q2->where('from_type', 'admin')->where('to_type', 'student')->where('to_id', $studentId);
                })->orWhere(function ($q2) use ($studentId) {
                    $q2->where('from_type', 'student')->where('from_id', $studentId)->where('to_type', 'admin');
                });
            })->orWhere(function ($ap) use ($parentId) {
                $ap->where(function ($q2) use ($parentId) {
                    $q2->where('from_type', 'parent')->where('from_id', $parentId)->where('to_type', 'admin');
                })->orWhere(function ($q2) use ($parentId) {
                    $q2->where('from_type', 'admin')->where('to_type', 'parent')->where('to_id', $parentId);
                });
            });

            foreach ($teacherIds as $teacherId) {
                $q->orWhere(function ($ts) use ($teacherId, $studentId) {
                    $ts->where(function ($q2) use ($teacherId, $studentId) {
                        $q2->where('from_type', 'teacher')->where('from_id', $teacherId)
                            ->where('to_type', 'student')->where('to_id', $studentId);
                    })->orWhere(function ($q2) use ($teacherId, $studentId) {
                        $q2->where('from_type', 'student')->where('from_id', $studentId)
                            ->where('to_type', 'teacher')->where('to_id', $teacherId);
                    });
                })->orWhere(function ($tp) use ($parentId, $teacherId) {
                    $tp->where(function ($q2) use ($parentId, $teacherId) {
                        $q2->where('from_type', 'parent')->where('from_id', $parentId)
                            ->where('to_type', 'teacher')->where('to_id', $teacherId);
                    })->orWhere(function ($q2) use ($parentId, $teacherId) {
                        $q2->where('from_type', 'teacher')->where('from_id', $teacherId)
                            ->where('to_type', 'parent')->where('to_id', $parentId);
                    });
                });
            }

            $q->orWhere(function ($sp) use ($parentId, $studentId) {
                $sp->where(function ($q2) use ($parentId, $studentId) {
                    $q2->where('from_type', 'student')->where('from_id', $studentId)
                        ->where('to_type', 'parent')->where('to_id', $parentId);
                })->orWhere(function ($q2) use ($parentId, $studentId) {
                    $q2->where('from_type', 'parent')->where('from_id', $parentId)
                        ->where('to_type', 'student')->where('to_id', $studentId);
                });
            });
        });
    }

    /** @return Collection<int, int> */
    private function teacherIdsForStudentPeer(ParentAccount $parent, int $studentId): Collection
    {
        if (!$this->assertParentOwnsStudent($parent, $studentId)) {
            return collect();
        }

        $student = Student::find($studentId);
        if (!$student) {
            return collect();
        }

        return $this->teachersForStudent($student)
            ->filter(fn (Staff $teacher) => $this->assertParentMayChatWithTeacher($parent, $teacher->id))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();
    }

    /** @return array{0: string, 1: int}|JsonResponse */
    private function resolveMessageTarget(string $peerType, int $peerId): array|JsonResponse
    {
        if ($peerType === 'teacher') {
            return ['teacher', $peerId];
        }

        if ($peerType === 'student') {
            return ['student', $peerId];
        }

        return ['admin', $peerId];
    }

    private function assertParentOwnsStudent(ParentAccount $parent, int $studentId): bool
    {
        return Student::query()
            ->where('id', $studentId)
            ->where('parent_account_id', $parent->id)
            ->exists();
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
            if ($request->filled('student_id')) {
                $request->merge([
                    'peer_id' => $request->input('student_id'),
                    'peer_type' => $request->input('peer_type', 'student'),
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

    private function assertParentMayChatWithTeacher(ParentAccount $parent, int $staffId): bool
    {
        $teacher = Staff::find($staffId);
        if (!$teacher || !str_contains(strtolower((string) ($teacher->designation ?? '')), 'teacher')) {
            return false;
        }

        $emptyRequest = new Request();
        $students = Student::query()->where('parent_account_id', $parent->id)->get();
        foreach ($students as $s) {
            $q = Student::query()->where('id', $s->id);
            $deny = TeacherStudentController::applyStudentsIndexFilters($emptyRequest, $teacher, $q);
            if ($deny === null && $q->exists()) {
                return true;
            }
        }

        return false;
    }

    private function resolveAuthenticatedParent(Request $request): ?ParentAccount
    {
        $user = $request->user();

        return $user instanceof ParentAccount ? $user : null;
    }
}
