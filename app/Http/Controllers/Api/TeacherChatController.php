<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminRole;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TeacherChatController extends Controller
{
    /**
     * Get messages between current teacher and super admin/admin.
     *
     * GET /api/teacher/chat/messages?per_page=20
     */
    public function index(Request $request): JsonResponse
    {
        $teacher = $request->user();

        if (!$teacher || strtolower(trim($teacher->designation ?? '')) !== 'teacher') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Only teachers can access this chat.',
            ], 403);
        }

        $perPage = (int) $request->get('per_page', 20);
        $perPage = in_array($perPage, [10, 20, 50, 100], true) ? $perPage : 20;

        $teacherId = $teacher->id;

        $query = Message::query()
            ->where(function ($q) use ($teacherId) {
                $q->where('from_type', 'teacher')
                    ->where('from_id', $teacherId);
            })
            ->orWhere(function ($q) use ($teacherId) {
                $q->where('to_type', 'teacher')
                    ->where('to_id', $teacherId);
            });

        $messages = $query->orderBy('created_at', 'asc')->paginate($perPage);

        $data = $messages->map(function (Message $message) {
            return [
                'id' => $message->id,
                'from_type' => $message->from_type,
                'from_id' => $message->from_id,
                'to_type' => $message->to_type,
                'to_id' => $message->to_id,
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
        });

        return response()->json([
            'success' => true,
            'message' => 'Chat messages loaded successfully.',
            'data' => [
                'messages' => $data,
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
     * Send a message from teacher to super admin (or first admin).
     *
     * POST /api/teacher/chat/messages
     * Body (multipart/form-data):
     *  - text (optional)
     *  - attachment (optional file: image/pdf/doc)
     */
    public function store(Request $request): JsonResponse
    {
        $teacher = $request->user();

        if (!$teacher || strtolower(trim($teacher->designation ?? '')) !== 'teacher') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Only teachers can send chat messages.',
            ], 403);
        }

        $validated = $request->validate([
            'text' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:5120'],
        ]);

        if (empty($validated['text']) && !$request->hasFile('attachment')) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide a message text or an attachment.',
            ], 422);
        }

        // Resolve target admin (prefer super admin)
        $admin = AdminRole::where('super_admin', true)->first();
        if (!$admin) {
            $admin = AdminRole::first();
        }

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'No admin user found to receive this message.',
            ], 500);
        }

        $attachmentPath = null;
        $attachmentType = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');

            // Store file on "public" disk
            $storedPath = $file->store('chat-attachments/teachers', 'public');
            // Save relative path (without 'storage/' prefix) in DB
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
            'to_type' => 'admin',
            'to_id' => $admin->id,
            'text' => $validated['text'] ?? null,
            'attachment_path' => $attachmentPath,
            'attachment_type' => $attachmentType,
            'read_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully.',
            'data' => [
                'id' => $message->id,
            ],
        ], 201);
    }
}


