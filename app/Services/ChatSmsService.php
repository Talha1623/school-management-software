<?php

namespace App\Services;

use App\Models\Accountant;
use App\Models\AdminRole;
use App\Models\GeneralSetting;
use App\Models\Message;
use App\Models\ParentAccount;
use App\Models\Staff;
use App\Models\Student;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatSmsService
{
    /**
     * SMS alert to the chat message recipient (who sent + short preview).
     */
    public function notifyForChatMessage(Message $message): void
    {
        if (!config('sms.enabled') || trim((string) config('sms.api_url')) === '') {
            return;
        }

        $phone = $this->resolvePhone((string) $message->to_type, (int) $message->to_id);
        if ($phone === null || $phone === '') {
            return;
        }

        $senderName = $this->resolveDisplayName((string) $message->from_type, (int) $message->from_id);
        $preview = $this->messagePreview($message);
        $school = trim((string) (GeneralSetting::getSettings()->school_name ?? 'School'));
        if ($school === '') {
            $school = 'School';
        }

        $body = sprintf('%s: %s sent you a Live Chat message', $school, $senderName);
        if ($preview !== '') {
            $body .= ': ' . $preview;
        }

        $this->send($phone, $body);
    }

    public function send(string $phone, string $message): bool
    {
        $phone = $this->normalizePhone($phone);
        $message = trim($message);
        if ($phone === '' || $message === '') {
            return false;
        }

        $apiUrl = trim((string) config('sms.api_url'));
        if ($apiUrl === '') {
            return false;
        }

        $phoneParam = (string) config('sms.phone_param', 'phone');
        $messageParam = (string) config('sms.message_param', 'message');
        $keyParam = (string) config('sms.key_param', 'api_key');
        $senderParam = (string) config('sms.sender_param', 'sender');

        $payload = [
            $phoneParam => $phone,
            $messageParam => $message,
        ];

        $apiKey = trim((string) config('sms.api_key', ''));
        if ($apiKey !== '') {
            $payload[$keyParam] = $apiKey;
        }

        $senderId = trim((string) config('sms.sender_id', ''));
        if ($senderId !== '') {
            $payload[$senderParam] = $senderId;
        }

        try {
            $method = strtoupper((string) config('sms.method', 'GET'));
            $response = $method === 'POST'
                ? Http::timeout(15)->asForm()->post($apiUrl, $payload)
                : Http::timeout(15)->get($apiUrl, $payload);

            if (!$response->successful()) {
                Log::warning('Chat SMS failed', [
                    'phone' => $phone,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('Chat SMS exception', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function messagePreview(Message $message): string
    {
        $text = trim((string) ($message->text ?? ''));
        if ($text !== '') {
            return strlen($text) > 120 ? substr($text, 0, 117) . '...' : $text;
        }

        return match ((string) ($message->attachment_type ?? '')) {
            'image' => 'Sent an image',
            'pdf' => 'Sent a PDF',
            'document' => 'Sent a document',
            default => $message->attachment_path ? 'Sent an attachment' : '',
        };
    }

    private function resolveDisplayName(string $type, int $id): string
    {
        return match ($type) {
            'admin', 'super_admin' => trim((string) (AdminRole::find($id)?->name ?? 'Admin')),
            'teacher' => trim((string) (Staff::find($id)?->name ?? 'Teacher')),
            'student' => trim((string) (Student::find($id)?->student_name ?? 'Student')),
            'parent' => trim((string) (ParentAccount::find($id)?->name ?? 'Parent')),
            'accountant' => trim((string) (Accountant::find($id)?->name ?? 'Accountant')),
            default => 'User',
        } ?: 'User';
    }

    private function resolvePhone(string $type, int $id): ?string
    {
        $phone = match ($type) {
            'admin', 'super_admin' => AdminRole::find($id)?->phone,
            'teacher' => Staff::find($id)?->phone,
            'student' => Student::find($id)?->father_phone ?: Student::find($id)?->whatsapp_number,
            'parent' => ParentAccount::find($id)?->phone ?: ParentAccount::find($id)?->whatsapp,
            'accountant' => null,
            default => null,
        };

        $phone = trim((string) ($phone ?? ''));

        return $phone !== '' ? $phone : null;
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '92') && strlen($digits) >= 12) {
            return $digits;
        }

        if (str_starts_with($digits, '0')) {
            return '92' . substr($digits, 1);
        }

        if (strlen($digits) === 10) {
            return '92' . $digits;
        }

        return $digits;
    }
}
