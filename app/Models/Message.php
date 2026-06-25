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

    /** Unread real chat messages in admin mail/envelope dropdown. */
    public function scopeUnreadChatToAdmin(Builder $query, int $adminId): Builder
    {
        return $query
            ->where('to_type', 'admin')
            ->where('to_id', $adminId)
            ->whereNull('read_at')
            ->whereIn('from_type', ['teacher', 'student', 'parent', 'accountant'])
            ->forLiveChat();
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


