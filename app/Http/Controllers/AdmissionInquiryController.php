<?php

namespace App\Http\Controllers;

use App\Models\AdmissionInquiry;
use App\Models\Campus;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Student;
use App\Models\ParentAccount;
use App\Models\StudentPayment;
use App\Models\StudentDiscount;
use App\Models\CustomFee;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class AdmissionInquiryController extends Controller
{
    /**
     * Display a listing of the admission inquiries.
     */
    public function index(Request $request): View
    {
        $query = AdmissionInquiry::query();
        
        // Search functionality - case insensitive and trim whitespace
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(parent) LIKE ?', ["%{$searchLower}%"])
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhereRaw('LOWER(full_address) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(gender) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $perPage = $request->get('per_page', 10);
        // Validate per_page to prevent invalid values
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $inquiries = $query->latest()->paginate($perPage)->withQueryString();
        
        // Get data for Admit Student modal (same as main Admit Student page)
        $campuses = \App\Models\Campus::orderBy('campus_name', 'asc')->pluck('campus_name');
        if ($campuses->isEmpty()) {
            $campusesFromClasses = \App\Models\ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromSections = \App\Models\Section::whereNotNull('campus')->distinct()->pluck('campus');
            $campuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
            if ($campuses->isEmpty()) {
                $campuses = collect(['Main Campus', 'Branch Campus 1', 'Branch Campus 2']);
            }
        }
        
        $classes = \App\Models\ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort()->values();
        if ($classes->isEmpty()) {
            $classes = collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']);
        }
        
        $transportRoutes = \App\Models\Transport::orderBy('route_name', 'asc')->pluck('route_name')->unique()->values();
        
        $feeTypes = \App\Models\FeeType::whereNotNull('fee_name')->distinct()->pluck('fee_name')->sort()->values();
        
        return view('admission.inquiry.manage', compact('inquiries', 'campuses', 'classes', 'transportRoutes', 'feeTypes'));
    }

    /**
     * Store a newly created admission inquiry.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'gender' => ['required', 'in:male,female,other'],
            'birthday' => ['required', 'date'],
            'full_address' => ['required', 'string'],
        ]);

        AdmissionInquiry::create($validated);

        return redirect()
            ->route('admission.inquiry.manage')
            ->with('success', 'Inquiry added successfully!');
    }

    /**
     * Update the specified admission inquiry.
     */
    public function update(Request $request, AdmissionInquiry $inquiry): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'gender' => ['required', 'in:male,female,other'],
            'birthday' => ['required', 'date'],
            'full_address' => ['required', 'string'],
        ]);

        $inquiry->update($validated);

        return redirect()
            ->route('admission.inquiry.manage')
            ->with('success', 'Inquiry updated successfully!');
    }

    /**
     * Remove the specified admission inquiry.
     */
    public function destroy(AdmissionInquiry $inquiry): RedirectResponse
    {
        $inquiry->delete();

        return redirect()
            ->route('admission.inquiry.manage')
            ->with('success', 'Inquiry deleted successfully!');
    }

    /**
     * Export inquiries to Excel, CSV, or PDF
     */
    public function export(Request $request, string $format)
    {
        $query = AdmissionInquiry::query();
        
        // Apply search filter if present
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('parent', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('full_address', 'like', "%{$search}%")
                  ->orWhere('gender', 'like', "%{$search}%");
            });
        }
        
        $inquiries = $query->latest()->get();
        
        switch ($format) {
            case 'excel':
                return $this->exportExcel($inquiries);
            case 'csv':
                return $this->exportCSV($inquiries);
            case 'pdf':
                return $this->exportPDF($inquiries);
            default:
                return redirect()->route('admission.inquiry.manage')
                    ->with('error', 'Invalid export format!');
        }
    }

    /**
     * Export to Excel (CSV format for Excel compatibility)
     */
    private function exportExcel($inquiries)
    {
        $filename = 'inquiries_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($inquiries) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for Excel UTF-8 compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Add headers
            fputcsv($file, ['ID', 'Name', 'Parent', 'Phone', 'Gender', 'Birthday', 'Full Address', 'Created At']);
            
            // Add data rows
            foreach ($inquiries as $inquiry) {
                fputcsv($file, [
                    $inquiry->id,
                    $inquiry->name,
                    $inquiry->parent,
                    $inquiry->phone,
                    ucfirst($inquiry->gender),
                    $inquiry->birthday ? $inquiry->birthday->format('Y-m-d') : 'N/A',
                    $inquiry->full_address,
                    $inquiry->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to CSV
     */
    private function exportCSV($inquiries)
    {
        $filename = 'inquiries_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($inquiries) {
            $file = fopen('php://output', 'w');
            
            // Add headers
            fputcsv($file, ['ID', 'Name', 'Parent', 'Phone', 'Gender', 'Birthday', 'Full Address', 'Created At']);
            
            // Add data rows
            foreach ($inquiries as $inquiry) {
                fputcsv($file, [
                    $inquiry->id,
                    $inquiry->name,
                    $inquiry->parent,
                    $inquiry->phone,
                    ucfirst($inquiry->gender),
                    $inquiry->birthday ? $inquiry->birthday->format('Y-m-d') : 'N/A',
                    $inquiry->full_address,
                    $inquiry->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to PDF
     */
    private function exportPDF($inquiries)
    {
        // Simple HTML to PDF conversion
        $html = view('admission.inquiry.pdf', compact('inquiries'))->render();
        
        // For now, return HTML that can be printed as PDF
        // You can integrate a PDF library like dompdf or snappy later
        return response($html)
            ->header('Content-Type', 'text/html');
    }

    /**
     * Display the Send SMS to Inquiry page.
     */
    public function sendSms(Request $request): View
    {
        // Get campuses for dropdown - First from Campus model, then fallback
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
            $allCampuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort();
            $campuses = $allCampuses->map(function($campus) {
                return (object)['campus_name' => $campus];
            });
        }

        return view('admission.inquiry.send-sms', compact('campuses'));
    }

    /**
     * Store and send SMS to inquiries.
     */
    public function storeSms(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['nullable', 'string', 'max:255'],
            'sms_message' => ['required', 'string', 'max:1000'],
            'date' => ['required', 'date'],
        ]);

        // TODO: Implement SMS sending logic here
        // For now, just return success message
        
        return redirect()
            ->route('admission.inquiry.send-sms')
            ->with('success', 'SMS sent successfully to inquiries!');
    }

    /**
     * Get inquiry data for admit modal
     */
    public function getData(AdmissionInquiry $inquiry)
    {
        return response()->json([
            'success' => true,
            'inquiry' => [
                'id' => $inquiry->id,
                'name' => $inquiry->name,
                'parent' => $inquiry->parent,
                'phone' => $inquiry->phone,
                'gender' => $inquiry->gender,
                'birthday' => $inquiry->birthday ? $inquiry->birthday->format('Y-m-d') : null,
                'full_address' => $inquiry->full_address,
            ]
        ]);
    }

    /**
     * Admit student from inquiry
     */
    public function admit(Request $request): RedirectResponse
    {
        $inquiryId = $request->input('inquiry_id');
        if (!$inquiryId) {
            return redirect()
                ->route('admission.inquiry.manage')
                ->with('error', 'Inquiry ID is required.');
        }
        
        $inquiry = AdmissionInquiry::findOrFail($inquiryId);
        
        try {
            // Validate required fields (same as main Admit Student page)
            $validated = $request->validate([
                'student_name' => ['required', 'string', 'max:255'],
                'surname_caste' => ['nullable', 'string', 'max:255'],
                'father_name' => ['required', 'string', 'max:255'],
                'gender' => ['required', 'in:male,female,other'],
                'date_of_birth' => ['required', 'date'],
                'place_of_birth' => ['nullable', 'string', 'max:255'],
                'class' => ['required', 'string', 'max:255'],
                'admission_date' => ['required', 'date'],
                'campus' => ['nullable', 'string', 'max:255'],
                'section' => ['nullable', 'string', 'max:255'],
                'father_id_card' => ['nullable', 'string', 'max:255'],
                'father_email' => ['nullable', 'email', 'max:255'],
                'father_phone' => ['required', 'string', 'max:20'],
                'mother_phone' => ['nullable', 'string', 'max:20'],
                'whatsapp_number' => ['nullable', 'string', 'max:20'],
                'religion' => ['nullable', 'string', 'max:255'],
                'home_address' => ['required', 'string'],
                'b_form_number' => ['nullable', 'string', 'max:255'],
                'student_code' => ['nullable', 'string', 'max:255'],
                'gr_number' => ['nullable', 'string', 'max:255'],
                'previous_school' => ['nullable', 'string', 'max:255'],
                'reference_remarks' => ['nullable', 'string'],
                'monthly_fee' => ['nullable', 'numeric', 'min:0'],
                'discounted_student' => ['nullable', 'in:0,1'],
                'discount_amount' => ['nullable', 'numeric', 'min:0'],
                'discount_reason' => ['nullable', 'string', 'max:255'],
                'transport_route' => ['nullable', 'string', 'max:255'],
                'transport_fare' => ['nullable', 'numeric', 'min:0'],
                'admission_notification' => ['nullable', 'string', 'max:255'],
                'create_parent_account' => ['nullable', 'in:0,1'],
                'parent_password' => ['nullable', 'string', 'min:6'],
                'generate_admission_fee' => ['nullable', 'in:0,1'],
                'admission_fee_amount' => ['nullable', 'numeric', 'min:0'],
                'generate_other_fee' => ['nullable', 'in:0,1'],
                'fee_type' => ['nullable', 'string', 'max:255'],
                'other_fee_amount' => ['nullable', 'numeric', 'min:0'],
                'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
                'captured_photo' => ['nullable', 'string'],
            ]);
            
            // Handle photo upload
            $photoPath = null;
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('students/photos', 'public');
            } elseif ($request->filled('captured_photo')) {
                // Handle base64 captured photo
                $base64Image = $request->input('captured_photo');
                if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $matches)) {
                    $imageData = base64_decode(substr($base64Image, strpos($base64Image, ',') + 1));
                    $extension = $matches[1];
                    $filename = 'captured_' . time() . '.' . $extension;
                    Storage::disk('public')->put('students/photos/' . $filename, $imageData);
                    $photoPath = 'students/photos/' . $filename;
                }
            }
            
            // Generate student code if not provided
            $studentCode = $validated['student_code'] ?? null;
            if (empty($studentCode) && !empty($validated['campus'])) {
                // Use AdmissionController's private method via reflection
                $admissionController = app(\App\Http\Controllers\AdmissionController::class);
                $reflection = new \ReflectionClass($admissionController);
                $generateMethod = $reflection->getMethod('generateNextStudentCode');
                $generateMethod->setAccessible(true);
                $studentCode = $generateMethod->invoke($admissionController, $validated['campus'], []);
                
                // Avoid reusing a code that already has payments
                while ($studentCode && StudentPayment::where('student_code', $studentCode)->exists()) {
                    $studentCode = $generateMethod->invoke($admissionController, $validated['campus'], [$studentCode]);
                }
            }
            
            // Handle parent account
            $parentAccountId = null;
            $createParentAccount = $request->input('create_parent_account', '0') == '1';
            
            if ($createParentAccount) {
                // Check for existing parent account by ID card (if provided)
                $existingParent = null;
                if (!empty($validated['father_id_card'])) {
                    // Normalize ID card
                    $idCard = trim($validated['father_id_card']);
                    $normalizedIdCard = str_replace(['-', ' ', '_', '.', '/'], '', strtolower($idCard));
                    
                    // Find existing parent account
                    $existingParent = ParentAccount::where(function($query) use ($idCard, $normalizedIdCard) {
                        $query->whereRaw('LOWER(TRIM(id_card_number)) = ?', [strtolower($idCard)])
                            ->orWhereRaw('LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(id_card_number), "-", ""), " ", ""), "_", ""), ".", ""), "/", "")) = ?', [$normalizedIdCard]);
                    })->first();
                }
                
                // Also check by email if provided
                if (!$existingParent && !empty($validated['father_email'])) {
                    $existingParent = ParentAccount::where('email', $validated['father_email'])->first();
                }
                
                if ($existingParent) {
                    $parentAccountId = $existingParent->id;
                } else {
                    // Create new parent account
                    // Generate unique email if not provided or if provided email already exists
                    $parentEmail = $validated['father_email'] ?? null;
                    if (empty($parentEmail)) {
                        $parentEmail = 'parent_' . time() . '_' . uniqid() . '@school.com';
                    } else {
                        // Check if email already exists, if yes, make it unique
                        $emailBase = $parentEmail;
                        $counter = 1;
                        while (ParentAccount::where('email', $parentEmail)->exists()) {
                            $parts = explode('@', $emailBase);
                            $parentEmail = $parts[0] . '_' . $counter . '@' . ($parts[1] ?? 'school.com');
                            $counter++;
                        }
                    }
                    
                    $parentPassword = $validated['parent_password'] ?? 'parent';
                    
                    try {
                        $parentAccount = ParentAccount::create([
                            'name' => $validated['father_name'],
                            'email' => $parentEmail,
                            'password' => $parentPassword,
                            'phone' => $validated['father_phone'] ?? null,
                            'whatsapp' => $validated['whatsapp_number'] ?? ($validated['mother_phone'] ?? null),
                            'id_card_number' => $validated['father_id_card'] ?? null,
                            'address' => $validated['home_address'] ?? null,
                        ]);
                        
                        $parentAccountId = $parentAccount->id;
                    } catch (\Exception $e) {
                        // Log error but don't fail the entire admission process
                        \Log::error('Failed to create parent account from Admission Inquiry: ' . $e->getMessage(), [
                            'father_name' => $validated['father_name'],
                            'father_email' => $parentEmail,
                            'error' => $e->getMessage()
                        ]);
                        // Continue without parent account ID
                    }
                }
            }
            
            // Prepare student data (same structure as main Admit Student page)
            $studentData = [
                'student_name' => $validated['student_name'],
                'surname_caste' => $validated['surname_caste'] ?? null,
                'gender' => $validated['gender'],
                'date_of_birth' => $validated['date_of_birth'],
                'place_of_birth' => $validated['place_of_birth'] ?? null,
                'admission_date' => $validated['admission_date'],
                'father_name' => $validated['father_name'],
                'father_id_card' => $validated['father_id_card'] ?? null,
                'father_email' => $validated['father_email'] ?? null,
                'father_phone' => $validated['father_phone'] ?? null,
                'mother_phone' => $validated['mother_phone'] ?? null,
                'whatsapp_number' => $validated['whatsapp_number'] ?? null,
                'religion' => $validated['religion'] ?? null,
                'home_address' => $validated['home_address'] ?? null,
                'b_form_number' => $validated['b_form_number'] ?? null,
                'campus' => $validated['campus'] ?? null,
                'class' => $validated['class'],
                'section' => $validated['section'] ?? null,
                'student_code' => $studentCode,
                'gr_number' => $validated['gr_number'] ?? null,
                'previous_school' => $validated['previous_school'] ?? null,
                'reference_remarks' => $validated['reference_remarks'] ?? null,
                'monthly_fee' => $validated['monthly_fee'] ?? null,
                'discounted_student' => ($validated['discounted_student'] ?? '0') == '1',
                'discount_amount' => $validated['discount_amount'] ?? null,
                'discount_reason' => $validated['discount_reason'] ?? null,
                'transport_route' => $validated['transport_route'] ?? null,
                'transport_fare' => $validated['transport_fare'] ?? null,
                'admission_notification' => $validated['admission_notification'] ?? null,
                'create_parent_account' => $createParentAccount,
                'generate_admission_fee' => ($validated['generate_admission_fee'] ?? '0') == '1' ? '1' : '0',
                'admission_fee_amount' => $validated['admission_fee_amount'] ?? null,
                'generate_other_fee' => ($validated['generate_other_fee'] ?? '0') == '1' ? '1' : '0',
                'fee_type' => $validated['fee_type'] ?? null,
                'other_fee_amount' => $validated['other_fee_amount'] ?? null,
                'photo' => $photoPath,
                'parent_account_id' => $parentAccountId,
            ];
            
            // Set password (use b_form_number if provided, otherwise default to 'student')
            if (!empty($validated['b_form_number'])) {
                $studentData['password'] = $validated['b_form_number']; // Will be hashed automatically by Student model
            } else {
                $studentData['password'] = 'student'; // Default password
            }
            
            // Create student
            $student = Student::create($studentData);
            
            // Remove any auto-generated monthly fees immediately after student creation
            // Monthly fees should only be generated manually via Fee Payment, not automatically during admission
            if (!empty($student->student_code)) {
                StudentPayment::where('student_code', $student->student_code)
                    ->where('method', 'Generated')
                    ->where(function($query) {
                        $query->where('payment_title', 'like', 'Monthly Fee - %')
                              ->orWhere('payment_title', 'like', 'monthly fee - %'); // Case insensitive check
                    })
                    ->delete();
            }
            
            // Handle discount creation (same as main Admit Student page)
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
            
            // Get accountant name for fee generation
            $accountantName = 'System';
            if (auth()->guard('accountant')->check()) {
                $accountantName = auth()->guard('accountant')->user()->name ?? 'System';
            } elseif (auth()->guard('admin')->check()) {
                $accountantName = auth()->guard('admin')->user()->name ?? 'System';
            }
            
            // Parse admission date for fee generation
            $admissionDate = !empty($validated['admission_date'])
                ? Carbon::parse($validated['admission_date'])
                : Carbon::now();
            $defaultDueDate = $admissionDate->copy()->addDays(15)->format('Y-m-d');
            
            // Generate admission fees if requested (same as main Admit Student page)
            if (!empty($student->student_code)) {
                $this->createGeneratedAdmissionFees($student, $validated, $defaultDueDate, $accountantName);
            }
            
            // Remove any auto-generated monthly fees again after all fee generation
            if (!empty($student->student_code)) {
                StudentPayment::where('student_code', $student->student_code)
                    ->where('method', 'Generated')
                    ->where(function($query) {
                        $query->where('payment_title', 'like', 'Monthly Fee - %')
                              ->orWhere('payment_title', 'like', 'monthly fee - %');
                    })
                    ->delete();
            }
            
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
            
            // Delete inquiry after successful admission - ensure it happens
            // Use direct query to ensure deletion happens even if model instance has issues
            try {
                $deleted = AdmissionInquiry::where('id', $inquiryId)->delete();
                
                if ($deleted > 0) {
                    Log::info('Inquiry deleted successfully after admission', [
                        'inquiry_id' => $inquiryId,
                        'student_id' => $student->id,
                        'student_code' => $student->student_code ?? null,
                    ]);
                } else {
                    // If delete returned 0, the inquiry might already be deleted or doesn't exist
                    Log::warning('Inquiry deletion returned 0 rows - inquiry may not exist', [
                        'inquiry_id' => $inquiryId,
                        'student_id' => $student->id,
                    ]);
                }
            } catch (\Exception $deleteException) {
                // Log the deletion error but don't fail the admission
                Log::error('Failed to delete inquiry after admission: ' . $deleteException->getMessage(), [
                    'inquiry_id' => $inquiryId,
                    'student_id' => $student->id ?? null,
                    'error' => $deleteException->getMessage(),
                    'trace' => $deleteException->getTraceAsString(),
                ]);
            }
            
            return redirect()
                ->route('admission.inquiry.manage')
                ->with('success', 'Student admitted successfully and inquiry removed!');
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Return validation errors
            return redirect()
                ->route('admission.inquiry.manage')
                ->withErrors($e->errors())
                ->withInput()
                ->with('error', 'Please fix the validation errors and try again.');
        } catch (\Exception $e) {
            // Log the full error for debugging
            Log::error('Failed to admit student from inquiry', [
                'inquiry_id' => $inquiryId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['password', 'parent_password', '_token']),
            ]);
            
            // If student was created but inquiry deletion failed, try to delete inquiry anyway
            if (isset($student) && $student->id) {
                try {
                    AdmissionInquiry::where('id', $inquiryId)->delete();
                    Log::info('Inquiry deleted in catch block after student creation', [
                        'inquiry_id' => $inquiryId,
                        'student_id' => $student->id,
                    ]);
                } catch (\Exception $deleteException) {
                    Log::error('Failed to delete inquiry in catch block: ' . $deleteException->getMessage(), [
                        'inquiry_id' => $inquiryId,
                        'student_id' => $student->id,
                    ]);
                }
            }
            
            return redirect()
                ->route('admission.inquiry.manage')
                ->withInput()
                ->with('error', 'Failed to admit student: ' . $e->getMessage() . '. Please check the logs for more details.');
        }
    }
    
    /**
     * Create generated admission fees (same as AdmissionController)
     */
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

    /**
     * Create a generated fee record (same as AdmissionController)
     */
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
}

