<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudentDeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class StudentDeviceTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $student = $request->user();
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $validated = $request->validate([
            'fcm_token' => ['required', 'string', 'min:20'],
            'platform' => ['nullable', 'string', 'max:20'],
        ]);

        if (!Schema::hasTable('student_device_tokens')) {
            return response()->json([
                'success' => false,
                'message' => 'Device token table missing on current tenant database.',
            ], 500);
        }

        StudentDeviceToken::updateOrCreate(
            [
                'student_id' => $student->id,
                'fcm_token' => $validated['fcm_token'],
            ],
            [
                'platform' => $validated['platform'] ?? 'android',
                'is_active' => true,
                'last_used_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Device token saved successfully.',
        ]);
    }
}

