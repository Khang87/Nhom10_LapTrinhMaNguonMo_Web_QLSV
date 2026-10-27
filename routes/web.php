<?php

use App\Http\Controllers\Admin\AcademicStatusController;
use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\ClassController;
use App\Http\Controllers\Admin\CourseOfferingController;
use App\Http\Controllers\Admin\CurriculumOfferingController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\EnrollmentController as AdminEnrollmentController;
use App\Http\Controllers\Admin\GradeController;
use App\Http\Controllers\Admin\MajorController;
use App\Http\Controllers\Admin\SemesterController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\StudyPlanController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\TimetableController as AdminTimetableController;
use App\Http\Controllers\Admin\TuitionController as AdminTuitionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\Student\CourseOfferingController as StudentCourseOfferingController;
use App\Http\Controllers\Student\EnrollmentController as StudentEnrollmentController;
use App\Http\Controllers\Student\GradeController as StudentGradeController;
use App\Http\Controllers\Student\TimetableController as StudentTimetableController;
use App\Http\Controllers\Student\TuitionController as StudentTuitionController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\Teacher\AttendanceController as TeacherAttendanceController;
use App\Http\Controllers\Teacher\DashboardController as TeacherDashboardController;
use App\Http\Controllers\Teacher\GradeController as TeacherGradeController;
use App\Http\Controllers\Teacher\OfferingController as TeacherOfferingController;
use App\Http\Controllers\Teacher\TimetableController as TeacherTimetableController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:10,15')
    ->name('login.store');
Route::get('/forgot-password', [PasswordResetController::class, 'showLinkRequestForm'])
    ->name('password.request');
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetOtp'])
    ->middleware('throttle:5,1')
    ->name('password.email');
Route::post('/forgot-password/verify', [PasswordResetController::class, 'verifyOtp'])
    ->middleware('throttle:10,1')
    ->name('password.verify');
Route::get('/reset-password', [PasswordResetController::class, 'showResetForm'])
    ->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])
    ->middleware('throttle:5,1')
    ->name('password.update');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', function () {
        return redirect()->route(auth()->user()->dashboardRoute());
    });

    Route::prefix('admin')->name('admin.')->middleware('checkRole:admin')->group(function () {
        Route::get('/dashboard', fn () => view('admin.dashboard'))->name('dashboard');
        Route::resource('accounts', AccountController::class)->except('show');
        Route::resource('classes', ClassController::class)->except('show');
        Route::resource('semesters', SemesterController::class)->except('show');
        Route::post('/semesters/{semester}/activate', [SemesterController::class, 'activate'])->name('semesters.activate');
        Route::resource('course-offerings', CourseOfferingController::class)->except('show');
        Route::get('/course-offerings/open-by-plan', [CurriculumOfferingController::class, 'index'])->name('course-offerings.open-by-plan');
        Route::post('/course-offerings/open-by-plan', [CurriculumOfferingController::class, 'store'])->name('course-offerings.open-by-plan.store');
        Route::resource('departments', DepartmentController::class)->except('show');
        Route::resource('majors', MajorController::class)->except('show');
        Route::get('/study-plans', [StudyPlanController::class, 'index'])->name('study-plans.index');
        Route::post('/study-plans', [StudyPlanController::class, 'store'])->name('study-plans.store');
        Route::put('/study-plans', [StudyPlanController::class, 'sync'])->name('study-plans.sync');
        Route::delete('/study-plans/{studyPlan}', [StudyPlanController::class, 'destroy'])->name('study-plans.destroy');
        Route::resource('subjects', SubjectController::class)->except('show');
        Route::get('/students/export', [StudentController::class, 'export'])->name('students.export');
        Route::get('/students/import', [StudentController::class, 'importForm'])->name('students.import');
        Route::get('/students/import/template', [StudentController::class, 'template'])->name('students.import.template');
        Route::post('/students/import', [StudentController::class, 'import'])->name('students.import.store');
        Route::resource('students', StudentController::class);
        Route::post('/students/{student}/academic-status', [AcademicStatusController::class, 'update'])->name('students.academic-status.update');
        Route::get('/grades', [GradeController::class, 'index'])->name('grades.index');
        Route::get('/grades/export', [GradeController::class, 'export'])->name('grades.export');
        Route::post('/grades', [GradeController::class, 'store'])->name('grades.store');
        Route::delete('/grades/{grade}', [GradeController::class, 'destroy'])->name('grades.destroy');
        Route::get('/attendances', [AttendanceController::class, 'index'])->name('attendances.index');
        Route::get('/attendances/export', [AttendanceController::class, 'export'])->name('attendances.export');
        Route::get('/timetable', [AdminTimetableController::class, 'index'])->name('timetable.index');
        Route::get('/timetable/export', [AdminTimetableController::class, 'export'])->name('timetable.export');
        Route::get('/tuition', [AdminTuitionController::class, 'index'])->name('tuition.index');
        Route::post('/tuition/declare', [AdminTuitionController::class, 'declare'])->name('tuition.declare');
        Route::post('/tuition/{invoice}/paid', [AdminTuitionController::class, 'markPaid'])->name('tuition.paid');
        Route::post('/tuition/{invoice}/unpaid', [AdminTuitionController::class, 'markUnpaid'])->name('tuition.unpaid');
        Route::get('/enrollments', [AdminEnrollmentController::class, 'index'])->name('enrollments.index');
        Route::get('/enrollments/export', [AdminEnrollmentController::class, 'export'])->name('enrollments.export');
        Route::post('/enrollments', [AdminEnrollmentController::class, 'store'])->name('enrollments.store');
        Route::delete('/enrollments/{enrollment}', [AdminEnrollmentController::class, 'drop'])->name('enrollments.drop');
    });

    Route::prefix('teacher')->name('teacher.')->middleware('checkRole:teacher')->group(function () {
        Route::get('/dashboard', [TeacherDashboardController::class, 'index'])->name('dashboard');
        Route::get('/my-offerings', [TeacherOfferingController::class, 'index'])->name('offerings.index');
        Route::get('/timetable', [TeacherTimetableController::class, 'index'])->name('timetable');
        Route::get('/course-offerings/{offering}/grades', [TeacherGradeController::class, 'edit'])->name('course-offerings.grades');
        Route::post('/course-offerings/{offering}/grades', [TeacherGradeController::class, 'update'])->name('course-offerings.grades.update');
        Route::post('/course-offerings/{offering}/grades/publish', [TeacherGradeController::class, 'publish'])->name('course-offerings.grades.publish');
        Route::get('/course-offerings/{offering}/attendance', [TeacherAttendanceController::class, 'index'])->name('course-offerings.attendance');
        Route::get('/course-offerings/{offering}/attendance/session/create', [TeacherAttendanceController::class, 'create'])->name('course-offerings.attendance.session.create');
        Route::post('/course-offerings/{offering}/attendance/session', [TeacherAttendanceController::class, 'store'])->name('course-offerings.attendance.session.store');
    });

    Route::prefix('student')->name('student.')->middleware('checkRole:student')->group(function () {
        Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');
        Route::get('/course-offerings', [StudentCourseOfferingController::class, 'index'])->name('course-offerings.index');
        Route::post('/course-offerings/{offering}/enroll', [StudentCourseOfferingController::class, 'enroll'])->name('course-offerings.enroll');
        Route::delete('/enrollments/{enrollment}', [StudentEnrollmentController::class, 'drop'])->name('enrollments.drop');
        Route::get('/grades', [StudentGradeController::class, 'index'])->name('grades.index');
        Route::get('/grades/history', [StudentGradeController::class, 'history'])->name('grades.history');
        Route::get('/timetable', [StudentTimetableController::class, 'index'])->name('timetable');
        Route::get('/tuition', [StudentTuitionController::class, 'index'])->name('tuition');
    });
});
