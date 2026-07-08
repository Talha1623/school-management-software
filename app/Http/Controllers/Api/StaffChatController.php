<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminRole;
use App\Models\Message;
use App\Models\Staff;
use App\Services\ChatService;
use App\Support\ChatActor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffChatController extends Controller
{
    public function __construct(
        private readonly ChatService $chatService,
    ) {
    }

    /**
     * GET /api/staff/chat/unread-count
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
                'unread_count' => Message::unreadLiveChatCount('teacher', (int) $staff->id),
            ],
            'token' => $request->user()?->currentAccessToken()?->token ?? null,
        ], 200);
    }

    /**
     * GET /api/staff/chat/messages
     * Query: peer_type (admin|teacher|staff|parent|student), peer_id
     */
    public function index(Request $request): JsonResponse
    {
        $staff = $request->user();
        if (!$staff instanceof Staff) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Staff authentication required.',
                'token' => null,
            ], 403);
        }

        $resolved = $this->resolvePeerFromRequest($request);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        [$peerType, $peerId] = $resolved;
        $actor = ChatActor::fromUser('staff', $staff);

        if (!$this->chatService->canActorsChat($actor, $peerType, $peerId)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to chat with this user.',
                'token' => null,
            ], 403);
        }

        $messages = $this->chatService->conversation($actor, $peerType, $peerId)->values()->all();
        $this->chatService->markConversationRead($actor, $peerType, $peerId);
        $totalUnread = Message::unreadLiveChatCount('teacher', (int) $staff->id);

        return response()->json([
            'success' => true,
            'message' => 'Chat messages loaded successfully.',
            'data' => [
                'peer_type' => $peerType,
                'peer_id' => $peerId,
                'viewer' => [
                    'type' => 'teacher',
                    'id' => (int) $staff->id,
                    'staff_id' => (int) $staff->id,
                    'name' => $staff->name,
                ],
                'ui_hints' => [
                    'my_message_align' => 'right',
                    'peer_message_align' => 'left',
                    'check_field' => 'is_mine',
                ],
                'messages' => $messages,
                'unread_count' => 0,
                'total_unread_count' => $totalUnread,
            ],
            'token' => $request->user()?->currentAccessToken()?->token ?? null,
        ], 200);
    }

    /**
     * POST /api/staff/chat/messages/read
     */
    public function markRead(Request $request): JsonResponse
    {
        $staff = $request->user();
        if (!$staff instanceof Staff) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Staff authentication required.',
                'token' => null,
            ], 403);
        }

        $resolved = $this->resolvePeerFromRequest($request);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        [$peerType, $peerId] = $resolved;
        $actor = ChatActor::fromUser('staff', $staff);

        if (!$this->chatService->canActorsChat($actor, $peerType, $peerId)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to access this chat.',
                'token' => null,
            ], 403);
        }

        $this->chatService->markConversationRead($actor, $peerType, $peerId);
        $totalUnread = Message::unreadLiveChatCount('teacher', (int) $staff->id);

        return response()->json([
            'success' => true,
            'message' => 'Messages marked as read.',
            'data' => [
                'peer_type' => $peerType,
                'peer_id' => $peerId,
                'thread_unread_count' => 0,
                'unread_count' => $totalUnread,
                'total_unread_count' => $totalUnread,
            ],
            'token' => $request->user()?->currentAccessToken()?->token ?? null,
        ], 200);
    }

    /**
     * Send staff chat message with optional image/PDF/document attachment.
     *
     * POST /api/staff/chat/messages
     * multipart/form-data:
     * - text: optional
     * - attachment: optional file (jpg,jpeg,png,webp,gif,pdf,doc,docx,txt)
     * - peer_type: optional admin|teacher|staff|parent|student (default: admin)
     * - peer_id: optional target id (default: first super admin/admin)
     * - staff_id/teacher_id/to_staff_id: accepted aliases for staff-to-staff chat
     */
    public function store(Request $request): JsonResponse
    {
        $staff = $request->user();
        if (!$staff instanceof Staff) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Staff authentication required.',
                'token' => null,
            ], 403);
        }

        $this->normalizeTextInput($request);

        $validated = $request->validate([
            'text' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,txt', 'max:5120'],
            'peer_type' => ['nullable', 'string', 'in:admin,teacher,parent,student'],
            'peer_id' => ['nullable', 'integer', 'min:1'],
        ]);

        if (empty($validated['text']) && !$request->hasFile('attachment')) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide message text or an attachment.',
                'token' => null,
            ], 422);
        }

        $resolved = $this->resolvePeerFromRequest($request);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        [$peerType, $peerId] = $resolved;

        if ($peerType === 'teacher' && $peerId === (int) $staff->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot send a chat message to yourself.',
                'token' => null,
            ], 422);
        }

        $actor = ChatActor::fromUser('staff', $staff);
        $message = $this->chatService->send(
            $actor,
            $peerType,
            $peerId,
            $validated['text'] ?? null,
            $request->file('attachment'),
        );

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully.',
            'data' => $this->chatService->formatMessageForActor($message, $actor),
            'token' => $request->user()?->currentAccessToken()?->token ?? null,
        ], 201);
    }

    /**
     * @return array{0: string, 1: int}|JsonResponse
     */
    private function resolvePeerFromRequest(Request $request): array|JsonResponse
    {
        $this->normalizePeerInput($request);

        $peerType = strtolower(trim((string) $request->input('peer_type', 'admin')));
        if ($peerType === '') {
            $peerType = 'admin';
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
            $admin = AdminRole::where('super_admin', true)->first() ?: AdminRole::first();
            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'No admin user found to receive this message.',
                    'token' => null,
                ], 500);
            }
            $peerId = (int) $admin->id;
        }

        if (!$peerId) {
            return response()->json([
                'success' => false,
                'message' => 'peer_id is required for this peer_type.',
                'token' => null,
            ], 422);
        }

        return [$peerType, $peerId];
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
