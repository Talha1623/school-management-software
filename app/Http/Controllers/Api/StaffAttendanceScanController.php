<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\StaffAttendanceController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Staff / teacher ID card QR scan API (check-in / check-out).
 */
class StaffAttendanceScanController extends StaffAttendanceController
{
    /**
     * POST /api/teacher/attendance/scan-id-card
     * POST /api/staff/attendance/scan-id-card
     *
     * Body: barcode | scan | emp_id | id_card (QR from staff card), or self_check_in: true
     */
    public function scanIdCard(Request $request): JsonResponse
    {
        return $this->scanIdCardApi($request);
    }
}
