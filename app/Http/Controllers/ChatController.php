<?php

namespace App\Http\Controllers;

use App\Models\AdminRole;
use App\Models\Message;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ChatController extends Controller
{
    /**
     * Show live chat page for Admin/Super Admin with teacher conversations.
     */
    public function index(Request $request): View
    {
        $admin = Auth::guard('admin')->user();

        // Only admin/super admin should access
        if (!$admin) {
            abort(403, 'Unauthorized');
        }

        // Get all staff as chat targets (so every newly added teacher/staff appears)
        $teachers = Staff::orderBy('name')->get();

        $selectedTeacherId = $request->get('teacher_id');
        $selectedTeacher = null;
        $messages = collect();

        if ($selectedTeacherId) {
            $selectedTeacher = $teachers->firstWhere('id', (int) $selectedTeacherId);

            if ($selectedTeacher) {
                $messages = Message::query()
                    ->where(function ($q) use ($admin, $selectedTeacher) {
                        $q->where('from_type', 'admin')
                            ->where('from_id', $admin->id)
                            ->where('to_type', 'teacher')
                            ->where('to_id', $selectedTeacher->id);
                    })
                    ->orWhere(function ($q) use ($admin, $selectedTeacher) {
                        $q->where('from_type', 'teacher')
                            ->where('from_id', $selectedTeacher->id)
                            ->where('to_type', 'admin')
                            ->where('to_id', $admin->id);
                    })
                    ->orderBy('created_at', 'asc')
                    ->get()
                    ->map(function (Message $message) {
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
                            'created_at' => $message->created_at?->format('Y-m-d H:i'),
                        ];
                    });

                // Mark all unread messages from this teacher to this admin as read
                Message::where('from_type', 'teacher')
                    ->where('from_id', $selectedTeacher->id)
                    ->where('to_type', 'admin')
                    ->where('to_id', $admin->id)
                    ->whereNull('read_at')
                    ->update(['read_at' => now()]);
            }
        }

        return view('live-chat', compact('admin', 'teachers', 'selectedTeacher', 'messages'));
    }

    /**
     * Send message from Admin/Super Admin to selected teacher.
     */
    public function sendToTeacher(Request $request, int $teacherId): RedirectResponse
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            abort(403, 'Unauthorized');
        }

        $teacher = Staff::where('id', $teacherId)->firstOrFail();

        $validated = $request->validate([
            'text' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:5120'],
        ]);

        if (empty($validated['text']) && !$request->hasFile('attachment')) {
            return redirect()
                ->back()
                ->with('error', 'Please type a message or attach a file.');
        }

        $attachmentPath = null;
        $attachmentType = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');

            try {
                // Ensure directory exists
                $directory = storage_path('app/public/chat-attachments/admins');
                if (!file_exists($directory)) {
                    File::makeDirectory($directory, 0755, true);
                }

                // Store file on "public" disk
                $storedPath = $file->store('chat-attachments/admins', 'public');
                
                if (!$storedPath) {
                    return redirect()
                        ->back()
                        ->with('error', 'Failed to save attachment. Please try again.');
                }

                // Verify file exists
                $fullPath = storage_path('app/public/' . $storedPath);
                if (!file_exists($fullPath)) {
                    return redirect()
                        ->back()
                        ->with('error', 'File was not saved correctly. Please try again.');
                }

                // Save relative path in DB (e.g., "chat-attachments/admins/xyz.jpg")
                $attachmentPath = $storedPath;

                $mime = $file->getClientMimeType();
                if (str_starts_with($mime, 'image/')) {
                    $attachmentType = 'image';
                } elseif ($mime === 'application/pdf') {
                    $attachmentType = 'pdf';
                } else {
                    $attachmentType = 'document';
                }
            } catch (\Exception $e) {
                return redirect()
                    ->back()
                    ->with('error', 'Error saving attachment: ' . $e->getMessage());
            }
        }

        Message::create([
            'from_type' => 'admin',
            'from_id' => $admin->id,
            'to_type' => 'teacher',
            'to_id' => $teacher->id,
            'text' => $validated['text'] ?? null,
            'attachment_path' => $attachmentPath,
            'attachment_type' => $attachmentType,
            'read_at' => null,
        ]);

        return redirect()
            ->route('live-chat', ['teacher_id' => $teacher->id])
            ->with('success', 'Message sent successfully.');
    }
}


