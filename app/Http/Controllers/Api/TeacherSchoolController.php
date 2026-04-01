<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WebsiteSetting;
use App\Models\SalarySetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeacherSchoolController extends Controller
{
	/**
	 * Get school timing info for teachers.
	 * Returns parsed start/end if available and raw text fallback.
	 */
	public function timings(Request $request): JsonResponse
	{
		$teacher = $request->user();
		if (!$teacher || !$teacher->isTeacher()) {
			return response()->json([
				'success' => false,
				'message' => 'Access denied. Only teachers can access this endpoint.',
				'token' => null,
			], 403);
		}

		$website = WebsiteSetting::getSettings();
		$salary = class_exists(SalarySetting::class) ? SalarySetting::getSettings() : null;

		$rawTiming = trim((string)($website->school_timing ?? ''));
		[$startTime, $endTime] = $this->parseTimingRange($rawTiming);

		return response()->json([
			'success' => true,
			'message' => 'School timing fetched successfully.',
			'data' => [
				'raw_timing' => $rawTiming,
				'start_time' => $startTime, // e.g., 08:00
				'end_time' => $endTime,     // e.g., 13:30
				'late_arrival_time' => $salary->late_arrival_time ?? null,
				'early_exit_time' => $salary->early_exit_time ?? null,
				'timezone' => config('app.timezone'),
			],
		], 200);
	}

	/**
	 * Parse "HH:MM(am/pm) - HH:MM(am/pm)" or "HH:MM - HH:MM" into 24h times.
	 */
	private function parseTimingRange(string $range): array
	{
		if ($range === '') {
			return [null, null];
		}

		// Normalize
		$normalized = strtolower(trim($range));
		$normalized = preg_replace('/\s+to\s+/',' - ', $normalized);

		$parts = array_map('trim', explode('-', $normalized));
		if (count($parts) !== 2) {
			return [null, null];
		}

		return [ $this->parseTime($parts[0]), $this->parseTime($parts[1]) ];
	}

	/**
	 * Parse a time token into 24h HH:MM. Accepts "8am", "8:00 am", "08:00", etc.
	 */
	private function parseTime(string $token): ?string
	{
		$token = trim($token);
		if ($token === '') {
			return null;
		}

		// Add space before am/pm if needed (e.g., 8am -> 8 am)
		$token = preg_replace('/(am|pm)$/i', ' $1', $token);

		$ts = strtotime($token);
		if ($ts === false) {
			return null;
		}
		return date('H:i', $ts);
	}
}

