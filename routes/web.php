<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Session;

// Language Switch Route
Route::get('/language/{locale}', function ($locale) {
    $supportedLocales = ['en', 'ur'];
    if (in_array($locale, $supportedLocales)) {
        Session::put('locale', $locale);
        // Also set cookie for persistence
        return redirect()->back()->cookie('locale', $locale, 60 * 24 * 30); // 30 days
    }
    return redirect()->back();
})->name('language.switch');

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware([App\Http\Middleware\AdminMiddleware::class])
    ->name('dashboard');

// Staff Authentication Routes
Route::prefix('staff')->name('staff.')->group(function () {
    Route::get('/login', [App\Http\Controllers\StaffAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [App\Http\Controllers\StaffAuthController::class, 'login'])->name('login.post');
    Route::post('/logout', [App\Http\Controllers\StaffAuthController::class, 'logout'])->name('logout');
    
    // Protected Staff Routes
    Route::middleware([App\Http\Middleware\StaffMiddleware::class])->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\StaffAuthController::class, 'dashboard'])->name('dashboard');
        Route::get('/dashboard/attendance-stats', [App\Http\Controllers\StaffAuthController::class, 'getAttendanceStats'])->name('dashboard.attendance-stats');

        // Live chat for teachers (staff) with admin
        Route::get('/chat', [App\Http\Controllers\StaffChatController::class, 'index'])->name('chat');
        Route::post('/chat', [App\Http\Controllers\StaffChatController::class, 'send'])->name('chat.send');
    });
});

// Accountant Authentication Routes
Route::prefix('accountant')->name('accountant.')->group(function () {
    Route::get('/login', [App\Http\Controllers\AccountantAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [App\Http\Controllers\AccountantAuthController::class, 'login'])->name('login');
    Route::post('/logout', [App\Http\Controllers\AccountantAuthController::class, 'logout'])->name('logout');
    
    // Protected Accountant Routes
    Route::middleware([App\Http\Middleware\AccountantMiddleware::class])->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\AccountantAuthController::class, 'dashboard'])->name('dashboard');
        
        // Accountant specific routes
        Route::get('/task-management', [App\Http\Controllers\AccountantController::class, 'taskManagement'])->name('task-management');
        Route::get('/fee-payment', [App\Http\Controllers\AccountantController::class, 'feePayment'])->name('fee-payment');
        Route::get('/family-fee-calculator', [App\Http\Controllers\AccountantController::class, 'familyFeeCalculator'])->name('family-fee-calculator');
        Route::get('/generate-monthly-fee', [App\Http\Controllers\AccountantController::class, 'generateMonthlyFee'])->name('generate-monthly-fee');
        Route::get('/generate-custom-fee', [App\Http\Controllers\AccountantController::class, 'generateCustomFee'])->name('generate-custom-fee');
        Route::get('/generate-transport-fee', [App\Http\Controllers\AccountantController::class, 'generateTransportFee'])->name('generate-transport-fee');
        Route::get('/fee-type', [App\Http\Controllers\AccountantController::class, 'feeType'])->name('fee-type');
        Route::get('/parents-credit-system', [App\Http\Controllers\AccountantController::class, 'parentsCreditSystem'])->name('parents-credit-system');
        Route::get('/direct-payment', [App\Http\Controllers\AccountantController::class, 'directPayment'])->name('direct-payment');
        Route::get('/direct-payment/student', [App\Http\Controllers\AccountantController::class, 'studentPayment'])->name('direct-payment.student');
        Route::get('/direct-payment/custom', [App\Http\Controllers\AccountantController::class, 'customPayment'])->name('direct-payment.custom');
        Route::get('/sms-fee-defaulters', [App\Http\Controllers\AccountantController::class, 'smsFeeDefaulters'])->name('sms-fee-defaulters');
        Route::get('/deleted-fees', [App\Http\Controllers\AccountantController::class, 'deletedFees'])->name('deleted-fees');
        Route::get('/print-fee-vouchers', [App\Http\Controllers\AccountantController::class, 'printFeeVouchers'])->name('print-fee-vouchers');
        Route::get('/print-balance-sheet', [App\Http\Controllers\AccountantController::class, 'printBalanceSheet'])->name('print-balance-sheet');
        Route::get('/expense-management', [App\Http\Controllers\AccountantController::class, 'expenseManagement'])->name('expense-management');
        Route::get('/reporting-area', [App\Http\Controllers\AccountantController::class, 'reportingArea'])->name('reporting-area');
        Route::get('/academic-calendar', [App\Http\Controllers\AccountantController::class, 'academicCalendar'])->name('academic-calendar');
        Route::get('/stock-inventory', [App\Http\Controllers\AccountantController::class, 'stockInventory'])->name('stock-inventory');
    });
});

// Student Authentication Routes
Route::prefix('student')->name('student.')->group(function () {
    Route::get('/login', [App\Http\Controllers\StudentAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [App\Http\Controllers\StudentAuthController::class, 'login'])->name('login');
    Route::post('/logout', [App\Http\Controllers\StudentAuthController::class, 'logout'])->name('logout');
    
    // Protected Student Routes
    Route::middleware([App\Http\Middleware\StudentMiddleware::class])->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\StudentAuthController::class, 'dashboard'])->name('dashboard');
    });
});

// Admin Authentication Routes
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [App\Http\Controllers\AdminAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [App\Http\Controllers\AdminAuthController::class, 'login'])->name('login');
    Route::post('/logout', [App\Http\Controllers\AdminAuthController::class, 'logout'])->name('logout');
    
    // Protected Admin Routes
    Route::middleware([App\Http\Middleware\AdminMiddleware::class])->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\AdminAuthController::class, 'dashboard'])->name('dashboard');
    });
});

// Admission Management Routes
Route::get('/admission/admit-student', [App\Http\Controllers\AdmissionController::class, 'create'])->name('admission.admit-student');
Route::post('/admission/admit-student', [App\Http\Controllers\AdmissionController::class, 'store'])->name('admission.admit-student.store');
Route::get('/admission/get-sections', [App\Http\Controllers\AdmissionController::class, 'getSections'])->name('admission.get-sections');
Route::get('/admission/get-parent-by-id-card', [App\Http\Controllers\AdmissionController::class, 'getParentByIdCard'])->name('admission.get-parent-by-id-card');

Route::get('/admission/admit-bulk-student', [App\Http\Controllers\AdmissionController::class, 'bulkCreate'])->name('admission.admit-bulk-student');
Route::post('/admission/admit-bulk-student', [App\Http\Controllers\AdmissionController::class, 'bulkStore'])->name('admission.admit-bulk-student.store');
Route::get('/admission/download-csv-template', [App\Http\Controllers\AdmissionController::class, 'downloadCsvTemplate'])->name('admission.download-csv-template');

// Admission Request Routes
Route::get('/admission/request', [App\Http\Controllers\AdmissionRequestController::class, 'index'])->name('admission.request');
Route::post('/admission/request', [App\Http\Controllers\AdmissionRequestController::class, 'store'])->name('admission.request.store');
Route::put('/admission/request/{admission_request}', [App\Http\Controllers\AdmissionRequestController::class, 'update'])->name('admission.request.update');
Route::delete('/admission/request/{admission_request}', [App\Http\Controllers\AdmissionRequestController::class, 'destroy'])->name('admission.request.destroy');
Route::get('/admission/request/export/{format}', [App\Http\Controllers\AdmissionRequestController::class, 'export'])->name('admission.request.export');

Route::get('/admission/report', [App\Http\Controllers\AdmissionController::class, 'report'])->name('admission.report');

// Admission Inquiry Routes
Route::get('/admission/inquiry/manage', [App\Http\Controllers\AdmissionInquiryController::class, 'index'])->name('admission.inquiry.manage');
Route::post('/admission/inquiry', [App\Http\Controllers\AdmissionInquiryController::class, 'store'])->name('admission.inquiry.store');
Route::put('/admission/inquiry/{inquiry}', [App\Http\Controllers\AdmissionInquiryController::class, 'update'])->name('admission.inquiry.update');
Route::delete('/admission/inquiry/{inquiry}', [App\Http\Controllers\AdmissionInquiryController::class, 'destroy'])->name('admission.inquiry.destroy');
Route::get('/admission/inquiry/export/{format}', [App\Http\Controllers\AdmissionInquiryController::class, 'export'])->name('admission.inquiry.export');

Route::get('/admission/inquiry/send-sms', function () {
    return view('admission.inquiry.send-sms');
})->name('admission.inquiry.send-sms');

// Student Management Routes
Route::get('/student/information', [App\Http\Controllers\StudentController::class, 'index'])->name('student.information');
Route::get('/student/information/export/{format}', [App\Http\Controllers\StudentController::class, 'export'])->name('student.information.export');
Route::delete('/student/information/delete-all', [App\Http\Controllers\StudentController::class, 'deleteAll'])->name('student.information.delete-all');

// Student Promotion Routes (must be before /student/{student} route)
Route::get('/student/promotion', [App\Http\Controllers\StudentPromotionController::class, 'index'])->name('student.promotion');
Route::post('/student/promotion', [App\Http\Controllers\StudentPromotionController::class, 'promote'])->name('student.promotion.promote');
Route::get('/student/promotion/get-sections', [App\Http\Controllers\StudentPromotionController::class, 'getSections'])->name('student.promotion.get-sections');

// Student Birthday Routes (must be before /student/{student} route)
Route::get('/student/birthday', [App\Http\Controllers\StudentBirthdayController::class, 'index'])->name('student.birthday');
Route::get('/student/birthday/export/{format}', [App\Http\Controllers\StudentBirthdayController::class, 'export'])->name('student.birthday.export');

// Student Transfer Routes (must be before /student/{student} route)
Route::get('/student/transfer', [App\Http\Controllers\StudentTransferController::class, 'index'])->name('student.transfer');
Route::post('/student/transfer', [App\Http\Controllers\StudentTransferController::class, 'transfer'])->name('student.transfer.store');
Route::get('/student/transfer/get-students', [App\Http\Controllers\StudentTransferController::class, 'getStudents'])->name('student.transfer.get-students');
Route::get('/student/transfer/search-student', [App\Http\Controllers\StudentTransferController::class, 'searchStudent'])->name('student.transfer.search-student');

// Student Delete Route (must be before /student/{student} route)
Route::delete('/student/{student}', [App\Http\Controllers\StudentController::class, 'destroy'])->name('student.delete');

// Student View Route (must be last to avoid conflicts)
Route::get('/student/{student}', [App\Http\Controllers\StudentController::class, 'show'])->name('student.view');

Route::get('/student/info-report', function () {
    return view('student.info-report');
})->name('student.info-report');

Route::get('/dashboard/crm', [DashboardController::class, 'crm'])->name('dashboard.crm');

// Parent Account Routes
Route::get('/parent/manage-access', [App\Http\Controllers\ParentAccountController::class, 'index'])->name('parent.manage-access');
Route::post('/parent/manage-access', [App\Http\Controllers\ParentAccountController::class, 'store'])->name('parent.manage-access.store');
Route::delete('/parent/manage-access/delete-all', [App\Http\Controllers\ParentAccountController::class, 'deleteAll'])->name('parent.manage-access.delete-all');
Route::put('/parent/manage-access/{parent_account}', [App\Http\Controllers\ParentAccountController::class, 'update'])->name('parent.manage-access.update');
Route::put('/parent/manage-access/{parent_account}/reset-password', [App\Http\Controllers\ParentAccountController::class, 'resetPassword'])->name('parent.manage-access.reset-password');
Route::post('/parent/manage-access/{parent_account}/connect-student', [App\Http\Controllers\ParentAccountController::class, 'connectStudent'])->name('parent.manage-access.connect-student');
Route::post('/parent/manage-access/{parent_account}/disconnect-student', [App\Http\Controllers\ParentAccountController::class, 'disconnectStudent'])->name('parent.manage-access.disconnect-student');
Route::get('/parent/manage-access/get-all-students', [App\Http\Controllers\ParentAccountController::class, 'getAllStudentsForConnect'])->name('parent.manage-access.get-all-students');
Route::delete('/parent/manage-access/{parent_account}', [App\Http\Controllers\ParentAccountController::class, 'destroy'])->name('parent.manage-access.destroy');
Route::get('/parent/manage-access/export/{format}', [App\Http\Controllers\ParentAccountController::class, 'export'])->name('parent.manage-access.export');

Route::get('/parent/account-request', [App\Http\Controllers\ParentAccountRequestController::class, 'index'])->name('parent.account-request');
Route::get('/parent/account-request/export/{format}', [App\Http\Controllers\ParentAccountRequestController::class, 'export'])->name('parent.account-request.export');

Route::get('/parent/print-gate-passes', [App\Http\Controllers\PrintGatePassesController::class, 'index'])->name('parent.print-gate-passes');
Route::post('/parent/print-gate-passes', [App\Http\Controllers\PrintGatePassesController::class, 'index'])->name('parent.print-gate-passes.filter');
Route::get('/parent/print-gate-passes/print', [App\Http\Controllers\PrintGatePassesController::class, 'print'])->name('parent.print-gate-passes.print');

Route::get('/parent/info-request', [App\Http\Controllers\ParentInfoRequestController::class, 'index'])->name('parent.info-request');
Route::post('/parent/info-request/filter', [App\Http\Controllers\ParentInfoRequestController::class, 'filter'])->name('parent.info-request.filter');

Route::get('/dashboard/project-management', [DashboardController::class, 'projectManagement'])->name('dashboard.project-management');

// Staff Management Routes
Route::get('/staff/management', [App\Http\Controllers\StaffManagementController::class, 'index'])->name('staff.management');
Route::get('/staff/management/next-emp-id', [App\Http\Controllers\StaffManagementController::class, 'getNextEmployeeId'])->name('staff.management.next-emp-id');
Route::post('/staff/management', [App\Http\Controllers\StaffManagementController::class, 'store'])->name('staff.management.store');
Route::delete('/staff/management/delete-all', [App\Http\Controllers\StaffManagementController::class, 'deleteAll'])->name('staff.management.delete-all');
Route::get('/staff/management/{staff}', [App\Http\Controllers\StaffManagementController::class, 'show'])->name('staff.management.show');
Route::put('/staff/management/{staff}', [App\Http\Controllers\StaffManagementController::class, 'update'])->name('staff.management.update');
Route::post('/staff/management/{staff}/toggle-status', [App\Http\Controllers\StaffManagementController::class, 'toggleStatus'])->name('staff.management.toggle-status');
Route::delete('/staff/management/{staff}', [App\Http\Controllers\StaffManagementController::class, 'destroy'])->name('staff.management.destroy');
Route::get('/staff/management/export/{format}', [App\Http\Controllers\StaffManagementController::class, 'export'])->name('staff.management.export');

Route::get('/staff/birthday', [App\Http\Controllers\StaffBirthdayController::class, 'index'])->name('staff.birthday');
Route::get('/staff/birthday/export/{format}', [App\Http\Controllers\StaffBirthdayController::class, 'export'])->name('staff.birthday.export');

Route::get('/staff/job-inquiry', [App\Http\Controllers\JobInquiryController::class, 'index'])->name('staff.job-inquiry');
Route::post('/staff/job-inquiry', [App\Http\Controllers\JobInquiryController::class, 'store'])->name('staff.job-inquiry.store');
Route::delete('/staff/job-inquiry/delete-all', [App\Http\Controllers\JobInquiryController::class, 'deleteAll'])->name('staff.job-inquiry.delete-all');
Route::get('/staff/job-inquiry/{job_inquiry}', [App\Http\Controllers\JobInquiryController::class, 'show'])->name('staff.job-inquiry.show');
Route::put('/staff/job-inquiry/{job_inquiry}', [App\Http\Controllers\JobInquiryController::class, 'update'])->name('staff.job-inquiry.update');
Route::delete('/staff/job-inquiry/{job_inquiry}', [App\Http\Controllers\JobInquiryController::class, 'destroy'])->name('staff.job-inquiry.destroy');
Route::get('/staff/job-inquiry/export/{format}', [App\Http\Controllers\JobInquiryController::class, 'export'])->name('staff.job-inquiry.export');

Route::get('/dashboard/lms', [DashboardController::class, 'lms'])->name('dashboard.lms');

// Task Management Routes
Route::get('/task-management', [App\Http\Controllers\TaskManagementController::class, 'index'])->name('task-management');
Route::post('/task-management', [App\Http\Controllers\TaskManagementController::class, 'store'])->name('task-management.store');
Route::delete('/task-management/delete-all', [App\Http\Controllers\TaskManagementController::class, 'deleteAll'])->name('task-management.delete-all');
Route::get('/task-management/{task}', [App\Http\Controllers\TaskManagementController::class, 'show'])->name('task-management.show');
Route::put('/task-management/{task}', [App\Http\Controllers\TaskManagementController::class, 'update'])->name('task-management.update');
Route::patch('/task-management/{task}/status', [App\Http\Controllers\TaskManagementController::class, 'updateStatus'])->name('task-management.update-status');
Route::delete('/task-management/{task}', [App\Http\Controllers\TaskManagementController::class, 'destroy'])->name('task-management.destroy');
Route::get('/task-management/export/{format}', [App\Http\Controllers\TaskManagementController::class, 'export'])->name('task-management.export');

Route::get('/dashboard/help-desk', [DashboardController::class, 'helpDesk'])->name('dashboard.help-desk');

// ID Card Printing Routes
Route::get('/id-card/print-student', [App\Http\Controllers\PrintStudentCardController::class, 'index'])->name('id-card.print-student');
Route::get('/id-card/print-student/print', [App\Http\Controllers\PrintStudentCardController::class, 'print'])->name('id-card.print-student.print');

Route::get('/id-card/print-staff', [App\Http\Controllers\PrintStaffCardController::class, 'index'])->name('id-card.print-staff');
Route::get('/id-card/print-staff/print', [App\Http\Controllers\PrintStaffCardController::class, 'print'])->name('id-card.print-staff.print');

// Accountant Routes
Route::get('/accountants', [App\Http\Controllers\AccountantController::class, 'index'])->name('accountants');
Route::post('/accountants', [App\Http\Controllers\AccountantController::class, 'store'])->name('accountants.store');
Route::get('/accountants/{accountant}', [App\Http\Controllers\AccountantController::class, 'show'])->name('accountants.show');
Route::put('/accountants/{accountant}', [App\Http\Controllers\AccountantController::class, 'update'])->name('accountants.update');
Route::delete('/accountants/{accountant}', [App\Http\Controllers\AccountantController::class, 'destroy'])->name('accountants.destroy');
Route::post('/accountants/{accountant}/toggle-app-login', [App\Http\Controllers\AccountantController::class, 'toggleAppLogin'])->name('accountants.toggle-app-login');
Route::post('/accountants/{accountant}/toggle-web-login', [App\Http\Controllers\AccountantController::class, 'toggleWebLogin'])->name('accountants.toggle-web-login');
Route::get('/accountants/export/{format}', [App\Http\Controllers\AccountantController::class, 'export'])->name('accountants.export');

Route::get('/dashboard/hr-management', [DashboardController::class, 'hrManagement'])->name('dashboard.hr-management');

// Accounting Routes (Protected - Admin & Super Admin Access)
Route::middleware([App\Http\Middleware\AdminMiddleware::class])->group(function () {
    Route::get('/accounting/generate-monthly-fee', [App\Http\Controllers\MonthlyFeeController::class, 'create'])->name('accounting.generate-monthly-fee');
    Route::post('/accounting/generate-monthly-fee', [App\Http\Controllers\MonthlyFeeController::class, 'store'])->name('accounting.generate-monthly-fee.store');
    Route::get('/accounting/get-sections-by-class', [App\Http\Controllers\MonthlyFeeController::class, 'getSectionsByClass'])->name('accounting.get-sections-by-class');
    Route::get('/accounting/get-students-with-fee-status', [App\Http\Controllers\MonthlyFeeController::class, 'getStudentsWithFeeStatus'])->name('accounting.get-students-with-fee-status');

    Route::get('/accounting/generate-custom-fee', [App\Http\Controllers\CustomFeeController::class, 'create'])->name('accounting.generate-custom-fee');
    Route::post('/accounting/generate-custom-fee', [App\Http\Controllers\CustomFeeController::class, 'store'])->name('accounting.generate-custom-fee.store');
    Route::get('/accounting/custom-fee/get-sections-by-class', [App\Http\Controllers\CustomFeeController::class, 'getSectionsByClass'])->name('accounting.custom-fee.get-sections-by-class');
    Route::get('/accounting/custom-fee/get-students', [App\Http\Controllers\CustomFeeController::class, 'getStudents'])->name('accounting.custom-fee.get-students');

    Route::get('/accounting/generate-transport-fee', [App\Http\Controllers\TransportFeeController::class, 'create'])->name('accounting.generate-transport-fee');
    Route::post('/accounting/generate-transport-fee', [App\Http\Controllers\TransportFeeController::class, 'store'])->name('accounting.generate-transport-fee.store');
    Route::get('/accounting/transport-fee/get-sections-by-class', [App\Http\Controllers\TransportFeeController::class, 'getSectionsByClass'])->name('accounting.transport-fee.get-sections-by-class');
});

// Fee Type Routes
Route::get('/accounting/fee-type', [App\Http\Controllers\FeeTypeController::class, 'index'])->name('accounting.fee-type');
Route::post('/accounting/fee-type', [App\Http\Controllers\FeeTypeController::class, 'store'])->name('accounting.fee-type.store');
Route::get('/accounting/fee-type/export/{format}', [App\Http\Controllers\FeeTypeController::class, 'export'])->name('accounting.fee-type.export');
Route::get('/accounting/fee-type/{feeType}', [App\Http\Controllers\FeeTypeController::class, 'show'])->name('accounting.fee-type.show');
Route::put('/accounting/fee-type/{feeType}', [App\Http\Controllers\FeeTypeController::class, 'update'])->name('accounting.fee-type.update');
Route::delete('/accounting/fee-type/{feeType}', [App\Http\Controllers\FeeTypeController::class, 'destroy'])->name('accounting.fee-type.destroy');

// Family Fee Calculator Route
Route::get('/accounting/family-fee-calculator', function () {
    // Get only parents from ParentAccount model (Manage Access me add kiye gaye)
    $parentAccounts = \App\Models\ParentAccount::select('id', 'name')
        ->orderBy('name', 'asc')
        ->get();
    
    // Create families list with only parent accounts
    $families = $parentAccounts->map(function ($parent) {
        return [
            'id' => 'parent_' . $parent->id,
            'name' => $parent->name,
            'type' => 'Parent Account'
        ];
    });
    
    return view('accounting.family-fee-calculator', compact('families'));
})->name('accounting.family-fee-calculator');

// Family Fee Calculator - Get Students by Family
Route::get('/accounting/family-fee-calculator/students', function (\Illuminate\Http\Request $request) {
    $familyId = $request->get('family_id');
    
    if (!$familyId) {
        return response()->json(['error' => 'Family ID is required'], 400);
    }
    
    $students = collect();
    
    // Only handle parent account ID (since we only show parent accounts now)
    if (str_starts_with($familyId, 'parent_')) {
        $parentId = str_replace('parent_', '', $familyId);
        $students = \App\Models\Student::where('parent_account_id', $parentId)
            ->select('id', 'student_name', 'class', 'section', 'student_code')
            ->orderBy('student_name', 'asc')
            ->get();
    }
    
    // Format students for response
    $formattedStudents = $students->map(function ($student) {
        return [
            'id' => $student->id,
            'name' => $student->student_name,
            'class' => $student->class ?? 'N/A',
            'section' => $student->section ?? 'N/A',
            'admission_no' => $student->student_code ?? 'N/A'
        ];
    });
    
    return response()->json(['students' => $formattedStudents]);
})->name('accounting.family-fee-calculator.students');

// Family Fee Calculator - Search by Father ID Card
Route::get('/accounting/family-fee-calculator/search-by-id-card', function (\Illuminate\Http\Request $request) {
    $fatherIdCard = $request->get('father_id_card');
    
    if (!$fatherIdCard) {
        return response()->json([
            'success' => false,
            'message' => 'Father ID Card is required'
        ], 400);
    }
    
    // Clean the input CNIC
    $cleanedIdCard = trim($fatherIdCard);
    $lowerIdCard = strtolower($cleanedIdCard);
    $normalizedIdCard = str_replace(['-', ' ', '_'], '', $lowerIdCard);
    
    // Find parent account by ID card number (case-insensitive, trimmed)
    $parentAccount = \App\Models\ParentAccount::where(function($query) use ($lowerIdCard, $normalizedIdCard) {
        $query->whereRaw('LOWER(TRIM(id_card_number)) = ?', [$lowerIdCard])
              ->orWhereRaw('LOWER(REPLACE(REPLACE(REPLACE(TRIM(id_card_number), "-", ""), " ", ""), "_", "")) = ?', [$normalizedIdCard]);
    })->first();
    
    // Get ALL students by father_id_card - use comprehensive query that handles all cases
    $studentsByFatherIdCard = \App\Models\Student::where(function($query) use ($cleanedIdCard) {
        // Try all possible matching strategies
        $query->where('father_id_card', $cleanedIdCard)  // Exact match
              ->orWhere('father_id_card', 'LIKE', $cleanedIdCard)  // LIKE exact
              ->orWhere('father_id_card', 'LIKE', '%' . $cleanedIdCard . '%')  // LIKE partial
              ->orWhereRaw('LOWER(father_id_card) = LOWER(?)', [$cleanedIdCard])  // Case-insensitive
              ->orWhereRaw('TRIM(father_id_card) = ?', [$cleanedIdCard])  // Trimmed
              ->orWhereRaw('LOWER(TRIM(father_id_card)) = LOWER(TRIM(?))', [$cleanedIdCard])  // Case-insensitive trimmed
              ->orWhereRaw('CAST(father_id_card AS CHAR) = ?', [$cleanedIdCard]);  // Cast to string (handles numeric types)
    })
    ->select('id', 'student_name', 'student_code', 'class', 'section', 'campus', 'monthly_fee', 'father_name', 'father_phone', 'father_email', 'home_address')
    ->get();
    
    // If still no results, try one more time with DB::raw for absolute certainty
    if ($studentsByFatherIdCard->isEmpty()) {
        $studentsByFatherIdCard = \App\Models\Student::whereRaw('father_id_card = ? OR father_id_card LIKE ? OR LOWER(father_id_card) = LOWER(?)', 
            [$cleanedIdCard, '%' . $cleanedIdCard . '%', $cleanedIdCard])
            ->select('id', 'student_name', 'student_code', 'class', 'section', 'campus', 'monthly_fee', 'father_name', 'father_phone', 'father_email', 'home_address')
            ->get();
    }
    
    // If parent account exists, also get students connected via parent_account_id
    $studentsByParentAccount = collect();
    if ($parentAccount) {
        $studentsByParentAccount = \App\Models\Student::where('parent_account_id', $parentAccount->id)
            ->select('id', 'student_name', 'student_code', 'class', 'section', 'campus', 'monthly_fee')
            ->get();
    }
    
    // Merge both collections and remove duplicates by student id, then sort by name
    $students = $studentsByParentAccount->merge($studentsByFatherIdCard)->unique('id')->sortBy('student_name')->values();
    
    // Debug: Check how many students found from each source
    \Log::info('Fee Calculator Search', [
        'father_id_card' => $fatherIdCard,
        'cleaned_id_card' => $cleanedIdCard,
        'students_by_father_id_card' => $studentsByFatherIdCard->count(),
        'students_by_parent_account' => $studentsByParentAccount->count(),
        'total_students' => $students->count()
    ]);
    
    // If no students found at all, return not found with detailed debug info
    if ($students->isEmpty()) {
        // Try one more direct query to see if ANY students exist with this ID
        $testQuery = \App\Models\Student::where('father_id_card', 'LIKE', '%' . $cleanedIdCard . '%')->count();
        
        return response()->json([
            'success' => true,
            'found' => false,
            'message' => 'No children found with this Father ID Card Number',
            'debug' => [
                'searched_id_card' => $cleanedIdCard,
                'original_input' => $fatherIdCard,
                'students_by_father_id_card_count' => $studentsByFatherIdCard->count(),
                'students_by_parent_account_count' => $studentsByParentAccount->count(),
                'parent_account_found' => $parentAccount ? true : false,
                'test_like_query_count' => $testQuery,
                'all_queries_tried' => 7
            ]
        ]);
    }
    
    // Prepare father information (use parent account if available, otherwise use first student's father info)
    $fatherInfo = [];
    if ($parentAccount) {
        $fatherInfo = [
            'id' => $parentAccount->id,
            'name' => $parentAccount->name,
            'id_card_number' => $parentAccount->id_card_number,
            'phone' => $parentAccount->phone,
            'email' => $parentAccount->email,
            'address' => $parentAccount->address,
        ];
    } else {
        // If no parent account, try to get father info from first student
        $firstStudent = $students->first();
        $fatherInfo = [
            'id' => null,
            'name' => $firstStudent->father_name ?? 'N/A',
            'id_card_number' => $fatherIdCard,
            'phone' => $firstStudent->father_phone ?? 'N/A',
            'email' => $firstStudent->father_email ?? 'N/A',
            'address' => $firstStudent->home_address ?? 'N/A',
        ];
    }
    
    return response()->json([
        'success' => true,
        'found' => true,
        'father' => $fatherInfo,
        'students' => $students->map(function ($student) {
            return [
                'id' => $student->id,
                'student_name' => $student->student_name,
                'student_code' => $student->student_code,
                'class' => $student->class,
                'section' => $student->section,
                'campus' => $student->campus,
                'monthly_fee' => $student->monthly_fee,
            ];
        })->values()->toArray()
    ]);
})->name('accounting.family-fee-calculator.search-by-id-card');

// Manage Advance Fee Routes
Route::get('/accounting/manage-advance-fee', [App\Http\Controllers\AdvanceFeeController::class, 'index'])->name('accounting.manage-advance-fee.index');
Route::post('/accounting/manage-advance-fee', [App\Http\Controllers\AdvanceFeeController::class, 'store'])->name('accounting.manage-advance-fee.store');
Route::get('/accounting/manage-advance-fee/export/{format}', [App\Http\Controllers\AdvanceFeeController::class, 'export'])->name('accounting.manage-advance-fee.export');
Route::get('/accounting/manage-advance-fee/{advanceFee}', [App\Http\Controllers\AdvanceFeeController::class, 'show'])->name('accounting.manage-advance-fee.show');
Route::put('/accounting/manage-advance-fee/{advanceFee}', [App\Http\Controllers\AdvanceFeeController::class, 'update'])->name('accounting.manage-advance-fee.update');
Route::delete('/accounting/manage-advance-fee/{advanceFee}', [App\Http\Controllers\AdvanceFeeController::class, 'destroy'])->name('accounting.manage-advance-fee.destroy');

// Parent Wallet System Routes
Route::get('/accounting/parent-wallet/installments', function () {
    return view('accounting.parent-wallet.installments');
})->name('accounting.parent-wallet.installments');

Route::get('/accounting/parent-wallet/sms-fee-duration', function () {
    return view('accounting.parent-wallet.sms-fee-duration');
})->name('accounting.parent-wallet.sms-fee-duration');

Route::get('/accounting/parent-wallet/print-balance-sheet', function () {
    return view('accounting.parent-wallet.print-balance-sheet');
})->name('accounting.parent-wallet.print-balance-sheet');

Route::get('/accounting/parent-wallet/deleted-fees', function () {
    return view('accounting.parent-wallet.deleted-fees');
})->name('accounting.parent-wallet.deleted-fees');

Route::get('/accounting/parent-wallet/bulk-fee-payment', function () {
    return view('accounting.parent-wallet.bulk-fee-payment');
})->name('accounting.parent-wallet.bulk-fee-payment');

Route::get('/accounting/parent-wallet/discount-student', function () {
    return view('accounting.parent-wallet.discount-student');
})->name('accounting.parent-wallet.discount-student');

Route::get('/accounting/parent-wallet/accounts-settlement', function () {
    return view('accounting.parent-wallet.accounts-settlement');
})->name('accounting.parent-wallet.accounts-settlement');

Route::get('/accounting/parent-wallet/online-rejected-payments', function () {
    return view('accounting.parent-wallet.online-rejected-payments');
})->name('accounting.parent-wallet.online-rejected-payments');

// Direct Payment Routes
Route::get('/accounting/direct-payment/student', [App\Http\Controllers\StudentPaymentController::class, 'create'])->name('accounting.direct-payment.student');
Route::post('/accounting/direct-payment/student', [App\Http\Controllers\StudentPaymentController::class, 'store'])->name('accounting.direct-payment.student.store');

// Make Installment Route
Route::get('/accounting/make-installment', function (\Illuminate\Http\Request $request) {
    $studentCode = $request->get('student_code');
    $student = null;
    
    if ($studentCode) {
        $student = \App\Models\Student::where('student_code', $studentCode)->first();
    }
    
    return view('accounting.parent-wallet.installments', compact('student', 'studentCode'));
})->name('accounting.make-installment');

// Particular Receipt Route
Route::get('/accounting/particular-receipt', function (\Illuminate\Http\Request $request) {
    $studentCode = $request->get('student_code');
    $student = null;
    
    if ($studentCode) {
        $student = \App\Models\Student::where('student_code', $studentCode)->first();
    }
    
    // Get student's payment history
    $payments = [];
    if ($student) {
        $payments = \App\Models\StudentPayment::where('student_code', $studentCode)
            ->where('method', '!=', 'Generated')
            ->orderBy('payment_date', 'desc')
            ->get();
    }
    
    return view('accounting.particular-receipt', compact('student', 'studentCode', 'payments'));
})->name('accounting.particular-receipt');

// Full Payment Route - Auto create payment record
Route::post('/fee-payment/full-payment', function (\Illuminate\Http\Request $request) {
    $studentCode = $request->get('student_code');
    
    if (!$studentCode) {
        return response()->json([
            'success' => false,
            'message' => 'Student code is required'
        ], 400);
    }
    
    // Get student data
    $student = \App\Models\Student::where('student_code', $studentCode)->first();
    
    if (!$student) {
        return response()->json([
            'success' => false,
            'message' => 'Student not found'
        ], 404);
    }
    
    // Calculate unpaid amount
    $totalPaid = \App\Models\StudentPayment::where('student_code', $studentCode)
        ->where('method', '!=', 'Generated')
        ->sum('payment_amount');
    $monthlyFee = $student->monthly_fee ?? 0;
    $unpaidAmount = max(0, $monthlyFee - $totalPaid);
    
    if ($unpaidAmount <= 0) {
        return response()->json([
            'success' => false,
            'message' => 'No unpaid amount found. Fee is already paid.'
        ], 400);
    }
    
    // Get current month and year for payment title
    $currentMonth = date('F');
    $currentYear = date('Y');
    $paymentTitle = "Monthly Fee - {$currentMonth} {$currentYear}";
    
    // Create payment record
    $payment = \App\Models\StudentPayment::create([
        'campus' => $student->campus,
        'student_code' => $studentCode,
        'payment_title' => $paymentTitle,
        'payment_amount' => $unpaidAmount,
        'discount' => 0,
        'method' => 'Cash Payment',
        'payment_date' => date('Y-m-d'),
        'sms_notification' => 'Yes',
        'late_fee' => 0,
        'accountant' => auth()->check() ? (auth()->user()->name ?? null) : null,
    ]);
    
    return response()->json([
        'success' => true,
        'message' => 'Full payment recorded successfully!',
        'payment' => [
            'id' => $payment->id,
            'amount' => $payment->payment_amount,
            'date' => $payment->payment_date,
        ]
    ]);
})->name('fee-payment.full-payment');

Route::get('/accounting/direct-payment/custom', [App\Http\Controllers\CustomPaymentController::class, 'create'])->name('accounting.direct-payment.custom');
Route::post('/accounting/direct-payment/custom', [App\Http\Controllers\CustomPaymentController::class, 'store'])->name('accounting.direct-payment.custom.store');

// Fee Increment Routes
Route::get('/accounting/fee-increment/percentage', [App\Http\Controllers\FeeIncrementPercentageController::class, 'create'])->name('accounting.fee-increment.percentage');
Route::post('/accounting/fee-increment/percentage', [App\Http\Controllers\FeeIncrementPercentageController::class, 'store'])->name('accounting.fee-increment.percentage.store');

Route::get('/accounting/fee-increment/amount', [App\Http\Controllers\FeeIncrementAmountController::class, 'create'])->name('accounting.fee-increment.amount');
Route::post('/accounting/fee-increment/amount', [App\Http\Controllers\FeeIncrementAmountController::class, 'store'])->name('accounting.fee-increment.amount.store');

// Fee Document Routes
Route::get('/accounting/fee-document/decrement-percentage', [App\Http\Controllers\FeeDecrementPercentageController::class, 'create'])->name('accounting.fee-document.decrement-percentage');
Route::post('/accounting/fee-document/decrement-percentage', [App\Http\Controllers\FeeDecrementPercentageController::class, 'store'])->name('accounting.fee-document.decrement-percentage.store');

Route::get('/accounting/fee-document/decrement-amount', [App\Http\Controllers\FeeDecrementAmountController::class, 'create'])->name('accounting.fee-document.decrement-amount');
Route::post('/accounting/fee-document/decrement-amount', [App\Http\Controllers\FeeDecrementAmountController::class, 'store'])->name('accounting.fee-document.decrement-amount.store');
 
// Fee Voucher Routes
Route::get('/accounting/fee-voucher/student', [App\Http\Controllers\StudentVoucherController::class, 'index'])->name('accounting.fee-voucher.student');
Route::get('/accounting/fee-voucher/get-sections-by-class', [App\Http\Controllers\StudentVoucherController::class, 'getSectionsByClass'])->name('accounting.fee-voucher.get-sections-by-class');
Route::get('/accounting/fee-voucher/print', [App\Http\Controllers\StudentVoucherController::class, 'print'])->name('accounting.fee-voucher.print');
Route::get('/accounting/fee-voucher/family', [App\Http\Controllers\FamilyVoucherController::class, 'index'])->name('accounting.fee-voucher.family');

// Parent Complain Route
Route::get('/parent-complain', [App\Http\Controllers\ParentComplaintController::class, 'index'])->name('parent-complain');
Route::get('/parent-complain/export/{format}', function () {
    // TODO: Add export functionality for parent complaints if needed
    return redirect()->route('parent-complain')->with('error', 'Export functionality will be implemented soon.');
})->name('parent-complain.export');

// Classes and Section Routes
Route::get('/classes/manage-classes', [App\Http\Controllers\ManageClassesController::class, 'index'])->name('classes.manage-classes');
Route::post('/classes/manage-classes', [App\Http\Controllers\ManageClassesController::class, 'store'])->name('classes.manage-classes.store');
Route::put('/classes/manage-classes/{class_model}', [App\Http\Controllers\ManageClassesController::class, 'update'])->name('classes.manage-classes.update');
Route::delete('/classes/manage-classes/{class_model}', [App\Http\Controllers\ManageClassesController::class, 'destroy'])->name('classes.manage-classes.destroy');
Route::get('/classes/manage-classes/export/{format}', [App\Http\Controllers\ManageClassesController::class, 'export'])->name('classes.manage-classes.export');

Route::get('/classes/manage-section', [App\Http\Controllers\ManageSectionController::class, 'index'])->name('classes.manage-section');
Route::post('/classes/manage-section', [App\Http\Controllers\ManageSectionController::class, 'store'])->name('classes.manage-section.store');
Route::put('/classes/manage-section/{section}', [App\Http\Controllers\ManageSectionController::class, 'update'])->name('classes.manage-section.update');
Route::delete('/classes/manage-section/{section}', [App\Http\Controllers\ManageSectionController::class, 'destroy'])->name('classes.manage-section.destroy');
Route::get('/classes/manage-section/export/{format}', [App\Http\Controllers\ManageSectionController::class, 'export'])->name('classes.manage-section.export');

// Manage Subjects Routes
Route::get('/manage-subjects', [App\Http\Controllers\ManageSubjectsController::class, 'index'])->name('manage-subjects');
Route::post('/manage-subjects', [App\Http\Controllers\ManageSubjectsController::class, 'store'])->name('manage-subjects.store');
Route::get('/manage-subjects/get-sections-by-class', [App\Http\Controllers\ManageSubjectsController::class, 'getSectionsByClass'])->name('manage-subjects.get-sections-by-class');
Route::get('/manage-subjects/export/{format}', [App\Http\Controllers\ManageSubjectsController::class, 'export'])->name('manage-subjects.export');

// Manage Attendance Routes
Route::get('/attendance/student', [App\Http\Controllers\StudentAttendanceController::class, 'index'])->name('attendance.student')->middleware([App\Http\Middleware\AdminOrStaffMiddleware::class]);
Route::get('/attendance/student/get-sections-by-class', [App\Http\Controllers\StudentAttendanceController::class, 'getSectionsByClass'])->name('attendance.student.get-sections-by-class');
Route::post('/attendance/student/store', [App\Http\Controllers\StudentAttendanceController::class, 'store'])->name('attendance.student.store')->middleware([App\Http\Middleware\AdminOrStaffMiddleware::class]);

Route::get('/attendance/staff', function () {
    return view('attendance.staff');
})->name('attendance.staff');

Route::get('/attendance/barcode', function () {
    return view('attendance.barcode');
})->name('attendance.barcode');

Route::get('/attendance/biometric', function () {
    return view('attendance.biometric');
})->name('attendance.biometric');

Route::get('/attendance/facial-record', function () {
    return view('attendance.facial-record');
})->name('attendance.facial-record');

Route::get('/attendance/account', [App\Http\Controllers\AttendanceAccountController::class, 'index'])->name('attendance.account');
Route::post('/attendance/account', [App\Http\Controllers\AttendanceAccountController::class, 'store'])->name('attendance.account.store');
Route::put('/attendance/account/{attendance_account}', [App\Http\Controllers\AttendanceAccountController::class, 'update'])->name('attendance.account.update');
Route::delete('/attendance/account/{attendance_account}', [App\Http\Controllers\AttendanceAccountController::class, 'destroy'])->name('attendance.account.destroy');
Route::get('/attendance/account/export/{format}', [App\Http\Controllers\AttendanceAccountController::class, 'export'])->name('attendance.account.export');

Route::get('/attendance/report', [App\Http\Controllers\AttendanceReportController::class, 'index'])->name('attendance.report');

// Online Classes Routes
Route::get('/online-classes', [App\Http\Controllers\OnlineClassesController::class, 'index'])->name('online-classes');
Route::post('/online-classes', [App\Http\Controllers\OnlineClassesController::class, 'store'])->name('online-classes.store');
Route::put('/online-classes/{online_class}', [App\Http\Controllers\OnlineClassesController::class, 'update'])->name('online-classes.update');
Route::delete('/online-classes/{online_class}', [App\Http\Controllers\OnlineClassesController::class, 'destroy'])->name('online-classes.destroy');
Route::get('/online-classes/export/{format}', [App\Http\Controllers\OnlineClassesController::class, 'export'])->name('online-classes.export');
Route::get('/online-classes/get-sections', [App\Http\Controllers\OnlineClassesController::class, 'getSections'])->name('online-classes.get-sections');

// Timetable Management Routes
Route::get('/timetable/add', [App\Http\Controllers\TimetableController::class, 'add'])->name('timetable.add');
Route::post('/timetable/add', [App\Http\Controllers\TimetableController::class, 'store'])->name('timetable.store');
Route::get('/timetable/get-sections-by-class', [App\Http\Controllers\TimetableController::class, 'getSectionsByClass'])->name('timetable.get-sections-by-class');

Route::get('/timetable/manage', [App\Http\Controllers\TimetableController::class, 'index'])->name('timetable.manage');
Route::get('/timetable/{timetable}/edit', [App\Http\Controllers\TimetableController::class, 'edit'])->name('timetable.edit');
Route::put('/timetable/{timetable}', [App\Http\Controllers\TimetableController::class, 'update'])->name('timetable.update');
Route::delete('/timetable/{timetable}', [App\Http\Controllers\TimetableController::class, 'destroy'])->name('timetable.destroy');
Route::get('/timetable/export/{format}', [App\Http\Controllers\TimetableController::class, 'export'])->name('timetable.export');

// Events Management Routes
Route::prefix('events')->name('events.')->group(function () {
    Route::get('/manage', [App\Http\Controllers\EventController::class, 'index'])->name('manage');
    Route::post('/', [App\Http\Controllers\EventController::class, 'store'])->name('store');
    Route::get('/{event}', [App\Http\Controllers\EventController::class, 'show'])->name('show');
    Route::put('/{event}', [App\Http\Controllers\EventController::class, 'update'])->name('update');
    Route::delete('/{event}', [App\Http\Controllers\EventController::class, 'destroy'])->name('destroy');
    Route::get('/export/{format}', [App\Http\Controllers\EventController::class, 'export'])->name('export');
});

// Academic Holiday Calendar Routes
Route::prefix('academic-calendar')->name('academic-calendar.')->group(function () {
    Route::get('/manage-events', [App\Http\Controllers\EventController::class, 'index'])->name('manage-events');
    Route::get('/view', [App\Http\Controllers\EventController::class, 'calendarView'])->name('view');
});

// Fee Management Route
Route::get('/fee-management', [App\Http\Controllers\FeeManagementController::class, 'index'])->name('fee-management');

// Fee Payment Route
Route::get('/fee-payment', [App\Http\Controllers\FeePaymentController::class, 'index'])->name('fee-payment');

// Fee Payment - Search Student
Route::get('/fee-payment/search-student', function (\Illuminate\Http\Request $request) {
    $search = $request->get('search');
    
    if (!$search) {
        return response()->json([
            'success' => false,
            'message' => 'Search term is required'
        ], 400);
    }
    
    $searchLower = strtolower(trim($search));
    
    // Search students by name or code
    $students = \App\Models\Student::where(function($query) use ($search, $searchLower) {
            $query->whereRaw('LOWER(student_name) LIKE ?', ["%{$searchLower}%"])
                  ->orWhere('student_code', 'like', "%{$search}%")
                  ->orWhere('gr_number', 'like', "%{$search}%");
        })
        ->select('id', 'student_name', 'student_code', 'father_name', 'class', 'section', 'campus', 'monthly_fee')
        ->orderBy('student_name', 'asc')
        ->limit(50)
        ->get();
    
    return response()->json([
        'success' => true,
        'students' => $students->map(function ($student) {
            // Check if student has unpaid fees
            $totalPaid = \App\Models\StudentPayment::where('student_code', $student->student_code)
                ->sum('payment_amount');
            $monthlyFee = $student->monthly_fee ?? 0;
            $hasUnpaid = ($monthlyFee > 0 && $totalPaid < $monthlyFee);
            $unpaidAmount = max(0, $monthlyFee - $totalPaid);
            
            return [
                'id' => $student->id,
                'student_name' => $student->student_name,
                'student_code' => $student->student_code,
                'father_name' => $student->father_name,
                'class' => $student->class,
                'section' => $student->section,
                'campus' => $student->campus,
                'monthly_fee' => $student->monthly_fee,
                'has_unpaid' => $hasUnpaid,
                'unpaid_amount' => $unpaidAmount,
            ];
        })
    ]);
})->name('fee-payment.search-student');

// Fee Payment - Search Student By CNIC / Parent ID
Route::get('/fee-payment/search-by-cnic', function (\Illuminate\Http\Request $request) {
    $cnic = $request->get('cnic');
    
    if (!$cnic) {
        return response()->json([
            'success' => false,
            'message' => 'CNIC / Parent ID is required'
        ], 400);
    }
    
    // Clean and normalize the input CNIC
    $cleanedCnic = trim($cnic);
    $normalizedInputCnic = str_replace(['-', ' ', '_', '.'], '', strtolower($cleanedCnic));
    
    // Find parent account by ID card number (exact match after normalization)
    $parentAccount = \App\Models\ParentAccount::where(function($query) use ($normalizedInputCnic, $cleanedCnic) {
        $query->whereRaw('LOWER(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(id_card_number), "-", ""), " ", ""), "_", ""), ".", "")) = ?', [$normalizedInputCnic])
              ->orWhereRaw('LOWER(TRIM(id_card_number)) = LOWER(TRIM(?))', [$cleanedCnic]);
    })->first();
    
    // Get students by father_id_card - STRICT MATCHING (no partial matches)
    // Only match if CNIC matches exactly after normalizing spaces, dashes, underscores, dots, and case
    $studentsByFatherIdCard = \App\Models\Student::where(function($query) use ($normalizedInputCnic, $cleanedCnic) {
        // Exact match after normalization (handles formatting differences)
        $query->whereRaw('LOWER(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(father_id_card), "-", ""), " ", ""), "_", ""), ".", "")) = ?', [$normalizedInputCnic])
              // Also try case-insensitive trimmed exact match
              ->orWhereRaw('LOWER(TRIM(father_id_card)) = LOWER(TRIM(?))', [$cleanedCnic])
              // Also try exact match as-is
              ->orWhere('father_id_card', $cleanedCnic);
    })
    ->select('id', 'student_name', 'student_code', 'class', 'section', 'campus', 'monthly_fee', 'father_name', 'father_phone', 'father_email', 'home_address')
    ->get();
    
    // If parent account exists, also get students connected via parent_account_id
    $studentsByParentAccount = collect();
    if ($parentAccount) {
        $studentsByParentAccount = \App\Models\Student::where('parent_account_id', $parentAccount->id)
            ->select('id', 'student_name', 'student_code', 'class', 'section', 'campus', 'monthly_fee', 'father_name', 'father_phone', 'father_email', 'home_address')
            ->get();
    }
    
    // Merge both collections and remove duplicates by student id, then sort by name
    $students = $studentsByParentAccount->merge($studentsByFatherIdCard)->unique('id')->sortBy('student_name')->values();
    
    return response()->json([
        'success' => true,
        'students' => $students->map(function ($student) {
            // Check if student has unpaid fees
            $totalPaid = \App\Models\StudentPayment::where('student_code', $student->student_code)
                ->sum('payment_amount');
            $monthlyFee = $student->monthly_fee ?? 0;
            $hasUnpaid = ($monthlyFee > 0 && $totalPaid < $monthlyFee);
            $unpaidAmount = max(0, $monthlyFee - $totalPaid);
            
            return [
                'id' => $student->id,
                'student_name' => $student->student_name,
                'student_code' => $student->student_code,
                'father_name' => $student->father_name,
                'class' => $student->class,
                'section' => $student->section,
                'campus' => $student->campus,
                'monthly_fee' => $student->monthly_fee,
                'has_unpaid' => $hasUnpaid,
                'unpaid_amount' => $unpaidAmount,
            ];
        })
    ]);
})->name('fee-payment.search-by-cnic');

// Expense Management Routes
Route::get('/expense-management/add', [App\Http\Controllers\ManagementExpenseController::class, 'index'])->name('expense-management.add');
Route::post('/expense-management/add', [App\Http\Controllers\ManagementExpenseController::class, 'store'])->name('expense-management.add.store');
Route::put('/expense-management/add/{managementExpense}', [App\Http\Controllers\ManagementExpenseController::class, 'update'])->name('expense-management.add.update');
Route::delete('/expense-management/add/{managementExpense}', [App\Http\Controllers\ManagementExpenseController::class, 'destroy'])->name('expense-management.add.destroy');
Route::get('/expense-management/add/export/{format}', [App\Http\Controllers\ManagementExpenseController::class, 'export'])->name('expense-management.add.export');

Route::get('/expense-management/categories', [App\Http\Controllers\ExpenseCategoryController::class, 'index'])->name('expense-management.categories');
Route::post('/expense-management/categories', [App\Http\Controllers\ExpenseCategoryController::class, 'store'])->name('expense-management.categories.store');
Route::put('/expense-management/categories/{expenseCategory}', [App\Http\Controllers\ExpenseCategoryController::class, 'update'])->name('expense-management.categories.update');
Route::delete('/expense-management/categories/{expenseCategory}', [App\Http\Controllers\ExpenseCategoryController::class, 'destroy'])->name('expense-management.categories.destroy');
Route::get('/expense-management/categories/export/{format}', [App\Http\Controllers\ExpenseCategoryController::class, 'export'])->name('expense-management.categories.export');

// Salary and Loan Management Routes
Route::get('/salary-loan/generate-salary', [App\Http\Controllers\GenerateSalaryController::class, 'index'])->name('salary-loan.generate-salary');
Route::post('/salary-loan/generate-salary', [App\Http\Controllers\GenerateSalaryController::class, 'store'])->name('salary-loan.generate-salary.store');

Route::get('/salary-loan/manage-salaries', [App\Http\Controllers\ManageSalariesController::class, 'index'])->name('salary-loan.manage-salaries');
Route::get('/salary-loan/manage-salaries/{salary}', [App\Http\Controllers\ManageSalariesController::class, 'show'])->name('salary-loan.manage-salaries.show');
Route::put('/salary-loan/manage-salaries/{salary}/payment', [App\Http\Controllers\ManageSalariesController::class, 'updatePayment'])->name('salary-loan.manage-salaries.payment');
Route::put('/salary-loan/manage-salaries/{salary}/status', [App\Http\Controllers\ManageSalariesController::class, 'updateStatus'])->name('salary-loan.manage-salaries.status');
Route::delete('/salary-loan/manage-salaries/{salary}', [App\Http\Controllers\ManageSalariesController::class, 'destroy'])->name('salary-loan.manage-salaries.destroy');
Route::get('/salary-loan/manage-salaries/export/{format}', [App\Http\Controllers\ManageSalariesController::class, 'export'])->name('salary-loan.manage-salaries.export');

Route::get('/salary-loan/loan-management', [App\Http\Controllers\LoanManagementController::class, 'index'])->name('salary-loan.loan-management');
Route::post('/salary-loan/loan-management', [App\Http\Controllers\LoanManagementController::class, 'store'])->name('salary-loan.loan-management.store');
Route::put('/salary-loan/loan-management/{loan}', [App\Http\Controllers\LoanManagementController::class, 'update'])->name('salary-loan.loan-management.update');
Route::delete('/salary-loan/loan-management/{loan}', [App\Http\Controllers\LoanManagementController::class, 'destroy'])->name('salary-loan.loan-management.destroy');
Route::get('/salary-loan/loan-management/export/{format}', [App\Http\Controllers\LoanManagementController::class, 'export'])->name('salary-loan.loan-management.export');

Route::get('/salary-loan/salary-setting', [App\Http\Controllers\SalarySettingController::class, 'index'])->name('salary-loan.salary-setting');
Route::put('/salary-loan/salary-setting', [App\Http\Controllers\SalarySettingController::class, 'update'])->name('salary-loan.salary-setting.update');

Route::get('/salary-loan/report', function () {
    return view('salary-loan.report');
})->name('salary-loan.report');

// Salary Increment Routes
Route::get('/salary-loan/increment/percentage', [App\Http\Controllers\SalaryIncrementPercentageController::class, 'index'])->name('salary-loan.increment.percentage');
Route::post('/salary-loan/increment/percentage', [App\Http\Controllers\SalaryIncrementPercentageController::class, 'store'])->name('salary-loan.increment.percentage.store');

Route::get('/salary-loan/increment/amount', [App\Http\Controllers\SalaryIncrementAmountController::class, 'index'])->name('salary-loan.increment.amount');
Route::post('/salary-loan/increment/amount', [App\Http\Controllers\SalaryIncrementAmountController::class, 'store'])->name('salary-loan.increment.amount.store');

// Salary Decrement Routes
Route::get('/salary-loan/decrement/percentage', [App\Http\Controllers\SalaryDecrementPercentageController::class, 'index'])->name('salary-loan.decrement.percentage');
Route::post('/salary-loan/decrement/percentage', [App\Http\Controllers\SalaryDecrementPercentageController::class, 'store'])->name('salary-loan.decrement.percentage.store');

Route::get('/salary-loan/decrement/amount', [App\Http\Controllers\SalaryDecrementAmountController::class, 'index'])->name('salary-loan.decrement.amount');
Route::post('/salary-loan/decrement/amount', [App\Http\Controllers\SalaryDecrementAmountController::class, 'store'])->name('salary-loan.decrement.amount.store');

Route::get('/dashboard/school', [DashboardController::class, 'school'])->name('dashboard.school');
Route::get('/dashboard/marketing', [DashboardController::class, 'marketing'])->name('dashboard.marketing');
Route::get('/dashboard/analytics', [DashboardController::class, 'analytics'])->name('dashboard.analytics');
Route::get('/dashboard/hospital', [DashboardController::class, 'hospital'])->name('dashboard.hospital');
Route::get('/dashboard/finance', [DashboardController::class, 'finance'])->name('dashboard.finance');

// Reporting Routes
Route::get('/reports/fee-default', [App\Http\Controllers\FeeDefaultReportController::class, 'index'])->name('reports.fee-default');

Route::get('/reports/head-wise-dues', function () {
    return view('reports.head-wise-dues');
})->name('reports.head-wise-dues');

Route::get('/reports/income-expense', [App\Http\Controllers\IncomeExpenseReportController::class, 'index'])->name('reports.income-expense');

Route::get('/reports/debit-credit', [App\Http\Controllers\DebitCreditStatementController::class, 'index'])->name('reports.debit-credit');

Route::get('/reports/unpaid-invoices', [App\Http\Controllers\UnpaidInvoicesController::class, 'index'])->name('reports.unpaid-invoices');

Route::get('/reports/fee-discount', [App\Http\Controllers\FeeDiscountController::class, 'index'])->name('reports.fee-discount');

Route::get('/reports/accounts-summary', [App\Http\Controllers\AccountsSummaryController::class, 'index'])->name('reports.accounts-summary');

Route::get('/reports/detailed-income', [App\Http\Controllers\DetailedIncomeController::class, 'index'])->name('reports.detailed-income');

Route::get('/reports/detailed-expense', [App\Http\Controllers\DetailedExpenseController::class, 'index'])->name('reports.detailed-expense');

Route::get('/reports/staff-salary', [App\Http\Controllers\StaffSalaryReportController::class, 'index'])->name('reports.staff-salary');
Route::get('/reports/staff-salary-summarized', [App\Http\Controllers\StaffSalaryReportController::class, 'summarized'])->name('reports.staff-salary-summarized');

Route::get('/reports/balance-sheet', [App\Http\Controllers\BalanceSheetController::class, 'index'])->name('reports.balance-sheet');

Route::get('/reports/admission-data', [App\Http\Controllers\AdmissionDataReportController::class, 'index'])->name('reports.admission-data');

// Stock & Inventory Routes
Route::get('/stock/point-of-sale', function () {
    return view('stock.point-of-sale');
})->name('stock.point-of-sale');

Route::get('/stock/manage-categories', [App\Http\Controllers\StockCategoryController::class, 'index'])->name('stock.manage-categories');
Route::post('/stock/manage-categories', [App\Http\Controllers\StockCategoryController::class, 'store'])->name('stock.manage-categories.store');
Route::put('/stock/manage-categories/{stockCategory}', [App\Http\Controllers\StockCategoryController::class, 'update'])->name('stock.manage-categories.update');
Route::delete('/stock/manage-categories/{stockCategory}', [App\Http\Controllers\StockCategoryController::class, 'destroy'])->name('stock.manage-categories.destroy');
Route::get('/stock/manage-categories/export/{format}', [App\Http\Controllers\StockCategoryController::class, 'export'])->name('stock.manage-categories.export');

Route::get('/stock/products', [App\Http\Controllers\ProductController::class, 'index'])->name('stock.products');
Route::post('/stock/products', [App\Http\Controllers\ProductController::class, 'store'])->name('stock.products.store');
Route::put('/stock/products/{product}', [App\Http\Controllers\ProductController::class, 'update'])->name('stock.products.update');
Route::delete('/stock/products/{product}', [App\Http\Controllers\ProductController::class, 'destroy'])->name('stock.products.destroy');
Route::get('/stock/products/export/{format}', [App\Http\Controllers\ProductController::class, 'export'])->name('stock.products.export');

Route::get('/stock/add-bulk-products', [App\Http\Controllers\BulkProductController::class, 'index'])->name('stock.add-bulk-products');
Route::post('/stock/add-bulk-products', [App\Http\Controllers\BulkProductController::class, 'store'])->name('stock.add-bulk-products.store');
Route::get('/stock/add-bulk-products/download-template', [App\Http\Controllers\BulkProductController::class, 'downloadTemplate'])->name('stock.add-bulk-products.download-template');

Route::get('/stock/manage-sale-records', [App\Http\Controllers\SaleRecordController::class, 'index'])->name('stock.manage-sale-records');

Route::get('/stock/sale-reports', function () {
    return view('stock.sale-reports');
})->name('stock.sale-reports');

// Student Behavior Management Routes
Route::get('/student-behavior/recording', [App\Http\Controllers\BehaviorRecordingController::class, 'index'])->name('student-behavior.recording')->middleware([App\Http\Middleware\AdminOrStaffMiddleware::class]);
Route::get('/student-behavior/recording/get-sections-by-class', [App\Http\Controllers\BehaviorRecordingController::class, 'getSectionsByClass'])->name('student-behavior.recording.get-sections-by-class');
Route::post('/student-behavior/recording/store', [App\Http\Controllers\BehaviorRecordingController::class, 'store'])->name('student-behavior.recording.store')->middleware([App\Http\Middleware\AdminOrStaffMiddleware::class]);

Route::get('/student-behavior/categories', [App\Http\Controllers\BehaviorCategoryController::class, 'index'])->name('student-behavior.categories');
Route::post('/student-behavior/categories', [App\Http\Controllers\BehaviorCategoryController::class, 'store'])->name('student-behavior.categories.store');
Route::put('/student-behavior/categories/{behaviorCategory}', [App\Http\Controllers\BehaviorCategoryController::class, 'update'])->name('student-behavior.categories.update');
Route::delete('/student-behavior/categories/{behaviorCategory}', [App\Http\Controllers\BehaviorCategoryController::class, 'destroy'])->name('student-behavior.categories.destroy');
Route::get('/student-behavior/categories/export/{format}', [App\Http\Controllers\BehaviorCategoryController::class, 'export'])->name('student-behavior.categories.export');

Route::get('/student-behavior/progress-tracking', [App\Http\Controllers\ProgressTrackingController::class, 'index'])->name('student-behavior.progress-tracking');

Route::get('/student-behavior/reporting-analysis', function () {
    return view('student-behavior.reporting-analysis');
})->name('student-behavior.reporting-analysis');

// Question Paper Routes
Route::get('/question-paper/manage-book', function () {
    return view('question-paper.manage-book');
})->name('question-paper.manage-book');

Route::get('/question-paper/question-bank', function () {
    return view('question-paper.question-bank');
})->name('question-paper.question-bank');

Route::get('/question-paper/generate', function () {
    return view('question-paper.generate');
})->name('question-paper.generate');

// Test Management Routes
Route::get('/test/list', [App\Http\Controllers\TestController::class, 'index'])->name('test.list');
Route::get('/test/list/get-sections', [App\Http\Controllers\TestController::class, 'getSections'])->name('test.list.get-sections');
Route::post('/test/list', [App\Http\Controllers\TestController::class, 'store'])->name('test.list.store');
Route::put('/test/list/{test}', [App\Http\Controllers\TestController::class, 'update'])->name('test.list.update');
Route::post('/test/list/{test}/toggle-result-status', [App\Http\Controllers\TestController::class, 'toggleResultStatus'])->name('test.list.toggle-result-status');
Route::delete('/test/list/{test}', [App\Http\Controllers\TestController::class, 'destroy'])->name('test.list.destroy');
Route::get('/test/list/export/{format}', [App\Http\Controllers\TestController::class, 'export'])->name('test.list.export');

Route::get('/test/marks-entry', [App\Http\Controllers\MarksEntryController::class, 'index'])->name('test.marks-entry');
Route::post('/test/marks-entry/save', [App\Http\Controllers\MarksEntryController::class, 'save'])->name('test.marks-entry.save');
Route::get('/test/marks-entry/get-sections', [App\Http\Controllers\MarksEntryController::class, 'getSections'])->name('test.marks-entry.get-sections');
Route::get('/test/marks-entry/get-tests', [App\Http\Controllers\MarksEntryController::class, 'getTests'])->name('test.marks-entry.get-tests');
Route::get('/test/marks-entry/get-subjects', [App\Http\Controllers\MarksEntryController::class, 'getSubjects'])->name('test.marks-entry.get-subjects');

Route::get('/test/schedule', [App\Http\Controllers\TestScheduleController::class, 'index'])->name('test.schedule');

// Test Reports - Assign Grades
Route::get('/test/assign-grades/particular', [App\Http\Controllers\AssignGradesController::class, 'particular'])->name('test.assign-grades.particular');
Route::get('/test/assign-grades/get-sections-by-class', [App\Http\Controllers\AssignGradesController::class, 'getSectionsByClass'])->name('test.assign-grades.get-sections-by-class');

Route::get('/test/assign-grades/combined', [App\Http\Controllers\AssignGradesController::class, 'combined'])->name('test.assign-grades.combined');
Route::post('/test/assign-grades/combined', [App\Http\Controllers\AssignGradesController::class, 'storeCombined'])->name('test.assign-grades.combined.store');
Route::put('/test/assign-grades/combined/{combinedResultGrade}', [App\Http\Controllers\AssignGradesController::class, 'updateCombined'])->name('test.assign-grades.combined.update');
Route::delete('/test/assign-grades/combined/{combinedResultGrade}', [App\Http\Controllers\AssignGradesController::class, 'destroyCombined'])->name('test.assign-grades.combined.destroy');
Route::get('/test/assign-grades/combined/export/{format}', [App\Http\Controllers\AssignGradesController::class, 'exportCombined'])->name('test.assign-grades.combined.export');

// Test Reports - Teacher Remarks
Route::get('/test/teacher-remarks/practical', [App\Http\Controllers\TeacherRemarksController::class, 'practical'])->name('test.teacher-remarks.practical')->middleware([App\Http\Middleware\AdminOrStaffMiddleware::class]);
Route::post('/test/teacher-remarks/practical/save', [App\Http\Controllers\TeacherRemarksController::class, 'saveRemarks'])->name('test.teacher-remarks.practical.save')->middleware([App\Http\Middleware\AdminOrStaffMiddleware::class]);
Route::get('/test/teacher-remarks/practical/get-sections', [App\Http\Controllers\TeacherRemarksController::class, 'getSections'])->name('test.teacher-remarks.practical.get-sections')->middleware([App\Http\Middleware\AdminOrStaffMiddleware::class]);
Route::get('/test/teacher-remarks/practical/get-subjects', [App\Http\Controllers\TeacherRemarksController::class, 'getSubjects'])->name('test.teacher-remarks.practical.get-subjects')->middleware([App\Http\Middleware\AdminOrStaffMiddleware::class]);
Route::get('/test/teacher-remarks/practical/get-tests', [App\Http\Controllers\TeacherRemarksController::class, 'getTests'])->name('test.teacher-remarks.practical.get-tests')->middleware([App\Http\Middleware\AdminOrStaffMiddleware::class]);

Route::get('/test/teacher-remarks/combined', [App\Http\Controllers\TeacherRemarksController::class, 'combined'])->name('test.teacher-remarks.combined')->middleware([App\Http\Middleware\AdminOrStaffMiddleware::class]);
Route::post('/test/teacher-remarks/combined/save', [App\Http\Controllers\TeacherRemarksController::class, 'saveCombinedRemarks'])->name('test.teacher-remarks.combined.save')->middleware([App\Http\Middleware\AdminOrStaffMiddleware::class]);
Route::get('/test/teacher-remarks/combined/get-class-sections', [App\Http\Controllers\TeacherRemarksController::class, 'getClassSections'])->name('test.teacher-remarks.combined.get-class-sections')->middleware([App\Http\Middleware\AdminOrStaffMiddleware::class]);

// Test Reports - Tabulation Sheet
Route::get('/test/tabulation-sheet/practical', [App\Http\Controllers\TabulationSheetController::class, 'practical'])->name('test.tabulation-sheet.practical');

Route::get('/test/tabulation-sheet/combine', [App\Http\Controllers\TabulationSheetController::class, 'combine'])->name('test.tabulation-sheet.combine');

// Test Reports - Position Holder
Route::get('/test/position-holder/practical', [App\Http\Controllers\PositionHolderController::class, 'practical'])->name('test.position-holder.practical');

Route::get('/test/position-holder/combine', [App\Http\Controllers\PositionHolderController::class, 'combine'])->name('test.position-holder.combine');

// Test Reports - Send Marks to Parents
Route::get('/test/send-marks/practical', [App\Http\Controllers\SendMarksController::class, 'practical'])->name('test.send-marks.practical');
Route::get('/test/send-marks/get-sections', [App\Http\Controllers\SendMarksController::class, 'getSections'])->name('test.send-marks.get-sections');
Route::get('/test/send-marks/get-subjects', [App\Http\Controllers\SendMarksController::class, 'getSubjects'])->name('test.send-marks.get-subjects');
Route::get('/test/send-marks/get-tests', [App\Http\Controllers\SendMarksController::class, 'getTests'])->name('test.send-marks.get-tests');

Route::get('/test/send-marks/combined', [App\Http\Controllers\SendMarksController::class, 'combined'])->name('test.send-marks.combined');
Route::get('/test/send-marks/get-sections-combined', [App\Http\Controllers\SendMarksController::class, 'getSectionsCombined'])->name('test.send-marks.get-sections-combined');

// Test Reports - Send Marksheet via WA
Route::get('/test/send-marksheet/practical', function () {
    return view('test.send-marksheet.practical');
})->name('test.send-marksheet.practical');

Route::get('/test/send-marksheet/combine', function () {
    return view('test.send-marksheet.combine');
})->name('test.send-marksheet.combine');

// Test Reports - Print Marksheets
Route::get('/test/print-marksheets/practical', [App\Http\Controllers\PrintMarksheetsController::class, 'practical'])->name('test.print-marksheets.practical');
Route::get('/test/print-marksheets/get-sections', [App\Http\Controllers\PrintMarksheetsController::class, 'getSections'])->name('test.print-marksheets.get-sections');
Route::get('/test/print-marksheets/get-subjects', [App\Http\Controllers\PrintMarksheetsController::class, 'getSubjects'])->name('test.print-marksheets.get-subjects');
Route::get('/test/print-marksheets/get-tests', [App\Http\Controllers\PrintMarksheetsController::class, 'getTests'])->name('test.print-marksheets.get-tests');

Route::get('/test/print-marksheets/combine', function () {
    return view('test.print-marksheets.combine');
})->name('test.print-marksheets.combine');

// Exam Management Routes
Route::get('/exam/list', [App\Http\Controllers\ExamController::class, 'index'])->name('exam.list');
Route::post('/exam/list', [App\Http\Controllers\ExamController::class, 'store'])->name('exam.list.store');
Route::put('/exam/list/{exam}', [App\Http\Controllers\ExamController::class, 'update'])->name('exam.list.update');
Route::delete('/exam/list/{exam}', [App\Http\Controllers\ExamController::class, 'destroy'])->name('exam.list.destroy');
Route::get('/exam/list/export/{format}', [App\Http\Controllers\ExamController::class, 'export'])->name('exam.list.export');

Route::get('/exam/marks-entry', [App\Http\Controllers\ExamController::class, 'marksEntry'])->name('exam.marks-entry');
Route::post('/exam/marks-entry/save', [App\Http\Controllers\ExamController::class, 'saveExamMarks'])->name('exam.marks-entry.save');
Route::get('/exam/marks-entry/get-sections', [App\Http\Controllers\ExamController::class, 'getSectionsForMarksEntry'])->name('exam.marks-entry.get-sections');
Route::get('/exam/marks-entry/get-subjects', [App\Http\Controllers\ExamController::class, 'getSubjectsForMarksEntry'])->name('exam.marks-entry.get-subjects');

Route::get('/exam/print-admit-cards', [App\Http\Controllers\ExamController::class, 'printAdmitCards'])->name('exam.print-admit-cards');
Route::get('/exam/print-admit-cards/get-exams', [App\Http\Controllers\ExamController::class, 'getExamsForPrintAdmitCards'])->name('exam.print-admit-cards.get-exams');

Route::get('/exam/send-admit-cards', function () {
    return view('exam.send-admit-cards');
})->name('exam.send-admit-cards');

// Exam Grades
Route::get('/exam/grades/particular', [App\Http\Controllers\ExamController::class, 'gradesParticular'])->name('exam.grades.particular');
Route::get('/exam/grades/get-exams', [App\Http\Controllers\ExamController::class, 'getExams'])->name('exam.grades.get-exams');

Route::get('/exam/grades/final', [App\Http\Controllers\FinalExamGradeController::class, 'index'])->name('exam.grades.final');
Route::post('/exam/grades/final', [App\Http\Controllers\FinalExamGradeController::class, 'store'])->name('exam.grades.final.store');
Route::put('/exam/grades/final/{finalExamGrade}', [App\Http\Controllers\FinalExamGradeController::class, 'update'])->name('exam.grades.final.update');
Route::delete('/exam/grades/final/{finalExamGrade}', [App\Http\Controllers\FinalExamGradeController::class, 'destroy'])->name('exam.grades.final.destroy');
Route::get('/exam/grades/final/export/{format}', [App\Http\Controllers\FinalExamGradeController::class, 'export'])->name('exam.grades.final.export');

// Teacher Remarks
Route::get('/exam/teacher-remarks/particular', [App\Http\Controllers\ExamController::class, 'teacherRemarksParticular'])->name('exam.teacher-remarks.particular');
Route::post('/exam/teacher-remarks/particular/save', [App\Http\Controllers\ExamController::class, 'saveTeacherRemarksParticular'])->name('exam.teacher-remarks.particular.save');
Route::get('/exam/teacher-remarks/get-exams', [App\Http\Controllers\ExamController::class, 'getExamsForTeacherRemarks'])->name('exam.teacher-remarks.get-exams');
Route::get('/exam/teacher-remarks/get-sections', [App\Http\Controllers\ExamController::class, 'getSectionsForTeacherRemarks'])->name('exam.teacher-remarks.get-sections');

Route::get('/exam/teacher-remarks/final', [App\Http\Controllers\ExamController::class, 'teacherRemarksFinal'])->name('exam.teacher-remarks.final');
Route::post('/exam/teacher-remarks/final/save', [App\Http\Controllers\ExamController::class, 'saveTeacherRemarksFinal'])->name('exam.teacher-remarks.final.save');

// Exam Timetable
Route::get('/exam/timetable/add', [App\Http\Controllers\ExamController::class, 'addTimetable'])->name('exam.timetable.add');
Route::get('/exam/timetable/get-sections', [App\Http\Controllers\ExamController::class, 'getSectionsForTimetable'])->name('exam.timetable.get-sections');
Route::get('/exam/timetable/get-subjects', [App\Http\Controllers\ExamController::class, 'getSubjectsForTimetable'])->name('exam.timetable.get-subjects');

Route::get('/exam/timetable/manage', [App\Http\Controllers\ExamController::class, 'manageTimetable'])->name('exam.timetable.manage');
Route::get('/exam/timetable/get-exams-manage', [App\Http\Controllers\ExamController::class, 'getExamsForManageTimetable'])->name('exam.timetable.get-exams-manage');

// Tabulation Sheet
Route::get('/exam/tabulation-sheet/particular', [App\Http\Controllers\ExamController::class, 'tabulationSheetParticular'])->name('exam.tabulation-sheet.particular');
Route::get('/exam/tabulation-sheet/get-exams', [App\Http\Controllers\ExamController::class, 'getExamsForTabulationSheet'])->name('exam.tabulation-sheet.get-exams');

Route::get('/exam/tabulation-sheet/final', [App\Http\Controllers\ExamController::class, 'tabulationSheetFinal'])->name('exam.tabulation-sheet.final');

// Position Holders
Route::get('/exam/position-holders/particular', [App\Http\Controllers\ExamController::class, 'positionHoldersParticular'])->name('exam.position-holders.particular');
Route::get('/exam/position-holders/get-exams', [App\Http\Controllers\ExamController::class, 'getExamsForPositionHolders'])->name('exam.position-holders.get-exams');

Route::get('/exam/position-holders/final', [App\Http\Controllers\ExamController::class, 'positionHoldersFinal'])->name('exam.position-holders.final');

// Send Marks to Parents
Route::get('/exam/send-marks/particular', function () {
    return view('exam.send-marks.particular');
})->name('exam.send-marks.particular');

Route::get('/exam/send-marks/final', function () {
    return view('exam.send-marks.final');
})->name('exam.send-marks.final');

// Print Marksheet
Route::get('/exam/print-marksheet/particular', function () {
    return view('exam.print-marksheet.particular');
})->name('exam.print-marksheet.particular');

Route::get('/exam/print-marksheet/final', function () {
    return view('exam.print-marksheet.final');
})->name('exam.print-marksheet.final');

// Quiz Management Routes
Route::get('/quiz/manage', [App\Http\Controllers\QuizController::class, 'index'])->name('quiz.manage');
Route::post('/quiz/manage', [App\Http\Controllers\QuizController::class, 'store'])->name('quiz.manage.store');
Route::put('/quiz/manage/{quiz}', [App\Http\Controllers\QuizController::class, 'update'])->name('quiz.manage.update');
Route::delete('/quiz/manage/{quiz}', [App\Http\Controllers\QuizController::class, 'destroy'])->name('quiz.manage.destroy');
Route::get('/quiz/manage/export/{format}', [App\Http\Controllers\QuizController::class, 'export'])->name('quiz.manage.export');
Route::get('/quiz/manage/get-sections-by-class', [App\Http\Controllers\QuizController::class, 'getSectionsByClass'])->name('quiz.manage.get-sections-by-class');

// Certification Routes
Route::get('/certification/student', [App\Http\Controllers\CertificationController::class, 'student'])->name('certification.student');
Route::get('/certification/student/get-classes', [App\Http\Controllers\CertificationController::class, 'getClasses'])->name('certification.student.get-classes');
Route::get('/certification/student/get-sections', [App\Http\Controllers\CertificationController::class, 'getSections'])->name('certification.student.get-sections');
Route::get('/certification/student/{student}/generate', [App\Http\Controllers\CertificationController::class, 'generateCertificate'])->name('certification.student.generate');

Route::get('/certification/staff', [App\Http\Controllers\CertificationController::class, 'staff'])->name('certification.staff');
Route::get('/certification/staff/{staff}/generate', [App\Http\Controllers\CertificationController::class, 'generateStaffCertificate'])->name('certification.staff.generate');

// Daily Homework Diary Routes
Route::get('/homework-diary/manage', [App\Http\Controllers\HomeworkDiaryController::class, 'manage'])->name('homework-diary.manage');
Route::get('/homework-diary/get-sections', [App\Http\Controllers\HomeworkDiaryController::class, 'getSections'])->name('homework-diary.get-sections');
Route::post('/homework-diary/store', [App\Http\Controllers\HomeworkDiaryController::class, 'store'])->name('homework-diary.store');
Route::post('/homework-diary/send', [App\Http\Controllers\HomeworkDiaryController::class, 'sendDiary'])->name('homework-diary.send');

Route::get('/homework-diary/send-sms', function () {
    return view('homework-diary.send-sms');
})->name('homework-diary.send-sms');

// Study Material - LMS Routes
Route::get('/study-material/lms', [App\Http\Controllers\StudyMaterialController::class, 'lms'])->name('study-material.lms');
Route::post('/study-material/lms', [App\Http\Controllers\StudyMaterialController::class, 'store'])->name('study-material.store');
Route::get('/study-material/{studyMaterial}/view-file', [App\Http\Controllers\StudyMaterialController::class, 'viewFile'])->name('study-material.view-file');
Route::delete('/study-material/lms/{studyMaterial}', [App\Http\Controllers\StudyMaterialController::class, 'destroy'])->name('study-material.destroy');
Route::get('/study-material/get-sections-by-class', [App\Http\Controllers\StudyMaterialController::class, 'getSectionsByClass'])->name('study-material.get-sections-by-class');
Route::get('/study-material/get-subjects-by-class-section', [App\Http\Controllers\StudyMaterialController::class, 'getSubjectsByClassSection'])->name('study-material.get-subjects-by-class-section');

// Leave Management Routes
Route::get('/leave-management', [App\Http\Controllers\LeaveManagementController::class, 'index'])->name('leave-management');
Route::post('/leave-management', [App\Http\Controllers\LeaveManagementController::class, 'store'])->name('leave-management.store');
Route::put('/leave-management/{leave}', [App\Http\Controllers\LeaveManagementController::class, 'update'])->name('leave-management.update');
Route::delete('/leave-management/{leave}', [App\Http\Controllers\LeaveManagementController::class, 'destroy'])->name('leave-management.destroy');

// Student Leave Request Routes (Public/Parent)
Route::get('/leave-request', function() {
    return view('leave-request');
})->name('leave-request');
Route::post('/leave-request', [App\Http\Controllers\LeaveManagementController::class, 'storeStudentLeave'])->name('leave-request.store');
Route::get('/leave-request/get-students', [App\Http\Controllers\LeaveManagementController::class, 'getStudentsByParentPhone'])->name('leave-request.get-students');

// SMS Management Routes
Route::get('/sms/parent', [App\Http\Controllers\SmsController::class, 'parent'])->name('sms.parent');

Route::get('/sms/staff', [App\Http\Controllers\SmsController::class, 'staff'])->name('sms.staff');

Route::get('/sms/specific-number', [App\Http\Controllers\SmsController::class, 'specificNumber'])->name('sms.specific-number');

Route::get('/sms/history', [App\Http\Controllers\SmsController::class, 'history'])->name('sms.history');

// Mobile App Notification Routes
Route::get('/notification/parent', [App\Http\Controllers\NotificationController::class, 'parent'])->name('notification.parent');

Route::get('/notification/staff', [App\Http\Controllers\NotificationController::class, 'staff'])->name('notification.staff');

Route::get('/notification/student', [App\Http\Controllers\NotificationController::class, 'student'])->name('notification.student');

Route::get('/notification/history', [App\Http\Controllers\NotificationController::class, 'history'])->name('notification.history');

// WhatsApp Notification Routes
Route::get('/whatsapp/parent', [App\Http\Controllers\WhatsAppController::class, 'parent'])->name('whatsapp.parent');

Route::get('/whatsapp/staff', [App\Http\Controllers\WhatsAppController::class, 'staff'])->name('whatsapp.staff');

Route::get('/whatsapp/history', [App\Http\Controllers\WhatsAppController::class, 'history'])->name('whatsapp.history');

// Send/WhatsApp Template Route
Route::get('/whatsapp/template', function () {
    return view('whatsapp.template');
})->name('whatsapp.template');

// Email Alerts Routes
Route::get('/email-alerts/specific', [App\Http\Controllers\EmailAlertsController::class, 'specific'])->name('email-alerts.specific');

Route::get('/email-alerts/history', [App\Http\Controllers\EmailAlertsController::class, 'history'])->name('email-alerts.history');

// School Noticeboard Routes
Route::get('/school/noticeboard', [App\Http\Controllers\NoticeboardController::class, 'index'])->name('school.noticeboard');
Route::post('/school/noticeboard', [App\Http\Controllers\NoticeboardController::class, 'store'])->name('school.noticeboard.store');
Route::put('/school/noticeboard/{noticeboard}', [App\Http\Controllers\NoticeboardController::class, 'update'])->name('school.noticeboard.update');
Route::delete('/school/noticeboard/{noticeboard}', [App\Http\Controllers\NoticeboardController::class, 'destroy'])->name('school.noticeboard.destroy');
Route::get('/school/noticeboard/export/{format}', [App\Http\Controllers\NoticeboardController::class, 'export'])->name('school.noticeboard.export');

// Manage Campuses Routes
Route::get('/manage/campuses', [App\Http\Controllers\CampusController::class, 'index'])->name('manage.campuses');
Route::post('/manage/campuses', [App\Http\Controllers\CampusController::class, 'store'])->name('manage.campuses.store');
Route::put('/manage/campuses/{campus}', [App\Http\Controllers\CampusController::class, 'update'])->name('manage.campuses.update');
Route::delete('/manage/campuses/{campus}', [App\Http\Controllers\CampusController::class, 'destroy'])->name('manage.campuses.destroy');
Route::get('/manage/campuses/export/{format}', [App\Http\Controllers\CampusController::class, 'export'])->name('manage.campuses.export');

// Admin Roles Management Routes (Super Admin Only)
Route::middleware([App\Http\Middleware\SuperAdminMiddleware::class])->group(function () {
    Route::get('/admin/roles-management', [App\Http\Controllers\AdminRoleController::class, 'index'])->name('admin.roles-management');
    Route::post('/admin/roles-management', [App\Http\Controllers\AdminRoleController::class, 'store'])->name('admin.roles-management.store');
    Route::put('/admin/roles-management/{adminRole}', [App\Http\Controllers\AdminRoleController::class, 'update'])->name('admin.roles-management.update');
    Route::delete('/admin/roles-management/{adminRole}', [App\Http\Controllers\AdminRoleController::class, 'destroy'])->name('admin.roles-management.destroy');
    Route::get('/admin/roles-management/export/{format}', [App\Http\Controllers\AdminRoleController::class, 'export'])->name('admin.roles-management.export');
});

// Transport Routes (Super Admin Only)
Route::middleware([App\Http\Middleware\SuperAdminMiddleware::class])->group(function () {
    Route::get('/transport/manage', [App\Http\Controllers\TransportController::class, 'index'])->name('transport.manage');
    Route::post('/transport/manage', [App\Http\Controllers\TransportController::class, 'store'])->name('transport.manage.store');
    Route::put('/transport/manage/{transport}', [App\Http\Controllers\TransportController::class, 'update'])->name('transport.manage.update');
    Route::delete('/transport/manage/{transport}', [App\Http\Controllers\TransportController::class, 'destroy'])->name('transport.manage.destroy');
    Route::get('/transport/manage/export/{format}', [App\Http\Controllers\TransportController::class, 'export'])->name('transport.manage.export');
    
    Route::get('/transport/reports', function () {
        return view('transport.reports');
    })->name('transport.reports');
});

// Manage Biometric Devices Route
Route::get('/biometric/devices', function () {
    return view('biometric.devices');
})->name('biometric.devices');

// Website Management Routes (Super Admin Only)
Route::middleware([App\Http\Middleware\SuperAdminMiddleware::class])->group(function () {
    Route::get('/website-management/general-gallery', function () {
        return view('website-management.general-gallery');
    })->name('website-management.general-gallery');
    
    Route::get('/website-management/classes-show', function () {
        return view('website-management.classes-show');
    })->name('website-management.classes-show');
});

// Placeholder routes for profile, settings, logout
Route::get('/profile', function () {
    return view('profile');
})->name('profile');

Route::get('/settings', function () {
    return view('settings');
})->name('settings');

Route::post('/logout', function () {
    // Add logout logic here
    return redirect('/');
})->name('logout');

// Additional routes for header links
Route::get('/calendar', function () {
    return view('calendar');
})->name('calendar');

Route::get('/chat', function () {
    return view('chat');
})->name('chat');

// App Routes
Route::get('/todo-list', function () {
    return view('blank-page');
})->name('todo-list');

Route::get('/contacts', function () {
    return view('blank-page');
})->name('contacts');

// Email Routes
Route::get('/inbox', function () {
    return view('blank-page');
})->name('inbox');

Route::get('/compose', function () {
    return view('blank-page');
})->name('compose');

Route::get('/read-email', function () {
    return view('blank-page');
})->name('read-email');

Route::get('/kanban-board', function () {
    return view('blank-page');
})->name('kanban-board');

// File Manager Routes
Route::get('/my-drive', function () {
    return view('blank-page');
})->name('my-drive');

Route::get('/assets', function () {
    return view('blank-page');
})->name('assets');

Route::get('/projects-file', function () {
    return view('blank-page');
})->name('projects-file');

Route::get('/personal', function () {
    return view('blank-page');
})->name('personal');

Route::get('/applications', function () {
    return view('blank-page');
})->name('applications');

Route::get('/documents', function () {
    return view('blank-page');
})->name('documents');

Route::get('/media', function () {
    return view('blank-page');
})->name('media');

// E-Commerce Routes
Route::get('/products-grid', function () {
    return view('blank-page');
})->name('products-grid');

Route::get('/products-list', function () {
    return view('blank-page');
})->name('products-list');

Route::get('/product-details', function () {
    return view('blank-page');
})->name('product-details');

Route::get('/create-product', function () {
    return view('blank-page');
})->name('create-product');

Route::get('/edit-product', function () {
    return view('blank-page');
})->name('edit-product');

Route::get('/cart', function () {
    return view('blank-page');
})->name('cart');

Route::get('/checkout', function () {
    return view('blank-page');
})->name('checkout');

Route::get('/orders', function () {
    return view('blank-page');
})->name('orders');

Route::get('/order-details', function () {
    return view('blank-page');
})->name('order-details');

Route::get('/create-order', function () {
    return view('blank-page');
})->name('create-order');

Route::get('/order-tracking', function () {
    return view('blank-page');
})->name('order-tracking');

Route::get('/customers', function () {
    return view('blank-page');
})->name('customers');

Route::get('/customer-details', function () {
    return view('blank-page');
})->name('customer-details');

Route::get('/categories', function () {
    return view('blank-page');
})->name('categories');

Route::get('/create-category', function () {
    return view('blank-page');
})->name('create-category');

Route::get('/edit-category', function () {
    return view('blank-page');
})->name('edit-category');

Route::get('/sellers', function () {
    return view('blank-page');
})->name('sellers');

Route::get('/seller-details', function () {
    return view('blank-page');
})->name('seller-details');

Route::get('/create-seller', function () {
    return view('blank-page');
})->name('create-seller');

Route::get('/reviews', function () {
    return view('blank-page');
})->name('reviews');

Route::get('/refunds', function () {
    return view('blank-page');
})->name('refunds');

// CRM Routes
Route::get('/contacts-crm', function () {
    return view('blank-page');
})->name('contacts-crm');

Route::get('/create-contact', function () {
    return view('blank-page');
})->name('create-contact');

Route::get('/customers-crm', function () {
    return view('blank-page');
})->name('customers-crm');

Route::get('/leads', function () {
    return view('blank-page');
})->name('leads');

Route::get('/create-lead', function () {
    return view('blank-page');
})->name('create-lead');

Route::get('/deals', function () {
    return view('blank-page');
})->name('deals');

// Project Management Routes
Route::get('/project-overview', function () {
    return view('blank-page');
})->name('project-overview');

Route::get('/projects-list', function () {
    return view('blank-page');
})->name('projects-list');

Route::get('/create-project', function () {
    return view('blank-page');
})->name('create-project');

Route::get('/clients', function () {
    return view('blank-page');
})->name('clients');

Route::get('/teams', function () {
    return view('blank-page');
})->name('teams');

Route::get('/kanban-board-project', function () {
    return view('blank-page');
})->name('kanban-board-project');

Route::get('/users', function () {
    return view('blank-page');
})->name('users');

Route::get('/create-user', function () {
    return view('blank-page');
})->name('create-user');

// LMS Routes
Route::get('/courses-list', function () {
    return view('blank-page');
})->name('courses-list');

Route::get('/course-details', function () {
    return view('blank-page');
})->name('course-details');

Route::get('/create-course', function () {
    return view('blank-page');
})->name('create-course');

Route::get('/edit-course', function () {
    return view('blank-page');
})->name('edit-course');

Route::get('/instructors', function () {
    return view('blank-page');
})->name('instructors');

// Help Desk Routes
Route::get('/tickets', function () {
    return view('blank-page');
})->name('tickets');

Route::get('/ticket-details', function () {
    return view('blank-page');
})->name('ticket-details');

Route::get('/agents', function () {
    return view('blank-page');
})->name('agents');

Route::get('/reports', function () {
    return view('blank-page');
})->name('reports');

// HR Management Routes
Route::get('/employee-list', function () {
    return view('blank-page');
})->name('employee-list');

Route::get('/add-new-employee', function () {
    return view('blank-page');
})->name('add-new-employee');

Route::get('/employee-leave', function () {
    return view('blank-page');
})->name('employee-leave');

Route::get('/add-leave', function () {
    return view('blank-page');
})->name('add-leave');

Route::get('/school-attendance', function () {
    return view('blank-page');
})->name('school-attendance');

Route::get('/departments', function () {
    return view('blank-page');
})->name('departments');

Route::get('/add-departments', function () {
    return view('blank-page');
})->name('add-departments');

Route::get('/holidays', function () {
    return view('blank-page');
})->name('holidays');

Route::get('/employee-salary', function () {
    return view('blank-page');
})->name('employee-salary');

Route::get('/create-payslip', function () {
    return view('blank-page');
})->name('create-payslip');

// School Routes
Route::get('/student-list', [App\Http\Controllers\StudentController::class, 'studentList'])->name('student-list')->middleware([App\Http\Middleware\AdminOrStaffMiddleware::class]);
Route::get('/student-list/get-sections', [App\Http\Controllers\StudentController::class, 'getSectionsForStudentList'])->name('student-list.get-sections');

Route::get('/add-student', function () {
    return view('blank-page');
})->name('add-student');

Route::get('/teacher-list', function () {
    return view('blank-page');
})->name('teacher-list');

Route::get('/add-teacher', function () {
    return view('blank-page');
})->name('add-teacher');

Route::get('/staff-list', function () {
    return view('blank-page');
})->name('staff-list');

Route::get('/add-staff', function () {
    return view('blank-page');
})->name('add-staff');

Route::get('/all-courses', function () {
    return view('blank-page');
})->name('all-courses');

Route::get('/add-course', function () {
    return view('blank-page');
})->name('add-course');

Route::get('/fees-collection', function () {
    return view('blank-page');
})->name('fees-collection');

Route::get('/add-fees', function () {
    return view('blank-page');
})->name('add-fees');

Route::get('/library', function () {
    return view('blank-page');
})->name('library');

Route::get('/add-library-book', function () {
    return view('blank-page');
})->name('add-library-book');

// Hospital Routes
Route::get('/patients', function () {
    return view('blank-page');
})->name('patients');

Route::get('/patient-details', function () {
    return view('blank-page');
})->name('patient-details');

Route::get('/doctors', function () {
    return view('blank-page');
})->name('doctors');

Route::get('/doctor-details', function () {
    return view('blank-page');
})->name('doctor-details');

Route::get('/all-schedule', function () {
    return view('blank-page');
})->name('all-schedule');

Route::get('/book-appointments', function () {
    return view('blank-page');
})->name('book-appointments');

// Events Routes (Legacy - kept for compatibility, redirects to manage)
Route::get('/events', function () {
    return redirect()->route('events.manage');
})->name('events');

Route::get('/event-details', function () {
    return view('blank-page');
})->name('event-details');

Route::get('/create-an-event', function () {
    return view('blank-page');
})->name('create-an-event');

Route::get('/edit-an-event', function () {
    return view('blank-page');
})->name('edit-an-event');

// Social Routes
Route::get('/user-profile', function () {
    return view('blank-page');
})->name('user-profile');

Route::get('/teams2', function () {
    return view('blank-page');
})->name('teams2');

Route::get('/projects', function () {
    return view('blank-page');
})->name('projects');

Route::get('/starter', function () {
    return view('blank-page');
})->name('starter');

// Invoice Routes
Route::get('/invoices', function () {
    return view('blank-page');
})->name('invoices');

Route::get('/invoice-details', function () {
    return view('blank-page');
})->name('invoice-details');

// Users Routes
Route::get('/team-members', function () {
    return view('blank-page');
})->name('team-members');

Route::get('/users-list', function () {
    return view('blank-page');
})->name('users-list');

Route::get('/add-user', function () {
    return view('blank-page');
})->name('add-user');

// Modules Routes - Icons
Route::get('/material-symbols', function () {
    return view('blank-page');
})->name('material-symbols');

Route::get('/remix-icon', function () {
    return view('blank-page');
})->name('remix-icon');

// UI Elements Routes
Route::get('/alerts', function () {
    return view('blank-page');
})->name('alerts');

Route::get('/avatar', function () {
    return view('blank-page');
})->name('avatar');

Route::get('/buttons', function () {
    return view('blank-page');
})->name('buttons');

Route::get('/cards', function () {
    return view('blank-page');
})->name('cards');

Route::get('/carousels', function () {
    return view('blank-page');
})->name('carousels');

Route::get('/dropdowns', function () {
    return view('blank-page');
})->name('dropdowns');

Route::get('/grids', function () {
    return view('blank-page');
})->name('grids');

Route::get('/images', function () {
    return view('blank-page');
})->name('images');

Route::get('/list', function () {
    return view('blank-page');
})->name('list');

Route::get('/modals', function () {
    return view('blank-page');
})->name('modals');

Route::get('/navs', function () {
    return view('blank-page');
})->name('navs');

Route::get('/paginations', function () {
    return view('blank-page');
})->name('paginations');

Route::get('/progress', function () {
    return view('blank-page');
})->name('progress');

Route::get('/spinners', function () {
    return view('blank-page');
})->name('spinners');

Route::get('/tabs', function () {
    return view('blank-page');
})->name('tabs');

Route::get('/accordions', function () {
    return view('blank-page');
})->name('accordions');

Route::get('/date-time-picker', function () {
    return view('blank-page');
})->name('date-time-picker');

Route::get('/videos', function () {
    return view('blank-page');
})->name('videos');

// Tables Routes
Route::get('/basic-table', function () {
    return view('blank-page');
})->name('basic-table');

Route::get('/data-table', function () {
    return view('blank-page');
})->name('data-table');

// Forms Routes
Route::get('/basic-elements', function () {
    return view('blank-page');
})->name('basic-elements');

Route::get('/advanced-elements', function () {
    return view('blank-page');
})->name('advanced-elements');

Route::get('/validation', function () {
    return view('blank-page');
})->name('validation');

Route::get('/wizard', function () {
    return view('blank-page');
})->name('wizard');

Route::get('/editors', function () {
    return view('blank-page');
})->name('editors');

Route::get('/file-uploader', function () {
    return view('blank-page');
})->name('file-uploader');

// ApexCharts Routes
Route::get('/line', function () {
    return view('blank-page');
})->name('line');

Route::get('/area', function () {
    return view('blank-page');
})->name('area');

Route::get('/column', function () {
    return view('blank-page');
})->name('column');

Route::get('/mixed', function () {
    return view('blank-page');
})->name('mixed');

Route::get('/radial-bar', function () {
    return view('blank-page');
})->name('radial-bar');

Route::get('/radar', function () {
    return view('blank-page');
})->name('radar');

Route::get('/pie', function () {
    return view('blank-page');
})->name('pie');

Route::get('/polar', function () {
    return view('blank-page');
})->name('polar');

Route::get('/more', function () {
    return view('blank-page');
})->name('more');

// Authentication Routes
Route::get('/sign-in', function () {
    return view('blank-page');
})->name('sign-in');

Route::get('/sign-up', function () {
    return view('blank-page');
})->name('sign-up');

Route::get('/forgot-password', function () {
    return view('blank-page');
})->name('forgot-password');

Route::get('/reset-password', function () {
    return view('blank-page');
})->name('reset-password');

Route::get('/confirm-email', function () {
    return view('blank-page');
})->name('confirm-email');

Route::get('/lock-screen', function () {
    return view('blank-page');
})->name('lock-screen');

// Extra Pages Routes
Route::get('/pricing', function () {
    return view('blank-page');
})->name('pricing');

Route::get('/timeline', function () {
    return view('blank-page');
})->name('timeline');

Route::get('/faq', function () {
    return view('blank-page');
})->name('faq');

Route::get('/gallery', function () {
    return view('blank-page');
})->name('gallery');

Route::get('/testimonials', function () {
    return view('blank-page');
})->name('testimonials');

Route::get('/search', function () {
    return view('blank-page');
})->name('search');

Route::get('/coming-soon', function () {
    return view('blank-page');
})->name('coming-soon');

Route::get('/blank-page', function () {
    return view('blank-page');
})->name('blank-page');

// Error Pages Routes
Route::get('/404-error-page', function () {
    return view('blank-page');
})->name('404-error-page');

Route::get('/internal-error', function () {
    return view('blank-page');
})->name('internal-error');

// Other Routes
Route::get('/widgets', function () {
    return view('blank-page');
})->name('widgets');

Route::get('/maps', function () {
    return view('blank-page');
})->name('maps');

Route::get('/notifications', function () {
    return view('blank-page');
})->name('notifications');

Route::get('/members', function () {
    return view('blank-page');
})->name('members');

Route::get('/my-profile', function () {
    return view('blank-page');
})->name('my-profile');

// Change Password Routes (Both Super Admin and Admin)
Route::middleware([App\Http\Middleware\AdminMiddleware::class])->group(function () {
    Route::get('/change-password', [App\Http\Controllers\ChangePasswordController::class, 'index'])->name('change-password');
    Route::put('/change-password', [App\Http\Controllers\ChangePasswordController::class, 'update'])->name('change-password.update');
    
    // Live Chat (Admin/Super Admin with Teachers)
    Route::get('/live-chat', [App\Http\Controllers\ChatController::class, 'index'])->name('live-chat');
    Route::post('/live-chat/teacher/{teacherId}', [App\Http\Controllers\ChatController::class, 'sendToTeacher'])->name('live-chat.send-teacher');
});

// Settings Routes (Super Admin Only)
Route::middleware([App\Http\Middleware\SuperAdminMiddleware::class])->group(function () {
    Route::get('/account-settings', function () {
        return view('blank-page');
    })->name('account-settings');
    
    Route::get('/connections', function () {
        return view('blank-page');
    })->name('connections');
    
    Route::get('/privacy-policy', function () {
        return view('blank-page');
    })->name('privacy-policy');
    
    Route::get('/terms-conditions', function () {
        return view('blank-page');
    })->name('terms-conditions');
    
    Route::get('/thermal-printer/setting', function () {
        return view('settings.thermal-printer');
    })->name('thermal-printer.setting');
    
    Route::get('/settings/general', function () {
        return view('settings.general');
    })->name('settings.general');
    
    Route::post('/settings/general', function () {
        // Handle form submission
        // For now, just redirect back with success message
        return redirect()->route('settings.general')->with('success', 'Settings saved successfully!');
    })->name('settings.general.store');
    
    Route::get('/settings/automation', function () {
        return view('settings.automation');
    })->name('settings.automation');
    
    Route::get('/settings/sms', function () {
        return view('settings.sms');
    })->name('settings.sms');
    
    Route::get('/settings/email', function () {
        return view('settings.email');
    })->name('settings.email');
    
    Route::get('/settings/payment', function () {
        return view('settings.payment');
    })->name('settings.payment');
    
    Route::get('/settings/exam', function () {
        return view('settings.exam');
    })->name('settings.exam');
});
