<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use App\Models\ClassModel;
use App\Models\Message;
use App\Models\Section;
use App\Models\Student;
use App\Services\ChatService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class MessagingController extends Controller
{
    public function __construct(
        private readonly ChatService $chatService,
    ) {
    }

    public function index(Request $request): View
    {
        $actor = $this->chatService->resolveActor();
        if (!$actor) {
            abort(403, 'Unauthorized');
        }

        $routes = $this->routesForGuard($actor->guard);

        $selectedCampus = trim((string) $request->get('campus', ''));
        $selectedClass = trim((string) $request->get('class', ''));
        $selectedSection = trim((string) $request->get('section', ''));
        $selectedType = (string) $request->get('recipient_type', '');
        $selectedId = (int) $request->get('recipient_id', 0);

        if ($selectedType === '' && (int) $request->get('teacher_id', 0) > 0) {
            $selectedType = 'teacher';
            $selectedId = (int) $request->get('teacher_id');
        }

        $showFilters = in_array($actor->role, ['super_admin', 'admin', 'teacher'], true);
        $layout = $this->layoutForGuard($actor->guard);
        $pageSubtitle = $actor->guard === 'accountant'
            ? 'Message accountants only'
            : 'Message staff, parents, and students securely';
        $campuses = collect();
        $classes = collect();
        $sections = collect();

        if ($showFilters) {
            $campuses = Campus::orderBy('campus_name', 'asc')->get();
            if ($campuses->isEmpty()) {
                $campuses = Student::whereNotNull('campus')->distinct()->orderBy('campus')->pluck('campus')
                    ->map(fn ($name) => (object) ['campus_name' => $name]);
            }

            $classes = ClassModel::whereNotNull('class_name')
                ->when($selectedCampus !== '', fn ($q) => $q->whereRaw('LOWER(TRIM(COALESCE(campus, ""))) = ?', [strtolower($selectedCampus)]))
                ->distinct()->orderBy('class_name')->pluck('class_name');

            $sections = Section::whereNotNull('name')
                ->when($selectedCampus !== '', fn ($q) => $q->whereRaw('LOWER(TRIM(COALESCE(campus, ""))) = ?', [strtolower($selectedCampus)]))
                ->when($selectedClass !== '', fn ($q) => $q->whereRaw('LOWER(TRIM(COALESCE(class, ""))) = ?', [strtolower($selectedClass)]))
                ->distinct()->orderBy('name')->pluck('name');
        }

        $contactGroups = $this->chatService->groupedContacts(
            $actor,
            $selectedCampus !== '' ? $selectedCampus : null,
            $selectedClass !== '' ? $selectedClass : null,
            $selectedSection !== '' ? $selectedSection : null,
        );

        $selectedPeer = null;
        $messages = collect();

        if ($selectedType !== '' && $selectedId > 0) {
            if (!$this->chatService->canActorsChat($actor, $selectedType, $selectedId)) {
                abort(403, 'You are not allowed to chat with this user.');
            }

            $selectedPeer = $this->chatService->findPeer($selectedType, $selectedId);
            if ($selectedPeer) {
                $messages = $this->chatService->conversation($actor, $selectedType, $selectedId);
                $this->chatService->markConversationRead($actor, $selectedType, $selectedId);
            }
        }

        $ajaxSend = $actor->guard === 'admin';
        if (in_array($actor->messageType, ['admin', 'super_admin'], true)) {
            $unreadBySender = Message::unreadCountsBySenderForAdminInbox(
                $actor->id,
                $actor->messageType === 'super_admin'
            );
        } else {
            $unreadBySender = Message::unreadCountsBySender($actor->messageType, $actor->id);
        }

        return view('chat.messenger', compact(
            'actor',
            'routes',
            'contactGroups',
            'selectedPeer',
            'selectedType',
            'selectedId',
            'messages',
            'showFilters',
            'campuses',
            'classes',
            'sections',
            'selectedCampus',
            'selectedClass',
            'selectedSection',
            'ajaxSend',
            'layout',
            'pageSubtitle',
            'unreadBySender',
        ));
    }

    public function send(Request $request): Response
    {
        $actor = $this->chatService->resolveActor();
        if (!$actor) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'recipient_type' => ['required', 'string', 'max:30'],
            'recipient_id' => ['required', 'integer', 'min:1'],
            'text' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:5120'],
            'campus' => ['nullable', 'string'],
            'class' => ['nullable', 'string'],
            'section' => ['nullable', 'string'],
        ]);

        if (empty($validated['text']) && !$request->hasFile('attachment')) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Please type a message or attach a file.'], 422);
            }

            return redirect()->back()->with('error', 'Please type a message or attach a file.');
        }

        $message = $this->chatService->send(
            $actor,
            $validated['recipient_type'],
            (int) $validated['recipient_id'],
            $validated['text'] ?? null,
            $request->file('attachment'),
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully.',
                'data' => $this->chatService->formatMessageForActor($message, $actor),
            ], 201);
        }

        $routes = $this->routesForGuard($actor->guard);

        return redirect()
            ->route($routes['index'], [
                'recipient_type' => $validated['recipient_type'],
                'recipient_id' => $validated['recipient_id'],
                'campus' => $validated['campus'] ?? null,
                'class' => $validated['class'] ?? null,
                'section' => $validated['section'] ?? null,
            ])
            ->with('success', 'Message sent successfully.');
    }

    /** @return array{index: string, send: string} */
    private function routesForGuard(string $guard): array
    {
        return match ($guard) {
            'staff' => ['index' => 'staff.chat', 'send' => 'staff.chat.send'],
            'accountant' => ['index' => 'accountant.chat', 'send' => 'accountant.chat.send'],
            'student' => ['index' => 'student.chat', 'send' => 'student.chat.send'],
            'parent' => ['index' => 'parent.chat', 'send' => 'parent.chat.send'],
            default => ['index' => 'live-chat', 'send' => 'live-chat.send'],
        };
    }

    private function layoutForGuard(string $guard): string
    {
        return match ($guard) {
            'accountant' => 'layouts.accountant',
            'student' => 'layouts.student',
            default => 'layouts.app',
        };
    }
}
