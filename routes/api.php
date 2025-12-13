<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Block web routes that shouldn't be accessed via API
// These routes should only be accessed via web routes (without /api prefix)
Route::match(['GET', 'POST'], '/admin/login', function (Request $request) {
    if ($request->isMethod('GET')) {
        return redirect('/admin/login', 301);
    }
    return response()->json([
        'success' => false,
        'message' => 'This endpoint is not available via API. Please use /admin/login instead.',
    ], 404);
})->name('api.admin.login.block');

Route::match(['GET', 'POST'], '/staff/login', function (Request $request) {
    if ($request->isMethod('GET')) {
        return redirect('/staff/login', 301);
    }
    return response()->json([
        'success' => false,
        'message' => 'This endpoint is not available via API. Please use /staff/login instead.',
    ], 404);
})->name('api.staff.login.block');

Route::match(['GET', 'POST'], '/student/login', function (Request $request) {
    if ($request->isMethod('GET')) {
        return redirect('/student/login', 301);
    }
    return response()->json([
        'success' => false,
        'message' => 'This endpoint is not available via API. Please use /student/login instead.',
    ], 404);
})->name('api.student.login.block');

Route::match(['GET', 'POST'], '/accountant/login', function (Request $request) {
    if ($request->isMethod('GET')) {
        return redirect('/accountant/login', 301);
    }
    return response()->json([
        'success' => false,
        'message' => 'This endpoint is not available via API. Please use /accountant/login instead.',
    ], 404);
})->name('api.accountant.login.block');

// Parent API Routes
Route::prefix('parent')->name('api.parent.')->group(function () {
    // Public routes (no authentication required)
    Route::post('/login', [App\Http\Controllers\Api\ParentAuthController::class, 'login'])->name('login');
    
    // Protected routes (require authentication)
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/logout', [App\Http\Controllers\Api\ParentAuthController::class, 'logout'])->name('logout');
        Route::get('/profile', [App\Http\Controllers\Api\ParentAuthController::class, 'profile'])->name('profile');
        Route::get('/personal-details', [App\Http\Controllers\Api\ParentAuthController::class, 'personalDetails'])->name('personal-details');
        Route::get('/students', [App\Http\Controllers\Api\ParentAuthController::class, 'students'])->name('students');
        Route::post('/change-password', [App\Http\Controllers\Api\ParentAuthController::class, 'changePassword'])->name('change-password');
        
        // Noticeboard Routes
        Route::match(['GET', 'POST'], '/notices', [App\Http\Controllers\Api\ParentNoticeboardController::class, 'list'])->name('notices.list');
        Route::get('/notices/{id}', [App\Http\Controllers\Api\ParentNoticeboardController::class, 'show'])->name('notices.show');
        Route::get('/notices/filter-options', [App\Http\Controllers\Api\ParentNoticeboardController::class, 'getFilterOptions'])->name('notices.filter-options');
        
        // Homework Routes
        Route::match(['GET', 'POST'], '/homework', [App\Http\Controllers\Api\ParentHomeworkController::class, 'list'])->name('homework.list');
        Route::get('/homework/student/{studentId}', [App\Http\Controllers\Api\ParentHomeworkController::class, 'getByStudent'])->name('homework.by-student');
        Route::get('/homework/subjects', [App\Http\Controllers\Api\ParentHomeworkController::class, 'getSubjects'])->name('homework.subjects');
        
        // Academic Calendar Routes
        Route::match(['GET', 'POST'], '/academic-calendar', [App\Http\Controllers\Api\ParentEventController::class, 'list'])->name('academic-calendar.list');
        Route::get('/academic-calendar/{month}/{year}', [App\Http\Controllers\Api\ParentEventController::class, 'getEventsByMonthYear'])->name('academic-calendar.by-month-year');
        Route::get('/academic-calendar/event/{id}', [App\Http\Controllers\Api\ParentEventController::class, 'show'])->name('academic-calendar.show');
        Route::get('/academic-calendar/calendar-view', [App\Http\Controllers\Api\ParentEventController::class, 'calendarView'])->name('academic-calendar.calendar-view');
        
        // Study Material Routes
        Route::match(['GET', 'POST'], '/study-material', [App\Http\Controllers\Api\ParentStudyMaterialController::class, 'list'])->name('study-material.list');
        Route::get('/study-material/student/{studentId}', [App\Http\Controllers\Api\ParentStudyMaterialController::class, 'getByStudent'])->name('study-material.by-student');
        Route::get('/study-material/subjects', [App\Http\Controllers\Api\ParentStudyMaterialController::class, 'getSubjects'])->name('study-material.subjects');
        
        // Leave Request Routes
        Route::get('/leave/students', [App\Http\Controllers\Api\ParentLeaveController::class, 'getStudents'])->name('leave.students');
        Route::post('/leave/create', [App\Http\Controllers\Api\ParentLeaveController::class, 'create'])->name('leave.create');
        Route::match(['GET', 'POST'], '/leave/list', [App\Http\Controllers\Api\ParentLeaveController::class, 'list'])->name('leave.list');
        Route::get('/leave/{id}', [App\Http\Controllers\Api\ParentLeaveController::class, 'show'])->name('leave.show');
    });
});

// Teacher API Routes
Route::prefix('teacher')->name('api.teacher.')->group(function () {
    // Public routes (no authentication required)
    Route::post('/login', [App\Http\Controllers\Api\TeacherAuthController::class, 'login'])->name('login');
    
    // Protected routes (require authentication)
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/logout', [App\Http\Controllers\Api\TeacherAuthController::class, 'logout'])->name('logout');
        Route::get('/profile', [App\Http\Controllers\Api\TeacherAuthController::class, 'profile'])->name('profile');
        Route::get('/personal-details', [App\Http\Controllers\Api\TeacherAuthController::class, 'personalDetails'])->name('personal-details');
        Route::post('/change-password', [App\Http\Controllers\Api\TeacherAuthController::class, 'changePassword'])->name('change-password');
        Route::get('/dashboard', [App\Http\Controllers\Api\TeacherController::class, 'dashboard'])->name('dashboard');
        Route::get('/assigned-classes', [App\Http\Controllers\Api\TeacherController::class, 'assignedClasses'])->name('assigned-classes');
        
        // Student List Routes
        Route::match(['GET', 'POST'], '/students', [App\Http\Controllers\Api\TeacherStudentController::class, 'index'])->name('students.index');
        Route::get('/students/filter-options', [App\Http\Controllers\Api\TeacherStudentController::class, 'getFilterOptions'])->name('students.filter-options');
        
        // Behavior Recording Routes
        Route::get('/behavior/filter-options', [App\Http\Controllers\Api\TeacherBehaviorController::class, 'getFilterOptions'])->name('behavior.filter-options');
        Route::get('/behavior/sections', [App\Http\Controllers\Api\TeacherBehaviorController::class, 'getSections'])->name('behavior.sections');
        Route::post('/behavior/students', [App\Http\Controllers\Api\TeacherBehaviorController::class, 'getStudents'])->name('behavior.students');
        Route::post('/behavior/save', [App\Http\Controllers\Api\TeacherBehaviorController::class, 'saveRecord'])->name('behavior.save');
        Route::get('/behavior/records', [App\Http\Controllers\Api\TeacherBehaviorController::class, 'getRecords'])->name('behavior.records');
        
        // Attendance Routes
        Route::post('/attendance/mark', [App\Http\Controllers\Api\TeacherAttendanceController::class, 'mark'])->name('attendance.mark');
        Route::post('/attendance/mark-bulk', [App\Http\Controllers\Api\TeacherAttendanceController::class, 'markBulk'])->name('attendance.mark-bulk');
        Route::match(['GET', 'POST'], '/attendance/list', [App\Http\Controllers\Api\TeacherAttendanceController::class, 'list'])->name('attendance.list');
        Route::get('/attendance/filter-options', [App\Http\Controllers\Api\TeacherAttendanceController::class, 'getFilterOptions'])->name('attendance.filter-options');
        Route::get('/attendance/student/{studentId}', [App\Http\Controllers\Api\TeacherAttendanceController::class, 'studentHistory'])->name('attendance.student-history');
        Route::match(['GET', 'POST'], '/attendance/class-students', [App\Http\Controllers\Api\TeacherAttendanceController::class, 'getClassStudents'])->name('attendance.class-students');
        Route::post('/attendance/class-students/mark', [App\Http\Controllers\Api\TeacherAttendanceController::class, 'markClassAttendance'])->name('attendance.class-students.mark');
        
        // Attendance Report Routes
        Route::match(['GET', 'POST'], '/attendance-report/monthly', [App\Http\Controllers\Api\TeacherAttendanceController::class, 'monthlyReport'])->name('attendance-report.monthly');
        Route::get('/attendance-report/filter-options', [App\Http\Controllers\Api\TeacherAttendanceController::class, 'getReportFilterOptions'])->name('attendance-report.filter-options');
        Route::get('/attendance-report/list', [App\Http\Controllers\Api\TeacherAttendanceController::class, 'getReportList'])->name('attendance-report.list');
        
        // Teacher Self Attendance Routes
        Route::match(['GET', 'POST'], '/self-attendance/mark', [App\Http\Controllers\Api\TeacherBehaviorController::class, 'markSelfAttendance'])->name('self-attendance.mark');
        Route::match(['GET', 'POST'], '/self-attendance/check', [App\Http\Controllers\Api\TeacherBehaviorController::class, 'checkSelfAttendance'])->name('self-attendance.check');
        Route::get('/self-attendance/history', [App\Http\Controllers\Api\TeacherBehaviorController::class, 'getSelfAttendanceHistory'])->name('self-attendance.history');
        Route::match(['GET', 'POST'], '/self-attendance/check-in', [App\Http\Controllers\Api\TeacherBehaviorController::class, 'checkIn'])->name('self-attendance.check-in');
        Route::match(['GET', 'POST'], '/self-attendance/check-out', [App\Http\Controllers\Api\TeacherBehaviorController::class, 'checkOut'])->name('self-attendance.check-out');
        
        // Academic Calendar Events Routes
        Route::post('/events/create', [App\Http\Controllers\Api\TeacherEventController::class, 'create'])->name('events.create');
        Route::get('/events/list', [App\Http\Controllers\Api\TeacherEventController::class, 'list'])->name('events.list');
        Route::get('/events/{month}/{year}', [App\Http\Controllers\Api\TeacherEventController::class, 'getEventsByMonthYear'])->name('events.by-month-year');
        Route::get('/events/calendar/view', [App\Http\Controllers\Api\TeacherEventController::class, 'calendarView'])->name('events.calendar-view');
        Route::get('/events/show/{id}', [App\Http\Controllers\Api\TeacherEventController::class, 'show'])->name('events.show');
        Route::put('/events/{id}', [App\Http\Controllers\Api\TeacherEventController::class, 'update'])->name('events.update');
        Route::delete('/events/{id}', [App\Http\Controllers\Api\TeacherEventController::class, 'delete'])->name('events.delete');
        
        // Homework Diary Routes
        Route::get('/homework-diary/filter-options', [App\Http\Controllers\Api\TeacherHomeworkDiaryController::class, 'getFilterOptions'])->name('homework-diary.filter-options');
        Route::get('/homework-diary/sections', [App\Http\Controllers\Api\TeacherHomeworkDiaryController::class, 'getSections'])->name('homework-diary.sections');
        Route::get('/homework-diary/my-subjects', [App\Http\Controllers\Api\TeacherHomeworkDiaryController::class, 'getMySubjects'])->name('homework-diary.my-subjects');
        Route::get('/homework-diary/teacher-subjects', [App\Http\Controllers\Api\TeacherHomeworkDiaryController::class, 'getTeacherSubjects'])->name('homework-diary.teacher-subjects');
        Route::get('/homework-diary/subjects-by-class', [App\Http\Controllers\Api\TeacherHomeworkDiaryController::class, 'getSubjectsByClass'])->name('homework-diary.subjects-by-class');
        Route::get('/homework-diary/subjects-with-homework', [App\Http\Controllers\Api\TeacherHomeworkDiaryController::class, 'getSubjectsWithHomework'])->name('homework-diary.subjects-with-homework');
        Route::get('/homework-diary/entries', [App\Http\Controllers\Api\TeacherHomeworkDiaryController::class, 'getEntries'])->name('homework-diary.entries');
        Route::post('/homework-diary/create', [App\Http\Controllers\Api\TeacherHomeworkDiaryController::class, 'create'])->name('homework-diary.create');
        Route::post('/homework-diary/create-bulk', [App\Http\Controllers\Api\TeacherHomeworkDiaryController::class, 'createBulk'])->name('homework-diary.create-bulk');
        Route::get('/homework-diary/list', [App\Http\Controllers\Api\TeacherHomeworkDiaryController::class, 'list'])->name('homework-diary.list');
        Route::put('/homework-diary/{id}', [App\Http\Controllers\Api\TeacherHomeworkDiaryController::class, 'update'])->name('homework-diary.update');
        Route::delete('/homework-diary/{id}', [App\Http\Controllers\Api\TeacherHomeworkDiaryController::class, 'delete'])->name('homework-diary.delete');
        
        // Noticeboard Routes (Read-only for teachers)
        Route::get('/noticeboard/list', [App\Http\Controllers\Api\TeacherNoticeboardController::class, 'list'])->name('noticeboard.list');
        Route::get('/noticeboard/{id}', [App\Http\Controllers\Api\TeacherNoticeboardController::class, 'show'])->name('noticeboard.show');
        Route::get('/noticeboard/filter-options', [App\Http\Controllers\Api\TeacherNoticeboardController::class, 'getFilterOptions'])->name('noticeboard.filter-options');
        
        // Test Management - Marks Entry Routes
        Route::get('/test-management/marks-entry/filter-options', [App\Http\Controllers\Api\TeacherTestManagementController::class, 'getMarksEntryFilterOptions'])->name('test-management.marks-entry.filter-options');
        Route::get('/test-management/marks-entry/sections', [App\Http\Controllers\Api\TeacherTestManagementController::class, 'getMarksEntrySections'])->name('test-management.marks-entry.sections');
        Route::get('/test-management/marks-entry/tests', [App\Http\Controllers\Api\TeacherTestManagementController::class, 'getMarksEntryTests'])->name('test-management.marks-entry.tests');
        Route::get('/test-management/marks-entry/subjects', [App\Http\Controllers\Api\TeacherTestManagementController::class, 'getMarksEntrySubjects'])->name('test-management.marks-entry.subjects');
        Route::get('/test-management/marks-entry/students', [App\Http\Controllers\Api\TeacherTestManagementController::class, 'getMarksEntryStudents'])->name('test-management.marks-entry.students');
        Route::post('/test-management/marks-entry/save', [App\Http\Controllers\Api\TeacherTestManagementController::class, 'saveMarksEntry'])->name('test-management.marks-entry.save');
        Route::post('/test-management/remarks-entry/save', [App\Http\Controllers\Api\TeacherTestManagementController::class, 'saveRemarksEntry'])->name('test-management.remarks-entry.save');
        
        // Test Management - My Test Routes
        Route::get('/test-management/my-test/subjects', [App\Http\Controllers\Api\TeacherTestManagementController::class, 'getMyTestSubjects'])->name('test-management.my-test.subjects');
        Route::get('/test-management/my-test/students', [App\Http\Controllers\Api\TeacherTestManagementController::class, 'getMyTestStudents'])->name('test-management.my-test.students');
        
        // Test Management - Get Assigned Subjects (Detailed list)
        Route::get('/test-management/assigned-subjects', [App\Http\Controllers\Api\TeacherTestManagementController::class, 'getAssignedSubjects'])->name('test-management.assigned-subjects');
        
        // Test Management - Get Test List (Teacher's created tests) - Must be before {id} route
        Route::match(['GET', 'POST'], '/test-management/list', [App\Http\Controllers\Api\TeacherTestManagementController::class, 'getTestList'])->name('test-management.list');
        
        // Test Management - Get Test by ID
        Route::get('/test-management/{id}', [App\Http\Controllers\Api\TeacherTestManagementController::class, 'getTest'])->name('test-management.get');
        
        // Leave Management Routes
        Route::match(['GET', 'POST'], '/leave/create', [App\Http\Controllers\Api\TeacherLeaveController::class, 'create'])->name('leave.create');
        Route::get('/leave/list', [App\Http\Controllers\Api\TeacherLeaveController::class, 'list'])->name('leave.list');
        Route::post('/leave/{id}/cancel', [App\Http\Controllers\Api\TeacherLeaveController::class, 'cancel'])->name('leave.cancel');
        
        // Timetable Management Routes
        Route::get('/timetable/filter-options', [App\Http\Controllers\Api\TeacherTimetableController::class, 'getFilterOptions'])->name('timetable.filter-options');
        Route::match(['GET', 'POST'], '/timetable/sections', [App\Http\Controllers\Api\TeacherTimetableController::class, 'getSectionsByClass'])->name('timetable.sections');
        Route::get('/timetable/list/{day}/{month}/{year}', [App\Http\Controllers\Api\TeacherTimetableController::class, 'getTimetable'])->name('timetable.list-by-date');
        Route::match(['GET', 'POST'], '/timetable/list', [App\Http\Controllers\Api\TeacherTimetableController::class, 'getTimetable'])->name('timetable.list');
        Route::match(['GET', 'POST'], '/timetable/by-class', [App\Http\Controllers\Api\TeacherTimetableController::class, 'getTimetableByClass'])->name('timetable.by-class');
    });
});

