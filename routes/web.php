<?php

use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\ClassController;
use App\Http\Controllers\Admin\GradeController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\StudentGradeController;
use App\Http\Controllers\StudentSubjectController;
use App\Http\Controllers\Teacher\DashboardController as TeacherDashboardController;
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
        Route::resource('subjects', SubjectController::class)->except('show');
        Route::resource('students', StudentController::class)->except('show');
        Route::get('/grades', [GradeController::class, 'index'])->name('grades.index');
        Route::get('/grades/export', [GradeController::class, 'export'])->name('grades.export');
        Route::post('/grades', [GradeController::class, 'store'])->name('grades.store');
        Route::delete('/grades/{grade}', [GradeController::class, 'destroy'])->name('grades.destroy');
        Route::get('/attendances', [AttendanceController::class, 'index'])->name('attendances.index');
        Route::get('/attendances/export', [AttendanceController::class, 'export'])->name('attendances.export');
        Route::post('/attendances', [AttendanceController::class, 'store'])->name('attendances.store');
    });

    Route::prefix('teacher')->name('teacher.')->middleware('checkRole:teacher')->group(function () {
        Route::get('/dashboard', [TeacherDashboardController::class, 'index'])->name('dashboard');
    });

    Route::prefix('student')->name('student.')->middleware('checkRole:student')->group(function () {
        Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');
        Route::get('/subjects', [StudentSubjectController::class, 'index'])->name('subjects.index');
        Route::post('/subjects/{subject}', [StudentSubjectController::class, 'register'])->name('subjects.register');
        Route::delete('/subjects/{subject}', [StudentSubjectController::class, 'unregister'])->name('subjects.unregister');
        Route::get('/grades', [StudentGradeController::class, 'index'])->name('grades.index');
    });
});
