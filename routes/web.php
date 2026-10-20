<?php

use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\ClassController;
use App\Http\Controllers\Admin\GradeController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\StudentGradeController;
use App\Http\Controllers\StudentSubjectController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:10,15')
    ->name('login.store');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', function () {
        return redirect()->route(auth()->user()->role.'.dashboard');
    });

    Route::prefix('admin')->name('admin.')->middleware('checkRole:admin')->group(function () {
        Route::get('/dashboard', fn () => view('admin.dashboard'))->name('dashboard');
        Route::resource('classes', ClassController::class)->except('show');
        Route::resource('subjects', SubjectController::class)->except('show');
        Route::resource('students', StudentController::class)->except('show');
        Route::get('/grades', [GradeController::class, 'index'])->name('grades.index');
        Route::post('/grades', [GradeController::class, 'store'])->name('grades.store');
        Route::delete('/grades/{grade}', [GradeController::class, 'destroy'])->name('grades.destroy');
        Route::get('/attendances', [AttendanceController::class, 'index'])->name('attendances.index');
        Route::post('/attendances', [AttendanceController::class, 'store'])->name('attendances.store');
    });

    Route::prefix('student')->name('student.')->middleware('checkRole:student')->group(function () {
        Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');
        Route::get('/subjects', [StudentSubjectController::class, 'index'])->name('subjects.index');
        Route::post('/subjects/{subject}', [StudentSubjectController::class, 'register'])->name('subjects.register');
        Route::delete('/subjects/{subject}', [StudentSubjectController::class, 'unregister'])->name('subjects.unregister');
        Route::get('/grades', [StudentGradeController::class, 'index'])->name('grades.index');
    });
});
