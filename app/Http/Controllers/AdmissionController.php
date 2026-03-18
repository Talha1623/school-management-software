<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Campus;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Transport;
use App\Models\ParentAccount;
use App\Models\FeeType;
use App\Models\CustomFee;
use App\Models\StudentPayment;
use App\Models\StudentDiscount;
use App\Models\AdvanceFee;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Maatwebsite\Excel\Facades\Excel;

class AdmissionController extends Controller
{
    /**
     * Show the form for admitting a new student.
     */
    public function create(): View
    {
        // Get campuses from campuses table
        $campuses = Campus::orderBy('campus_name', 'asc')
            ->pluck('campus_name')
            ->values();

        // Get classes (filter by selected campus if provided)
        $classes = collect();
        $selectedCampus = old('campus');
        if ($selectedCampus) {
            $classes = ClassModel::whereNotNull('class_name')
                ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($selectedCampus))])
                ->distinct()
                ->pluck('class_name')
                ->sort()
                ->values();
        }

        // Get sections (will be loaded via AJAX based on class)
        $sections = collect();

        // Generate next student code (only if campus is selected)
        $nextStudentCode = $selectedCampus
            ? $this->generateNextStudentCode($selectedCampus)
            : null;

        // Get transport routes
        $transportRoutes = Transport::orderBy('route_name', 'asc')->pluck('route_name')->unique()->values();

        // Get fee types (filtered by campus if selected)
        $feeTypesQuery = FeeType::query();
        if ($selectedCampus) {
            $feeTypesQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($selectedCampus))]);
        }
        $feeTypes = $feeTypesQuery->orderBy('fee_name', 'asc')->pluck('fee_name')->unique()->values();

        return view('admission.admit-student', compact('campuses', 'classes', 'sections', 'nextStudentCode', 'transportRoutes', 'feeTypes'));
    }

    /**
     * Get transport route fare (AJAX)
     */
    public function getRouteFare(Request $request)
    {
        $routeName = $request->get('route');
        $campus = $request->get('campus');
        
        if (!$routeName) {
            return response()->json(['fare' => 0]);
        }
        
        $transportQuery = Transport::where('route_name', $routeName);
        if ($campus) {
            $transportQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        $transport = $transportQuery->first();
        
        if ($transport) {
            return response()->json(['fare' => $transport->route_fare]);
        }
        
        return response()->json(['fare' => 0]);
    }

    /**
     * Get transport routes by campus (AJAX)
     */
    public function getTransportRoutesByCampus(Request $request): JsonResponse
    {
        $campus = $request->get('campus');

        $query = Transport::query();
        if ($campus) {
            $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }

        $routes = $query->orderBy('route_name', 'asc')
            ->pluck('route_name')
            ->unique()
            ->values();

        return response()->json(['routes' => $routes]);
    }

    /**
     * Get fee types by campus (AJAX)
     */
    public function getFeeTypesByCampus(Request $request): JsonResponse
    {
        $campus = $request->get('campus');

        $query = FeeType::query();
        if ($campus) {
            $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }

        $feeTypes = $query->orderBy('fee_name', 'asc')
            ->pluck('fee_name')
            ->unique()
            ->values();

        return response()->json(['fee_types' => $feeTypes]);
    }

    /**
     * Generate next student code per campus (e.g., ST1-001, ST2-001)
     */
    private function generateNextStudentCode(?string $campus, array $usedCodes = []): string
    {
        $prefix = $this->resolveCampusCodePrefix($campus);

        // Get all students with pattern PREFIX-XXX
        $students = Student::where('student_code', 'like', $prefix . '-%')
            ->whereNotNull('student_code')
            ->pluck('student_code')
            ->toArray();

        // Also include any historical codes from payments (covers deleted students)
        $paymentCodes = StudentPayment::where('student_code', 'like', $prefix . '-%')
            ->whereNotNull('student_code')
            ->pluck('student_code')
            ->toArray();

        // Merge with used codes from current bulk operation (same prefix only)
        $usedCodes = array_filter($usedCodes, function ($code) use ($prefix) {
            return stripos($code, $prefix . '-') === 0;
        });
        $allCodes = array_unique(array_merge($students, $paymentCodes, $usedCodes));

        if (empty($allCodes)) {
            // If no student code found, start with PREFIX-001
            return $prefix . '-001';
        }

        // Extract numbers and find the maximum
        $maxNumber = 0;
        foreach ($allCodes as $code) {
            if (preg_match('/^' . preg_quote($prefix, '/') . '-(\d+)$/i', $code, $matches)) {
                $number = (int) $matches[1];
                $maxNumber = max($maxNumber, $number);
            }
        }

        // Return next number
        return $prefix . '-' . str_pad($maxNumber + 1, 3, '0', STR_PAD_LEFT);
    }

    private function resolveCampusCodePrefix(?string $campus): string
    {
        $campus = trim((string) $campus);
        if ($campus !== '') {
            $campusRecord = Campus::whereRaw('LOWER(TRIM(campus_name)) = ?', [strtolower($campus)])->first();
            if ($campusRecord && !empty($campusRecord->code_prefix)) {
                return strtoupper(trim($campusRecord->code_prefix));
            }

            // Fallback: if campus name contains digits, use ST + digits
            if (preg_match('/(\d+)/', $campus, $matches)) {
                $prefix = 'ST' . $matches[1];
                // Save it to campus record for future use
                if ($campusRecord) {
                    $campusRecord->code_prefix = $prefix;
                    $campusRecord->save();
                }
                return strtoupper($prefix);
            }

            // If no code_prefix and no digits in name, find next available campus number
            // IMPORTANT: Check both existing campuses AND student/payment codes to avoid reusing deleted campus numbers
            $usedCampusNumbers = [];
            
            // Check from existing campuses with code_prefix (only active campuses, not deleted)
            $campusesWithPrefix = Campus::whereNotNull('code_prefix')
                ->where('code_prefix', 'like', 'ST%')
                ->get();
            
            foreach ($campusesWithPrefix as $campusWithPrefix) {
                if (preg_match('/^ST(\d+)$/i', $campusWithPrefix->code_prefix, $matches)) {
                    $campusNum = (int) $matches[1];
                    $usedCampusNumbers[] = $campusNum;
                }
            }
            
            // Also check from student codes to find any used campus numbers (even if campus is deleted)
            $studentCodes = Student::whereNotNull('student_code')
                ->where('student_code', 'like', 'ST%-%')
                ->pluck('student_code')
                ->toArray();
            
            // Also check from payment codes (covers deleted students/campuses)
            // This is CRITICAL: Payment codes preserve history even after campus/student deletion
            $paymentCodes = StudentPayment::whereNotNull('student_code')
                ->where('student_code', 'like', 'ST%-%')
                ->distinct()
                ->pluck('student_code')
                ->toArray();
            
            $allCodes = array_unique(array_merge($studentCodes, $paymentCodes));
            
            // Extract campus numbers from all codes
            foreach ($allCodes as $code) {
                $code = trim($code);
                if (empty($code)) continue;
                
                // Match pattern ST1-001, ST2-001, etc. to extract campus number
                if (preg_match('/^ST(\d+)-(\d+)$/i', $code, $matches)) {
                    $campusNum = (int) $matches[1];
                    if ($campusNum > 0 && !in_array($campusNum, $usedCampusNumbers)) {
                        $usedCampusNumbers[] = $campusNum;
                    }
                }
            }
            
            // Sort used numbers for easier debugging
            sort($usedCampusNumbers);
            
            // IMPORTANT: If no codes found but we have existing campuses, ensure we don't reuse
            // This handles the case where campus was deleted but no student/payment codes exist
            if (empty($allCodes) && !empty($campusesWithPrefix)) {
                // Already handled by existing campuses check above
            }
            
            // Find next sequential campus number (always use max + 1, never reuse deleted numbers)
            $nextCampusNumber = 1;
            if (!empty($usedCampusNumbers)) {
                $maxCampusNumber = max($usedCampusNumbers);
                // Always use next sequential number after max (no gaps, no reuse)
                $nextCampusNumber = $maxCampusNumber + 1;
            }
            $prefix = 'ST' . $nextCampusNumber;
            
            // Save it to campus record for future use
            if ($campusRecord) {
                $campusRecord->code_prefix = $prefix;
                $campusRecord->save();
            }
            
            return strtoupper($prefix);
        }

        // If no campus provided, return ST (shouldn't happen in normal flow)
        return 'ST';
    }

    /**
     * Get next student code by campus (AJAX).
     */
    public function getNextStudentCode(Request $request): JsonResponse
    {
        $campus = $request->get('campus');
        if (!$campus) {
            return response()->json(['code' => '']);
        }

        return response()->json([
            'code' => $this->generateNextStudentCode($campus),
        ]);
    }

    /**
     * Parse date from various formats (Excel, CSV, etc.)
     */
    private function parseDate($dateString): ?string
    {
        if (empty($dateString)) {
            return null;
        }
        
        // If it's already a valid date format (YYYY-MM-DD)
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateString)) {
            try {
                $date = \Carbon\Carbon::createFromFormat('Y-m-d', $dateString);
                return $date->format('Y-m-d');
            } catch (\Exception $e) {
                // Continue to try other formats
            }
        }
        
        // Try common date formats
        $formats = [
            'Y-m-d',
            'd/m/Y',
            'm/d/Y',
            'd-m-Y',
            'm-d-Y',
            'Y/m/d',
            'd M Y',
            'M d, Y',
        ];
        
        foreach ($formats as $format) {
            try {
                $date = \Carbon\Carbon::createFromFormat($format, $dateString);
                return $date->format('Y-m-d');
            } catch (\Exception $e) {
                continue;
            }
        }
        
        // Try Excel date serial number (days since 1900-01-01)
        if (is_numeric($dateString)) {
            try {
                $excelDate = \Carbon\Carbon::create(1900, 1, 1)->addDays((int)$dateString - 2);
                return $excelDate->format('Y-m-d');
            } catch (\Exception $e) {
                // Not an Excel date
            }
        }
        
        // Try strtotime as last resort
        try {
            $timestamp = strtotime($dateString);
            if ($timestamp !== false) {
                return date('Y-m-d', $timestamp);
            }
        } catch (\Exception $e) {
            // Failed
        }
        
        return null;
    }

    private function normalizeIdCard(?string $idCard): array
    {
        $digitMap = [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ];
        $cleaned = trim(strtr((string) $idCard, $digitMap));
        $lower = strtolower($cleaned);
        $normalized = str_replace(['-', ' ', '_', '.', '/'], '', $lower);

        return [$cleaned, $lower, $normalized];
    }

    private function findParentAccountByIdCard(?string $idCard): ?ParentAccount
    {
        if (empty($idCard)) {
            return null;
        }

        [$cleaned, $lower, $normalized] = $this->normalizeIdCard($idCard);

        return ParentAccount::where(function ($query) use ($lower, $normalized) {
            $query->whereRaw('LOWER(TRIM(id_card_number)) = ?', [$lower])
                ->orWhereRaw(
                    'LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(id_card_number), "-", ""), " ", ""), "_", ""), ".", ""), "/", "")) = ?',
                    [$normalized]
                )
                ->orWhereRaw(
                    'LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(id_card_number), "-", ""), " ", ""), "_", ""), ".", ""), "/", "")) LIKE ?',
                    ['%' . $normalized . '%']
                );
        })->first();
    }

    /**
     * Get sections for a specific class (AJAX endpoint).
     */
    public function getSections(Request $request)
    {
        $class = $request->get('class');
        $campus = $request->get('campus');
        
        if (!$class) {
            return response()->json(['sections' => []]);
        }
        
        // First verify that the class exists in ClassModel (not deleted)
        $classQuery = ClassModel::whereRaw('LOWER(TRIM(COALESCE(class_name, ""))) = ?', [strtolower(trim($class))]);
        if ($campus) {
            $classQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        $classExists = $classQuery
            ->exists();
        
        if (!$classExists) {
            // Class doesn't exist (was deleted), return empty sections
            return response()->json(['sections' => []]);
        }
        
        // Use case-insensitive matching for class (same as other parts of the system)
        $sectionsQuery = Section::whereRaw('LOWER(TRIM(COALESCE(class, ""))) = ?', [strtolower(trim($class))])
            ->whereNotNull('name')
            ->where('name', '!=', '');
        if ($campus) {
            $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        $sections = $sectionsQuery->distinct()->pluck('name')->sort()->values();
        
        // If no sections found in Section model, try to get from students table
        if ($sections->isEmpty()) {
            $hasSection = \Schema::hasColumn('students', 'section');
            if ($hasSection) {
                try {
                    $studentsQuery = \App\Models\Student::whereRaw('LOWER(TRIM(COALESCE(class, ""))) = ?', [strtolower(trim($class))])
                        ->whereNotNull('section')
                        ->where('section', '!=', '');
                    if ($campus) {
                        $studentsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
                    }
                    $sections = $studentsQuery->distinct()->pluck('section')->sort()->values();
                } catch (\Exception $e) {
                    $sections = collect();
                }
            }
        }
        
        // Only return empty if truly no sections found (don't return default sections)
        return response()->json(['sections' => $sections]);
    }

    /**
     * Get classes for a specific campus (AJAX endpoint).
     */
    public function getClassesByCampus(Request $request): JsonResponse
    {
        $campus = $request->get('campus');
        if (!$campus) {
            return response()->json(['classes' => []]);
        }

        $classes = ClassModel::whereNotNull('class_name')
            ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))])
            ->distinct()
            ->pluck('class_name')
            ->sort()
            ->values();

        return response()->json(['classes' => $classes]);
    }

    /**
     * Get parent account details by Father ID Card (AJAX endpoint).
     */
    public function getParentByIdCard(Request $request)
    {
        $fatherIdCard = $request->get('father_id_card');
        $campus = $request->get('campus');
        
        if (!$fatherIdCard) {
            return response()->json([
                'success' => false,
                'message' => 'Father ID Card is required'
            ], 400);
        }
        
        // IMPORTANT: Only search for parent account if campus is selected
        // This prevents returning parent data when campus is not selected
        if (empty($campus) || trim($campus) === '') {
            return response()->json([
                'success' => true,
                'found' => false,
                'message' => 'Please select a campus first to search for parent account.'
            ]);
        }
        
        // Find parent account by ID card number (normalized)
        $parentAccount = $this->findParentAccountByIdCard($fatherIdCard);
        
        if ($parentAccount) {
            // Get connected students count for the selected campus
            $studentsCount = $parentAccount->students()
                ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))])
                ->count();
            
            // Also check total students count (across all campuses)
            $totalStudentsCount = $parentAccount->students()->count();
            
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
                    'total_students_count' => $totalStudentsCount,
                ],
                'message' => "Existing parent account found! This parent has {$studentsCount} child/children in this campus ({$totalStudentsCount} total)."
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
    public function store(Request $request): JsonResponse|RedirectResponse
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
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_reason' => ['nullable', 'string', 'max:255'],
            'transport_route' => ['nullable', 'string', 'max:255'],
            'transport_fare' => ['nullable', 'numeric', 'min:0'],
            'admission_notification' => ['nullable', 'string', 'max:255'],
            'create_parent_account' => ['nullable', 'boolean'],
            'generate_other_fee' => ['nullable', 'string', 'max:255'],
            'generate_admission_fee' => ['nullable', 'string', 'max:255'],
            'admission_fee_amount' => ['nullable', 'numeric', 'min:0'],
            'fee_type' => ['nullable', 'string', 'max:255'],
            'other_fee_amount' => ['nullable', 'numeric', 'min:0'],
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
            $existingParentAccount = $this->findParentAccountByIdCard($request->input('father_id_card'));
        }
        
        // Add validation for parent password if creating parent account
        if ($createParentAccount && !$existingParentAccount) {
            $rules['parent_password'] = ['nullable', 'string', 'min:6'];
            $rules['father_email'] = ['required', 'email', 'max:255', 'unique:parent_accounts,email'];
        }

        try {
            $validated = $request->validate($rules);
        } catch (ValidationException $e) {
            // Return JSON response for AJAX requests with validation errors
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }
            throw $e;
        }

        $photoPath = $this->handlePhotoUpload($request);

        // Normalize Father ID Card for consistent matching/storage
        if (!empty($validated['father_id_card'])) {
            [$cleanedIdCard] = $this->normalizeIdCard($validated['father_id_card']);
            $validated['father_id_card'] = $cleanedIdCard;
        }

        // Check if parent account already exists by Father ID Card (re-check after validation)
        $parentAccountId = null;
        
        if (!empty($validated['father_id_card'])) {
            // Re-check existing parent account (in case it was created between validation and submission)
            $existingParentAccount = $this->findParentAccountByIdCard($validated['father_id_card']);
            
            if ($existingParentAccount) {
                // Parent account already exists, link student to it
                $parentAccountId = $existingParentAccount->id;
                
                // Update parent account details if new information is provided
                $updateData = [];
                if (!empty($validated['father_name']) && $existingParentAccount->name !== $validated['father_name']) {
                    $updateData['name'] = $validated['father_name'];
                }
                if (!empty($validated['father_email']) && $existingParentAccount->email !== $validated['father_email']) {
                    // Check if email already exists for another parent account
                    $emailExists = ParentAccount::where('email', $validated['father_email'])
                        ->where('id', '!=', $existingParentAccount->id)
                        ->exists();
                    
                    if (!$emailExists) {
                        $updateData['email'] = $validated['father_email'];
                    }
                    // If email exists for another account, skip updating email (silently ignore)
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
                $this->ensureAdvanceFeeForParent($existingParentAccount);
            }
        }

        // Create new parent account only if:
        // 1. create_parent_account is checked AND
        // 2. No existing parent account found
        if ($createParentAccount && !$existingParentAccount) {
            // Password will be automatically hashed by ParentAccount model's setPasswordAttribute
            // Default password is "parent" if not provided
            $parentPassword = !empty($validated['parent_password']) ? $validated['parent_password'] : 'parent';
            
            $parentAccount = ParentAccount::create([
                'name' => $validated['father_name'],
                'email' => $validated['father_email'],
                'password' => $parentPassword, // Model will hash it automatically
                'phone' => $validated['father_phone'] ?? null,
                'whatsapp' => $validated['whatsapp_number'] ?? null,
                'id_card_number' => $validated['father_id_card'] ?? null,
                'address' => $validated['home_address'] ?? null,
            ]);

            $this->ensureAdvanceFeeForParent($parentAccount);
            
            $parentAccountId = $parentAccount->id;
        } else {
            // Even if not creating parent account, ensure AdvanceFee exists for father_id_card
            if (!empty($validated['father_id_card'])) {
                $this->ensureAdvanceFeeForFatherIdCard(
                    $validated['father_id_card'],
                    $validated['father_name'] ?? null,
                    $validated['father_phone'] ?? null
                );
            }
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

        if (empty($studentData['student_code']) && !empty($validated['campus'])) {
            $studentData['student_code'] = $this->generateNextStudentCode($validated['campus']);
            // Avoid reusing a code that already has payments (e.g. old student data)
            while (StudentPayment::where('student_code', $studentData['student_code'])->exists()) {
                $studentData['student_code'] = $this->generateNextStudentCode($validated['campus'], [$studentData['student_code']]);
            }
        } elseif (!empty($studentData['student_code'])) {
            // Block manual code reuse if payment history exists
            if (StudentPayment::where('student_code', $studentData['student_code'])->exists()) {
                $message = 'Student code already has payment history. Please use a new code or clear old payments.';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message,
                    ], 422);
                }
                return redirect()
                    ->route('admission.admit-student')
                    ->withInput()
                    ->with('error', $message);
            }
        }

        if (empty($studentData['password'])) {
            // Default student password when not provided
            $studentData['password'] = 'student';
        }

        $student = Student::create($studentData);
        // Remove any auto-generated monthly fees immediately after student creation
        $this->removeAutoGeneratedMonthlyFees($student);

        $discountAmount = ($validated['discounted_student'] ?? false)
            ? (float) ($validated['discount_amount'] ?? 0)
            : 0.0;
        if ($discountAmount > 0 && !empty($student->student_code)) {
            $discountTitle = trim((string) ($validated['discount_reason'] ?? ''));
            if ($discountTitle === '') {
                $discountTitle = 'Admission Discount';
            }
            $discountExists = StudentDiscount::where('student_code', $student->student_code)
                ->where('discount_title', $discountTitle)
                ->where('discount_amount', $discountAmount)
                ->exists();
            if (!$discountExists) {
                StudentDiscount::create([
                    'student_code' => $student->student_code,
                    'discount_title' => $discountTitle,
                    'discount_amount' => $discountAmount,
                    'created_by' => auth()->check() ? (auth()->user()->name ?? null) : null,
                ]);
            }
        }
        $printUrl = route('student.print', $student);
        $feeVoucherUrl = null;
        if (!empty($student->student_code)) {
            $feeVoucherUrl = route('accounting.fee-voucher.print', [
                'student_code' => $student->student_code
            ]);
        }

        $accountantName = 'System';
        if (auth()->guard('accountant')->check()) {
            $accountantName = auth()->guard('accountant')->user()->name ?? 'System';
        } elseif (auth()->guard('admin')->check()) {
            $accountantName = auth()->guard('admin')->user()->name ?? 'System';
        }

        $admissionDate = !empty($validated['admission_date'])
            ? Carbon::parse($validated['admission_date'])
            : Carbon::now();
        $admissionMonth = $admissionDate->format('F');
        $admissionYear = $admissionDate->format('Y');
        $defaultDueDate = $admissionDate->copy()->addDays(15)->format('Y-m-d');

        if (!empty($student->student_code)) {
            // Fees are generated later from Fee Payment; do not auto-generate on admission.
        }

        if (!empty($student->student_code)) {
            $this->createGeneratedAdmissionFees($student, $validated, $admissionDate->format('Y-m-d'), $accountantName);
        }
        
        // Remove any auto-generated monthly fees again after all fee generation (in case any were created)
        // This ensures no monthly fees are added during admission - they should only be generated manually via Fee Payment
        $this->removeAutoGeneratedMonthlyFees($student);

        // Save custom fee if "Generate Other Fee" is "Yes" and fee type and amount are provided
        if ($request->input('generate_other_fee') == '1' && 
            !empty($validated['fee_type']) && 
            !empty($validated['other_fee_amount'])) {
            
            CustomFee::create([
                'campus' => $validated['campus'] ?? null,
                'class' => $validated['class'] ?? null,
                'section' => $validated['section'] ?? null,
                'fee_type' => $validated['fee_type'],
                'amount' => $validated['other_fee_amount'],
            ]);
        }

        $successMessage = 'Student admitted successfully!';
        if ($existingParentAccount) {
            $studentsCount = $existingParentAccount->students()->count();
            $successMessage .= " Student has been linked to existing parent account ({$existingParentAccount->name}). This parent now has {$studentsCount} child/children.";
        } elseif ($createParentAccount && $parentAccountId) {
            $successMessage .= ' Parent account has been created and linked to the student.';
        }

        // Return JSON response for AJAX requests
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'print_url' => $printUrl,
                'fee_voucher_url' => $feeVoucherUrl
            ]);
        }

        return redirect()
            ->route('admission.admit-student')
            ->with('success', $successMessage)
            ->with('print_url', $printUrl)
            ->with('fee_voucher_url', $feeVoucherUrl);
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

    private function removeAutoGeneratedMonthlyFees(Student $student): void
    {
        if (empty($student->student_code)) {
            return;
        }

        // Remove any auto-generated monthly fees for this student
        // Monthly fees should only be generated manually via Fee Payment, not automatically during admission
        StudentPayment::where('student_code', $student->student_code)
            ->where('method', 'Generated')
            ->where(function($query) {
                $query->where('payment_title', 'like', 'Monthly Fee - %')
                      ->orWhere('payment_title', 'like', 'monthly fee - %'); // Case insensitive check
            })
            ->delete();
    }

    private function ensureAdvanceFeeForParent(ParentAccount $parentAccount): void
    {
        AdvanceFee::firstOrCreate(
            ['parent_id' => (string) $parentAccount->id],
            [
                'name' => $parentAccount->name,
                'email' => $parentAccount->email,
                'phone' => $parentAccount->phone,
                'id_card_number' => $parentAccount->id_card_number,
                'available_credit' => 0,
                'increase' => 0,
                'decrease' => 0,
                'childs' => 0,
            ]
        );
    }

    /**
     * Ensure AdvanceFee record exists for a student's father_id_card
     * This is used when bulk uploading students without creating parent accounts
     */
    private function ensureAdvanceFeeForFatherIdCard(?string $fatherIdCard, ?string $fatherName, ?string $fatherPhone = null): void
    {
        if (empty($fatherIdCard)) {
            return;
        }

        // Check if AdvanceFee already exists by id_card_number
        $existingAdvanceFee = AdvanceFee::where('id_card_number', $fatherIdCard)->first();
        
        if (!$existingAdvanceFee) {
            // Create AdvanceFee record based on father_id_card
            AdvanceFee::create([
                'parent_id' => null, // No parent account linked
                'name' => $fatherName ?? 'Unknown',
                'email' => null,
                'phone' => $fatherPhone,
                'id_card_number' => $fatherIdCard,
                'available_credit' => 0,
                'increase' => 0,
                'decrease' => 0,
                'childs' => 0,
            ]);
        }
    }

    private function createGeneratedAdmissionFees(Student $student, array $validated, string $dueDate, string $accountantName): void
    {
        if (empty($student->student_code)) {
            return;
        }

        $generateAdmission = ($validated['generate_admission_fee'] ?? '') === '1';
        $admissionAmount = (float) ($validated['admission_fee_amount'] ?? 0);
        if ($generateAdmission && $admissionAmount > 0) {
            $this->createGeneratedFeeRecord(
                $student,
                'Admission Fee',
                $admissionAmount,
                $dueDate,
                $accountantName
            );
        }

        $generateOther = ($validated['generate_other_fee'] ?? '') === '1';
        $otherAmount = (float) ($validated['other_fee_amount'] ?? 0);
        $otherTitle = trim((string) ($validated['fee_type'] ?? ''));
        if ($generateOther && $otherAmount > 0) {
            $title = $otherTitle !== '' ? $otherTitle : 'Other Fee';
            $this->createGeneratedFeeRecord(
                $student,
                $title,
                $otherAmount,
                $dueDate,
                $accountantName
            );
        }
    }

    private function createGeneratedFeeRecord(Student $student, string $title, float $amount, string $dueDate, string $accountantName): void
    {
        if ($amount <= 0 || empty($student->student_code)) {
            return;
        }

        $exists = StudentPayment::where('student_code', $student->student_code)
            ->where('payment_title', $title)
            ->where('method', 'Generated')
            ->exists();
        if ($exists) {
            return;
        }

        StudentPayment::create([
            'campus' => $student->campus ?? null,
            'student_code' => $student->student_code,
            'payment_title' => $title,
            'payment_amount' => $amount,
            'discount' => 0,
            'method' => 'Generated',
            'payment_date' => $dueDate,
            'sms_notification' => 'Yes',
            'late_fee' => 0,
            'accountant' => $accountantName,
        ]);
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
                // Validate CSV upload
                // Campus, class, and section can come from CSV or form
                // If CSV has Campus column, form campus is optional
                $validated = $request->validate([
                    'csv_campus' => ['nullable', 'string', 'max:255'], // Optional if CSV has campus
                    'csv_class' => ['nullable', 'string', 'max:255'], // Optional if CSV has class
                    'csv_section' => ['nullable', 'string', 'max:255'],
                    'csv_create_parent_accounts' => ['required', 'in:0,1'],
                    'csv_file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:10240'], // 10MB max, allow Excel files
                ]);

                // Default to form values, but will be overridden by CSV if present
                $defaultCampus = $validated['csv_campus'] ?? null;
                $defaultClass = $validated['csv_class'] ?? null;
                $defaultSection = $validated['csv_section'] ?? null;
                $createParentAccounts = $request->input('csv_create_parent_accounts', '0') == '1';
                $file = $request->file('csv_file');
                $fileExtension = strtolower($file->getClientOriginalExtension());

                // Map headers to expected field names (with variations)
                $headerMap = [
                    'Campus' => 'campus',
                    'campus' => 'campus',
                    'CAMPUS' => 'campus',
                    'Student Code' => 'student_code',
                    'student code' => 'student_code',
                    'STUDENT CODE' => 'student_code',
                    'StudentCode' => 'student_code',
                    'Code' => 'student_code',
                    'code' => 'student_code',
                    'Name' => 'student_name',
                    'name' => 'student_name',
                    'NAME' => 'student_name',
                    'Student Name' => 'student_name',
                    'student name' => 'student_name',
                    'STUDENT NAME' => 'student_name',
                    'Surname/Caste' => 'surname_caste',
                    'surname/caste' => 'surname_caste',
                    'Surname' => 'surname_caste',
                    'surname' => 'surname_caste',
                    'Gender' => 'gender',
                    'gender' => 'gender',
                    'GENDER' => 'gender',
                    'Date Of Birth' => 'date_of_birth',
                    'date of birth' => 'date_of_birth',
                    'DATE OF BIRTH' => 'date_of_birth',
                    'DateOfBirth' => 'date_of_birth',
                    'Birthday' => 'date_of_birth',
                    'birthday' => 'date_of_birth',
                    'BIRTHDAY' => 'date_of_birth',
                    'DOB' => 'date_of_birth',
                    'dob' => 'date_of_birth',
                    'Place Of Birth' => 'place_of_birth',
                    'place of birth' => 'place_of_birth',
                    'PlaceOfBirth' => 'place_of_birth',
                    'Father Name' => 'father_name',
                    'father name' => 'father_name',
                    'FATHER NAME' => 'father_name',
                    'FatherName' => 'father_name',
                    'Father CNIC' => 'father_id_card',
                    'father cnic' => 'father_id_card',
                    'FatherCNIC' => 'father_id_card',
                    'Father ID Card' => 'father_id_card',
                    'father id card' => 'father_id_card',
                    'FatherIDCard' => 'father_id_card',
                    'Father Email' => 'father_email',
                    'father email' => 'father_email',
                    'FatherEmail' => 'father_email',
                    'Father Phone' => 'father_phone',
                    'father phone' => 'father_phone',
                    'FatherPhone' => 'father_phone',
                    'Mother Phone' => 'mother_phone',
                    'mother phone' => 'mother_phone',
                    'MotherPhone' => 'mother_phone',
                    'WhatsApp Number' => 'whatsapp_number',
                    'whatsapp number' => 'whatsapp_number',
                    'WhatsAppNumber' => 'whatsapp_number',
                    'WhatsApp' => 'whatsapp_number',
                    'whatsapp' => 'whatsapp_number',
                    'Religion' => 'religion',
                    'religion' => 'religion',
                    'Home Address' => 'home_address',
                    'home address' => 'home_address',
                    'HomeAddress' => 'home_address',
                    'Address' => 'home_address',
                    'address' => 'home_address',
                    'Class' => 'class',
                    'class' => 'class',
                    'CLASS' => 'class',
                    'Section' => 'section',
                    'section' => 'section',
                    'SECTION' => 'section',
                    'Sections' => 'section',
                    'sections' => 'section',
                    'GR Number' => 'gr_number',
                    'gr number' => 'gr_number',
                    'GRNumber' => 'gr_number',
                    'GR' => 'gr_number',
                    'gr' => 'gr_number',
                    'Previous School' => 'previous_school',
                    'previous school' => 'previous_school',
                    'PreviousSchool' => 'previous_school',
                    'Admission Date' => 'admission_date',
                    'admission date' => 'admission_date',
                    'AdmissionDate' => 'admission_date',
                    'Reference/Remarks' => 'reference_remarks',
                    'reference/remarks' => 'reference_remarks',
                    'Reference' => 'reference_remarks',
                    'reference' => 'reference_remarks',
                    'Student Password' => 'b_form_number',
                    'student password' => 'b_form_number',
                    'Password' => 'b_form_number',
                    'password' => 'b_form_number',
                    'Monthly Fee' => 'monthly_fee',
                    'monthly fee' => 'monthly_fee',
                    'MonthlyFee' => 'monthly_fee',
                    'Discounted Student' => 'discounted_student',
                    'discounted student' => 'discounted_student',
                    'Discount Amount' => 'discount_amount',
                    'discount amount' => 'discount_amount',
                    'Discount Reason' => 'discount_reason',
                    'discount reason' => 'discount_reason',
                    'Transport Route' => 'transport_route',
                    'transport route' => 'transport_route',
                    'TransportRoute' => 'transport_route',
                    'Transport Fare' => 'transport_fare',
                    'transport fare' => 'transport_fare',
                    'TransportFare' => 'transport_fare',
                    'Arrears' => 'arrears',
                    'arrears' => 'arrears',
                ];

                $rows = [];
                
                // Handle Excel files (.xlsx, .xls)
                if (in_array($fileExtension, ['xlsx', 'xls'])) {
                    try {
                        $data = Excel::toArray([], $file);
                        if (!empty($data[0])) {
                            $rows = $data[0];
                        }
                    } catch (\Exception $e) {
                        return redirect()
                            ->route('admission.admit-bulk-student')
                            ->with('error', 'Error reading Excel file: ' . $e->getMessage());
                    }
                } else {
                    // Handle CSV files - try different encodings
                    $encodings = ['UTF-8', 'Windows-1252', 'ISO-8859-1', 'UTF-16'];
                    $handle = null;
                    $fileContent = file_get_contents($file->getRealPath());
                    
                    foreach ($encodings as $encoding) {
                        $converted = mb_convert_encoding($fileContent, 'UTF-8', $encoding);
                        $tempFile = tmpfile();
                        fwrite($tempFile, $converted);
                        rewind($tempFile);
                        
                        // Check for BOM and skip it
                        $bom = fread($tempFile, 3);
                        if ($bom !== "\xEF\xBB\xBF") {
                            rewind($tempFile);
                        }
                        
                        $testRows = [];
                        while (($row = fgetcsv($tempFile)) !== false) {
                            if (!empty($row) && !empty(array_filter($row))) {
                                $testRows[] = $row;
                            }
                        }
                        
                        if (!empty($testRows) && count($testRows) > 0) {
                            $rows = $testRows;
                            fclose($tempFile);
                            break;
                        }
                        fclose($tempFile);
                    }
                    
                    // If still no rows, try default method with explicit delimiter detection
                    if (empty($rows)) {
                        $handle = fopen($file->getRealPath(), 'r');
                        
                        // Check for BOM and skip it
                        $bom = fread($handle, 3);
                        if ($bom !== "\xEF\xBB\xBF") {
                            rewind($handle);
                        }
                        
                        // Try to detect delimiter from first line
                        $firstLine = fgets($handle);
                        rewind($handle);
                        if ($bom !== "\xEF\xBB\xBF") {
                            rewind($handle);
                        }
                        
                        $delimiter = ',';
                        $delimiters = [',', ';', "\t"];
                        $maxCount = 0;
                        foreach ($delimiters as $d) {
                            $count = substr_count($firstLine, $d);
                            if ($count > $maxCount) {
                                $maxCount = $count;
                                $delimiter = $d;
                            }
                        }
                        
                        while (($row = fgetcsv($handle, 0, $delimiter, '"', '"')) !== false) {
                            // Filter out completely empty rows
                            if (!empty(array_filter($row, function($cell) { return trim($cell) !== ''; }))) {
                                $rows[] = $row;
                            }
                        }
                        fclose($handle);
                    }
                }
                
                if (empty($rows)) {
                    return redirect()
                        ->route('admission.admit-bulk-student')
                        ->with('error', 'File is empty or could not be read. Please check the file format and encoding.');
                }
                
                // Parse header row (first row) - normalize headers
                $headers = array_map(function($header) {
                    return trim($header);
                }, $rows[0]);
                
                // Create column index map with case-insensitive matching
                $columnMap = [];
                foreach ($headers as $index => $header) {
                    $header = trim($header);
                    // Try exact match first
                    if (isset($headerMap[$header])) {
                        $columnMap[$index] = $headerMap[$header];
                    } else {
                        // Try case-insensitive match
                        $headerLower = strtolower($header);
                        foreach ($headerMap as $mapKey => $mapValue) {
                            if (strtolower($mapKey) === $headerLower) {
                                $columnMap[$index] = $mapValue;
                                break;
                            }
                        }
                    }
                }
                
                // Validate required columns
                $requiredColumns = ['student_name', 'gender', 'father_name', 'date_of_birth'];
                $missingColumns = [];
                $foundColumns = [];
                foreach ($requiredColumns as $required) {
                    if (!in_array($required, $columnMap)) {
                        // Find the header name for this field
                        $headerName = null;
                        foreach ($headerMap as $key => $value) {
                            if ($value === $required) {
                                $headerName = $key;
                                break;
                            }
                        }
                        $missingColumns[] = $headerName ?? $required;
                    } else {
                        $foundColumns[] = $required;
                    }
                }
                
                if (!empty($missingColumns)) {
                    $errorMsg = 'File is missing required columns: ' . implode(', ', $missingColumns);
                    $errorMsg .= '. Found columns: ' . implode(', ', array_map(function($col) use ($headerMap) {
                        foreach ($headerMap as $key => $value) {
                            if ($value === $col) return $key;
                        }
                        return $col;
                    }, $foundColumns));
                    $errorMsg .= '. All headers in file: ' . implode(', ', $headers);
                    return redirect()
                        ->route('admission.admit-bulk-student')
                        ->with('error', $errorMsg);
                }
                
                // Read data rows (skip header row)
                $students = [];
                for ($i = 1; $i < count($rows); $i++) {
                    $row = $rows[$i];
                    
                    // Skip completely empty rows (all cells are empty/null)
                    $rowHasData = false;
                    foreach ($row as $cell) {
                        if (!empty(trim((string)$cell))) {
                            $rowHasData = true;
                            break;
                        }
                    }
                    if (!$rowHasData) {
                        continue;
                    }
                    
                    $studentData = [];
                    foreach ($columnMap as $index => $field) {
                        if (isset($row[$index])) {
                            $value = $row[$index];
                            // Convert to string, trim, and handle null/empty
                            $trimmedValue = is_null($value) ? '' : trim((string)$value);
                            // Always set the field value (even if empty) so it can be used later
                            // Empty strings will be handled by validation
                            $studentData[$field] = $trimmedValue;
                        }
                    }
                    
                    // Skip if no name (empty row)
                    if (empty($studentData['student_name'] ?? '')) {
                        continue;
                    }
                    
                    // Get campus, class, section from CSV (if provided) or use form defaults
                    $csvCampus = !empty($studentData['campus'] ?? '') ? trim($studentData['campus']) : null;
                    $csvClass = !empty($studentData['class'] ?? '') ? trim($studentData['class']) : null;
                    $csvSection = isset($studentData['section']) && $studentData['section'] !== '' 
                        ? trim($studentData['section']) 
                        : null;
                    
                    // Validate that campus and class match form selection (if form values are provided)
                    if ($defaultCampus) {
                        // If form has campus selected, CSV campus must match (case-insensitive)
                        if ($csvCampus && strtolower(trim($csvCampus)) !== strtolower(trim($defaultCampus))) {
                            $rowNumber = $i + 2;
                            return redirect()
                                ->route('admission.admit-bulk-student')
                                ->with('error', "Row {$rowNumber}: Campus mismatch. Form selected: '{$defaultCampus}', but CSV has: '{$csvCampus}'. Please ensure CSV campus matches the selected campus.");
                        }
                        $studentData['campus'] = $defaultCampus; // Use form campus
                    } else {
                        // If form doesn't have campus, use CSV campus
                        if (empty($csvCampus)) {
                            $rowNumber = $i + 2;
                            return redirect()
                                ->route('admission.admit-bulk-student')
                                ->with('error', "Row {$rowNumber}: Campus is required. Please provide Campus in CSV or select it in the form.");
                        }
                        $studentData['campus'] = $csvCampus;
                    }
                    
                    if ($defaultClass) {
                        // If form has class selected, CSV class must match (case-insensitive)
                        if ($csvClass && strtolower(trim($csvClass)) !== strtolower(trim($defaultClass))) {
                            $rowNumber = $i + 2;
                            return redirect()
                                ->route('admission.admit-bulk-student')
                                ->with('error', "Row {$rowNumber}: Class mismatch. Form selected: '{$defaultClass}', but CSV has: '{$csvClass}'. Please ensure CSV class matches the selected class.");
                        }
                        $studentData['class'] = $defaultClass; // Use form class
                    } else {
                        // If form doesn't have class, use CSV class
                        if (empty($csvClass)) {
                            $rowNumber = $i + 2;
                            return redirect()
                                ->route('admission.admit-bulk-student')
                                ->with('error', "Row {$rowNumber}: Class is required. Please provide Class in CSV or select it in the form.");
                        }
                        $studentData['class'] = $csvClass;
                    }
                    
                    // Section validation (if form has section selected, CSV section must match)
                    if ($defaultSection) {
                        if ($csvSection && strtolower(trim($csvSection)) !== strtolower(trim($defaultSection))) {
                            $rowNumber = $i + 2;
                            return redirect()
                                ->route('admission.admit-bulk-student')
                                ->with('error', "Row {$rowNumber}: Section mismatch. Form selected: '{$defaultSection}', but CSV has: '{$csvSection}'. Please ensure CSV section matches the selected section.");
                        }
                        $studentData['section'] = $defaultSection; // Use form section
                    } else {
                        // If form doesn't have section, use CSV section (can be null)
                        $studentData['section'] = $csvSection;
                    }
                    
                    // Normalize gender
                    if (isset($studentData['gender'])) {
                        $gender = strtolower(trim($studentData['gender']));
                        if (in_array($gender, ['male', 'female', 'other', 'm', 'f', 'o'])) {
                            $studentData['gender'] = in_array($gender, ['m', 'male']) ? 'male' : (in_array($gender, ['f', 'female']) ? 'female' : 'other');
                        } else {
                            $studentData['gender'] = 'male'; // default
                        }
                    }
                    
                    // Parse dates
                    if (isset($studentData['date_of_birth']) && !empty($studentData['date_of_birth'])) {
                        $date = $this->parseDate($studentData['date_of_birth']);
                        if ($date) {
                            $studentData['date_of_birth'] = $date;
                        } else {
                            $rowNumber = $i + 2; // +2 because we start from index 1 (after header) and rows are 1-indexed
                            return redirect()
                                ->route('admission.admit-bulk-student')
                                ->with('error', "Row {$rowNumber}: Invalid date format for Birthday. Use YYYY-MM-DD format.");
                        }
                    }
                    
                    if (isset($studentData['admission_date']) && !empty($studentData['admission_date'])) {
                        $date = $this->parseDate($studentData['admission_date']);
                        if ($date) {
                            $studentData['admission_date'] = $date;
                        } else {
                            // If admission date is invalid, use today's date
                            $studentData['admission_date'] = now()->format('Y-m-d');
                        }
                    } else {
                        // If admission date is not provided, use today's date
                        $studentData['admission_date'] = now()->format('Y-m-d');
                    }
                    
                    // Parse numeric fields
                    if (isset($studentData['monthly_fee']) && !empty($studentData['monthly_fee'])) {
                        $studentData['monthly_fee'] = is_numeric($studentData['monthly_fee']) ? $studentData['monthly_fee'] : null;
                    }
                    
                    if (isset($studentData['discount_amount']) && !empty($studentData['discount_amount'])) {
                        $studentData['discount_amount'] = is_numeric($studentData['discount_amount']) ? $studentData['discount_amount'] : null;
                    }
                    
                    if (isset($studentData['transport_fare']) && !empty($studentData['transport_fare'])) {
                        $studentData['transport_fare'] = is_numeric($studentData['transport_fare']) ? $studentData['transport_fare'] : null;
                    }
                    
                    if (isset($studentData['arrears']) && !empty($studentData['arrears'])) {
                        $studentData['arrears'] = is_numeric($studentData['arrears']) ? $studentData['arrears'] : null;
                    }
                    
                    // Normalize discounted_student field
                    if (isset($studentData['discounted_student'])) {
                        $discounted = strtolower(trim($studentData['discounted_student']));
                        if (in_array($discounted, ['yes', 'y', '1', 'true'])) {
                            $studentData['discounted_student'] = '1';
                        } else {
                            $studentData['discounted_student'] = '0';
                        }
                    }
                    
                    $students[] = $studentData;
                }
                
                if (empty($students)) {
                    return redirect()
                        ->route('admission.admit-bulk-student')
                        ->with('error', 'CSV file is empty or contains no valid student data.');
                }
                
                // Process students similar to manual entry
                $successCount = 0;
                $errorCount = 0;
                $errors = [];
                $usedStudentCodes = [];
                
                foreach ($students as $index => $studentData) {
                    try {
                        $studentNumber = $index + 1;
                        
                        // Get campus, class, section for this student (already set in studentData)
                        $campus = !empty($studentData['campus'] ?? '') ? trim($studentData['campus']) : $defaultCampus;
                        $class = !empty($studentData['class'] ?? '') ? trim($studentData['class']) : $defaultClass;
                        // Section can be empty, but if provided in CSV, use it; otherwise use form default
                        $section = (isset($studentData['section']) && $studentData['section'] !== '' && $studentData['section'] !== null) 
                            ? trim($studentData['section']) 
                            : ($defaultSection ?? null);
                        
                        // Validate required fields
                        if (empty($studentData['student_name'])) {
                            throw new \Exception('Name is required');
                        }
                        if (empty($studentData['gender'])) {
                            throw new \Exception('Gender is required');
                        }
                        if (empty($studentData['father_name'])) {
                            throw new \Exception('Father Name is required');
                        }
                        if (empty($studentData['date_of_birth'])) {
                            throw new \Exception('Birthday is required');
                        }
                        if (empty($campus)) {
                            throw new \Exception('Campus is required. Please provide Campus in CSV or select it in the form.');
                        }
                        if (empty($class)) {
                            throw new \Exception('Class is required. Please provide Class in CSV or select it in the form.');
                        }
                        
                        // Check if parent account exists or create new
                        $parentAccountId = null;
                        if ($createParentAccounts && !empty($studentData['father_id_card'])) {
                            $existingParent = ParentAccount::where('id_card_number', $studentData['father_id_card'])->first();
                            
                            if ($existingParent) {
                                $parentAccountId = $existingParent->id;
                                $this->ensureAdvanceFeeForParent($existingParent);
                            } else {
                                // Create new parent account
                                $parentEmail = 'parent_' . time() . '_' . $index . '_' . ($studentData['father_id_card'] ?? uniqid()) . '@school.com';
                                
                                $parentAccount = ParentAccount::create([
                                    'name' => $studentData['father_name'],
                                    'email' => $parentEmail,
                                    'password' => $studentData['father_id_card'] ?? 'parent',
                                    'phone' => $studentData['father_phone'] ?? null,
                                    'id_card_number' => $studentData['father_id_card'] ?? null,
                                    'address' => $studentData['home_address'] ?? null,
                                ]);
                                $this->ensureAdvanceFeeForParent($parentAccount);
                                
                                $parentAccountId = $parentAccount->id;
                            }
                        }
                        
                        // Generate student code if not provided
                        $studentCode = !empty($studentData['student_code'])
                            ? trim($studentData['student_code'])
                            : $this->generateNextStudentCode($campus, $usedStudentCodes);

                        // Check if student code already exists in database
                        if (!empty($studentData['student_code'])) {
                            // Check if code already exists in students table
                            if (Student::where('student_code', $studentCode)->exists()) {
                                throw new \Exception("Student code '{$studentCode}' already exists in the system.");
                            }
                            // Check if code has payment history
                            if (StudentPayment::where('student_code', $studentCode)->exists()) {
                                throw new \Exception("Student code '{$studentCode}' already has payment history.");
                            }
                            // Check if code is already used in current batch (duplicate in same CSV)
                            if (in_array($studentCode, $usedStudentCodes)) {
                                throw new \Exception("Student code '{$studentCode}' is duplicated in the file. Each student must have a unique code.");
                            }
                        } else {
                            // Avoid reusing a code that already has payments
                            while (StudentPayment::where('student_code', $studentCode)->exists() || 
                                   Student::where('student_code', $studentCode)->exists() ||
                                   in_array($studentCode, $usedStudentCodes)) {
                                $usedStudentCodes[] = $studentCode;
                                $studentCode = $this->generateNextStudentCode($campus, $usedStudentCodes);
                            }
                        }
                        
                        // Track used codes to avoid duplicates in current batch
                        $usedStudentCodes[] = $studentCode;
                        
                        // Create student with all fields from CSV
                        $student = Student::create([
                            'student_name' => $studentData['student_name'],
                            'surname_caste' => $studentData['surname_caste'] ?? null,
                            'gender' => $studentData['gender'],
                            'date_of_birth' => $studentData['date_of_birth'],
                            'place_of_birth' => $studentData['place_of_birth'] ?? null,
                            'admission_date' => $studentData['admission_date'] ?? now()->format('Y-m-d'),
                            'home_address' => $studentData['home_address'] ?? null,
                            'father_name' => $studentData['father_name'],
                            'father_id_card' => $studentData['father_id_card'] ?? null,
                            'father_email' => $studentData['father_email'] ?? null,
                            'father_phone' => $studentData['father_phone'] ?? null,
                            'mother_phone' => $studentData['mother_phone'] ?? null,
                            'whatsapp_number' => $studentData['whatsapp_number'] ?? null,
                            'religion' => $studentData['religion'] ?? null,
                            'monthly_fee' => $studentData['monthly_fee'] ?? null,
                            'student_code' => $studentCode,
                            'gr_number' => $studentData['gr_number'] ?? null,
                            'campus' => $campus,
                            'class' => $class,
                            'section' => $section,
                            'previous_school' => $studentData['previous_school'] ?? null,
                            'reference_remarks' => $studentData['reference_remarks'] ?? null,
                            'b_form_number' => $studentData['b_form_number'] ?? 'student',
                            'discounted_student' => $studentData['discounted_student'] ?? '0',
                            'discount_amount' => $studentData['discount_amount'] ?? null,
                            'discount_reason' => $studentData['discount_reason'] ?? null,
                            'transport_route' => $studentData['transport_route'] ?? null,
                            'transport_fare' => $studentData['transport_fare'] ?? null,
                            'parent_account_id' => $parentAccountId,
                            'password' => $studentData['b_form_number'] ?? ($studentData['father_id_card'] ?? $studentCode),
                        ]);
                        
                        // Handle arrears if provided
                        if (!empty($studentData['arrears']) && is_numeric($studentData['arrears']) && $studentData['arrears'] > 0) {
                            // Create arrears payment record
                            StudentPayment::create([
                                'student_code' => $studentCode,
                                'payment_amount' => $studentData['arrears'],
                                'payment_date' => now(),
                                'method' => 'Arrears',
                                'payment_title' => 'Arrears from CSV import',
                                'campus' => $campus,
                            ]);
                        }
                        $this->removeAutoGeneratedMonthlyFees($student);
                        
                        $successCount++;
                    } catch (\Exception $e) {
                        $errorCount++;
                        $studentName = $studentData['student_name'] ?? 'Unknown';
                        $errors[] = "Row " . ($index + 2) . " ({$studentName}): " . $e->getMessage();
                    }
                }
                
                $message = "CSV upload completed! Successfully admitted {$successCount} student(s).";
                if ($errorCount > 0) {
                    $message .= " {$errorCount} student(s) failed.";
                }
                
                return redirect()
                    ->route('admission.admit-bulk-student')
                    ->with('success', $message)
                    ->with('errors', $errors);
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
                            $this->ensureAdvanceFeeForParent($existingParent);
                        } else {
                            // Create new parent account
                            $parentEmail = 'parent_' . time() . '_' . $index . '_' . ($studentData['father_id_card'] ?? uniqid()) . '@school.com';
                            
                            $parentAccount = ParentAccount::create([
                                'name' => $studentData['father_name'],
                                'email' => $parentEmail,
                                'password' => $studentData['father_id_card'] ?? 'parent', // Model will hash it automatically
                                'phone' => $studentData['father_phone'] ?? null,
                                'id_card_number' => $studentData['father_id_card'] ?? null,
                                'address' => $studentData['home_address'] ?? null,
                            ]);
                            $this->ensureAdvanceFeeForParent($parentAccount);
                            
                            $parentAccountId = $parentAccount->id;
                        }
                    } else {
                        // Even if not creating parent account, ensure AdvanceFee exists for father_id_card
                        if (!empty($studentData['father_id_card'])) {
                            $this->ensureAdvanceFeeForFatherIdCard(
                                $studentData['father_id_card'],
                                $studentData['father_name'] ?? null,
                                $studentData['father_phone'] ?? null
                            );
                        }
                    }

                    // Generate student code if not provided
                    $studentCode = !empty($studentData['student_code'])
                        ? trim($studentData['student_code'])
                        : $this->generateNextStudentCode($campus, $usedStudentCodes);
                    
                    if (!empty($studentData['student_code'])) {
                        // Check if code already exists in students table
                        if (Student::where('student_code', $studentCode)->exists()) {
                            throw new \Exception("Student code '{$studentCode}' already exists in the system.");
                        }
                        // Check if code has payment history
                        if (StudentPayment::where('student_code', $studentCode)->exists()) {
                            throw new \Exception("Student code '{$studentCode}' already has payment history.");
                        }
                        // Check if code is already used in current batch (duplicate in same entry)
                        if (in_array($studentCode, $usedStudentCodes)) {
                            throw new \Exception("Student code '{$studentCode}' is duplicated. Each student must have a unique code.");
                        }
                    } else {
                        // Avoid reusing a code that already has payments or exists in database
                        while (StudentPayment::where('student_code', $studentCode)->exists() || 
                               Student::where('student_code', $studentCode)->exists() ||
                               in_array($studentCode, $usedStudentCodes)) {
                            $usedStudentCodes[] = $studentCode;
                            $studentCode = $this->generateNextStudentCode($campus, $usedStudentCodes);
                        }
                    }
                    
                    // Track used codes to avoid duplicates in current batch
                    $usedStudentCodes[] = $studentCode;

                    // Create student
                    $student = Student::create([
                        'student_name' => $studentData['student_name'],
                        'gender' => $studentData['gender'],
                        'date_of_birth' => $studentData['date_of_birth'],
                        'admission_date' => now()->format('Y-m-d'), // Add admission_date for manual entry
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
                    $this->removeAutoGeneratedMonthlyFees($student);

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

    /**
     * Download CSV template for bulk student admission.
     */
    public function downloadCsvTemplate(): StreamedResponse
    {
        $headers = [
            'Campus',
            'Student Code',
            'Name',
            'Surname/Caste',
            'Gender',
            'Date Of Birth',
            'Place Of Birth',
            'Father Name',
            'Father CNIC',
            'Father Email',
            'Father Phone',
            'Mother Phone',
            'WhatsApp Number',
            'Religion',
            'Home Address',
            'Class',
            'Section',
            'GR Number',
            'Previous School',
            'Admission Date',
            'Reference/Remarks',
            'Student Password',
            'Monthly Fee',
            'Discounted Student',
            'Discount Amount',
            'Discount Reason',
            'Transport Route',
            'Transport Fare',
            'Arrears'
        ];

        $filename = 'student_bulk_admission_template.csv';

        return response()->streamDownload(function () use ($headers) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for Excel UTF-8 support
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Write headers
            fputcsv($file, $headers);
            
            // Close file
            fclose($file);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Show admission report page with dynamic statistics.
     */
    public function report(): View
    {
        // Calculate admissions today
        $admissionsToday = Student::whereDate('admission_date', today())->count();
        
        // Calculate admissions this month
        $admissionsThisMonth = Student::whereYear('admission_date', now()->year)
            ->whereMonth('admission_date', now()->month)
            ->count();
        
        // Calculate active students (students with admission_date)
        $activeStudents = Student::whereNotNull('admission_date')->count();
        
        // Calculate deactivated students (students without admission_date or with deactivation flag)
        // Note: Adjust this based on your deactivation logic
        $deactivatedStudents = Student::whereNull('admission_date')->count();
        
        return view('admission.report', compact(
            'admissionsToday',
            'admissionsThisMonth',
            'activeStudents',
            'deactivatedStudents'
        ));
    }

    /**
     * Show today's admissions report.
     */
    public function reportToday(Request $request): View
    {
        // Check for print parameter - accept both boolean and string '1'
        $isPrint = $request->has('print') && ($request->boolean('print') || $request->get('print') == '1' || $request->get('print') === 1);
        
        // Get students admitted today
        $students = Student::whereDate('admission_date', today())
            ->orderBy('admission_date', 'desc')
            ->orderBy('student_name', 'asc')
            ->get();
        
        return view('admission.report-today', compact('students', 'isPrint'));
    }

    /**
     * Show monthly admissions report.
     */
    public function reportMonthly(Request $request): View
    {
        $isPrint = $request->boolean('print');
        
        // Get students admitted this month
        $students = Student::whereYear('admission_date', now()->year)
            ->whereMonth('admission_date', now()->month)
            ->orderBy('admission_date', 'desc')
            ->orderBy('student_name', 'asc')
            ->get();
        
        return view('admission.report-monthly', compact('students', 'isPrint'));
    }

    /**
     * Show yearly admissions report.
     */
    public function reportYearly(Request $request): View
    {
        // Check for print parameter - accept both boolean and string '1'
        $isPrint = $request->has('print') && ($request->boolean('print') || $request->get('print') == '1' || $request->get('print') === 1);
        
        // Get students admitted this year
        $students = Student::whereYear('admission_date', now()->year)
            ->orderBy('admission_date', 'desc')
            ->orderBy('student_name', 'asc')
            ->get();
        
        return view('admission.report-yearly', compact('students', 'isPrint'));
    }

    /**
     * Show admission forms report.
     */
    public function reportForms(Request $request): View
    {
        // Check for print parameter - accept both boolean and string '1'
        $isPrint = $request->has('print') && ($request->boolean('print') || $request->get('print') == '1' || $request->get('print') === 1);
        
        // Get filter values
        $filterCampus = $request->get('filter_campus');
        $filterClass = $request->get('filter_class');
        $filterSection = $request->get('filter_section');
        
        // Get all students with admission forms
        $query = Student::whereNotNull('admission_date');
        
        // Apply filters
        if ($filterCampus) {
            $query->where('campus', $filterCampus);
        }
        if ($filterClass) {
            $query->where('class', $filterClass);
        }
        if ($filterSection) {
            $query->where('section', $filterSection);
        }
        
        $students = $query->orderBy('admission_date', 'desc')
            ->orderBy('student_name', 'asc')
            ->get();
        
        // Get campuses, classes, sections for filters
        $campuses = Student::whereNotNull('campus')->distinct()->pluck('campus')->sort()->values();
        $classes = Student::whereNotNull('class')->distinct()->pluck('class')->sort()->values();
        $sections = Student::whereNotNull('section')->distinct()->pluck('section')->sort()->values();
        
        return view('admission.report-forms', compact('students', 'isPrint', 'campuses', 'classes', 'sections', 'filterCampus', 'filterClass', 'filterSection'));
    }

    /**
     * Show blank admission form.
     */
    public function reportBlank(Request $request): View
    {
        // Check for print parameter - accept both boolean and string '1', default to true for blank form
        $isPrint = !$request->has('print') || ($request->has('print') && ($request->boolean('print') || $request->get('print') == '1' || $request->get('print') === 1));
        
        return view('admission.report-blank', compact('isPrint'));
    }
}

