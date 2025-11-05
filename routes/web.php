<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Admission Management Routes
Route::get('/admission/admit-student', [App\Http\Controllers\AdmissionController::class, 'create'])->name('admission.admit-student');
Route::post('/admission/admit-student', [App\Http\Controllers\AdmissionController::class, 'store'])->name('admission.admit-student.store');

Route::get('/admission/admit-bulk-student', function () {
    return view('admission.admit-bulk-student');
})->name('admission.admit-bulk-student');

// Admission Request Routes
Route::get('/admission/request', [App\Http\Controllers\AdmissionRequestController::class, 'index'])->name('admission.request');
Route::post('/admission/request', [App\Http\Controllers\AdmissionRequestController::class, 'store'])->name('admission.request.store');
Route::put('/admission/request/{admission_request}', [App\Http\Controllers\AdmissionRequestController::class, 'update'])->name('admission.request.update');
Route::delete('/admission/request/{admission_request}', [App\Http\Controllers\AdmissionRequestController::class, 'destroy'])->name('admission.request.destroy');
Route::get('/admission/request/export/{format}', [App\Http\Controllers\AdmissionRequestController::class, 'export'])->name('admission.request.export');

Route::get('/admission/report', function () {
    return view('admission.report');
})->name('admission.report');

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

Route::get('/student/promotion', [App\Http\Controllers\StudentPromotionController::class, 'index'])->name('student.promotion');
Route::post('/student/promotion', [App\Http\Controllers\StudentPromotionController::class, 'promote'])->name('student.promotion.promote');

Route::get('/student/birthday', [App\Http\Controllers\StudentBirthdayController::class, 'index'])->name('student.birthday');
Route::get('/student/birthday/export/{format}', [App\Http\Controllers\StudentBirthdayController::class, 'export'])->name('student.birthday.export');

Route::get('/student/transfer', function () {
    return view('student.transfer');
})->name('student.transfer');

Route::get('/student/info-report', function () {
    return view('student.info-report');
})->name('student.info-report');

Route::get('/dashboard/crm', [DashboardController::class, 'crm'])->name('dashboard.crm');

// Parent Account Routes
Route::get('/parent/manage-access', [App\Http\Controllers\ParentAccountController::class, 'index'])->name('parent.manage-access');
Route::post('/parent/manage-access', [App\Http\Controllers\ParentAccountController::class, 'store'])->name('parent.manage-access.store');
Route::delete('/parent/manage-access/delete-all', [App\Http\Controllers\ParentAccountController::class, 'deleteAll'])->name('parent.manage-access.delete-all');
Route::put('/parent/manage-access/{parent_account}', [App\Http\Controllers\ParentAccountController::class, 'update'])->name('parent.manage-access.update');
Route::delete('/parent/manage-access/{parent_account}', [App\Http\Controllers\ParentAccountController::class, 'destroy'])->name('parent.manage-access.destroy');
Route::get('/parent/manage-access/export/{format}', [App\Http\Controllers\ParentAccountController::class, 'export'])->name('parent.manage-access.export');

Route::get('/parent/account-request', [App\Http\Controllers\ParentAccountRequestController::class, 'index'])->name('parent.account-request');
Route::get('/parent/account-request/export/{format}', [App\Http\Controllers\ParentAccountRequestController::class, 'export'])->name('parent.account-request.export');

Route::get('/parent/print-gate-passes', function () {
    return view('parent.print-gate-passes');
})->name('parent.print-gate-passes');

Route::get('/parent/info-request', [App\Http\Controllers\ParentInfoRequestController::class, 'index'])->name('parent.info-request');
Route::post('/parent/info-request/filter', [App\Http\Controllers\ParentInfoRequestController::class, 'filter'])->name('parent.info-request.filter');

Route::get('/dashboard/project-management', [DashboardController::class, 'projectManagement'])->name('dashboard.project-management');

// Staff Management Routes
Route::get('/staff/management', [App\Http\Controllers\StaffManagementController::class, 'index'])->name('staff.management');
Route::post('/staff/management', [App\Http\Controllers\StaffManagementController::class, 'store'])->name('staff.management.store');
Route::delete('/staff/management/delete-all', [App\Http\Controllers\StaffManagementController::class, 'deleteAll'])->name('staff.management.delete-all');
Route::get('/staff/management/{staff}', [App\Http\Controllers\StaffManagementController::class, 'show'])->name('staff.management.show');
Route::put('/staff/management/{staff}', [App\Http\Controllers\StaffManagementController::class, 'update'])->name('staff.management.update');
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
Route::delete('/task-management/{task}', [App\Http\Controllers\TaskManagementController::class, 'destroy'])->name('task-management.destroy');
Route::get('/task-management/export/{format}', [App\Http\Controllers\TaskManagementController::class, 'export'])->name('task-management.export');

Route::get('/dashboard/help-desk', [DashboardController::class, 'helpDesk'])->name('dashboard.help-desk');

// ID Card Printing Routes
Route::get('/id-card/print-student', function () {
    return view('id-card.print-student');
})->name('id-card.print-student');

Route::get('/id-card/print-staff', function () {
    return view('id-card.print-staff');
})->name('id-card.print-staff');

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

// Accounting Routes
Route::get('/accounting/generate-monthly-fee', function () {
    return view('accounting.generate-monthly-fee');
})->name('accounting.generate-monthly-fee');

Route::get('/accounting/generate-custom-fee', function () {
    return view('accounting.generate-custom-fee');
})->name('accounting.generate-custom-fee');

Route::get('/accounting/generate-transport-fee', function () {
    return view('accounting.generate-transport-fee');
})->name('accounting.generate-transport-fee');

Route::get('/accounting/fee-type', function () {
    return view('accounting.fee-type');
})->name('accounting.fee-type');

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
Route::get('/accounting/direct-payment/student', function () {
    return view('accounting.direct-payment.student');
})->name('accounting.direct-payment.student');

Route::get('/accounting/direct-payment/custom', function () {
    return view('accounting.direct-payment.custom');
})->name('accounting.direct-payment.custom');

// Fee Increment Routes
Route::get('/accounting/fee-increment/percentage', function () {
    return view('accounting.fee-increment.percentage');
})->name('accounting.fee-increment.percentage');

Route::get('/accounting/fee-increment/amount', function () {
    return view('accounting.fee-increment.amount');
})->name('accounting.fee-increment.amount');

// Fee Document Routes
Route::get('/accounting/fee-document/decrement-percentage', function () {
    return view('accounting.fee-document.decrement-percentage');
})->name('accounting.fee-document.decrement-percentage');

Route::get('/accounting/fee-document/decrement-amount', function () {
    return view('accounting.fee-document.decrement-amount');
})->name('accounting.fee-document.decrement-amount');

// Fee Voucher Routes
Route::get('/accounting/fee-voucher/student', function () {
    return view('accounting.fee-voucher.student');
})->name('accounting.fee-voucher.student');

Route::get('/accounting/fee-voucher/family', function () {
    return view('accounting.fee-voucher.family');
})->name('accounting.fee-voucher.family');

// Parent Complain Route
Route::get('/parent-complain', function () {
    return view('parent-complain');
})->name('parent-complain');
Route::get('/parent-complain/export/{format}', function () {
    // TODO: Add controller and export functionality
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

// Manage Subjects Route
Route::get('/manage-subjects', [App\Http\Controllers\ManageSubjectsController::class, 'index'])->name('manage-subjects');

// Manage Attendance Routes
Route::get('/attendance/student', function () {
    return view('attendance.student');
})->name('attendance.student');

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

Route::get('/attendance/report', function () {
    return view('attendance.report');
})->name('attendance.report');

// Online Classes Routes
Route::get('/online-classes', [App\Http\Controllers\OnlineClassesController::class, 'index'])->name('online-classes');
Route::post('/online-classes', [App\Http\Controllers\OnlineClassesController::class, 'store'])->name('online-classes.store');
Route::put('/online-classes/{online_class}', [App\Http\Controllers\OnlineClassesController::class, 'update'])->name('online-classes.update');
Route::delete('/online-classes/{online_class}', [App\Http\Controllers\OnlineClassesController::class, 'destroy'])->name('online-classes.destroy');
Route::get('/online-classes/export/{format}', [App\Http\Controllers\OnlineClassesController::class, 'export'])->name('online-classes.export');

// Timetable Management Routes
Route::get('/timetable/add', function () {
    return view('timetable.add');
})->name('timetable.add');
Route::post('/timetable/add', [App\Http\Controllers\TimetableController::class, 'store'])->name('timetable.store');

Route::get('/timetable/manage', [App\Http\Controllers\TimetableController::class, 'index'])->name('timetable.manage');

// Academic Holiday Calendar Routes
Route::get('/academic-calendar/manage-events', function () {
    return view('academic-calendar.manage-events');
})->name('academic-calendar.manage-events');

Route::get('/academic-calendar/view', function () {
    return view('academic-calendar.view');
})->name('academic-calendar.view');

// Fee Management Route
Route::get('/fee-management', function () {
    return view('fee-management');
})->name('fee-management');

// Fee Payment Route
Route::get('/fee-payment', function () {
    return view('fee-payment');
})->name('fee-payment');

// Expense Management Routes
Route::get('/expense-management/add', function () {
    return view('expense-management.add');
})->name('expense-management.add');

Route::get('/expense-management/categories', function () {
    return view('expense-management.categories');
})->name('expense-management.categories');

// Salary and Loan Management Routes
Route::get('/salary-loan/generate-salary', function () {
    return view('salary-loan.generate-salary');
})->name('salary-loan.generate-salary');

Route::get('/salary-loan/manage-salaries', function () {
    return view('salary-loan.manage-salaries');
})->name('salary-loan.manage-salaries');

Route::get('/salary-loan/loan-management', function () {
    return view('salary-loan.loan-management');
})->name('salary-loan.loan-management');

Route::get('/salary-loan/salary-setting', function () {
    return view('salary-loan.salary-setting');
})->name('salary-loan.salary-setting');

Route::get('/salary-loan/report', function () {
    return view('salary-loan.report');
})->name('salary-loan.report');

// Salary Increment Routes
Route::get('/salary-loan/increment/percentage', function () {
    return view('salary-loan.increment.percentage');
})->name('salary-loan.increment.percentage');

Route::get('/salary-loan/increment/amount', function () {
    return view('salary-loan.increment.amount');
})->name('salary-loan.increment.amount');

// Salary Decrement Routes
Route::get('/salary-loan/decrement/percentage', function () {
    return view('salary-loan.decrement.percentage');
})->name('salary-loan.decrement.percentage');

Route::get('/salary-loan/decrement/amount', function () {
    return view('salary-loan.decrement.amount');
})->name('salary-loan.decrement.amount');

Route::get('/dashboard/school', [DashboardController::class, 'school'])->name('dashboard.school');
Route::get('/dashboard/marketing', [DashboardController::class, 'marketing'])->name('dashboard.marketing');
Route::get('/dashboard/analytics', [DashboardController::class, 'analytics'])->name('dashboard.analytics');
Route::get('/dashboard/hospital', [DashboardController::class, 'hospital'])->name('dashboard.hospital');
Route::get('/dashboard/finance', [DashboardController::class, 'finance'])->name('dashboard.finance');

// Reporting Routes
Route::get('/reports/fee-default', function () {
    return view('reports.fee-default');
})->name('reports.fee-default');

Route::get('/reports/head-wise-dues', function () {
    return view('reports.head-wise-dues');
})->name('reports.head-wise-dues');

Route::get('/reports/income-expense', function () {
    return view('reports.income-expense');
})->name('reports.income-expense');

Route::get('/reports/debit-credit', function () {
    return view('reports.debit-credit');
})->name('reports.debit-credit');

Route::get('/reports/unpaid-invoices', function () {
    return view('reports.unpaid-invoices');
})->name('reports.unpaid-invoices');

Route::get('/reports/accounts-summary', function () {
    return view('reports.accounts-summary');
})->name('reports.accounts-summary');

Route::get('/reports/detailed-income', function () {
    return view('reports.detailed-income');
})->name('reports.detailed-income');

Route::get('/reports/detailed-expense', function () {
    return view('reports.detailed-expense');
})->name('reports.detailed-expense');

Route::get('/reports/staff-salary', function () {
    return view('reports.staff-salary');
})->name('reports.staff-salary');

Route::get('/reports/balance-sheet', function () {
    return view('reports.balance-sheet');
})->name('reports.balance-sheet');

Route::get('/reports/admission-data', function () {
    return view('reports.admission-data');
})->name('reports.admission-data');

// Stock & Inventory Routes
Route::get('/stock/point-of-sale', function () {
    return view('stock.point-of-sale');
})->name('stock.point-of-sale');

Route::get('/stock/manage-categories', function () {
    return view('stock.manage-categories');
})->name('stock.manage-categories');

Route::get('/stock/add-bulk-products', function () {
    return view('stock.add-bulk-products');
})->name('stock.add-bulk-products');

Route::get('/stock/manage-sale-records', function () {
    return view('stock.manage-sale-records');
})->name('stock.manage-sale-records');

Route::get('/stock/sale-reports', function () {
    return view('stock.sale-reports');
})->name('stock.sale-reports');

// Student Behavior Management Routes
Route::get('/student-behavior/recording', function () {
    return view('student-behavior.recording');
})->name('student-behavior.recording');

Route::get('/student-behavior/categories', function () {
    return view('student-behavior.categories');
})->name('student-behavior.categories');

Route::get('/student-behavior/progress-tracking', function () {
    return view('student-behavior.progress-tracking');
})->name('student-behavior.progress-tracking');

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
Route::get('/test/list', function () {
    return view('test.list');
})->name('test.list');

Route::get('/test/marks-entry', function () {
    return view('test.marks-entry');
})->name('test.marks-entry');

Route::get('/test/schedule', function () {
    return view('test.schedule');
})->name('test.schedule');

// Test Reports - Assign Grades
Route::get('/test/assign-grades/particular', function () {
    return view('test.assign-grades.particular');
})->name('test.assign-grades.particular');

Route::get('/test/assign-grades/combined', function () {
    return view('test.assign-grades.combined');
})->name('test.assign-grades.combined');

// Test Reports - Teacher Remarks
Route::get('/test/teacher-remarks/practical', function () {
    return view('test.teacher-remarks.practical');
})->name('test.teacher-remarks.practical');

Route::get('/test/teacher-remarks/combined', function () {
    return view('test.teacher-remarks.combined');
})->name('test.teacher-remarks.combined');

// Test Reports - Tabulation Sheet
Route::get('/test/tabulation-sheet/practical', function () {
    return view('test.tabulation-sheet.practical');
})->name('test.tabulation-sheet.practical');

Route::get('/test/tabulation-sheet/combine', function () {
    return view('test.tabulation-sheet.combine');
})->name('test.tabulation-sheet.combine');

// Test Reports - Position Holder
Route::get('/test/position-holder/practical', function () {
    return view('test.position-holder.practical');
})->name('test.position-holder.practical');

Route::get('/test/position-holder/combine', function () {
    return view('test.position-holder.combine');
})->name('test.position-holder.combine');

// Test Reports - Send Marks to Parents
Route::get('/test/send-marks/practical', function () {
    return view('test.send-marks.practical');
})->name('test.send-marks.practical');

Route::get('/test/send-marks/combined', function () {
    return view('test.send-marks.combined');
})->name('test.send-marks.combined');

// Test Reports - Send Marksheet via WA
Route::get('/test/send-marksheet/practical', function () {
    return view('test.send-marksheet.practical');
})->name('test.send-marksheet.practical');

Route::get('/test/send-marksheet/combine', function () {
    return view('test.send-marksheet.combine');
})->name('test.send-marksheet.combine');

// Test Reports - Print Marksheets
Route::get('/test/print-marksheets/practical', function () {
    return view('test.print-marksheets.practical');
})->name('test.print-marksheets.practical');

Route::get('/test/print-marksheets/combine', function () {
    return view('test.print-marksheets.combine');
})->name('test.print-marksheets.combine');

// Exam Management Routes
Route::get('/exam/list', function () {
    return view('exam.list');
})->name('exam.list');

Route::get('/exam/marks-entry', function () {
    return view('exam.marks-entry');
})->name('exam.marks-entry');

Route::get('/exam/print-admit-cards', function () {
    return view('exam.print-admit-cards');
})->name('exam.print-admit-cards');

Route::get('/exam/send-admit-cards', function () {
    return view('exam.send-admit-cards');
})->name('exam.send-admit-cards');

// Exam Grades
Route::get('/exam/grades/particular', function () {
    return view('exam.grades.particular');
})->name('exam.grades.particular');

Route::get('/exam/grades/final', function () {
    return view('exam.grades.final');
})->name('exam.grades.final');

// Teacher Remarks
Route::get('/exam/teacher-remarks/particular', function () {
    return view('exam.teacher-remarks.particular');
})->name('exam.teacher-remarks.particular');

Route::get('/exam/teacher-remarks/final', function () {
    return view('exam.teacher-remarks.final');
})->name('exam.teacher-remarks.final');

// Exam Timetable
Route::get('/exam/timetable/add', function () {
    return view('exam.timetable.add');
})->name('exam.timetable.add');

Route::get('/exam/timetable/manage', function () {
    return view('exam.timetable.manage');
})->name('exam.timetable.manage');

// Tabulation Sheet
Route::get('/exam/tabulation-sheet/particular', function () {
    return view('exam.tabulation-sheet.particular');
})->name('exam.tabulation-sheet.particular');

Route::get('/exam/tabulation-sheet/final', function () {
    return view('exam.tabulation-sheet.final');
})->name('exam.tabulation-sheet.final');

// Position Holders
Route::get('/exam/position-holders/particular', function () {
    return view('exam.position-holders.particular');
})->name('exam.position-holders.particular');

Route::get('/exam/position-holders/final', function () {
    return view('exam.position-holders.final');
})->name('exam.position-holders.final');

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
Route::get('/quiz/manage', function () {
    return view('quiz.manage');
})->name('quiz.manage');

// Certification Routes
Route::get('/certification/student', function () {
    return view('certification.student');
})->name('certification.student');

Route::get('/certification/staff', function () {
    return view('certification.staff');
})->name('certification.staff');

// Daily Homework Diary Routes
Route::get('/homework-diary/manage', function () {
    return view('homework-diary.manage');
})->name('homework-diary.manage');

Route::get('/homework-diary/send-sms', function () {
    return view('homework-diary.send-sms');
})->name('homework-diary.send-sms');

// Study Material - LMS Route
Route::get('/study-material/lms', function () {
    return view('study-material.lms');
})->name('study-material.lms');

// Leave Management Route
Route::get('/leave-management', function () {
    return view('leave-management');
})->name('leave-management');

// SMS Management Routes
Route::get('/sms/parent', function () {
    return view('sms.parent');
})->name('sms.parent');

Route::get('/sms/staff', function () {
    return view('sms.staff');
})->name('sms.staff');

Route::get('/sms/specific-number', function () {
    return view('sms.specific-number');
})->name('sms.specific-number');

Route::get('/sms/history', function () {
    return view('sms.history');
})->name('sms.history');

// Mobile App Notification Routes
Route::get('/notification/parent', function () {
    return view('notification.parent');
})->name('notification.parent');

Route::get('/notification/staff', function () {
    return view('notification.staff');
})->name('notification.staff');

Route::get('/notification/student', function () {
    return view('notification.student');
})->name('notification.student');

Route::get('/notification/history', function () {
    return view('notification.history');
})->name('notification.history');

// WhatsApp Notification Routes
Route::get('/whatsapp/parent', function () {
    return view('whatsapp.parent');
})->name('whatsapp.parent');

Route::get('/whatsapp/staff', function () {
    return view('whatsapp.staff');
})->name('whatsapp.staff');

Route::get('/whatsapp/history', function () {
    return view('whatsapp.history');
})->name('whatsapp.history');

// roboBuddy-whatsapp bot Route
Route::get('/robobuddy/whatsapp-bot', function () {
    return view('robobuddy.whatsapp-bot');
})->name('robobuddy.whatsapp-bot');

// Send/WhatsApp Template Route
Route::get('/whatsapp/template', function () {
    return view('whatsapp.template');
})->name('whatsapp.template');

// Email Alerts Routes
Route::get('/email-alerts/specific', function () {
    return view('email-alerts.specific');
})->name('email-alerts.specific');

Route::get('/email-alerts/history', function () {
    return view('email-alerts.history');
})->name('email-alerts.history');

// School Noticeboard Route
Route::get('/school/noticeboard', function () {
    return view('school.noticeboard');
})->name('school.noticeboard');

// Manage Campuses Route
Route::get('/manage/campuses', function () {
    return view('manage.campuses');
})->name('manage.campuses');

// Admin Roles Management Route
Route::get('/admin/roles-management', function () {
    return view('admin.roles-management');
})->name('admin.roles-management');

// Transport Routes
Route::get('/transport/manage', function () {
    return view('transport.manage');
})->name('transport.manage');

Route::get('/transport/reports', function () {
    return view('transport.reports');
})->name('transport.reports');

// Manage Biometric Devices Route
Route::get('/biometric/devices', function () {
    return view('biometric.devices');
})->name('biometric.devices');

// Website Management Routes
Route::get('/website-management/general-gallery', function () {
    return view('website-management.general-gallery');
})->name('website-management.general-gallery');

Route::get('/website-management/classes-show', function () {
    return view('website-management.classes-show');
})->name('website-management.classes-show');

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
Route::get('/student-list', function () {
    return view('blank-page');
})->name('student-list');

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

// Events Routes
Route::get('/events', function () {
    return view('blank-page');
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

// Settings Routes
Route::get('/account-settings', function () {
    return view('blank-page');
})->name('account-settings');

Route::get('/change-password', function () {
    return view('blank-page');
})->name('change-password');

Route::get('/connections', function () {
    return view('blank-page');
})->name('connections');

Route::get('/privacy-policy', function () {
    return view('blank-page');
})->name('privacy-policy');

Route::get('/terms-conditions', function () {
    return view('blank-page');
})->name('terms-conditions');
