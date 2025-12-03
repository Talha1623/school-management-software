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

            // Revoke all existing tokens (optional - for single device login)
            // $teacher->tokens()->delete();

            // Create token
            $token = $teacher->createToken('teacher-api-token')->plainTextToken;

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
            // Revoke current token
            $request->user()->currentAccessToken()->delete();

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
}

