<?php

namespace App\Services;

class ChatPermissionService
{
    /** @var array<string, list<string>> */
    private const ALLOWED = [
        'super_admin' => ['super_admin', 'admin', 'teacher', 'parent', 'student'],
        'admin' => ['super_admin', 'admin', 'teacher'],
        'accountant' => ['accountant'],
        'teacher' => ['super_admin', 'admin', 'teacher', 'parent', 'student'],
        'parent' => ['super_admin', 'teacher'],
        'student' => ['super_admin', 'teacher'],
        'platform_super_admin' => ['super_admin', 'admin', 'teacher', 'parent', 'student'],
    ];

    public function canChat(string $senderRole, string $receiverRole): bool
    {
        $allowed = self::ALLOWED[$senderRole] ?? [];

        return in_array($receiverRole, $allowed, true)
            && in_array($senderRole, self::ALLOWED[$receiverRole] ?? [], true);
    }

    /** @return list<string> */
    public function allowedReceiverRoles(string $senderRole): array
    {
        return self::ALLOWED[$senderRole] ?? [];
    }
}
