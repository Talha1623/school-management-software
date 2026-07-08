<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminRole;
use App\Models\Message;
use App\Models\ParentAccount;
use App\Models\Staff;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class TeacherChatController extends Controller
{
    /**
     * Chat directory: super admin, school admins, and students (with parent info).
     * Colleague staff/teachers are not listed here.
     *
     * GET /api/teacher/chat/contacts
     */
    public function contacts(Request $request): JsonResponse
    {
        $teacher = $request->user();

        if (!$teacher instanceof Staff || !$teacher->isTeacher()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Staff login required.',
                'token' => null,
            ], 403);
        }

        $limit = (int) $request->get('limit', 500);
        $limit = max(1, min($limit, 1000));

        $query = Student::query()->with('parentAccount');
        $deny = TeacherStudentController::applyStudentsIndexFilters($request, $teacher, $query);
        if ($deny) {
            return $deny;
        }

        $students = $query->orderBy('student_name')->limit($limit)->get();
        $conversationStats = $this->buildConversationStatsMap($teacher, $students);

        $studentsPayload = $students
            ->map(fn (Student $student) => $this->enrichContactWithConversationStats(
                $this->formatStudentContactRow($student),
                'student',
                (int) $student->id,
                $conversationStats
            ));

        $superAdminsPayload = AdminRole::query()
            ->where('super_admin', true)
            ->orderBy('name')
            ->get()
            ->map(fn (AdminRole $admin) => $this->enrichContactWithConversationStats(
                $this->formatAdminContactRow($admin),
                'admin',
                (int) $admin->id,
                $conversationStats
            ));

        $adminsPayload = AdminRole::query()
            ->where(function (Builder $q) {
                $q->where('super_admin', false)->orWhereNull('super_admin');
            })
            ->orderBy('name')
            ->get()
            ->map(fn (AdminRole $admin) => $this->enrichContactWithConversationStats(
                $this->formatAdminContactRow($admin),
                'admin',
                (int) $admin->id,
                $conversationStats
            ));

        $superAdminPayload = $superAdminsPayload->first()
            ?? $adminsPayload->first();

        $superAdminsPayload = $this->sortContactsByRecentActivity($superAdminsPayload, 'name');
        $adminsPayload = $this->sortContactsByRecentActivity(
            $adminsPayload->reject(fn (array $row) => $superAdminsPayload->contains(fn (array $super) => (int) $super['id'] === (int) $row['id'])),
            'name'
        );
        $studentsPayload = $this->sortContactsByRecentActivity($studentsPayload, 'student_name');

        $recentContacts = collect()
            ->merge($superAdminsPayload->map(fn (array $row) => $this->wrapRecentContact($row, 'super_admin')))
            ->merge($adminsPayload->map(fn (array $row) => $this->wrapRecentContact($row, 'admin')))
            ->merge($studentsPayload->map(fn (array $row) => $this->wrapRecentContact($row, 'student')));

        $recentContacts = $this->sortContactsByRecentActivity($recentContacts, 'name')->values();

        return response()->json([
            'success' => true,
            'message' => 'Chat contacts loaded successfully.',
            'data' => [
                'super_admin' => $superAdminPayload,
                'super_admins' => $superAdminsPayload->values(),
                'admins' => $adminsPayload->values(),
                'students' => $studentsPayload,
                'recent_contacts' => $recentContacts,
                'students_truncated' => $students->count() >= $limit,
                'limit' => $limit,
            ],
        ], 200);
    }

    /**
     * Single chat contact by type and id.
     *
     * GET /api/teacher/chat/contacts/{type}/{id}
     * type: admin | student | parent | teacher
     *
     * Query: messages_limit (optional, default 200, max 500)
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
        $teacher = $request->user();

        if (!$teacher instanceof Staff || !$teacher->isTeacher()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Staff login required.',
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
        if ($type === 'super_admin') {
            $type = 'admin';
        }
        $allowedTypes = ['admin', 'student', 'parent', 'teacher'];
        if (!in_array($type, $allowedTypes, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid type. Allowed: admin, student, parent, teacher.',
                'token' => null,
            ], 422);
        }

        $emptyRequest = new Request();

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
                $peer = Staff::find($numericId);
                if (!$peer) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Contact not found.',
                        'token' => null,
                    ], 404);
                }
                $isSelf = $peer->id === $teacher->id;
                $status = strtolower(trim((string) ($peer->status ?? '')));
                if ($status !== '' && $status !== 'active') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Contact not found.',
                        'token' => null,
                    ], 404);
                }
                if (!$isSelf && $teacher->campus && $peer->campus) {
                    if (strtolower(trim($teacher->campus)) !== strtolower(trim($peer->campus))) {
                        return response()->json([
                            'success' => false,
                            'message' => 'You cannot access this contact.',
                            'token' => null,
                        ], 403);
                    }
                }
                $contact = $this->formatTeacherContactRow($peer);
                break;

            case 'student':
                $student = Student::find($numericId);
                if (!$student) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Contact not found.',
                        'token' => null,
                    ], 404);
                }
                if (!$this->teacherCanAccessStudent($teacher, $student)) {
                    $assignedClasses = $teacher->assignedTeachingClassNames();
                    $message = $assignedClasses->isEmpty()
                        ? 'No class assigned to you. Admin must assign your class in Manage Section.'
                        : 'You cannot access this student. Assigned class(es): ' . $assignedClasses->implode(', ');

                    return response()->json([
                        'success' => false,
                        'message' => $message,
                        'token' => null,
                        'assigned_classes' => $assignedClasses->values(),
                    ], 403);
                }
                $student->loadMissing('parentAccount');
                $contact = $this->formatStudentContactRow($student);
                break;

            case 'parent':
                $parent = ParentAccount::find($numericId);
                if (!$parent) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Contact not found.',
                        'token' => null,
                    ], 404);
                }
                $allowedStudents = Student::query()
                    ->where('parent_account_id', $parent->id)
                    ->get()
                    ->filter(function (Student $s) use ($teacher, $emptyRequest) {
                        $q = Student::query()->where('id', $s->id);
                        $deny = TeacherStudentController::applyStudentsIndexFilters($emptyRequest, $teacher, $q);

                        return $deny === null && $q->exists();
                    })
                    ->values();

                if ($allowedStudents->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You cannot access this parent.',
                        'token' => null,
                    ], 403);
                }

                $contact = [
                    'chat_peer' => ['type' => 'parent', 'id' => $parent->id],
                    'id' => $parent->id,
                    'name' => $parent->name,
                    'email' => $parent->email,
                    'phone' => $parent->phone,
                    'whatsapp' => $parent->whatsapp,
                    'address' => $parent->address,
                    'profession' => $parent->profession,
                    'has_login_access' => method_exists($parent, 'hasLoginAccess') ? $parent->hasLoginAccess() : false,
                    'students' => $allowedStudents->map(function (Student $s) {
                        return [
                            'id' => $s->id,
                            'student_code' => $s->student_code,
                            'student_name' => $s->student_name,
                            'class' => $s->class,
                            'section' => $s->section,
                            'campus' => $s->campus,
                        ];
                    })->values()->all(),
                ];
                break;

            default:
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid type.',
                    'token' => null,
                ], 422);
        }

        $messagesQuery = null;
        $adminInboxHint = null;

        if ($type === 'admin') {
            $messagesQuery = [
                'peer_type' => 'admin',
                'peer_id' => $numericId,
            ];
        } elseif ($type === 'teacher' && $teacher->id === $numericId) {
            $messagesQuery = [
                'peer_type' => null,
                'peer_id' => null,
                'endpoint' => 'GET /api/teacher/chat/messages',
                'note' => 'Call without peer_type/peer_id for combined admin + parent inbox.',
            ];
        } elseif ($type === 'teacher') {
            $messagesQuery = [
                'peer_type' => 'teacher',
                'peer_id' => $numericId,
                'send' => [
                    'method' => 'POST',
                    'endpoint' => '/api/teacher/chat/messages',
                    'peer_type' => 'teacher',
                    'peer_id' => $numericId,
                ],
            ];
        } elseif ($type === 'parent') {
            $messagesQuery = [
                'peer_type' => 'parent',
                'peer_id' => $numericId,
            ];
        } elseif ($type === 'student') {
            $messagesQuery = [
                'peer_type' => 'student',
                'peer_id' => $numericId,
            ];
        }

        if ($type === 'teacher' && $teacher->id === $numericId) {
            $adminInboxHint = [
                'merged_into_contact' => 'Your inbox: school admin and parent messages for you.',
                'raw_messages_api' => 'GET /api/teacher/chat/messages — omit peer_type/peer_id.',
            ];
        }

        $messageBlock = $this->contactShowMessagesBlock($request, $teacher, $type, $numericId);

        return response()->json([
            'success' => true,
            'message' => 'Contact loaded successfully.',
            'data' => [
                'type' => $type,
                'id' => $numericId,
                'viewer' => [
                    'type' => 'teacher',
                    'id' => (int) $teacher->id,
                    'staff_id' => (int) $teacher->id,
                    'name' => $teacher->name,
                ],
                'ui_hints' => [
                    'my_message_align' => 'right',
                    'peer_message_align' => 'left',
                    'check_field' => 'is_mine',
                ],
                'contact' => $contact,
                'messages_query' => $messagesQuery,
                'admin_inbox_hint' => $adminInboxHint,
                'messages' => $messageBlock['messages'],
                'unread_count' => $messageBlock['unread_count'],
                'total_unread_count' => $messageBlock['total_unread_count'],
                'messages_limit' => $messageBlock['messages_limit'],
                'messages_truncated' => $messageBlock['messages_truncated'],
            ],
        ], 200);
    }

    /**
     * GET /api/teacher/chat/messages
     * Query: peer_type (optional): admin | teacher | parent | student
     *        peer_id: required for teacher, parent, student
     *        Omit both: combined school admin + parent inbox for logged-in teacher.
     */
    public function index(Request $request): JsonResponse
    {
        $teacher = $request->user();

        if (!$teacher instanceof Staff || !$teacher->isTeacher()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Staff login required.',
                'token' => null,
            ], 403);
        }

        $teacherId = $teacher->id;
        $peerType = $request->query('peer_type');
        $peerType = is_string($peerType) ? strtolower(trim($peerType)) : null;
        if ($peerType === '') {
            $peerType = null;
        }
        $peerIdRaw = $request->query('peer_id');
        $peerIdForResponse = ($peerIdRaw !== null && $peerIdRaw !== '' && ctype_digit((string) $peerIdRaw))
            ? (int) $peerIdRaw
            : null;

        $messagesBase = Message::query();
        $unreadBase = Message::query();

        if ($peerType === 'teacher') {
            if ($peerIdForResponse === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'peer_id is required when peer_type is teacher.',
                    'token' => null,
                ], 422);
            }
            $peerId = $peerIdForResponse;
            $deny = $this->validateStaffPeer($teacher, $peerId);
            if ($deny) {
                return $deny;
            }
            if ($teacherId === $peerId) {
                $this->applyTeacherOwnInboxMessages($messagesBase, $teacherId);
                $this->applyTeacherOwnInboxUnread($unreadBase, $teacherId);
            } else {
                $this->applyTeacherColleagueThread($messagesBase, $teacherId, $peerId);
                $this->applyTeacherColleagueUnread($unreadBase, $teacherId, $peerId);
            }
        } elseif ($peerType === 'parent') {
            if ($peerIdForResponse === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'peer_id is required when peer_type is parent.',
                    'token' => null,
                ], 422);
            }
            $parentPeerId = $peerIdForResponse;
            $deny = $this->assertTeacherMayChatWithParent($teacher, $parentPeerId);
            if ($deny) {
                return $deny;
            }
            $this->applyTeacherParentThread($messagesBase, $teacherId, $parentPeerId);
            $this->applyTeacherParentUnread($unreadBase, $teacherId, $parentPeerId);
        } elseif ($peerType === 'student') {
            if ($peerIdForResponse === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'peer_id is required when peer_type is student.',
                    'token' => null,
                ], 422);
            }
            $student = Student::find($peerIdForResponse);
            if (!$student || !$this->teacherCanAccessStudent($teacher, $student)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot access this student chat.',
                    'token' => null,
                ], 403);
            }
            $this->applyStudentContactMessages($messagesBase, $teacherId, $peerIdForResponse, $student->parent_account_id);
            $this->applyStudentContactUnread($unreadBase, $teacherId, $peerIdForResponse, $student->parent_account_id);
        } elseif ($peerType === 'admin' && $peerIdForResponse !== null) {
            $adminId = $peerIdForResponse;
            if (!AdminRole::find($adminId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin not found.',
                    'token' => null,
                ], 404);
            }
            $messagesBase->where(function ($q) use ($teacherId, $adminId) {
                $q->where(function ($q2) use ($teacherId, $adminId) {
                    $q2->where('from_type', 'teacher')->where('from_id', $teacherId)
                        ->where('to_type', 'admin')->where('to_id', $adminId);
                })->orWhere(function ($q2) use ($teacherId, $adminId) {
                    $q2->where('from_type', 'admin')->where('from_id', $adminId)
                        ->where('to_type', 'teacher')->where('to_id', $teacherId);
                });
            });
            $this->applyAdminToTeacherUnread($unreadBase, $teacherId, $adminId);
        } elseif ($peerType === null || $peerType === 'admin') {
            $this->applyTeacherOwnInboxMessages($messagesBase, $teacherId);
            $this->applyTeacherOwnInboxUnread($unreadBase, $teacherId);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Invalid peer. Use peer_type=teacher|parent|student|admin with peer_id, or omit for admin+parent inbox.',
                'token' => null,
            ], 422);
        }

        $perPage = (int) $request->get('per_page', 20);
        $perPage = in_array($perPage, [10, 20, 50, 100], true) ? $perPage : 20;

        $unreadCount = (clone $unreadBase)->count();
        $messages = (clone $messagesBase)->orderBy('created_at', 'asc')->paginate($perPage);

        $shouldMarkRead = $peerType === null
            || ($peerType === 'admin' && $peerIdForResponse === null)
            || ($peerType !== null && $peerIdForResponse !== null);

        if ($shouldMarkRead) {
            $markResult = $this->markThreadAsRead($teacher, $peerType, $peerIdForResponse);
            if ($markResult instanceof JsonResponse) {
                return $markResult;
            }
            $unreadCount = 0;
        }

        $resolvedPeerType = $peerType;
        if ($resolvedPeerType === null || ($resolvedPeerType === 'admin' && $peerIdForResponse === null)) {
            $resolvedPeerType = 'admin';
        }

        $responsePeerId = null;
        if (in_array($peerType, ['teacher', 'parent', 'student'], true)
            || ($peerType === 'admin' && $peerIdForResponse !== null)) {
            $responsePeerId = $peerIdForResponse;
        }

        $data = $messages->map(fn (Message $message) => $this->formatMessageRow($message, $teacher));

        return response()->json([
            'success' => true,
            'message' => 'Chat messages loaded successfully.',
            'data' => [
                'peer_type' => $resolvedPeerType,
                'peer_id' => $responsePeerId,
                'viewer' => [
                    'type' => 'teacher',
                    'id' => (int) $teacher->id,
                    'staff_id' => (int) $teacher->id,
                    'name' => $teacher->name,
                ],
                'ui_hints' => [
                    'my_message_align' => 'right',
                    'peer_message_align' => 'left',
                    'check_field' => 'is_mine',
                ],
                'messages' => $data,
                'unread_count' => $unreadCount,
                'total_unread_count' => $this->totalUnreadForTeacher($teacher),
                'pagination' => [
                    'current_page' => $messages->currentPage(),
                    'last_page' => $messages->lastPage(),
                    'per_page' => $messages->perPage(),
                    'total' => $messages->total(),
                    'from' => $messages->firstItem(),
                    'to' => $messages->lastItem(),
                ],
            ],
        ], 200);
    }

    /**
     * POST /api/teacher/chat/messages
     * Body: text, attachment (optional), peer_type (admin|teacher|parent|student), peer_id
     */
    public function store(Request $request): JsonResponse
    {
        $teacher = $request->user();

        if (!$teacher instanceof Staff || !$teacher->isTeacher()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Staff login required.',
                'token' => null,
            ], 403);
        }

        if ($request->filled('peer_type')) {
            $peerTypeInput = strtolower(trim((string) $request->input('peer_type')));
            if ($peerTypeInput === 'staff') {
                $peerTypeInput = 'teacher';
            }
            $request->merge(['peer_type' => $peerTypeInput]);
        }

        if (!$request->filled('peer_id')) {
            foreach (['staff_id', 'teacher_id', 'to_staff_id', 'recipient_id', 'receiver_id'] as $alias) {
                if ($request->filled($alias)) {
                    $request->merge([
                        'peer_id' => $request->input($alias),
                        'peer_type' => $request->input('peer_type', 'teacher'),
                    ]);
                    break;
                }
            }
        }

        $validated = $request->validate([
            'text' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:5120'],
            'peer_type' => ['nullable', 'string', 'in:admin,teacher,staff,parent,student'],
            'peer_id' => ['nullable', 'integer', 'min:1'],
        ]);

        if (empty($validated['text']) && !$request->hasFile('attachment')) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide a message text or an attachment.',
            ], 422);
        }

        $peerType = $validated['peer_type'] ?? null;
        if ($peerType === 'staff') {
            $peerType = 'teacher';
        }
        $peerId = $validated['peer_id'] ?? null;
        $toType = 'admin';
        $toId = null;

        if ($peerType === 'teacher') {
            if (!$peerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'peer_id is required when peer_type is teacher.',
                    'token' => null,
                ], 422);
            }
            $deny = $this->validateStaffPeer($teacher, $peerId);
            if ($deny) {
                return $deny;
            }
            if ($peerId === $teacher->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot send a chat message to yourself.',
                    'token' => null,
                ], 422);
            }
            $toType = 'teacher';
            $toId = $peerId;
        } elseif ($peerType === 'parent') {
            if (!$peerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'peer_id is required when peer_type is parent.',
                    'token' => null,
                ], 422);
            }
            $deny = $this->assertTeacherMayChatWithParent($teacher, $peerId);
            if ($deny) {
                return $deny;
            }
            $toType = 'parent';
            $toId = $peerId;
        } elseif ($peerType === 'student') {
            if (!$peerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'peer_id is required when peer_type is student.',
                    'token' => null,
                ], 422);
            }
            $student = Student::find($peerId);
            if (!$student || !$this->teacherCanAccessStudent($teacher, $student)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot message this student.',
                    'token' => null,
                ], 403);
            }
            $toType = 'student';
            $toId = $peerId;
        } elseif ($peerType === 'admin' && $peerId) {
            if (!AdminRole::find($peerId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin not found.',
                    'token' => null,
                ], 404);
            }
            $toType = 'admin';
            $toId = $peerId;
        } else {
            $admin = AdminRole::where('super_admin', true)->first() ?? AdminRole::first();
            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'No admin user found to receive this message.',
                ], 500);
            }
            $toType = 'admin';
            $toId = $admin->id;
        }

        $attachmentPath = null;
        $attachmentType = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $folder = match ($toType) {
                'student' => 'chat-attachments/teacher-student',
                'parent' => 'chat-attachments/teacher-parent',
                'teacher' => 'chat-attachments/teachers',
                default => 'chat-attachments/teachers',
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
            'from_type' => 'teacher',
            'from_id' => $teacher->id,
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
            'data' => $this->formatMessageRow($message, $teacher),
        ], 201);
    }

    /**
     * GET /api/teacher/chat/unread-count
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $staff = $request->user();
        if (!$staff instanceof Staff) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Staff authentication required.',
                'token' => null,
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Unread chat count loaded successfully.',
            'data' => [
                'unread_count' => $this->totalUnreadForTeacher($staff),
            ],
            'token' => $request->user()?->currentAccessToken()?->token ?? null,
        ], 200);
    }

    /**
     * POST /api/teacher/chat/messages/read
     * Optional: peer_type + peer_id to mark a specific thread as read.
     */
    public function markRead(Request $request): JsonResponse
    {
        $teacher = $request->user();

        if (!$teacher instanceof Staff || !$teacher->isTeacher()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Staff login required.',
                'token' => null,
            ], 403);
        }

        $peerType = $request->input('peer_type', $request->query('peer_type'));
        $peerType = is_string($peerType) ? strtolower(trim($peerType)) : null;
        if ($peerType === '') {
            $peerType = null;
        }
        if ($peerType === 'super_admin') {
            $peerType = 'admin';
        }
        $peerIdRaw = $request->input('peer_id', $request->query('peer_id'));
        $peerId = ($peerIdRaw !== null && $peerIdRaw !== '' && ctype_digit((string) $peerIdRaw))
            ? (int) $peerIdRaw
            : null;

        $result = $this->markThreadAsRead($teacher, $peerType, $peerId);
        if ($result instanceof JsonResponse) {
            return $result;
        }

        return response()->json([
            'success' => true,
            'message' => 'Messages marked as read.',
            'data' => [
                'updated' => $result['updated'],
                'thread_unread_count' => 0,
                'unread_count' => $result['total_unread_count'],
                'total_unread_count' => $result['total_unread_count'],
                'peer_type' => $peerType,
                'peer_id' => $peerId,
            ],
            'token' => $request->user()?->currentAccessToken()?->token ?? null,
        ], 200);
    }

    /**
     * @return array{updated: int, total_unread_count: int}|JsonResponse
     */
    private function markThreadAsRead(Staff $teacher, ?string $peerType, ?int $peerId): array|JsonResponse
    {
        $q = Message::query()->whereNull('read_at');

        if ($peerType === 'teacher' && $peerId !== null) {
            $deny = $this->validateStaffPeer($teacher, $peerId);
            if ($deny) {
                return $deny;
            }
            $q->where('from_type', 'teacher')
                ->where('from_id', $peerId)
                ->where('to_type', 'teacher')
                ->where('to_id', $teacher->id);
        } elseif ($peerType === 'parent' && $peerId !== null) {
            $deny = $this->assertTeacherMayChatWithParent($teacher, $peerId);
            if ($deny) {
                return $deny;
            }
            $q->where('from_type', 'parent')
                ->where('from_id', $peerId)
                ->where('to_type', 'teacher')
                ->where('to_id', $teacher->id);
        } elseif ($peerType === 'student' && $peerId !== null) {
            $student = Student::find($peerId);
            if (!$student || !$this->teacherCanAccessStudent($teacher, $student)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot access this student chat.',
                    'token' => null,
                ], 403);
            }
            $q->where(function ($inner) use ($teacher, $peerId, $student) {
                $inner->where(function ($q2) use ($teacher, $peerId) {
                    $q2->where('from_type', 'student')->where('from_id', $peerId)
                        ->where('to_type', 'teacher')->where('to_id', $teacher->id);
                });
                if ($student->parent_account_id) {
                    $inner->orWhere(function ($q2) use ($teacher, $student) {
                        $q2->where('from_type', 'parent')->where('from_id', $student->parent_account_id)
                            ->where('to_type', 'teacher')->where('to_id', $teacher->id);
                    });
                }
            });
        } elseif ($peerType === 'admin' && $peerId !== null) {
            if (!AdminRole::find($peerId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin not found.',
                    'token' => null,
                ], 404);
            }
            $this->applyAdminToTeacherMarkRead($q, (int) $teacher->id, $peerId);
        } else {
            $q->where(function ($inner) use ($teacher) {
                $inner->where(function ($q2) use ($teacher) {
                    $q2->whereIn('from_type', ['admin', 'super_admin'])
                        ->where('to_type', 'teacher')
                        ->where('to_id', $teacher->id);
                })->orWhere(function ($q2) use ($teacher) {
                    $q2->where('from_type', 'parent')
                        ->where('to_type', 'teacher')
                        ->where('to_id', $teacher->id);
                });
            });
        }

        $updated = (int) $q->update(['read_at' => now()]);

        return [
            'updated' => $updated,
            'total_unread_count' => $this->totalUnreadForTeacher($teacher),
        ];
    }

    private function totalUnreadForTeacher(Staff $teacher): int
    {
        return Message::unreadLiveChatCount('teacher', (int) $teacher->id);
    }

    private function applyAdminToTeacherMarkRead(Builder $q, int $teacherId, int $adminId): void
    {
        $admin = AdminRole::find($adminId);
        $q->where('to_type', 'teacher')->where('to_id', $teacherId);
        $q->where(function ($from) use ($adminId, $admin) {
            $from->where(function ($f) use ($adminId) {
                $f->where('from_type', 'admin')->where('from_id', $adminId);
            });
            if ($admin && $admin->super_admin) {
                $from->orWhere(function ($f) use ($adminId) {
                    $f->where('from_type', 'super_admin')->where('from_id', $adminId);
                });
            }
        });
    }

    private function applyAdminToTeacherUnread(Builder $unreadBase, int $teacherId, int $adminId): void
    {
        $this->applyAdminToTeacherMarkRead($unreadBase, $teacherId, $adminId);
        $unreadBase->whereNull('read_at');
    }

    private function teacherCanAccessStudent(Staff $teacher, Student $student): bool
    {
        if ($teacher->campus) {
            if (strcasecmp(trim((string) ($student->campus ?? '')), trim($teacher->campus)) !== 0) {
                return false;
            }
        }

        $assignedClasses = $teacher->assignedTeachingClassNames();
        if ($assignedClasses->isEmpty()) {
            return false;
        }

        $studentClassKey = Staff::normalizeClassKey((string) ($student->class ?? ''));
        if ($studentClassKey === '') {
            return false;
        }

        return $assignedClasses->contains(
            fn ($assigned) => Staff::normalizeClassKey((string) $assigned) === $studentClassKey
        );
    }

    /**
     * @return JsonResponse|null Error response, or null if allowed
     */
    private function assertTeacherMayChatWithParent(Staff $teacher, int $parentAccountId): ?JsonResponse
    {
        $parent = ParentAccount::find($parentAccountId);
        if (!$parent) {
            return response()->json([
                'success' => false,
                'message' => 'Parent not found.',
                'token' => null,
            ], 404);
        }

        $emptyRequest = new Request();
        $students = Student::query()->where('parent_account_id', $parentAccountId)->get();
        if ($students->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot message this parent.',
                'token' => null,
            ], 403);
        }

        foreach ($students as $s) {
            $q = Student::query()->where('id', $s->id);
            $deny = TeacherStudentController::applyStudentsIndexFilters($emptyRequest, $teacher, $q);
            if ($deny === null && $q->exists()) {
                return null;
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'You cannot message this parent.',
            'token' => null,
        ], 403);
    }

    /**
     * @return JsonResponse|null Error response, or null if this staff member may be messaged
     */
    private function validateStaffPeer(Staff $viewer, int $peerStaffId): ?JsonResponse
    {
        if ($peerStaffId === $viewer->id) {
            return null;
        }

        $peer = Staff::find($peerStaffId);
        if (!$peer) {
            return response()->json([
                'success' => false,
                'message' => 'Staff member not found.',
                'token' => null,
            ], 404);
        }

        $status = strtolower(trim((string) ($peer->status ?? '')));
        if ($status !== '' && $status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Staff member not found.',
                'token' => null,
            ], 404);
        }

        if ($viewer->campus && $peer->campus) {
            if (strtolower(trim($viewer->campus)) !== strtolower(trim($peer->campus))) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot access this staff member.',
                    'token' => null,
                ], 403);
            }
        }

        return null;
    }

    /**
     * @return array{messages: array, unread_count: int, messages_limit: int, messages_truncated: bool}
     */
    private function contactShowMessagesBlock(Request $request, Staff $teacher, string $type, int $numericId): array
    {
        $empty = [
            'messages' => [],
            'unread_count' => 0,
            'messages_limit' => 0,
            'messages_truncated' => false,
        ];

        $limit = (int) $request->query('messages_limit', 200);
        $limit = max(1, min($limit, 500));

        $teacherId = $teacher->id;
        $messagesBase = Message::query();
        $unreadBase = Message::query();

        if ($type === 'admin') {
            if (!AdminRole::find($numericId)) {
                return array_merge($empty, ['messages_limit' => $limit]);
            }
            $messagesBase->where(function ($q) use ($teacherId, $numericId) {
                $q->where(function ($q2) use ($teacherId, $numericId) {
                    $q2->where('from_type', 'teacher')->where('from_id', $teacherId)
                        ->where('to_type', 'admin')->where('to_id', $numericId);
                })->orWhere(function ($q2) use ($teacherId, $numericId) {
                    $q2->where('from_type', 'admin')->where('from_id', $numericId)
                        ->where('to_type', 'teacher')->where('to_id', $teacherId);
                });
            });
            $this->applyAdminToTeacherUnread($unreadBase, $teacherId, $numericId);
        } elseif ($type === 'teacher') {
            $peerId = $numericId;
            if ($teacherId === $peerId) {
                $this->applyTeacherOwnInboxMessages($messagesBase, $teacherId);
                $this->applyTeacherOwnInboxUnread($unreadBase, $teacherId);
            } else {
                if ($this->validateStaffPeer($teacher, $numericId)) {
                    return array_merge($empty, ['messages_limit' => $limit]);
                }
                $this->applyTeacherColleagueThread($messagesBase, $teacherId, $peerId);
                $this->applyTeacherColleagueUnread($unreadBase, $teacherId, $peerId);
            }
        } elseif ($type === 'parent') {
            $this->applyTeacherParentThread($messagesBase, $teacherId, $numericId);
            $this->applyTeacherParentUnread($unreadBase, $teacherId, $numericId);
        } elseif ($type === 'student') {
            $student = Student::find($numericId);
            if (!$student) {
                return array_merge($empty, ['messages_limit' => $limit]);
            }
            $this->applyStudentContactMessages($messagesBase, $teacherId, $numericId, $student->parent_account_id);
            $this->applyStudentContactUnread($unreadBase, $teacherId, $numericId, $student->parent_account_id);
        } else {
            return array_merge($empty, ['messages_limit' => $limit]);
        }

        $totalCount = (clone $messagesBase)->count();
        $messages = (clone $messagesBase)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->sortBy('created_at')
            ->values();

        $markPeerType = $type;
        $markPeerId = $numericId;
        if ($type === 'teacher' && $teacherId === $numericId) {
            $markPeerType = null;
            $markPeerId = null;
        }

        $markResult = $this->markThreadAsRead($teacher, $markPeerType, $markPeerId);
        if ($markResult instanceof JsonResponse) {
            $markResult = ['updated' => 0, 'total_unread_count' => $this->totalUnreadForTeacher($teacher)];
        }

        return [
            'messages' => $messages->map(fn (Message $m) => $this->formatMessageRow($m, $teacher))->values()->all(),
            'unread_count' => 0,
            'total_unread_count' => $markResult['total_unread_count'],
            'messages_limit' => $limit,
            'messages_truncated' => $totalCount > $limit,
        ];
    }

    private function applyTeacherOwnInboxMessages(Builder $messagesBase, int $teacherId): void
    {
        $messagesBase->where(function ($q) use ($teacherId) {
            $q->where(function ($adm) use ($teacherId) {
                $adm->where(function ($q2) use ($teacherId) {
                    $q2->where('from_type', 'teacher')->where('from_id', $teacherId)->where('to_type', 'admin');
                })->orWhere(function ($q2) use ($teacherId) {
                    $q2->where('from_type', 'admin')->where('to_type', 'teacher')->where('to_id', $teacherId);
                });
            })->orWhere(function ($par) use ($teacherId) {
                $par->where(function ($q2) use ($teacherId) {
                    $q2->where('from_type', 'parent')->where('to_type', 'teacher')->where('to_id', $teacherId);
                })->orWhere(function ($q2) use ($teacherId) {
                    $q2->where('from_type', 'teacher')->where('from_id', $teacherId)->where('to_type', 'parent');
                });
            });
        });
    }

    private function applyTeacherOwnInboxUnread(Builder $unreadBase, int $teacherId): void
    {
        $unreadBase->where(function ($q) use ($teacherId) {
            $q->where(function ($q2) use ($teacherId) {
                $q2->whereIn('from_type', ['admin', 'super_admin'])
                    ->where('to_type', 'teacher')
                    ->where('to_id', $teacherId);
            })->orWhere(function ($q2) use ($teacherId) {
                $q2->where('from_type', 'parent')
                    ->where('to_type', 'teacher')
                    ->where('to_id', $teacherId);
            });
        })->whereNull('read_at');
    }

    /** Direct staff-to-staff thread only (viewer ↔ selected colleague). */
    private function applyTeacherColleagueThread(Builder $messagesBase, int $viewerStaffId, int $peerStaffId): void
    {
        $messagesBase->where(function ($q) use ($viewerStaffId, $peerStaffId) {
            $q->where(function ($q2) use ($viewerStaffId, $peerStaffId) {
                $q2->where('from_type', 'teacher')->where('from_id', $viewerStaffId)
                    ->where('to_type', 'teacher')->where('to_id', $peerStaffId);
            })->orWhere(function ($q2) use ($viewerStaffId, $peerStaffId) {
                $q2->where('from_type', 'teacher')->where('from_id', $peerStaffId)
                    ->where('to_type', 'teacher')->where('to_id', $viewerStaffId);
            });
        });
    }

    private function applyTeacherColleagueUnread(Builder $unreadBase, int $viewerStaffId, int $peerStaffId): void
    {
        $unreadBase->where('from_type', 'teacher')
            ->where('from_id', $peerStaffId)
            ->where('to_type', 'teacher')
            ->where('to_id', $viewerStaffId)
            ->whereNull('read_at');
    }

    private function applyTeacherParentThread(Builder $messagesBase, int $teacherId, int $parentAccountId): void
    {
        $messagesBase->where(function ($q) use ($teacherId, $parentAccountId) {
            $q->where(function ($q2) use ($teacherId, $parentAccountId) {
                $q2->where('from_type', 'parent')->where('from_id', $parentAccountId)
                    ->where('to_type', 'teacher')->where('to_id', $teacherId);
            })->orWhere(function ($q2) use ($teacherId, $parentAccountId) {
                $q2->where('from_type', 'teacher')->where('from_id', $teacherId)
                    ->where('to_type', 'parent')->where('to_id', $parentAccountId);
            });
        });
    }

    private function applyTeacherParentUnread(Builder $unreadBase, int $teacherId, int $parentAccountId): void
    {
        $unreadBase->where('from_type', 'parent')
            ->where('from_id', $parentAccountId)
            ->where('to_type', 'teacher')
            ->where('to_id', $teacherId)
            ->whereNull('read_at');
    }

    /**
     * Student contact thread: school admin ↔ student/parent/teacher (this teacher), teacher ↔ student/parent.
     */
    private function applyStudentContactMessages(Builder $messagesBase, int $teacherId, int $studentId, ?int $parentAccountId): void
    {
        $messagesBase->where(function ($q) use ($teacherId, $studentId, $parentAccountId) {
            $q->where(function ($stu) use ($studentId) {
                $stu->where(function ($q2) use ($studentId) {
                    $q2->where('from_type', 'admin')->where('to_type', 'student')->where('to_id', $studentId);
                })->orWhere(function ($q2) use ($studentId) {
                    $q2->where('from_type', 'student')->where('from_id', $studentId)->where('to_type', 'admin');
                });
            })->orWhere(function ($ts) use ($teacherId, $studentId) {
                $ts->where(function ($q2) use ($teacherId, $studentId) {
                    $q2->where('from_type', 'teacher')->where('from_id', $teacherId)
                        ->where('to_type', 'student')->where('to_id', $studentId);
                })->orWhere(function ($q2) use ($teacherId, $studentId) {
                    $q2->where('from_type', 'student')->where('from_id', $studentId)
                        ->where('to_type', 'teacher')->where('to_id', $teacherId);
                });
            })->orWhere(function ($at) use ($teacherId) {
                // Super Admin live chat often stores admin ↔ teacher; show on assigned student threads too.
                $at->where(function ($q2) use ($teacherId) {
                    $q2->where('from_type', 'admin')->where('to_type', 'teacher')->where('to_id', $teacherId);
                })->orWhere(function ($q2) use ($teacherId) {
                    $q2->where('from_type', 'teacher')->where('from_id', $teacherId)->where('to_type', 'admin');
                });
            });

            if ($parentAccountId) {
                $q->orWhere(function ($tp) use ($teacherId, $parentAccountId) {
                    $tp->where(function ($q2) use ($teacherId, $parentAccountId) {
                        $q2->where('from_type', 'teacher')->where('from_id', $teacherId)
                            ->where('to_type', 'parent')->where('to_id', $parentAccountId);
                    })->orWhere(function ($q2) use ($teacherId, $parentAccountId) {
                        $q2->where('from_type', 'parent')->where('from_id', $parentAccountId)
                            ->where('to_type', 'teacher')->where('to_id', $teacherId);
                    });
                })->orWhere(function ($ap) use ($parentAccountId) {
                    $ap->where(function ($q2) use ($parentAccountId) {
                        $q2->where('from_type', 'admin')->where('to_type', 'parent')->where('to_id', $parentAccountId);
                    })->orWhere(function ($q2) use ($parentAccountId) {
                        $q2->where('from_type', 'parent')->where('from_id', $parentAccountId)->where('to_type', 'admin');
                    });
                });
            }
        });
    }

    private function applyStudentContactUnread(Builder $unreadBase, int $teacherId, int $studentId, ?int $parentAccountId): void
    {
        $unreadBase->where(function ($q) use ($teacherId, $studentId, $parentAccountId) {
            $q->where(function ($q2) use ($teacherId, $studentId) {
                $q2->where('from_type', 'student')->where('from_id', $studentId)
                    ->where('to_type', 'teacher')->where('to_id', $teacherId);
            })->orWhere(function ($q2) use ($teacherId) {
                $q2->where('from_type', 'admin')->where('to_type', 'teacher')->where('to_id', $teacherId);
            });
            if ($parentAccountId) {
                $q->orWhere(function ($q2) use ($teacherId, $parentAccountId) {
                    $q2->where('from_type', 'parent')->where('from_id', $parentAccountId)
                        ->where('to_type', 'teacher')->where('to_id', $teacherId);
                });
            }
        })->whereNull('read_at');
    }

    private function formatMessageRow(Message $message, ?Staff $viewer = null): array
    {
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
            $isMine = $message->from_type === 'teacher' && (int) $message->from_id === $viewerId;
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
            $row['sender'] = [
                'type' => $message->from_type,
                'id' => (int) $message->from_id,
            ];
            $row['receiver'] = [
                'type' => $message->to_type,
                'id' => (int) $message->to_id,
            ];
        }

        return $row;
    }

    private function formatAdminContactRow(AdminRole $admin): array
    {
        return [
            'chat_peer' => ['type' => 'admin', 'id' => $admin->id],
            'id' => $admin->id,
            'name' => $admin->name,
            'email' => $admin->email,
            'phone' => $admin->phone,
            'is_super_admin' => (bool) $admin->super_admin,
        ];
    }

    private function formatTeacherContactRow(Staff $staff): array
    {
        $photoUrl = null;
        if ($staff->photo) {
            $photoUrl = Storage::url($staff->photo);
            if (!filter_var($photoUrl, FILTER_VALIDATE_URL)) {
                $photoUrl = url($photoUrl);
            }
        }

        return [
            'chat_peer' => ['type' => 'teacher', 'id' => $staff->id],
            'id' => $staff->id,
            'name' => $staff->name,
            'email' => $staff->email,
            'phone' => $staff->phone,
            'whatsapp' => $staff->whatsapp,
            'designation' => $staff->designation,
            'campus' => $staff->campus,
            'photo' => $photoUrl,
        ];
    }

    private function formatStudentContactRow(Student $student): array
    {
        $parentAccount = $student->parentAccount;
        if ($parentAccount) {
            $parent = [
                'chat_peer' => ['type' => 'parent', 'id' => $parentAccount->id],
                'parent_account_id' => $parentAccount->id,
                'name' => $parentAccount->name,
                'email' => $parentAccount->email,
                'phone' => $parentAccount->phone,
                'whatsapp' => $parentAccount->whatsapp,
                'has_login_access' => method_exists($parentAccount, 'hasLoginAccess') ? $parentAccount->hasLoginAccess() : false,
            ];
        } else {
            $parent = [
                'chat_peer' => null,
                'parent_account_id' => null,
                'name' => $student->father_name ?? null,
                'email' => $student->father_email ?? null,
                'phone' => $student->father_phone ?? null,
                'whatsapp' => $student->whatsapp_number ?? null,
                'mother_phone' => $student->mother_phone ?? null,
                'note' => 'No linked parent account; fields are from admission record.',
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

    /**
     * @return array<string, array{unread_count: int, last_message_at: ?string, last_message_text: ?string, has_unread: bool}>
     */
    private function buildConversationStatsMap(Staff $teacher, Collection $students): array
    {
        $teacherId = (int) $teacher->id;
        $parentToStudentIds = $students
            ->filter(fn (Student $student) => $student->parent_account_id)
            ->groupBy('parent_account_id')
            ->map(fn (Collection $group) => $group->pluck('id')->map(fn ($id) => (int) $id)->all());

        $stats = [];

        $messages = Message::query()
            ->forLiveChat()
            ->where(function (Builder $query) use ($teacherId) {
                $query->where(function (Builder $incoming) use ($teacherId) {
                    $incoming->where('to_type', 'teacher')->where('to_id', $teacherId);
                })->orWhere(function (Builder $outgoing) use ($teacherId) {
                    $outgoing->where('from_type', 'teacher')->where('from_id', $teacherId);
                });
            })
            ->orderByDesc('created_at')
            ->limit(10000)
            ->get(['from_type', 'from_id', 'to_type', 'to_id', 'text', 'read_at', 'created_at']);

        foreach ($messages as $message) {
            $peerKeys = $this->conversationPeerKeysForMessage($message, $teacherId, $parentToStudentIds);

            foreach ($peerKeys as $peerKey) {
                if (! isset($stats[$peerKey])) {
                    $stats[$peerKey] = [
                        'unread_count' => 0,
                        'last_message_at' => null,
                        'last_message_text' => $message->text,
                        'has_unread' => false,
                    ];
                }

                if ($stats[$peerKey]['last_message_at'] === null) {
                    $stats[$peerKey]['last_message_at'] = $message->created_at?->format('Y-m-d H:i:s');
                    $stats[$peerKey]['last_message_text'] = $message->text;
                }

                if ($message->to_type === 'teacher'
                    && (int) $message->to_id === $teacherId
                    && $message->read_at === null
                    && $this->incomingMessageMatchesPeerKey($message, $teacherId, $peerKey, $parentToStudentIds)) {
                    $stats[$peerKey]['unread_count']++;
                    $stats[$peerKey]['has_unread'] = true;
                }
            }
        }

        return $stats;
    }

    /**
     * @param  array<string, array{unread_count: int, last_message_at: ?string, last_message_text: ?string, has_unread: bool}>  $stats
     * @return array<string, mixed>
     */
    private function enrichContactWithConversationStats(array $contact, string $peerType, int $peerId, array $stats): array
    {
        $peerKey = "{$peerType}:{$peerId}";
        $meta = $stats[$peerKey] ?? [
            'unread_count' => 0,
            'last_message_at' => null,
            'last_message_text' => null,
            'has_unread' => false,
        ];

        return array_merge($contact, $meta);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function sortContactsByRecentActivity(Collection $contacts, string $nameField): Collection
    {
        return $contacts->sort(function (array $a, array $b) use ($nameField) {
            $unreadA = (int) ($a['unread_count'] ?? 0);
            $unreadB = (int) ($b['unread_count'] ?? 0);
            if ($unreadA !== $unreadB) {
                return $unreadB <=> $unreadA;
            }

            $timeA = (string) ($a['last_message_at'] ?? '');
            $timeB = (string) ($b['last_message_at'] ?? '');
            if ($timeA !== $timeB) {
                return strcmp($timeB, $timeA);
            }

            $nameA = (string) ($a[$nameField] ?? $a['name'] ?? $a['student_name'] ?? '');
            $nameB = (string) ($b[$nameField] ?? $b['name'] ?? $b['student_name'] ?? '');

            return strcasecmp($nameA, $nameB);
        })->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function wrapRecentContact(array $contact, string $contactType): array
    {
        return [
            'contact_type' => $contactType,
            'chat_peer' => $contact['chat_peer'] ?? null,
            'name' => $contact['name'] ?? $contact['student_name'] ?? null,
            'unread_count' => (int) ($contact['unread_count'] ?? 0),
            'has_unread' => (bool) ($contact['has_unread'] ?? false),
            'last_message_at' => $contact['last_message_at'] ?? null,
            'last_message_text' => $contact['last_message_text'] ?? null,
            'contact' => $contact,
        ];
    }

    /**
     * @param  Collection<int|string, list<int>>  $parentToStudentIds
     * @return list<string>
     */
    private function conversationPeerKeysForMessage(Message $message, int $teacherId, Collection $parentToStudentIds): array
    {
        $keys = [];

        if ($message->from_type === 'teacher' && (int) $message->from_id === $teacherId) {
            $peerType = $this->normalizePeerType((string) $message->to_type);
            if ($peerType !== null) {
                $keys[] = "{$peerType}:{$message->to_id}";
            }

            return array_values(array_unique($keys));
        }

        if ($message->to_type !== 'teacher' || (int) $message->to_id !== $teacherId) {
            return [];
        }

        if ($message->from_type === 'parent') {
            $studentIds = $parentToStudentIds->get((int) $message->from_id, []);
            foreach ($studentIds as $studentId) {
                $keys[] = "student:{$studentId}";
            }
            $keys[] = 'parent:' . (int) $message->from_id;

            return array_values(array_unique($keys));
        }

        $peerType = $this->normalizePeerType((string) $message->from_type);
        if ($peerType !== null) {
            $keys[] = "{$peerType}:{$message->from_id}";
        }

        return array_values(array_unique($keys));
    }

    /**
     * @param  Collection<int|string, list<int>>  $parentToStudentIds
     */
    private function incomingMessageMatchesPeerKey(
        Message $message,
        int $teacherId,
        string $peerKey,
        Collection $parentToStudentIds
    ): bool {
        return in_array($peerKey, $this->conversationPeerKeysForMessage($message, $teacherId, $parentToStudentIds), true);
    }

    private function normalizePeerType(string $type): ?string
    {
        return match ($type) {
            'admin', 'super_admin' => 'admin',
            'teacher', 'parent', 'student' => $type,
            default => null,
        };
    }
}


