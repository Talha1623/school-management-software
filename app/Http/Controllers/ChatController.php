<?php

namespace App\Http\Controllers;

use App\Models\AdminRole;
use App\Models\Accountant;
use App\Models\Campus;
use App\Models\ClassModel;
use App\Models\Message;
use App\Models\ParentAccount;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Services\ChatSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ChatController extends Controller
{
    public function __construct(
        private readonly ChatSmsService $chatSmsService,
    ) {
    }

    /**
     * Show live chat page for Admin/Super Admin with teacher/student/parent conversations.
     */
    public function index(Request $request): View
    {
        $admin = Auth::guard('admin')->user();

        // Only admin/super admin should access
        if (!$admin) {
            abort(403, 'Unauthorized');
        }

        $selectedCampus = trim((string) $request->get('campus', ''));
        $selectedClass = trim((string) $request->get('class', ''));
        $selectedSection = trim((string) $request->get('section', ''));

        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        if ($campuses->isEmpty()) {
            $campusValues = Student::whereNotNull('campus')->distinct()->pluck('campus')
                ->merge(Staff::whereNotNull('campus')->distinct()->pluck('campus'))
                ->merge(Accountant::whereNotNull('campus')->distinct()->pluck('campus'))
                ->unique()
                ->sort()
                ->values();
            $campuses = $campusValues->map(fn ($name) => (object) ['campus_name' => $name]);
        }

        $classes = ClassModel::whereNotNull('class_name')
            ->when($selectedCampus !== '', function ($q) use ($selectedCampus) {
                $q->whereRaw('LOWER(TRIM(COALESCE(campus, ""))) = ?', [strtolower($selectedCampus)]);
            })
            ->distinct()
            ->orderBy('class_name', 'asc')
            ->pluck('class_name');

        $sections = Section::whereNotNull('name')
            ->when($selectedCampus !== '', function ($q) use ($selectedCampus) {
                $q->whereRaw('LOWER(TRIM(COALESCE(campus, ""))) = ?', [strtolower($selectedCampus)]);
            })
            ->when($selectedClass !== '', function ($q) use ($selectedClass) {
                $q->whereRaw('LOWER(TRIM(COALESCE(class, ""))) = ?', [strtolower($selectedClass)]);
            })
            ->distinct()
            ->orderBy('name', 'asc')
            ->pluck('name');

        // Get all staff teachers as chat targets
        $teachers = Staff::query()
            ->when($selectedCampus !== '', function ($q) use ($selectedCampus) {
                $q->whereRaw('LOWER(TRIM(COALESCE(campus, ""))) = ?', [strtolower($selectedCampus)]);
            })
            ->orderBy('name')
            ->get();

        // Add accountants to same recipient bucket as requested.
        $accountants = Accountant::query()
            ->when($selectedCampus !== '', function ($q) use ($selectedCampus) {
                $q->whereRaw('LOWER(TRIM(COALESCE(campus, ""))) = ?', [strtolower($selectedCampus)]);
            })
            ->orderBy('name')
            ->get();

        $students = Student::query()
            ->whereNotNull('student_name')
            ->when($selectedCampus !== '', function ($q) use ($selectedCampus) {
                $q->whereRaw('LOWER(TRIM(COALESCE(campus, ""))) = ?', [strtolower($selectedCampus)]);
            })
            ->when($selectedClass !== '', function ($q) use ($selectedClass) {
                $q->whereRaw('LOWER(TRIM(COALESCE(class, ""))) = ?', [strtolower($selectedClass)]);
            })
            ->when($selectedSection !== '', function ($q) use ($selectedSection) {
                $q->whereRaw('LOWER(TRIM(COALESCE(section, ""))) = ?', [strtolower($selectedSection)]);
            })
            ->orderBy('student_name')
            ->get();

        $parents = ParentAccount::query()
            ->whereNotNull('name')
            ->when($selectedCampus !== '' || $selectedClass !== '' || $selectedSection !== '', function ($q) use ($selectedCampus, $selectedClass, $selectedSection) {
                $q->whereHas('students', function ($sq) use ($selectedCampus, $selectedClass, $selectedSection) {
                    if ($selectedCampus !== '') {
                        $sq->whereRaw('LOWER(TRIM(COALESCE(campus, ""))) = ?', [strtolower($selectedCampus)]);
                    }
                    if ($selectedClass !== '') {
                        $sq->whereRaw('LOWER(TRIM(COALESCE(class, ""))) = ?', [strtolower($selectedClass)]);
                    }
                    if ($selectedSection !== '') {
                        $sq->whereRaw('LOWER(TRIM(COALESCE(section, ""))) = ?', [strtolower($selectedSection)]);
                    }
                });
            })
            ->orderBy('name')
            ->get();

        $selectedType = (string) $request->get('recipient_type', '');
        $selectedId = (int) $request->get('recipient_id', 0);
        // Backward compatibility with existing teacher_id query param
        if ($selectedType === '' && (int) $request->get('teacher_id', 0) > 0) {
            $selectedType = 'teacher';
            $selectedId = (int) $request->get('teacher_id');
        }

        $selectedRecipient = null;
        $messages = collect();
        $isSuperAdmin = ! empty($admin->super_admin);
        $unreadBySender = Message::unreadCountsBySenderForAdminInbox((int) $admin->id, $isSuperAdmin);

        if (in_array($selectedType, ['teacher', 'student', 'parent', 'accountant'], true) && $selectedId > 0) {
            if ($selectedType === 'teacher') {
                $selectedRecipient = $teachers->firstWhere('id', $selectedId);
            } elseif ($selectedType === 'student') {
                $selectedRecipient = $students->firstWhere('id', $selectedId);
            } elseif ($selectedType === 'accountant') {
                $selectedRecipient = $accountants->firstWhere('id', $selectedId);
            } else {
                $selectedRecipient = $parents->firstWhere('id', $selectedId);
            }

            if ($selectedRecipient) {
                $messages = Message::query()
                    ->where(function ($outer) use ($admin, $selectedType, $selectedRecipient, $isSuperAdmin) {
                        $outer->where(function ($q) use ($admin, $selectedType, $selectedRecipient) {
                            $q->where('from_type', 'admin')
                                ->where('from_id', $admin->id)
                                ->where('to_type', $selectedType)
                                ->where('to_id', $selectedRecipient->id);
                        })->orWhere(function ($q) use ($admin, $selectedType, $selectedRecipient, $isSuperAdmin) {
                            $q->where('from_type', $selectedType)
                                ->where('from_id', $selectedRecipient->id);

                            if ($isSuperAdmin) {
                                $q->whereIn('to_type', ['admin', 'super_admin'])
                                    ->where('to_id', $admin->id);
                            } else {
                                $q->where('to_type', 'admin')
                                    ->where('to_id', $admin->id);
                            }
                        });
                    })
                    ->forLiveChat()
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

                // Mark all unread messages from selected recipient to this admin as read
                Message::query()
                    ->where('from_type', $selectedType)
                    ->where('from_id', $selectedRecipient->id)
                    ->when($isSuperAdmin, function ($q) {
                        $q->whereIn('to_type', ['admin', 'super_admin']);
                    }, function ($q) {
                        $q->where('to_type', 'admin');
                    })
                    ->where('to_id', $admin->id)
                    ->whereNull('read_at')
                    ->update(['read_at' => now()]);
            }
        }

        return view('live-chat', compact(
            'admin',
            'teachers',
            'accountants',
            'students',
            'parents',
            'campuses',
            'classes',
            'sections',
            'selectedCampus',
            'selectedClass',
            'selectedSection',
            'selectedType',
            'selectedRecipient',
            'messages',
            'unreadBySender'
        ));
    }

    /**
     * Unread live-chat message count for the current user (sidebar badge polling).
     */
    public function unreadCount(): JsonResponse
    {
        $admin = Auth::guard('admin')->user();
        if ($admin) {
            return response()->json([
                'count' => Message::unreadLiveChatCountForAdminInbox(
                    (int) $admin->id,
                    ! empty($admin->super_admin)
                ),
            ]);
        }

        $staff = Auth::guard('staff')->user();
        if ($staff) {
            return response()->json([
                'count' => Message::unreadLiveChatCount('teacher', (int) $staff->id),
            ]);
        }

        $accountant = Auth::guard('accountant')->user();
        if ($accountant) {
            return response()->json([
                'count' => Message::unreadLiveChatCount('accountant', (int) $accountant->id),
            ]);
        }

        return response()->json(['count' => 0], 401);
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

        $message = Message::create([
            'from_type' => 'admin',
            'from_id' => $admin->id,
            'to_type' => 'teacher',
            'to_id' => $teacher->id,
            'text' => $validated['text'] ?? null,
            'attachment_path' => $attachmentPath,
            'attachment_type' => $attachmentType,
            'read_at' => null,
        ]);

        $this->chatSmsService->notifyForChatMessage($message);

        return redirect()
            ->route('live-chat', ['teacher_id' => $teacher->id])
            ->with('success', 'Message sent successfully.');
    }

    /**
     * Send message from Admin/Super Admin to selected recipient type.
     */
    public function send(Request $request): Response
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'recipient_type' => ['required', 'in:teacher,student,parent,accountant'],
            'recipient_id' => ['required', 'integer', 'min:1'],
            'text' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:5120'],
            'campus' => ['nullable', 'string'],
            'class' => ['nullable', 'string'],
            'section' => ['nullable', 'string'],
        ]);

        if (empty($validated['text']) && !$request->hasFile('attachment')) {
            return redirect()->back()->with('error', 'Please type a message or attach a file.');
        }

        $recipientType = $validated['recipient_type'];
        $recipientId = (int) $validated['recipient_id'];

        if ($recipientType === 'teacher') {
            $recipient = Staff::where('id', $recipientId)->first();
        } elseif ($recipientType === 'student') {
            $recipient = Student::where('id', $recipientId)->first();
        } elseif ($recipientType === 'accountant') {
            $recipient = Accountant::where('id', $recipientId)->first();
        } else {
            $recipient = ParentAccount::where('id', $recipientId)->first();
        }

        if (!$recipient) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected recipient not found.',
                ], 404);
            }
            return redirect()->back()->with('error', 'Selected recipient not found.');
        }

        $attachmentPath = null;
        $attachmentType = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            try {
                $directory = storage_path('app/public/chat-attachments/admins');
                if (!file_exists($directory)) {
                    File::makeDirectory($directory, 0755, true);
                }

                $storedPath = $file->store('chat-attachments/admins', 'public');
                if (!$storedPath) {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Failed to save attachment. Please try again.',
                        ], 500);
                    }
                    return redirect()->back()->with('error', 'Failed to save attachment. Please try again.');
                }

                $fullPath = storage_path('app/public/' . $storedPath);
                if (!file_exists($fullPath)) {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'File was not saved correctly. Please try again.',
                        ], 500);
                    }
                    return redirect()->back()->with('error', 'File was not saved correctly. Please try again.');
                }

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
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Error saving attachment: ' . $e->getMessage(),
                    ], 500);
                }
                return redirect()->back()->with('error', 'Error saving attachment: ' . $e->getMessage());
            }
        }

        $message = Message::create([
            'from_type' => 'admin',
            'from_id' => $admin->id,
            'to_type' => $recipientType,
            'to_id' => $recipientId,
            'text' => $validated['text'] ?? null,
            'attachment_path' => $attachmentPath,
            'attachment_type' => $attachmentType,
            'read_at' => null,
        ]);

        $this->chatSmsService->notifyForChatMessage($message);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully.',
                'data' => [
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
                ],
            ], 201);
        }

        return redirect()
            ->route('live-chat', [
                'recipient_type' => $recipientType,
                'recipient_id' => $recipientId,
                'campus' => $validated['campus'] ?? null,
                'class' => $validated['class'] ?? null,
                'section' => $validated['section'] ?? null,
            ])
            ->with('success', 'Message sent successfully.');
    }

    /**
     * Mark all notifications as read for the current admin.
     */
    public function markAllAsRead(): RedirectResponse
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            abort(403, 'Unauthorized');
        }

        // Mark all unread messages from teachers to this admin as read
        $isSuperAdmin = ! empty($admin->super_admin);

        Message::whereIn('from_type', ['teacher', 'student', 'parent', 'accountant', 'accountant_notification', 'staff_notification'])
            ->when($isSuperAdmin, function ($q) {
                $q->whereIn('to_type', ['admin', 'super_admin']);
            }, function ($q) {
                $q->where('to_type', 'admin');
            })
            ->where('to_id', $admin->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return redirect()->back()->with('success', 'All notifications marked as read.');
    }

    /**
     * Mark all notifications as read for the logged-in accountant.
     */
    public function markAccountantNotificationsAsRead(): RedirectResponse
    {
        $accountant = Auth::guard('accountant')->user();

        if (!$accountant) {
            abort(403, 'Unauthorized');
        }

        Message::where('to_type', 'accountant')
            ->where('to_id', $accountant->id)
            ->whereIn('from_type', ['admin', 'super_admin', 'staff_notification', 'accountant'])
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return redirect()->back()->with('success', 'All notifications marked as read.');
    }
}


