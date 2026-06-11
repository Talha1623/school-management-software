<?php

namespace App\Support;

use Illuminate\Contracts\Auth\Authenticatable;

class ChatActor
{
    public function __construct(
        public string $guard,
        public string $role,
        public string $messageType,
        public int $id,
        public string $name,
        public ?string $campus = null,
        public ?string $subtitle = null,
    ) {
    }

    public static function fromUser(string $guard, Authenticatable $user): self
    {
        $role = match ($guard) {
            'admin' => !empty($user->super_admin) ? 'super_admin' : 'admin',
            'staff' => 'teacher',
            'accountant' => 'accountant',
            'student' => 'student',
            'parent' => 'parent',
            'platform_super_admin' => 'platform_super_admin',
            default => $guard,
        };

        $messageType = match ($role) {
            'super_admin' => 'super_admin',
            'teacher' => 'teacher',
            default => $role,
        };

        $name = trim((string) ($user->name ?? $user->student_name ?? $user->email ?? 'User'));
        $campus = $user->campus ?? $user->admin_of ?? null;

        $subtitle = match ($role) {
            'super_admin' => 'Super Admin',
            'admin' => 'Admin',
            'accountant' => 'Accountant',
            'teacher' => trim((string) ($user->designation ?? 'Teacher')),
            'student' => trim((string) (($user->class ?? '') . ($user->section ? ' - ' . $user->section : ''))),
            'parent' => 'Parent',
            'platform_super_admin' => 'Platform Super Admin',
            default => ucfirst($role),
        };

        return new self(
            guard: $guard,
            role: $role,
            messageType: $messageType,
            id: (int) $user->getAuthIdentifier(),
            name: $name !== '' ? $name : 'User',
            campus: is_string($campus) && trim($campus) !== '' ? trim($campus) : null,
            subtitle: $subtitle !== '' ? $subtitle : null,
        );
    }

    public function peerKey(string $peerType, int $peerId): string
    {
        return $peerType . ':' . $peerId;
    }
}
