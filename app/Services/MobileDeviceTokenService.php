<?php

namespace App\Services;

use App\Models\MobileDeviceToken;
use App\Models\StaffDeviceToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class MobileDeviceTokenService
{
    /**
     * Accepts mobileToken object or flat deviceId/fcmToken keys from login body.
     *
     * @return array{device_id: ?string, fcm_token: ?string}
     */
    public function extractFromRequest(Request $request): array
    {
        $mobileToken = $request->input('mobileToken');
        if (!is_array($mobileToken)) {
            $mobileToken = $request->input('mobile_token');
        }

        $deviceId = null;
        $fcmToken = null;

        if (is_array($mobileToken)) {
            $deviceId = $mobileToken['deviceId'] ?? $mobileToken['device_id'] ?? null;
            $fcmToken = $mobileToken['fcmToken'] ?? $mobileToken['fcm_token'] ?? null;
        }

        if ($deviceId === null) {
            $deviceId = $request->input('deviceId', $request->input('device_id'));
        }
        if ($fcmToken === null) {
            $fcmToken = $request->input('fcmToken', $request->input('fcm_token'));
        }

        $deviceId = is_string($deviceId) ? trim($deviceId) : (is_numeric($deviceId) ? trim((string) $deviceId) : '');
        $fcmToken = is_string($fcmToken) ? trim($fcmToken) : (is_numeric($fcmToken) ? trim((string) $fcmToken) : '');

        return [
            'device_id' => $deviceId !== '' ? $deviceId : null,
            'fcm_token' => $fcmToken !== '' ? $fcmToken : null,
        ];
    }

    public function storeForUser(string $userType, int $userId, Request $request): ?MobileDeviceToken
    {
        if (!Schema::hasTable('mobile_device_tokens')) {
            return null;
        }

        ['device_id' => $deviceId, 'fcm_token' => $fcmToken] = $this->extractFromRequest($request);

        if (!$deviceId && !$fcmToken) {
            return null;
        }

        if (!$deviceId) {
            $deviceId = 'unknown-device-' . $userType . '-' . $userId;
        }

        $record = MobileDeviceToken::updateOrCreate(
            [
                'user_type' => $userType,
                'user_id' => $userId,
                'device_id' => $deviceId,
            ],
            [
                'fcm_token' => $fcmToken,
                'last_login_at' => now(),
            ]
        );

        // Keep staff push table in sync for teacher app login tokens.
        if ($userType === 'teacher' && $fcmToken && Schema::hasTable('staff_device_tokens')) {
            StaffDeviceToken::updateOrCreate(
                [
                    'staff_id' => $userId,
                    'fcm_token' => $fcmToken,
                ],
                [
                    'platform' => 'mobile',
                    'is_active' => true,
                    'last_used_at' => now(),
                ]
            );
        }

        return $record;
    }
}
