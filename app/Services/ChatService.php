<?php

namespace App\Services;

use App\Models\Accountant;
use App\Models\AdminRole;
use App\Models\Message;
use App\Models\ParentAccount;
use App\Models\Staff;
use App\Models\Student;
use App\Support\ChatActor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class ChatService
{
    public function __construct(
        private readonly ChatPermissionService $permissions,
    ) {
    }

    public function resolveActor(): ?ChatActor
    {
        $preferredGuard = $this->preferredGuardForRequest();
        if ($preferredGuard) {
            $user = Auth::guard($preferredGuard)->user();
            if ($user) {
                return ChatActor::fromUser($preferredGuard, $user);
            }
        }

        foreach (['accountant', 'staff', 'admin', 'student', 'parent', 'platform_super_admin'] as $guard) {
            $user = Auth::guard($guard)->user();
            if ($user) {
                return ChatActor::fromUser($guard, $user);
            }
        }

        return null;
    }

    private function preferredGuardForRequest(): ?string
    {
        $request = request();
        if (!$request) {
            return null;
        }

        if ($request->routeIs('accountant.*') || str_starts_with($request->path(), 'accountant/')) {
            return 'accountant';
        }

        if ($request->routeIs('staff.*') || str_starts_with($request->path(), 'staff/')) {
            return 'staff';
        }

        if ($request->routeIs('student.*') || str_starts_with($request->path(), 'student/')) {
            return 'student';
        }

        if ($request->routeIs('parent.*') || str_starts_with($request->path(), 'parent/')) {
            return 'parent';
        }

        return null;
    }

    /**
     * @return array<string, Collection<int, array<string, mixed>>>
     */
    public function groupedContacts(ChatActor $actor, ?string $campus = null, ?string $class = null, ?string $section = null): array
    {
        if ($actor->role === 'student' && !$campus && $actor->campus) {
            $campus = $actor->campus;
        }

        $groups = [];
        foreach ($this->permissions->allowedReceiverRoles($actor->role) as $role) {
            $contacts = $this->contactsForRole($actor, $role, $campus, $class, $section);
            if ($contacts->isNotEmpty()) {
                $groups[$this->roleLabel($role)] = $contacts;
            }
        }

        return $groups;
    }

    public function findPeer(string $peerType, int $peerId): ?array
    {
        return match ($peerType) {
            'super_admin', 'admin' => $this->mapAdmin(AdminRole::find($peerId), $peerType === 'super_admin'),
            'accountant' => $this->mapSimple(Accountant::find($peerId), 'accountant', 'Accountant'),
            'teacher' => $this->mapStaff(Staff::find($peerId)),
            'parent' => $this->mapSimple(ParentAccount::find($peerId), 'parent', 'Parent'),
            'student' => $this->mapStudent(Student::find($peerId)),
            default => null,
        };
    }

    public function canActorsChat(ChatActor $actor, string $peerType, int $peerId): bool
    {
        $peer = $this->findPeer($peerType, $peerId);
        if (!$peer) {
            return false;
        }

        return $this->permissions->canChat($actor->role, $peer['role']);
    }

    public function conversation(ChatActor $actor, string $peerType, int $peerId): Collection
    {
        $peer = $this->findPeer($peerType, $peerId);
        if (!$peer) {
            return collect();
        }

        $query = Message::query()->where(function ($outer) use ($actor, $peer, $peerType, $peerId) {
            $outer->where(function ($q) use ($actor, $peerType, $peerId) {
                $q->where('from_type', $actor->messageType)
                    ->where('from_id', $actor->id)
                    ->where('to_type', $peerType)
                    ->where('to_id', $peerId);
            })->orWhere(function ($q) use ($actor, $peerType, $peerId) {
                $q->where('from_type', $peerType)
                    ->where('from_id', $peerId);

                if (in_array($actor->messageType, ['admin', 'super_admin'], true)) {
                    $q->whereIn('to_type', ['admin', 'super_admin'])
                        ->where('to_id', $actor->id);
                } else {
                    $q->where('to_type', $actor->messageType)
                        ->where('to_id', $actor->id);
                }
            });

            $this->appendLegacyAdminSuperAdminClauses($outer, $actor, $peer, $peerType, $peerId);
        })->orderBy('created_at', 'asc');

        return $query->get()->map(fn (Message $message) => $this->formatMessage($message, $actor));
    }

    public function markConversationRead(ChatActor $actor, string $peerType, int $peerId): void
    {
        Message::query()
            ->where('from_type', $peerType)
            ->where('from_id', $peerId)
            ->when(
                in_array($actor->messageType, ['admin', 'super_admin'], true),
                function ($q) use ($actor) {
                    $q->whereIn('to_type', ['admin', 'super_admin'])
                        ->where('to_id', $actor->id);
                },
                function ($q) use ($actor) {
                    $q->where('to_type', $actor->messageType)
                        ->where('to_id', $actor->id);
                }
            )
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        if ($peerType === 'super_admin') {
            $admin = AdminRole::find($peerId);
            if ($admin && $admin->super_admin) {
                Message::query()
                    ->where('from_type', 'admin')
                    ->where('from_id', $peerId)
                    ->where('to_type', $actor->messageType)
                    ->where('to_id', $actor->id)
                    ->whereNull('read_at')
                    ->update(['read_at' => now()]);
            }
        }

        if ($actor->messageType === 'teacher' && in_array($peerType, ['admin', 'super_admin'], true)) {
            $admin = AdminRole::find($peerId);
            $fromTypes = ['admin'];
            if ($admin && $admin->super_admin) {
                $fromTypes[] = 'super_admin';
            }
            Message::query()
                ->whereIn('from_type', $fromTypes)
                ->where('from_id', $peerId)
                ->where('to_type', 'teacher')
                ->where('to_id', $actor->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        if ($actor->messageType === 'super_admin') {
            Message::query()
                ->where('from_type', $peerType)
                ->where('from_id', $peerId)
                ->where('to_type', 'admin')
                ->where('to_id', $actor->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }
    }

    public function send(ChatActor $actor, string $peerType, int $peerId, ?string $text, ?UploadedFile $attachment = null): Message
    {
        if (!$this->canActorsChat($actor, $peerType, $peerId)) {
            abort(403, 'You are not allowed to chat with this user.');
        }

        [$attachmentPath, $attachmentType] = $this->storeAttachment($actor, $attachment);

        return Message::create([
            'from_type' => $actor->messageType,
            'from_id' => $actor->id,
            'to_type' => $peerType,
            'to_id' => $peerId,
            'text' => $text,
            'attachment_path' => $attachmentPath,
            'attachment_type' => $attachmentType,
            'read_at' => null,
        ]);
    }

    private function contactsForRole(ChatActor $actor, string $receiverRole, ?string $campus, ?string $class, ?string $section): Collection
    {
        return match ($receiverRole) {
            'super_admin' => AdminRole::query()
                ->where('super_admin', true)
                ->when($actor->role === 'super_admin', fn ($q) => $q->where('id', '!=', $actor->id))
                ->orderBy('name')
                ->get()
                ->map(fn (AdminRole $admin) => $this->mapAdmin($admin, true))
                ->filter(),
            'admin' => AdminRole::query()
                ->where(function ($q) {
                    $q->where('super_admin', false)->orWhereNull('super_admin');
                })
                ->when($actor->role === 'admin', fn ($q) => $q->where('id', '!=', $actor->id))
                ->orderBy('name')
                ->get()
                ->map(fn (AdminRole $admin) => $this->mapAdmin($admin, false))
                ->filter(),
            'accountant' => Accountant::query()
                ->when($campus, fn ($q) => $q->whereRaw('LOWER(TRIM(COALESCE(campus, ""))) = ?', [strtolower($campus)]))
                ->when($actor->role === 'accountant', fn ($q) => $q->where('id', '!=', $actor->id))
                ->orderBy('name')
                ->get()
                ->map(fn (Accountant $row) => $this->mapSimple($row, 'accountant', 'Accountant'))
                ->filter(),
            'teacher' => Staff::query()
                ->when($campus, fn ($q) => $q->whereRaw('LOWER(TRIM(COALESCE(campus, ""))) = ?', [strtolower($campus)]))
                ->when($actor->role === 'parent' && !$campus, function ($q) use ($actor) {
                    $parent = ParentAccount::with('students')->find($actor->id);
                    $campuses = $parent?->students
                        ?->pluck('campus')
                        ->filter(fn ($c) => is_string($c) && trim($c) !== '')
                        ->map(fn ($c) => strtolower(trim($c)))
                        ->unique()
                        ->values() ?? collect();

                    if ($campuses->isNotEmpty()) {
                        $q->where(function ($inner) use ($campuses) {
                            foreach ($campuses as $campusName) {
                                $inner->orWhereRaw('LOWER(TRIM(COALESCE(campus, ""))) = ?', [$campusName]);
                            }
                        });
                    }
                })
                ->when($actor->role === 'teacher', fn ($q) => $q->where('id', '!=', $actor->id))
                ->orderBy('name')
                ->get()
                ->map(fn (Staff $row) => $this->mapStaff($row))
                ->filter(),
            'parent' => ParentAccount::query()
                ->whereNotNull('name')
                ->when($campus || $class || $section, function ($q) use ($campus, $class, $section) {
                    $q->whereHas('students', function ($sq) use ($campus, $class, $section) {
                        if ($campus) {
                            $sq->whereRaw('LOWER(TRIM(COALESCE(campus, ""))) = ?', [strtolower($campus)]);
                        }
                        if ($class) {
                            $sq->whereRaw('LOWER(TRIM(COALESCE(class, ""))) = ?', [strtolower($class)]);
                        }
                        if ($section) {
                            $sq->whereRaw('LOWER(TRIM(COALESCE(section, ""))) = ?', [strtolower($section)]);
                        }
                    });
                })
                ->orderBy('name')
                ->get()
                ->map(fn (ParentAccount $row) => $this->mapSimple($row, 'parent', 'Parent'))
                ->filter(),
            'student' => Student::query()
                ->whereNotNull('student_name')
                ->when($campus, fn ($q) => $q->whereRaw('LOWER(TRIM(COALESCE(campus, ""))) = ?', [strtolower($campus)]))
                ->when($class, fn ($q) => $q->whereRaw('LOWER(TRIM(COALESCE(class, ""))) = ?', [strtolower($class)]))
                ->when($section, fn ($q) => $q->whereRaw('LOWER(TRIM(COALESCE(section, ""))) = ?', [strtolower($section)]))
                ->when($actor->role === 'student', fn ($q) => $q->where('id', '!=', $actor->id))
                ->orderBy('student_name')
                ->get()
                ->map(fn (Student $row) => $this->mapStudent($row))
                ->filter(),
            default => collect(),
        };
    }

    private function mapAdmin(?AdminRole $admin, bool $forceSuper): ?array
    {
        if (!$admin) {
            return null;
        }

        $isSuper = $forceSuper || (bool) $admin->super_admin;
        $role = $isSuper ? 'super_admin' : 'admin';
        $type = $isSuper ? 'super_admin' : 'admin';

        return [
            'type' => $type,
            'id' => (int) $admin->id,
            'role' => $role,
            'name' => $admin->name,
            'subtitle' => $isSuper ? 'Super Admin' : 'Admin',
            'campus' => $admin->admin_of,
        ];
    }

    private function mapStaff(?Staff $staff): ?array
    {
        if (!$staff) {
            return null;
        }

        return [
            'type' => 'teacher',
            'id' => (int) $staff->id,
            'role' => 'teacher',
            'name' => $staff->name,
            'subtitle' => trim((string) ($staff->designation ?? 'Teacher')) . ($staff->campus ? ' - ' . $staff->campus : ''),
            'campus' => $staff->campus,
        ];
    }

    private function mapStudent(?Student $student): ?array
    {
        if (!$student) {
            return null;
        }

        $classSection = trim(($student->class ?? '') . ($student->section ? ' - ' . $student->section : ''));

        return [
            'type' => 'student',
            'id' => (int) $student->id,
            'role' => 'student',
            'name' => $student->student_name,
            'subtitle' => $classSection !== '' ? $classSection : 'Student',
            'campus' => $student->campus,
        ];
    }

    private function mapSimple(?object $row, string $type, string $label): ?array
    {
        if (!$row) {
            return null;
        }

        return [
            'type' => $type,
            'id' => (int) $row->id,
            'role' => $type,
            'name' => $row->name ?? $row->student_name ?? 'User',
            'subtitle' => $label . (isset($row->campus) && $row->campus ? ' - ' . $row->campus : ''),
            'campus' => $row->campus ?? null,
        ];
    }

    public function formatMessageForActor(Message $message, ChatActor $actor): array
    {
        return $this->formatMessage($message, $actor);
    }

    private function formatMessage(Message $message, ChatActor $actor): array
    {
        $actorId = (int) $actor->id;
        $isMine = ($message->from_type === $actor->messageType && (int) $message->from_id === $actorId)
            || ($actor->messageType === 'super_admin' && $message->from_type === 'admin' && (int) $message->from_id === $actorId);

        return [
            'id' => $message->id,
            'from_type' => $message->from_type,
            'from_id' => (int) $message->from_id,
            'to_type' => $message->to_type,
            'to_id' => (int) $message->to_id,
            'is_mine' => $isMine,
            'isMine' => $isMine,
            'is_sent_by_me' => $isMine,
            'isSentByMe' => $isMine,
            'is_received_by_me' => !$isMine,
            'isReceivedByMe' => !$isMine,
            'direction' => $isMine ? 'outgoing' : 'incoming',
            'display_as' => $isMine ? 'sent' : 'received',
            'align' => $isMine ? 'right' : 'left',
            'bubble_align' => $isMine ? 'end' : 'start',
            'sender' => [
                'type' => $message->from_type,
                'id' => (int) $message->from_id,
            ],
            'receiver' => [
                'type' => $message->to_type,
                'id' => (int) $message->to_id,
            ],
            'text' => $message->text,
            'attachment_url' => $message->attachment_path
                ? (str_starts_with($message->attachment_path, 'storage/')
                    ? asset($message->attachment_path)
                    : asset('storage/' . $message->attachment_path))
                : null,
            'attachment_type' => $message->attachment_type,
            'created_at' => $message->created_at?->format('Y-m-d H:i'),
        ];
    }

    private function appendLegacyAdminSuperAdminClauses($outer, ChatActor $actor, array $peer, string $peerType, int $peerId): void
    {
        if ($peerType === 'super_admin' && $actor->messageType === 'teacher') {
            $outer->orWhere(function ($q) use ($actor, $peerId) {
                $q->where('from_type', 'teacher')->where('from_id', $actor->id)
                    ->where('to_type', 'admin')->where('to_id', $peerId);
            })->orWhere(function ($q) use ($actor, $peerId) {
                $q->where('from_type', 'admin')->where('from_id', $peerId)
                    ->where('to_type', 'teacher')->where('to_id', $actor->id);
            });
        }

        if ($actor->messageType === 'super_admin' && $peerType === 'teacher') {
            $outer->orWhere(function ($q) use ($actor, $peerId) {
                $q->where('from_type', 'admin')->where('from_id', $actor->id)
                    ->where('to_type', 'teacher')->where('to_id', $peerId);
            })->orWhere(function ($q) use ($actor, $peerId) {
                $q->where('from_type', 'teacher')->where('from_id', $peerId)
                    ->where('to_type', 'admin')->where('to_id', $actor->id);
            });
        }

        if ($peerType === 'super_admin' && in_array($actor->messageType, ['parent', 'student'], true)) {
            $outer->orWhere(function ($q) use ($actor, $peerId) {
                $q->where('from_type', $actor->messageType)->where('from_id', $actor->id)
                    ->where('to_type', 'admin')->where('to_id', $peerId);
            })->orWhere(function ($q) use ($actor, $peerId) {
                $q->where('from_type', 'admin')->where('from_id', $peerId)
                    ->where('to_type', $actor->messageType)->where('to_id', $actor->id);
            });
        }
    }

    /** @return array{0: ?string, 1: ?string} */
    private function storeAttachment(ChatActor $actor, ?UploadedFile $file): array
    {
        if (!$file) {
            return [null, null];
        }

        $folder = 'chat-attachments/' . $actor->messageType;
        $directory = storage_path('app/public/' . $folder);
        if (!file_exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $storedPath = $file->store($folder, 'public');
        $mime = $file->getClientMimeType();
        $attachmentType = str_starts_with($mime, 'image/')
            ? 'image'
            : ($mime === 'application/pdf' ? 'pdf' : 'document');

        return [$storedPath, $attachmentType];
    }

    private function roleLabel(string $role): string
    {
        return match ($role) {
            'super_admin' => 'Super Admin',
            'admin' => 'Admin',
            'accountant' => 'Accountant',
            'teacher' => 'Teachers',
            'parent' => 'Parents',
            'student' => 'Students',
            default => ucfirst($role),
        };
    }
}
