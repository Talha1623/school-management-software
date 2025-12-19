<?php

namespace App\Http\Controllers;

use App\Models\AdminRole;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class StaffChatController extends Controller
{
    /**
     * Show chat page for teacher (staff) with assigned admin/super admin.
     */
    public function index(Request $request): View
    {
        $teacher = Auth::guard('staff')->user();

        if (!$teacher) {
            abort(403, 'Unauthorized');
        }

        // Optional: restrict to teachers only
        // if (stripos(strtolower($teacher->designation ?? ''), 'teacher') === false) {
        //     abort(403, 'Only teachers can access this chat.');
        // }

        // Resolve target admin (prefer super admin)
        $admin = AdminRole::where('super_admin', true)->first();
        if (!$admin) {
            $admin = AdminRole::first();
        }

        $messages = collect();

        if ($admin) {
            $messages = Message::query()
                ->where(function ($q) use ($teacher, $admin) {
                    $q->where('from_type', 'teacher')
                        ->where('from_id', $teacher->id)
                        ->where('to_type', 'admin')
                        ->where('to_id', $admin->id);
                })
                ->orWhere(function ($q) use ($teacher, $admin) {
                    $q->where('from_type', 'admin')
                        ->where('from_id', $admin->id)
                        ->where('to_type', 'teacher')
                        ->where('to_id', $teacher->id);
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

            // Mark all unread messages from admin to this teacher as read
            Message::where('from_type', 'admin')
                ->where('from_id', $admin->id)
                ->where('to_type', 'teacher')
                ->where('to_id', $teacher->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        return view('staff.live-chat', compact('teacher', 'admin', 'messages'));
    }

    /**
     * Send message from teacher (staff) to admin.
     */
    public function send(Request $request): RedirectResponse
    {
        $teacher = Auth::guard('staff')->user();

        if (!$teacher) {
            abort(403, 'Unauthorized');
        }

        // if (stripos(strtolower($teacher->designation ?? ''), 'teacher') === false) {
        //     abort(403, 'Only teachers can send chat messages.');
        // }

        $validated = $request->validate([
            'text' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:5120'],
        ]);

        if (empty($validated['text']) && !$request->hasFile('attachment')) {
            return redirect()
                ->back()
                ->with('error', 'Please type a message or attach a file.');
        }

        // Resolve target admin (prefer super admin)
        $admin = AdminRole::where('super_admin', true)->first();
        if (!$admin) {
            $admin = AdminRole::first();
        }

        if (!$admin) {
            return redirect()
                ->back()
                ->with('error', 'No admin user found to receive this message.');
        }

        $attachmentPath = null;
        $attachmentType = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');

            try {
                // Ensure directory exists
                $directory = storage_path('app/public/chat-attachments/teachers');
                if (!file_exists($directory)) {
                    File::makeDirectory($directory, 0755, true);
                }

                // Store file on "public" disk
                $storedPath = $file->store('chat-attachments/teachers', 'public');
                
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

                // Save relative path in DB (e.g., "chat-attachments/teachers/xyz.jpg")
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
            'from_type' => 'teacher',
            'from_id' => $teacher->id,
            'to_type' => 'admin',
            'to_id' => $admin->id,
            'text' => $validated['text'] ?? null,
            'attachment_path' => $attachmentPath,
            'attachment_type' => $attachmentType,
            'read_at' => null,
        ]);

        return redirect()
            ->route('staff.chat')
            ->with('success', 'Message sent successfully.');
    }
}


