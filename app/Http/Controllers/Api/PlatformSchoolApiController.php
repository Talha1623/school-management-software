<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlatformSchool;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlatformSchoolApiController extends Controller
{
    /**
     * Public schools listing API for platform admin integrations.
     */
    public function list(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $schools = PlatformSchool::on('landlord')
            ->when(!empty($validated['status']), function ($query) use ($validated) {
                $query->where('status', $validated['status']);
            })
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Schools list fetched successfully.',
            'data' => $schools,
        ], 200);
    }
}
