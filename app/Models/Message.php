<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    /** Automated alerts — show in notification bell, not in Live Chat threads. */
    public const NOTIFICATION_FROM_TYPES = [
        'staff_notification',
        'accountant_notification',
    ];

    /** Legacy accountant rows stored as from_type=accountant before accountant_notification existed. */
    private const LEGACY_ACCOUNTANT_NOTIFICATION_PATTERNS = [
        '%recorded %fee payment%',
        '%generated transport fee%',
        '%generated custom fee%',
        '%generated monthly fee%',
        '%updated task%',
        '%balance sheet settlement%',
    ];

    protected $fillable = [
        'from_type',
        'from_id',
        'to_type',
        'to_id',
        'text',
        'attachment_path',
        'attachment_type',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    /**
     * Exclude system/activity notifications from Live Chat conversation lists.
     */
    public function scopeForLiveChat(Builder $query): Builder
    {
        return $query->whereNotIn('from_type', self::NOTIFICATION_FROM_TYPES)
            ->where(function (Builder $q) {
                $q->where('from_type', '!=', 'accountant')
                    ->orWhere(function (Builder $legacy) {
                        $legacy->where('from_type', 'accountant')
                            ->where(function (Builder $textFilter) {
                                foreach (self::LEGACY_ACCOUNTANT_NOTIFICATION_PATTERNS as $pattern) {
                                    $textFilter->where('text', 'not like', $pattern);
                                }
                            });
                    });
            });
    }

    /**
     * Messages delivered to an admin user (super admins may receive admin + super_admin rows).
     */
    public function scopeToAdminInbox(Builder $query, int $adminId, bool $isSuperAdmin = false): Builder
    {
        $query->where('to_id', $adminId);

        if ($isSuperAdmin) {
            return $query->whereIn('to_type', ['admin', 'super_admin']);
        }

        return $query->where('to_type', 'admin');
    }

    /** Unread real chat messages in admin mail/envelope dropdown. */
    public function scopeUnreadChatToAdmin(Builder $query, int $adminId, bool $isSuperAdmin = false): Builder
    {
        return $query
            ->toAdminInbox($adminId, $isSuperAdmin)
            ->whereNull('read_at')
            ->whereIn('from_type', ['teacher', 'student', 'parent', 'accountant'])
            ->forLiveChat();
    }

    public static function unreadLiveChatCountForAdminInbox(int $adminId, bool $isSuperAdmin = false): int
    {
        return (int) static::query()
            ->toAdminInbox($adminId, $isSuperAdmin)
            ->whereNull('read_at')
            ->forLiveChat()
            ->count();
    }

    /**
     * @return array<string, int> Keys: "{from_type}:{from_id}"
     */
    public static function unreadCountsBySenderForAdminInbox(int $adminId, bool $isSuperAdmin = false): array
    {
        return static::query()
            ->toAdminInbox($adminId, $isSuperAdmin)
            ->whereNull('read_at')
            ->forLiveChat()
            ->selectRaw('from_type, from_id, COUNT(*) as unread_count')
            ->groupBy('from_type', 'from_id')
            ->get()
            ->mapWithKeys(fn ($row) => ["{$row->from_type}:{$row->from_id}" => (int) $row->unread_count])
            ->all();
    }

    public static function unreadLiveChatCount(string $toType, int $toId): int
    {
        return (int) static::query()
            ->where('to_type', $toType)
            ->where('to_id', $toId)
            ->whereNull('read_at')
            ->forLiveChat()
            ->count();
    }

    /**
     * @return array<string, int> Keys: "{from_type}:{from_id}"
     */
    public static function unreadCountsBySender(string $toType, int $toId): array
    {
        return static::query()
            ->where('to_type', $toType)
            ->where('to_id', $toId)
            ->whereNull('read_at')
            ->forLiveChat()
            ->selectRaw('from_type, from_id, COUNT(*) as unread_count')
            ->groupBy('from_type', 'from_id')
            ->get()
            ->mapWithKeys(fn ($row) => ["{$row->from_type}:{$row->from_id}" => (int) $row->unread_count])
            ->all();
    }

    public function liveChatRecipientType(): string
    {
        return $this->from_type === 'staff_notification' ? 'teacher' : (string) $this->from_type;
    }

    public function liveChatUrl(): string
    {
        return route('live-chat', [
            'recipient_type' => $this->liveChatRecipientType(),
            'recipient_id' => $this->from_id,
        ]);
    }
}


