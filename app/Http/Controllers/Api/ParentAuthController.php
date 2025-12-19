<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParentAccount;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class ParentAuthController extends Controller
{
    /**
     * Parent Login API
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        try {
            // Validate request
            $credentials = $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required', 'string'],
            ]);

            // Trim and normalize email (case-insensitive search)
            $email = trim(strtolower($credentials['email']));

            // Find parent by email (case-insensitive, trimmed comparison)
            $parent = ParentAccount::whereRaw('LOWER(TRIM(email)) = ?', [$email])->first();

            // Check if parent exists
            if (!$parent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials',
                    'token' => null,
                ], 200);
            }

            // Check if password exists
            if (empty($parent->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password not set. Please contact administrator.',
                    'token' => null,
                ], 200);
            }

            // Check password
            if (!Hash::check($credentials['password'], $parent->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials',
                    'token' => null,
                ], 200);
            }

            // Check if parent has login access
            if (!$parent->hasLoginAccess()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have login access. Please contact administrator.',
                    'token' => null,
                ], 200);
            }

            // Check if parent already has a stored token
            if (!empty($parent->api_token)) {
                // Return existing stored token
                return response()->json([
                    'success' => true,
                    'message' => 'Login successful',
                    'token' => $parent->api_token,
                ], 200);
            }

            // Delete all existing Sanctum tokens for this parent
            $parent->tokens()->delete();

            // Create new token (without expiration - never expires)
            try {
                $token = $parent->createToken('parent-api-token', ['*'])->plainTextToken;
            } catch (\Exception $tokenException) {
                \Log::error('Token creation failed: ' . $tokenException->getMessage());
                throw $tokenException;
            }

            // Store the token in parent_accounts table for future logins
            $parent->api_token = $token;
            $parent->save();

            // Return token with success message
            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'token' => $token,
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'token' => null,
            ], 200);
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Parent login error: ' . $e->getMessage(), [
                'email' => $request->input('email'),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during login: ' . $e->getMessage(),
                'token' => null,
            ], 200);
        }
    }

    /**
     * Parent Logout API
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $parent = $request->user();
            
            // Revoke current token
            $parent->currentAccessToken()->delete();
            
            // Clear stored api_token
            $parent->api_token = null;
            $parent->save();

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
     * Get Parent Profile API
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function profile(Request $request): JsonResponse
    {
        try {
            $parent = $request->user();

            if (!$parent) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'token' => null,
                ], 404);
            }

            // Get connected students
            $students = $parent->students()->get();
            $studentsData = $students->map(function($student) {
                return [
                    'id' => $student->id,
                    'student_name' => $student->student_name,
                    'student_code' => $student->student_code,
                    'class' => $student->class,
                    'section' => $student->section,
                    'campus' => $student->campus,
                    'gender' => $student->gender,
                    'date_of_birth' => $student->date_of_birth ? $student->date_of_birth->format('Y-m-d') : null,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Profile retrieved successfully',
                'data' => [
                    'parent' => [
                        'id' => $parent->id,
                        'name' => $parent->name,
                        'email' => $parent->email,
                        'phone' => $parent->phone,
                        'whatsapp' => $parent->whatsapp,
                        'id_card_number' => $parent->id_card_number,
                        'address' => $parent->address,
                        'profession' => $parent->profession,
                    ],
                    'students' => $studentsData,
                    'total_students' => $students->count(),
                    'has_login_access' => $parent->hasLoginAccess(),
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
     * Get Parent Personal Details (For Mobile App)
     * Returns only essential personal information
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function personalDetails(Request $request): JsonResponse
    {
        try {
            $parent = $request->user();

            if (!$parent) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'token' => null,
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Personal details retrieved successfully',
                'data' => [
                    'name' => $parent->name ?? null,
                    'email' => $parent->email ?? null,
                    'phone' => $parent->phone ?? null,
                    'whatsapp' => $parent->whatsapp ?? null,
                    'id_card_number' => $parent->id_card_number ?? null,
                    'address' => $parent->address ?? null,
                    'profession' => $parent->profession ?? null,
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
     * Get All Students List API
     * Returns all students connected to this parent account
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function students(Request $request): JsonResponse
    {
        try {
            $parent = $request->user();

            if (!$parent) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'token' => null,
                ], 404);
            }

            // Get all connected students
            $students = $parent->students()->orderBy('class', 'asc')
                ->orderBy('section', 'asc')
                ->orderBy('student_name', 'asc')
                ->get();

            // Format students data
            $studentsData = $students->map(function($student) {
                // Get photo URL if exists
                $photoUrl = null;
                if ($student->photo) {
                    $photoUrl = asset('storage/' . $student->photo);
                    // Convert to full URL if needed
                    if (!filter_var($photoUrl, FILTER_VALIDATE_URL)) {
                        $photoUrl = url($photoUrl);
                    }
                }

                return [
                    'id' => $student->id,
                    'student_name' => $student->student_name,
                    'surname_caste' => $student->surname_caste,
                    'full_name' => trim($student->student_name . ' ' . ($student->surname_caste ?? '')),
                    'student_code' => $student->student_code,
                    'gr_number' => $student->gr_number,
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
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Students list retrieved successfully',
                'data' => [
                    'parent_id' => $parent->id,
                    'parent_name' => $parent->name,
                    'total_students' => $students->count(),
                    'students' => $studentsData,
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving students list: ' . $e->getMessage(),
                'token' => null,
            ], 200);
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
            $parent = $request->user();

            if (!$parent) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'token' => null,
                ], 404);
            }

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
            if (empty($parent->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password not set. Please contact administrator.',
                    'token' => null,
                ], 400);
            }

            // Verify current password
            if (!Hash::check($validated['current_password'], $parent->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect.',
                    'token' => null,
                ], 400);
            }

            // Check if new password is same as current password
            if (Hash::check($validated['new_password'], $parent->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'New password must be different from current password.',
                    'token' => null,
                ], 400);
            }

            // Update password
            $parent->password = $validated['new_password'];
            $parent->save();

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

