<?php

namespace App\Services;

use App\Models\AdminRole;
use App\Models\Message;

class SystemNotificationService
{
    /**
     * Notify super admins (fallback: all admins) about an accountant action.
     */
    public function notifySuperAdminsFromAccountant(string $text, int $accountantId): void
    {
        $admins = AdminRole::query()
            ->where('super_admin', true)
            ->orderBy('id')
            ->get();

        if ($admins->isEmpty()) {
            $admins = AdminRole::query()->orderBy('id')->get();
        }

        foreach ($admins as $admin) {
            Message::create([
                'from_type' => 'accountant',
                'from_id' => $accountantId,
                'to_type' => 'admin',
                'to_id' => (int) $admin->id,
                'text' => $text,
                'read_at' => null,
            ]);
        }
    }

    /**
     * Send a confirmation/alert to the accountant inbox (header notifications).
     */
    public function notifyAccountant(int $accountantId, string $text): void
    {
        $adminId = AdminRole::query()
            ->where('super_admin', true)
            ->orderBy('id')
            ->value('id');

        if (!$adminId) {
            $adminId = AdminRole::query()->orderBy('id')->value('id');
        }

        if (!$adminId) {
            return;
        }

        Message::create([
            'from_type' => 'admin',
            'from_id' => (int) $adminId,
            'to_type' => 'accountant',
            'to_id' => $accountantId,
            'text' => $text,
            'read_at' => null,
        ]);
    }
}
