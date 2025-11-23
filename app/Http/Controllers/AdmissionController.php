<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Campus;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Transport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
    private function generateNextStudentCode(): string
    {
        // Get all students with pattern ST0001-X
        $students = Student::where('student_code', 'like', 'ST0001-%')
            ->whereNotNull('student_code')
            ->pluck('student_code')
            ->toArray();

        if (empty($students)) {
            // If no student code found, start with ST0001-1
            return 'ST0001-1';
        }

        // Extract numbers and find the maximum
        $maxNumber = 0;
        foreach ($students as $code) {
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
     * Store a newly admitted student in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
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
        ]);

        $photoPath = $this->handlePhotoUpload($request);

        // Prepare student data
        $studentData = [
            ...$validated,
            'photo' => $photoPath,
            'discounted_student' => $request->has('discounted_student'),
            'create_parent_account' => $request->has('create_parent_account'),
        ];

        // Hash B-Form Number as password if provided
        if (!empty($validated['b_form_number'])) {
            $studentData['password'] = $validated['b_form_number']; // Will be hashed automatically by Student model's setPasswordAttribute
        }

        Student::create($studentData);

        return redirect()
            ->route('admission.admit-student')
            ->with('success', 'Student admitted successfully!');
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
}

