<?php

namespace App\Services;

use App\Models\ParentDeviceToken;
use App\Models\StaffDeviceToken;
use App\Models\StudentDeviceToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FirebasePushService
{
    public function sendToTokens(array $tokens, string $title, string $body, array $data = []): array
    {
        $result = [
            'tokens' => 0,
            'sent' => 0,
            'failed' => 0,
            'disabled' => false,
            'reason' => null,
        ];

        if (empty($tokens)) {
            $result['reason'] = 'no_tokens';
            return $result;
        }

        $projectId = (string) config('services.firebase.project_id', env('FIREBASE_PROJECT_ID', ''));
        $serviceAccountPath = (string) config('services.firebase.service_account_path', env('FIREBASE_SERVICE_ACCOUNT_PATH', ''));
        $accessToken = $this->getAccessToken($serviceAccountPath);

        if ($projectId === '' || $accessToken === null) {
            $result['disabled'] = true;
            $result['reason'] = $projectId === ''
                ? 'missing_project_id'
                : 'missing_service_account_access_token';
            Log::warning('FCM disabled: missing project id or service account access token', [
                'reason' => $result['reason'],
                'has_project_id' => $projectId !== '',
                'has_service_account_path' => $serviceAccountPath !== '',
            ]);
            return $result;
        }

        $uniqueTokens = array_values(array_unique(array_filter($tokens)));
        $result['tokens'] = count($uniqueTokens);

        $stringData = $this->normalizeDataPayload($data);

        foreach ($uniqueTokens as $token) {
            try {
                $message = [
                    'token' => $token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'android' => [
                        'priority' => 'high',
                    ],
                    'apns' => [
                        'headers' => [
                            'apns-priority' => '10',
                        ],
                    ],
                ];

                if ($stringData !== []) {
                    $message['data'] = $stringData;
                }

                $response = Http::timeout(15)
                    ->retry(2, 300)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Content-Type' => 'application/json',
                    ])->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                        'message' => $message,
                    ]);

                if (!$response->ok()) {
                    $result['failed']++;
                    if (str_contains($response->body(), 'UNREGISTERED') || str_contains($response->body(), 'INVALID_ARGUMENT')) {
                        $this->deactivateToken($token);
                    }
                    Log::warning('FCM send failed response', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                        'token_suffix' => substr($token, -12),
                    ]);
                } else {
                    $result['sent']++;
                    Log::info('FCM send success', [
                        'token_suffix' => substr($token, -12),
                    ]);
                }
            } catch (\Throwable $e) {
                $result['failed']++;
                Log::warning('FCM send failed', ['error' => $e->getMessage()]);
            }
        }

        Log::info('FCM send summary', $result);
        return $result;
    }

    private function getAccessToken(string $serviceAccountPath): ?string
    {
        $cacheKey = 'firebase_access_token_v1';
        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $json = $this->readServiceAccountJson($serviceAccountPath);
        if (!$json) {
            return null;
        }

        $clientEmail = $json['client_email'] ?? null;
        $privateKey = $json['private_key'] ?? null;
        $tokenUri = $json['token_uri'] ?? 'https://oauth2.googleapis.com/token';

        if (!$clientEmail || !$privateKey) {
            return null;
        }

        $now = time();
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $payload = [
            'iss' => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => $tokenUri,
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        $jwt = $this->createJwt($header, $payload, $privateKey);
        if (!$jwt) {
            return null;
        }

        try {
            $response = Http::asForm()->post($tokenUri, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if (!$response->ok()) {
                Log::warning('FCM token fetch failed', ['response' => $response->body()]);
                return null;
            }

            $token = $response->json('access_token');
            if (is_string($token) && $token !== '') {
                // Cache for ~55 minutes (token expires in 60 mins).
                Cache::put($cacheKey, $token, now()->addMinutes(55));
                return $token;
            }
            return null;
        } catch (\Throwable $e) {
            Log::warning('FCM token fetch exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function readServiceAccountJson(string $path): ?array
    {
        if ($path === '') {
            return null;
        }

        try {
            $fullPath = file_exists($path) ? $path : base_path($path);
            if (!file_exists($fullPath) && Storage::disk('local')->exists($path)) {
                $content = Storage::disk('local')->get($path);
            } else {
                $content = file_get_contents($fullPath);
            }

            if (!$content) {
                return null;
            }

            $decoded = json_decode($content, true);
            return is_array($decoded) ? $decoded : null;
        } catch (\Throwable $e) {
            Log::warning('Unable to read Firebase service account file', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function createJwt(array $header, array $payload, string $privateKey): ?string
    {
        $base64Header = $this->base64UrlEncode(json_encode($header));
        $base64Payload = $this->base64UrlEncode(json_encode($payload));
        $unsigned = $base64Header . '.' . $base64Payload;

        $signature = '';
        $ok = openssl_sign($unsigned, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        if (!$ok) {
            return null;
        }

        return $unsigned . '.' . $this->base64UrlEncode($signature);
    }

    private function base64UrlEncode(string $input): string
    {
        return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
    }

    /**
     * FCM data keys and values must be strings.
     *
     * @return array<string, string>
     */
    private function normalizeDataPayload(array $data): array
    {
        $normalized = [];
        foreach ($data as $key => $value) {
            if ($value === null) {
                continue;
            }
            $normalized[(string) $key] = is_scalar($value)
                ? (string) $value
                : json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return $normalized;
    }

    private function deactivateToken(string $token): void
    {
        foreach ([
            [StudentDeviceToken::class, 'student_device_tokens'],
            [ParentDeviceToken::class, 'parent_device_tokens'],
            [StaffDeviceToken::class, 'staff_device_tokens'],
        ] as [$model, $table]) {
            try {
                if (! \Illuminate\Support\Facades\Schema::hasTable($table)) {
                    continue;
                }
                $model::where('fcm_token', $token)->update(['is_active' => false]);
            } catch (\Throwable $e) {
                Log::warning("Failed to deactivate token on {$table}", ['error' => $e->getMessage()]);
            }
        }
    }
}

