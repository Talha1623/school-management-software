<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class TeacherAuthController extends Controller
{
    /**
     * Teacher Login API
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

            // Find staff by email (case-insensitive)
            $teacher = Staff::where('email', $credentials['email'])->first();

            // Check if staff exists
            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials',
                    'token' => null,
                ], 200);
            }

            // Check if designation is teacher (case-insensitive)
            $designation = strtolower(trim($teacher->designation ?? ''));
            if ($designation !== 'teacher') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can login.',
                    'token' => null,
                ], 200);
            }

            // Check if password exists
            if (empty($teacher->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password not set. Please contact administrator.',
                    'token' => null,
                ], 200);
            }

            // Check password
            if (!Hash::check($credentials['password'], $teacher->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials',
                    'token' => null,
                ], 200);
            }

            // Check if teacher has dashboard access
            if (!$teacher->hasDashboardAccess()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have login access. Please contact administrator.',
                    'token' => null,
                ], 200);
            }

            // Check if teacher already has a stored token
            if (!empty($teacher->api_token)) {
                // Return existing stored token
                return response()->json([
                    'success' => true,
                    'message' => 'Login successful',
                    'token' => $teacher->api_token,
                ], 200);
            }

            // Delete all existing Sanctum tokens for this teacher
            $teacher->tokens()->delete();

            // Create new token without expiration (never expires)
            $token = $teacher->createToken('teacher-api-token', ['*'], null)->plainTextToken;

            // Store the token in staff table for future logins
            $teacher->api_token = $token;
            $teacher->save();

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
                'token' => null,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during login',
                'token' => null,
            ], 200);
        }
    }

    /**
     * Teacher Logout API
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();
            
            // Revoke current token
            $teacher->currentAccessToken()->delete();
            
            // Clear stored api_token
            $teacher->api_token = null;
            $teacher->save();

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
     * Get Teacher Profile API
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function profile(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();

            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'token' => null,
                ], 404);
            }

            // Get photo URL
            $photoUrl = null;
            if ($teacher->photo) {
                $photoUrl = Storage::url($teacher->photo);
                // Convert to full URL if needed
                if (!filter_var($photoUrl, FILTER_VALIDATE_URL)) {
                    $photoUrl = url($photoUrl);
                }
            }

            // Get assigned subjects
            $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                ->orderBy('subject_name', 'asc')
                ->orderBy('class', 'asc')
                ->orderBy('section', 'asc')
                ->get();

            $subjectsData = $assignedSubjects->map(function($subject) {
                return [
                    'subject_id' => $subject->id,
                    'subject_name' => $subject->subject_name,
                    'campus' => $subject->campus,
                    'class' => $subject->class,
                    'section' => $subject->section,
                ];
            });

            // Get unique classes and sections
            $classesTaught = $assignedSubjects->pluck('class')->unique()->sort()->values();
            $sectionsTaught = $assignedSubjects->pluck('section')->unique()->sort()->values();

            return response()->json([
                'success' => true,
                'message' => 'Profile retrieved successfully',
                'data' => [
                    'teacher' => [
                        // Basic Information
                        'id' => $teacher->id,
                        'name' => $teacher->name,
                        'email' => $teacher->email,
                        'emp_id' => $teacher->emp_id,
                        'designation' => $teacher->designation,
                        'role' => $teacher->designation,
                        'campus' => $teacher->campus,
                        
                        // Contact Information
                        'phone' => $teacher->phone,
                        'whatsapp' => $teacher->whatsapp,
                        
                        // Personal Information
                        'father_husband_name' => $teacher->father_husband_name,
                        'gender' => $teacher->gender,
                        'photo' => $photoUrl,
                        
                        // Professional Information
                        'qualification' => $teacher->qualification,
                        'joining_date' => $teacher->joining_date ? $teacher->joining_date->format('Y-m-d') : null,
                        
                        // Assigned Subjects
                        'assigned_subjects' => $subjectsData,
                        'total_subjects' => $assignedSubjects->count(),
                        'classes_taught' => $classesTaught,
                        'sections_taught' => $sectionsTaught,
                    ],
                    'has_dashboard_access' => $teacher->hasDashboardAccess(),
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
     * Get Teacher Personal Details (For Mobile App)
     * Returns only essential personal information
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function personalDetails(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();

            if (!$teacher) {
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
                    'father_name' => $teacher->father_husband_name ?? null,
                    'email' => $teacher->email ?? null,
                    'phone' => $teacher->phone ?? null,
                    'id_card' => $teacher->emp_id ?? null,
                    'gender' => $teacher->gender ?? null,
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
            $teacher = $request->user();

            if (!$teacher) {
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
            if (empty($teacher->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password not set. Please contact administrator.',
                    'token' => null,
                ], 400);
            }

            // Verify current password
            if (!Hash::check($validated['current_password'], $teacher->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect.',
                    'token' => null,
                ], 400);
            }

            // Check if new password is same as current password
            if (Hash::check($validated['new_password'], $teacher->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'New password must be different from current password.',
                    'token' => null,
                ], 400);
            }

            // Update password
            $teacher->password = $validated['new_password'];
            $teacher->save();

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

