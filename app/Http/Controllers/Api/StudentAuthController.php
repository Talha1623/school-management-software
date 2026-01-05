<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class StudentAuthController extends Controller
{
    /**
     * Student Login API
     * Student Code = email (in request)
     * B-Form Number = password
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        try {
            // Validate request
            $credentials = $request->validate([
                'email' => ['required', 'string'], // This will be student_code
                'password' => ['required', 'string'], // This will be b_form_number
            ]);

            // Trim and normalize student_code
            $studentCode = trim($credentials['email']);
            $password = trim($credentials['password']);

            // Find student by student_code (case-insensitive, trimmed comparison)
            // Try case-insensitive match first
            $student = Student::whereRaw('LOWER(TRIM(student_code)) = LOWER(?)', [$studentCode])->first();
            
            // If not found, try without TRIM (in case database doesn't support it)
            if (!$student) {
                $student = Student::whereRaw('LOWER(student_code) = LOWER(?)', [$studentCode])->first();
            }
            
            // If still not found, try exact match (in case of special characters or exact case match)
            if (!$student) {
                $student = Student::where('student_code', $studentCode)->first();
            }

            // Check if student exists
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials',
                    'token' => null,
                ], 200);
            }

            // Check if password is set
            if (empty($student->password)) {
                // If password is not set but b_form_number exists, set it as password
                if (!empty($student->b_form_number)) {
                    $student->password = $student->b_form_number;
                    $student->save();
                    // Refresh the model to get the hashed password
                    $student->refresh();
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Password not set. Please contact administrator.',
                        'token' => null,
                    ], 200);
                }
            }

            // Check password (B-Form Number)
            // First check if password matches the stored hashed password
            $passwordMatches = Hash::check($password, $student->password);
            
            // If password doesn't match, also check if it matches b_form_number directly
            // (in case b_form_number was updated but password wasn't)
            if (!$passwordMatches && !empty($student->b_form_number)) {
                // Check if the provided password matches the b_form_number
                if ($password === $student->b_form_number) {
                    // Update password with b_form_number
                    $student->password = $student->b_form_number;
                    $student->save();
                    $student->refresh();
                    $passwordMatches = true;
                }
            }

            if (!$passwordMatches) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials',
                    'token' => null,
                ], 200);
            }

            // Check if student has login access
            if (!$student->hasLoginAccess()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have login access. Please contact administrator.',
                    'token' => null,
                ], 200);
            }

            // Refresh student model to get latest data from database
            $student->refresh();
            
            // Check if student already has a stored token
            // This must be checked BEFORE creating new token to ensure same token every time
            // Check both model attribute and direct DB query for reliability
            $storedToken = null;
            
            // First try model attribute
            if (!empty($student->api_token)) {
                $storedToken = $student->api_token;
            } else {
                // If model doesn't have it, check database directly
                $storedToken = DB::table('students')
                    ->where('id', $student->id)
                    ->value('api_token');
            }
            
            // If token exists, return it immediately - SAME TOKEN EVERY TIME
            if (!empty($storedToken) && trim($storedToken) !== '') {
                return response()->json([
                    'success' => true,
                    'message' => 'Login successful',
                    'token' => trim($storedToken),
                ], 200);
            }

            // Delete all existing Sanctum tokens for this student
            $student->tokens()->delete();

            // Create new token (without expiration - never expires)
            try {
                $token = $student->createToken('student-api-token', ['*'])->plainTextToken;
            } catch (\Exception $tokenException) {
                \Log::error('Token creation failed for student: ' . $tokenException->getMessage(), [
                    'student_code' => $studentCode,
                    'student_id' => $student->id,
                    'trace' => $tokenException->getTraceAsString()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create authentication token. Please try again.',
                    'token' => null,
                ], 200);
            }

            // Store the token in students table for future logins
            // This ensures the same token is returned on every login
            try {
                // Save using DB::table() for reliability
                DB::table('students')
                    ->where('id', $student->id)
                    ->update(['api_token' => $token]);
                
                // Refresh model to sync with database
                $student->refresh();
                
                // Verify token was saved
                $savedToken = DB::table('students')
                    ->where('id', $student->id)
                    ->value('api_token');
                
                if (empty($savedToken) || trim($savedToken) !== $token) {
                    \Log::error('CRITICAL: Token was not saved properly', [
                        'student_id' => $student->id,
                        'student_code' => $studentCode
                    ]);
                }
            } catch (\Exception $e) {
                // If column doesn't exist, log warning but continue
                if (strpos($e->getMessage(), 'Unknown column') !== false || strpos($e->getMessage(), 'Column not found') !== false) {
                    \Log::warning('api_token column does not exist. Please run: php artisan migrate', [
                        'student_id' => $student->id,
                        'error' => $e->getMessage()
                    ]);
                } else {
                    \Log::error('Failed to save api_token: ' . $e->getMessage(), [
                        'student_id' => $student->id,
                        'student_code' => $studentCode
                    ]);
                }
                // Continue - token is still valid for this session
            }

            // Return token with success message
            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'token' => $token,
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', $e->errors()['email'] ?? $e->errors()['password'] ?? ['Invalid input']),
                'token' => null,
            ], 200);
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Student login error: ' . $e->getMessage(), [
                'student_code' => $request->input('email'),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return more specific error message in development, generic in production
            $errorMessage = config('app.debug') 
                ? 'An error occurred during login: ' . $e->getMessage()
                : 'An error occurred during login. Please try again later.';
            
            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'token' => null,
            ], 200);
        }
    }

    /**
     * Student Logout API
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            // Get authenticated user - ensure it's a Student instance
            $user = $request->user();
            
            if (!$user || !($user instanceof Student)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid user type. Student authentication required.',
                    'token' => null,
                ], 403);
            }
            
            $student = $user;
            
            // Revoke current token
            $student->currentAccessToken()->delete();
            
            // Clear stored api_token
            $student->api_token = null;
            $student->save();

            return response()->json([
                'success' => true,
                'message' => 'Logout successful',
                'token' => null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during logout',
                'token' => null,
            ], 200);
        }
    }

    /**
     * Get Student Profile API
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function profile(Request $request): JsonResponse
    {
        try {
            // Get authenticated user - ensure it's a Student instance
            $user = $request->user();
            
            // Check if user is actually a Student model instance
            // This prevents errors when a Staff or other user type token is used
            if (!$user || !($user instanceof Student)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid user type. Student authentication required.',
                    'token' => null,
                ], 403);
            }
            
            $student = $user;

            // Get photo URL if exists
            $photoUrl = null;
            if ($student->photo) {
                $photoUrl = asset('storage/' . $student->photo);
                // Convert to full URL if needed
                if (!filter_var($photoUrl, FILTER_VALIDATE_URL)) {
                    $photoUrl = url($photoUrl);
                }
            }

            // Calculate Attendance (Current Year)
            $currentYear = Carbon::now()->year;
            $attendanceRecords = \App\Models\StudentAttendance::where('student_id', $student->id)
                ->whereYear('attendance_date', $currentYear)
                ->get();

            $totalDays = $attendanceRecords->whereIn('status', ['Present', 'Absent'])->count();
            $presentDays = $attendanceRecords->where('status', 'Present')->count();
            $absentDays = $attendanceRecords->where('status', 'Absent')->count();
            $attendancePercentage = 0;
            
            if ($totalDays > 0) {
                $attendancePercentage = round(($presentDays / $totalDays) * 100, 2);
            }

            // Calculate Fee Information (Current Year)
            $feeYear = $currentYear;
            $totalFee = $student->monthly_fee ? (float) $student->monthly_fee * 12 : 0.0; // Annual fee (monthly * 12)
            
            $payments = \App\Models\StudentPayment::where('student_code', $student->student_code)
                ->whereYear('payment_date', $feeYear)
                ->get();

            $paidFee = (float) $payments->sum('payment_amount');
            $discount = (float) $payments->sum('discount');
            $lateFee = (float) $payments->sum('late_fee');
            
            // Remaining fee = Total fee - Paid - Discount + Late Fee
            $remainingFee = max($totalFee - $paidFee - $discount + $lateFee, 0.0);
            
            // Dues fee (same as remaining fee, but explicitly named)
            $duesFee = $remainingFee;

            return response()->json([
                'success' => true,
                'message' => 'Profile retrieved successfully',
                'data' => [
                    'id' => $student->id,
                    'student_name' => $student->student_name,
                    'surname_caste' => $student->surname_caste,
                    'full_name' => trim($student->student_name . ' ' . ($student->surname_caste ?? '')),
                    'student_code' => $student->student_code,
                    'gr_number' => $student->gr_number,
                    'email' => $student->email,
                    'class' => $student->class,
                    'section' => $student->section,
                    'campus' => $student->campus,
                    'gender' => $student->gender,
                    'date_of_birth' => $student->date_of_birth ? $student->date_of_birth->format('Y-m-d') : null,
                    'date_of_birth_formatted' => $student->date_of_birth ? $student->date_of_birth->format('d M Y') : null,
                    'age' => $student->date_of_birth ? Carbon::parse($student->date_of_birth)->age : null,
                    'admission_date' => $student->admission_date ? $student->admission_date->format('Y-m-d') : null,
                    'admission_date_formatted' => $student->admission_date ? $student->admission_date->format('d M Y') : null,
                    'photo' => $photoUrl,
                    'monthly_fee' => $student->monthly_fee ? (float) $student->monthly_fee : null,
                    'discounted_student' => (bool) $student->discounted_student,
                    'transport_route' => $student->transport_route,
                    'b_form_number' => $student->b_form_number,
                    'religion' => $student->religion,
                    'place_of_birth' => $student->place_of_birth,
                    'home_address' => $student->home_address,
                    'previous_school' => $student->previous_school,
                    'father_name' => $student->father_name,
                    'father_email' => $student->father_email,
                    'father_phone' => $student->father_phone,
                    'mother_phone' => $student->mother_phone,
                    'whatsapp_number' => $student->whatsapp_number,
                    'has_login_access' => $student->hasLoginAccess(),
                    // Classes Attended Count (Current Year)
                    'classes_attended' => $presentDays,
                    // Remaining Fee (Current Year)
                    'remaining_fee' => $remainingFee,
                    // Attendance Information (Current Year)
                    'attendance' => [
                        'total_days' => $totalDays,
                        'present_days' => $presentDays,
                        'absent_days' => $absentDays,
                        'attendance_percentage' => $attendancePercentage,
                        'year' => $currentYear,
                    ],
                    // Fee Information (Current Year)
                    'fee' => [
                        'total_fee' => $totalFee,
                        'paid_fee' => $paidFee,
                        'remaining_fee' => $remainingFee,
                        'dues_fee' => $duesFee,
                        'discount' => $discount,
                        'late_fee' => $lateFee,
                        'year' => $feeYear,
                    ],
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving profile: ' . $e->getMessage(),
                'token' => null,
            ], 200);
        }
    }

    /**
     * Get Student Personal Details (For Mobile App)
     * Returns only essential personal information
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function personalDetails(Request $request): JsonResponse
    {
        try {
            // Get authenticated user - ensure it's a Student instance
            $user = $request->user();
            
            if (!$user || !($user instanceof Student)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid user type. Student authentication required.',
                    'token' => null,
                ], 403);
            }
            
            $student = $user;

            // Get photo URL if exists
            $photoUrl = null;
            if ($student->photo) {
                $photoUrl = asset('storage/' . $student->photo);
                if (!filter_var($photoUrl, FILTER_VALIDATE_URL)) {
                    $photoUrl = url($photoUrl);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Personal details retrieved successfully',
                'data' => [
                    'student_name' => $student->student_name ?? null,
                    'surname_caste' => $student->surname_caste ?? null,
                    'full_name' => trim(($student->student_name ?? '') . ' ' . ($student->surname_caste ?? '')),
                    'student_code' => $student->student_code ?? null,
                    'email' => $student->email ?? null,
                    'class' => $student->class ?? null,
                    'section' => $student->section ?? null,
                    'campus' => $student->campus ?? null,
                    'gender' => $student->gender ?? null,
                    'date_of_birth' => $student->date_of_birth ? $student->date_of_birth->format('Y-m-d') : null,
                    'photo' => $photoUrl,
                    'father_name' => $student->father_name ?? null,
                    'father_phone' => $student->father_phone ?? null,
                    'mother_phone' => $student->mother_phone ?? null,
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving personal details: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }

    /**
     * Change Password API
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function changePassword(Request $request): JsonResponse
    {
        try {
            // Get authenticated user - ensure it's a Student instance
            $user = $request->user();
            
            if (!$user || !($user instanceof Student)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid user type. Student authentication required.',
                    'token' => null,
                ], 403);
            }
            
            $student = $user;

            // Validate request
            $validated = $request->validate([
                'current_password' => ['required', 'string'],
                'new_password' => ['required', 'string', 'min:6'],
                'confirm_password' => ['required', 'string', 'same:new_password'],
            ], [
                'current_password.required' => 'Current password is required.',
                'new_password.required' => 'New password is required.',
                'new_password.min' => 'New password must be at least 6 characters.',
                'confirm_password.required' => 'Confirm password is required.',
                'confirm_password.same' => 'Confirm password must match new password.',
            ]);

            // Check if password exists
            if (empty($student->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password not set. Please contact administrator.',
                    'token' => null,
                ], 400);
            }

            // Verify current password
            if (!Hash::check($validated['current_password'], $student->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect.',
                    'token' => null,
                ], 400);
            }

            // Check if new password is same as current password
            if (Hash::check($validated['new_password'], $student->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'New password must be different from current password.',
                    'token' => null,
                ], 400);
            }

            // Update password
            $student->password = $validated['new_password'];
            $student->save();

            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully.',
                'data' => [],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
                'token' => $request->bearerToken(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while changing password: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }
}

