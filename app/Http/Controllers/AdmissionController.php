<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Campus;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Transport;
use App\Models\ParentAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AdmissionController extends Controller
{
    /**
     * Show the form for admitting a new student.
     */
    public function create(): View
    {
        // Get campuses from campuses table
        $campuses = Campus::orderBy('campus_name', 'asc')->pluck('campus_name');
        if ($campuses->isEmpty()) {
            // Fallback to other sources if campuses table is empty
            $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
            $campuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
            
            if ($campuses->isEmpty()) {
                $campuses = collect(['Main Campus', 'Branch Campus 1', 'Branch Campus 2']);
            }
        }

        // Get classes
        $classes = ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort()->values();
        if ($classes->isEmpty()) {
            $classes = collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']);
        }

        // Get sections (will be loaded via AJAX based on class)
        $sections = collect();

        // Generate next student code
        $nextStudentCode = $this->generateNextStudentCode();

        // Get transport routes
        $transportRoutes = Transport::orderBy('route_name', 'asc')->pluck('route_name')->unique()->values();

        return view('admission.admit-student', compact('campuses', 'classes', 'sections', 'nextStudentCode', 'transportRoutes'));
    }

    /**
     * Generate next student code in format ST0001-1, ST0001-2, etc.
     */
    private function generateNextStudentCode(array $usedCodes = []): string
    {
        // Get all students with pattern ST0001-X
        $students = Student::where('student_code', 'like', 'ST0001-%')
            ->whereNotNull('student_code')
            ->pluck('student_code')
            ->toArray();

        // Merge with used codes from current bulk operation
        $allCodes = array_merge($students, $usedCodes);

        if (empty($allCodes)) {
            // If no student code found, start with ST0001-1
            return 'ST0001-1';
        }

        // Extract numbers and find the maximum
        $maxNumber = 0;
        foreach ($allCodes as $code) {
            $parts = explode('-', $code);
            if (count($parts) == 2 && $parts[0] == 'ST0001') {
                $number = (int) $parts[1];
                if ($number > $maxNumber) {
                    $maxNumber = $number;
                }
            }
        }

        // Return next number
        return 'ST0001-' . ($maxNumber + 1);
    }

    /**
     * Get sections for a specific class (AJAX endpoint).
     */
    public function getSections(Request $request)
    {
        $class = $request->get('class');
        
        if (!$class) {
            return response()->json(['sections' => []]);
        }
        
        $sections = Section::where('class', $class)
            ->whereNotNull('name')
            ->distinct()
            ->pluck('name')
            ->sort()
            ->values();
        
        if ($sections->isEmpty()) {
            $sections = collect(['A', 'B', 'C', 'D', 'E']);
        }
        
        return response()->json(['sections' => $sections]);
    }

    /**
     * Get parent account details by Father ID Card (AJAX endpoint).
     */
    public function getParentByIdCard(Request $request)
    {
        $fatherIdCard = $request->get('father_id_card');
        
        if (!$fatherIdCard) {
            return response()->json([
                'success' => false,
                'message' => 'Father ID Card is required'
            ], 400);
        }
        
        // Find parent account by ID card number
        $parentAccount = ParentAccount::where('id_card_number', $fatherIdCard)->first();
        
        if ($parentAccount) {
            // Get connected students count
            $studentsCount = $parentAccount->students()->count();
            
            return response()->json([
                'success' => true,
                'found' => true,
                'parent' => [
                    'id' => $parentAccount->id,
                    'name' => $parentAccount->name,
                    'email' => $parentAccount->email,
                    'phone' => $parentAccount->phone,
                    'whatsapp' => $parentAccount->whatsapp,
                    'id_card_number' => $parentAccount->id_card_number,
                    'address' => $parentAccount->address,
                    'profession' => $parentAccount->profession,
                    'students_count' => $studentsCount,
                ],
                'message' => "Existing parent account found! This parent has {$studentsCount} child/children already connected."
            ]);
        }
        
        return response()->json([
            'success' => true,
            'found' => false,
            'message' => 'No existing parent account found with this ID Card. You can create a new parent account.'
        ]);
    }

    /**
     * Store a newly admitted student in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        // Check if creating parent account
        $createParentAccount = $request->input('create_parent_account') == '1';
        
        // Validation rules
        $rules = [
            'student_name' => ['required', 'string', 'max:255'],
            'surname_caste' => ['nullable', 'string', 'max:255'],
            'gender' => ['required', 'in:male,female,other'],
            'date_of_birth' => ['required', 'date'],
            'place_of_birth' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'father_id_card' => ['nullable', 'string', 'max:255'],
            'father_name' => ['required', 'string', 'max:255'],
            'father_email' => ['nullable', 'email', 'max:255'],
            'father_phone' => ['nullable', 'string', 'max:20'],
            'mother_phone' => ['nullable', 'string', 'max:20'],
            'whatsapp_number' => ['nullable', 'string', 'max:20'],
            'religion' => ['nullable', 'string', 'max:255'],
            'home_address' => ['nullable', 'string'],
            'b_form_number' => ['nullable', 'string', 'max:255'],
            'monthly_fee' => ['nullable', 'numeric', 'min:0'],
            'discounted_student' => ['nullable', 'boolean'],
            'transport_route' => ['nullable', 'string', 'max:255'],
            'admission_notification' => ['nullable', 'string', 'max:255'],
            'create_parent_account' => ['nullable', 'boolean'],
            'generate_other_fee' => ['nullable', 'string', 'max:255'],
            'student_code' => ['nullable', 'string', 'max:255'],
            'gr_number' => ['nullable', 'string', 'max:255'],
            'campus' => ['nullable', 'string', 'max:255'],
            'class' => ['required', 'string', 'max:255'],
            'section' => ['nullable', 'string', 'max:255'],
            'previous_school' => ['nullable', 'string', 'max:255'],
            'admission_date' => ['required', 'date'],
            'reference_remarks' => ['nullable', 'string'],
            'captured_photo' => ['nullable', 'string'],
        ];

        // Check if parent account already exists by Father ID Card
        $existingParentAccount = null;
        if (!empty($request->input('father_id_card'))) {
            $existingParentAccount = ParentAccount::where('id_card_number', $request->input('father_id_card'))->first();
        }
        
        // Add validation for parent password if creating parent account
        if ($createParentAccount && !$existingParentAccount) {
            $rules['parent_password'] = ['required', 'string', 'min:6'];
            $rules['father_email'] = ['required', 'email', 'max:255', 'unique:parent_accounts,email'];
        }

        $validated = $request->validate($rules);

        $photoPath = $this->handlePhotoUpload($request);

        // Check if parent account already exists by Father ID Card (re-check after validation)
        $parentAccountId = null;
        
        if (!empty($validated['father_id_card'])) {
            // Re-check existing parent account (in case it was created between validation and submission)
            $existingParentAccount = ParentAccount::where('id_card_number', $validated['father_id_card'])->first();
            
            if ($existingParentAccount) {
                // Parent account already exists, link student to it
                $parentAccountId = $existingParentAccount->id;
                
                // Update parent account details if new information is provided
                $updateData = [];
                if (!empty($validated['father_name']) && $existingParentAccount->name !== $validated['father_name']) {
                    $updateData['name'] = $validated['father_name'];
                }
                if (!empty($validated['father_email']) && $existingParentAccount->email !== $validated['father_email']) {
                    $updateData['email'] = $validated['father_email'];
                }
                if (!empty($validated['father_phone']) && $existingParentAccount->phone !== $validated['father_phone']) {
                    $updateData['phone'] = $validated['father_phone'];
                }
                if (!empty($validated['whatsapp_number']) && $existingParentAccount->whatsapp !== $validated['whatsapp_number']) {
                    $updateData['whatsapp'] = $validated['whatsapp_number'];
                }
                if (!empty($validated['home_address']) && $existingParentAccount->address !== $validated['home_address']) {
                    $updateData['address'] = $validated['home_address'];
                }
                
                if (!empty($updateData)) {
                    $existingParentAccount->update($updateData);
                }
            }
        }

        // Create new parent account only if:
        // 1. create_parent_account is checked AND
        // 2. No existing parent account found
        if ($createParentAccount && !$existingParentAccount) {
            // Password will be automatically hashed by ParentAccount model's setPasswordAttribute
            $parentAccount = ParentAccount::create([
                'name' => $validated['father_name'],
                'email' => $validated['father_email'],
                'password' => $validated['parent_password'], // Model will hash it automatically
                'phone' => $validated['father_phone'] ?? null,
                'whatsapp' => $validated['whatsapp_number'] ?? null,
                'id_card_number' => $validated['father_id_card'] ?? null,
                'address' => $validated['home_address'] ?? null,
            ]);
            
            $parentAccountId = $parentAccount->id;
        }

        // Prepare student data
        $studentData = [
            ...$validated,
            'photo' => $photoPath,
            'discounted_student' => $request->has('discounted_student'),
            'create_parent_account' => $createParentAccount,
            'parent_account_id' => $parentAccountId,
        ];

        // Hash B-Form Number as password if provided
        if (!empty($validated['b_form_number'])) {
            $studentData['password'] = $validated['b_form_number']; // Will be hashed automatically by Student model's setPasswordAttribute
        }

        Student::create($studentData);

        $successMessage = 'Student admitted successfully!';
        if ($existingParentAccount) {
            $studentsCount = $existingParentAccount->students()->count();
            $successMessage .= " Student has been linked to existing parent account ({$existingParentAccount->name}). This parent now has {$studentsCount} child/children.";
        } elseif ($createParentAccount && $parentAccountId) {
            $successMessage .= ' Parent account has been created and linked to the student.';
        }

        return redirect()
            ->route('admission.admit-student')
            ->with('success', $successMessage);
    }

    /**
     * Handle photo upload from file or live capture.
     */
    private function handlePhotoUpload(Request $request): ?string
    {
        if ($request->hasFile('photo')) {
            return $request->file('photo')->store('students/photos', 'public');
        }

        if ($request->filled('captured_photo')) {
            return $this->saveCapturedPhoto($request->input('captured_photo'));
        }

        return null;
    }

    /**
     * Save base64 captured photo to storage.
     */
    private function saveCapturedPhoto(string $base64Image): string
    {
        $imageData = base64_decode(
            preg_replace('#^data:image/\w+;base64,#i', '', $base64Image)
        );

        $filename = 'student_' . time() . '_' . uniqid() . '.jpg';
        $path = 'students/photos/' . $filename;

        Storage::disk('public')->put($path, $imageData);

        return $path;
    }

    /**
     * Show the form for bulk student admission.
     */
    public function bulkCreate(): View
    {
        // Get campuses from campuses table
        $campuses = Campus::orderBy('campus_name', 'asc')->pluck('campus_name');
        if ($campuses->isEmpty()) {
            // Fallback to other sources if campuses table is empty
            $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
            $campuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
            
            if ($campuses->isEmpty()) {
                $campuses = collect(['Main Campus', 'Branch Campus 1', 'Branch Campus 2']);
            }
        }

        // Get classes
        $classes = ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort()->values();
        if ($classes->isEmpty()) {
            $classes = collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']);
        }

        // Get sections (will be loaded via AJAX based on class)
        $sections = collect();

        return view('admission.admit-bulk-student', compact('campuses', 'classes', 'sections'));
    }

    /**
     * Store bulk students admission.
     */
    public function bulkStore(Request $request): RedirectResponse
    {
        try {
            $inputMethod = $request->input('input_method', 'manual');
            
            if ($inputMethod === 'csv') {
                // CSV upload will be handled later
                return redirect()
                    ->route('admission.admit-bulk-student')
                    ->with('error', 'CSV upload functionality will be available soon.');
            }

            // Manual entry
            $validated = $request->validate([
                'campus' => ['required', 'string', 'max:255'],
                'class' => ['required', 'string', 'max:255'],
                'section' => ['nullable', 'string', 'max:255'],
                'create_parent_accounts' => ['required', 'in:0,1'],
                'number_of_students' => ['required', 'integer', 'min:1', 'max:50'],
                'students' => ['required', 'array', 'min:1'],
                'students.*.student_code' => ['nullable', 'string', 'max:255'],
                'students.*.student_name' => ['required', 'string', 'max:255'],
                'students.*.gender' => ['required', 'in:male,female,other'],
                'students.*.father_name' => ['required', 'string', 'max:255'],
                'students.*.father_id_card' => ['nullable', 'string', 'max:255'],
                'students.*.father_phone' => ['nullable', 'string', 'max:20'],
                'students.*.mother_phone' => ['nullable', 'string', 'max:20'],
                'students.*.date_of_birth' => ['required', 'date'],
                'students.*.home_address' => ['nullable', 'string'],
                'students.*.monthly_fee' => ['nullable', 'numeric', 'min:0'],
                'students.*.arrears' => ['nullable', 'numeric', 'min:0'],
            ]);

            $campus = $validated['campus'];
            $class = $validated['class'];
            $section = $validated['section'] ?? null;
            $createParentAccounts = $request->input('create_parent_accounts', '0') == '1';
            $students = $validated['students'];

            $successCount = 0;
            $errorCount = 0;
            $errors = [];
            $usedStudentCodes = [];

            foreach ($students as $index => $studentData) {
                try {
                    $studentNumber = $index + 1;
                    
                    // Check if parent account exists or create new
                    $parentAccountId = null;
                    if ($createParentAccounts && !empty($studentData['father_id_card'])) {
                        $existingParent = ParentAccount::where('id_card_number', $studentData['father_id_card'])->first();
                        
                        if ($existingParent) {
                            $parentAccountId = $existingParent->id;
                        } else {
                            // Create new parent account
                            $parentEmail = 'parent_' . time() . '_' . $index . '_' . ($studentData['father_id_card'] ?? uniqid()) . '@school.com';
                            
                            $parentAccount = ParentAccount::create([
                                'name' => $studentData['father_name'],
                                'email' => $parentEmail,
                                'password' => $studentData['father_id_card'] ?? 'password123', // Model will hash it automatically
                                'phone' => $studentData['father_phone'] ?? null,
                                'id_card_number' => $studentData['father_id_card'] ?? null,
                                'address' => $studentData['home_address'] ?? null,
                            ]);
                            
                            $parentAccountId = $parentAccount->id;
                        }
                    }

                    // Generate student code if not provided
                    $studentCode = !empty($studentData['student_code']) 
                        ? $studentData['student_code'] 
                        : $this->generateNextStudentCode($usedStudentCodes);
                    
                    // Track used codes to avoid duplicates
                    $usedStudentCodes[] = $studentCode;

                    // Create student
                    Student::create([
                        'student_name' => $studentData['student_name'],
                        'gender' => $studentData['gender'],
                        'date_of_birth' => $studentData['date_of_birth'],
                        'home_address' => $studentData['home_address'] ?? null,
                        'father_name' => $studentData['father_name'],
                        'father_id_card' => $studentData['father_id_card'] ?? null,
                        'father_phone' => $studentData['father_phone'] ?? null,
                        'mother_phone' => $studentData['mother_phone'] ?? null,
                        'monthly_fee' => $studentData['monthly_fee'] ?? null,
                        'student_code' => $studentCode,
                        'campus' => $campus,
                        'class' => $class,
                        'section' => $section,
                        'parent_account_id' => $parentAccountId,
                        'password' => $studentData['father_id_card'] ?? $studentCode, // Will be hashed automatically
                    ]);

                    $successCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = "Student {$studentNumber}: " . $e->getMessage();
                }
            }

            $message = "Bulk admission completed! Successfully admitted {$successCount} student(s).";
            if ($errorCount > 0) {
                $message .= " {$errorCount} student(s) failed. Errors: " . implode('; ', array_slice($errors, 0, 5));
            }

            return redirect()
                ->route('admission.admit-bulk-student')
                ->with('success', $message)
                ->with('errors', $errors);

        } catch (\Exception $e) {
            return redirect()
                ->route('admission.admit-bulk-student')
                ->with('error', 'An error occurred: ' . $e->getMessage());
        }
    }
}

