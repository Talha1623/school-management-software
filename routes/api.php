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

// Teacher API Routes
Route::prefix('teacher')->name('api.teacher.')->group(function () {
    // Public routes (no authentication required)
    Route::post('/login', [App\Http\Controllers\Api\TeacherAuthController::class, 'login'])->name('login');
    
    // Protected routes (require authentication)
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/logout', [App\Http\Controllers\Api\TeacherAuthController::class, 'logout'])->name('logout');
        Route::get('/profile', [App\Http\Controllers\Api\TeacherAuthController::class, 'profile'])->name('profile');
        Route::get('/dashboard', [App\Http\Controllers\Api\TeacherController::class, 'dashboard'])->name('dashboard');
        
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
        
        // Attendance Report Routes
        Route::match(['GET', 'POST'], '/attendance-report/monthly', [App\Http\Controllers\Api\TeacherAttendanceController::class, 'monthlyReport'])->name('attendance-report.monthly');
        Route::get('/attendance-report/filter-options', [App\Http\Controllers\Api\TeacherAttendanceController::class, 'getReportFilterOptions'])->name('attendance-report.filter-options');
        
        // Teacher Self Attendance Routes
        Route::post('/self-attendance/mark', [App\Http\Controllers\Api\TeacherBehaviorController::class, 'markSelfAttendance'])->name('self-attendance.mark');
        Route::get('/self-attendance/check', [App\Http\Controllers\Api\TeacherBehaviorController::class, 'checkSelfAttendance'])->name('self-attendance.check');
        Route::get('/self-attendance/history', [App\Http\Controllers\Api\TeacherBehaviorController::class, 'getSelfAttendanceHistory'])->name('self-attendance.history');
        
        // Academic Calendar Events Routes
        Route::post('/events/create', [App\Http\Controllers\Api\TeacherEventController::class, 'create'])->name('events.create');
        Route::get('/events/list', [App\Http\Controllers\Api\TeacherEventController::class, 'list'])->name('events.list');
        Route::get('/events/{id}', [App\Http\Controllers\Api\TeacherEventController::class, 'show'])->name('events.show');
        Route::put('/events/{id}', [App\Http\Controllers\Api\TeacherEventController::class, 'update'])->name('events.update');
        Route::delete('/events/{id}', [App\Http\Controllers\Api\TeacherEventController::class, 'delete'])->name('events.delete');
        Route::get('/events/calendar/view', [App\Http\Controllers\Api\TeacherEventController::class, 'calendarView'])->name('events.calendar-view');
        
        // Homework Diary Routes
        Route::get('/homework-diary/filter-options', [App\Http\Controllers\Api\TeacherHomeworkDiaryController::class, 'getFilterOptions'])->name('homework-diary.filter-options');
        Route::get('/homework-diary/sections', [App\Http\Controllers\Api\TeacherHomeworkDiaryController::class, 'getSections'])->name('homework-diary.sections');
        Route::get('/homework-diary/my-subjects', [App\Http\Controllers\Api\TeacherHomeworkDiaryController::class, 'getMySubjects'])->name('homework-diary.my-subjects');
        Route::get('/homework-diary/entries', [App\Http\Controllers\Api\TeacherHomeworkDiaryController::class, 'getEntries'])->name('homework-diary.entries');
        Route::post('/homework-diary/create', [App\Http\Controllers\Api\TeacherHomeworkDiaryController::class, 'create'])->name('homework-diary.create');
        Route::post('/homework-diary/create-bulk', [App\Http\Controllers\Api\TeacherHomeworkDiaryController::class, 'createBulk'])->name('homework-diary.create-bulk');
        Route::get('/homework-diary/list', [App\Http\Controllers\Api\TeacherHomeworkDiaryController::class, 'list'])->name('homework-diary.list');
        Route::put('/homework-diary/{id}', [App\Http\Controllers\Api\TeacherHomeworkDiaryController::class, 'update'])->name('homework-diary.update');
        Route::delete('/homework-diary/{id}', [App\Http\Controllers\Api\TeacherHomeworkDiaryController::class, 'delete'])->name('homework-diary.delete');
    });
});

