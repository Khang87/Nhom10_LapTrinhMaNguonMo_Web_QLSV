<?php

use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\ClassController;
use App\Http\Controllers\Admin\CourseSectionManagementController;
use App\Http\Controllers\Admin\FacultyController;
use App\Http\Controllers\Admin\GradeController as AdminGradeController;
use App\Http\Controllers\Admin\LecturerController as AdminLecturerController;
use App\Http\Controllers\Admin\SemesterController;
use App\Http\Controllers\Admin\SpecializationController;
use App\Http\Controllers\Admin\StudentController as AdminStudentController;
use App\Http\Controllers\Admin\SubjectController as AdminSubjectController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CurriculumController;
use App\Http\Controllers\Lecturer\AttendanceController as LecturerAttendanceController;
use App\Http\Controllers\Lecturer\GradeController as LecturerGradeController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\StudentGradeController;
use App\Http\Controllers\StudentProfileController;
use App\Http\Controllers\StudentSubjectController;
use App\Http\Controllers\StudentTuitionController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:10,15')
    ->name('login.store');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', function () {
        $role = strtolower(auth()->user()->role);
        if ($role === 'lecturer') {
            return redirect()->route('lecturer.schedule.index');
        }
        return redirect()->route($role . '.dashboard');
    });

    // Cập nhật trạng thái buổi học (Dành cho Giảng viên & Admin)
    Route::post('/schedules/{schedule}/update-status', [ScheduleController::class, 'updateStatus'])
        ->middleware('checkRole:admin,lecturer')
        ->name('schedules.updateStatus');

    // ========================================================
    // PHÂN HỆ QUẢN TRỊ VIÊN (ADMIN)
    // ========================================================
    Route::prefix('admin')->name('admin.')->middleware('checkRole:admin')->group(function () {
        Route::get('/dashboard', fn () => view('admin.dashboard'))->name('dashboard');

        // 0. Quản lý Khoa phụ trách (Khoa -> Chuyên ngành -> Môn học)
        Route::resource('faculties', FacultyController::class);

        // 1. Quản lý Chuyên ngành (Thêm, Xóa, Sửa + Xem khung chương trình theo kỳ)
        Route::resource('specializations', SpecializationController::class);
        Route::get('/specializations/{specialization}/curriculum', [SpecializationController::class, 'curriculum'])
            ->name('specializations.curriculum');
        Route::post('/specializations/{specialization}/curriculum/add', [SpecializationController::class, 'addCurriculumItem'])
            ->name('specializations.curriculum.add');
        Route::put('/specializations/curriculum/{item}', [SpecializationController::class, 'updateCurriculumItem'])
            ->name('specializations.curriculum.update');
        Route::delete('/specializations/curriculum/{item}', [SpecializationController::class, 'removeCurriculumItem'])
            ->name('specializations.curriculum.remove');

        // 2. Quản lý Môn học (Theo chuyên ngành & chương trình khung từng kỳ)
        Route::put('/subjects', [AdminSubjectController::class, 'update'])->name('subjects.update_direct');
        Route::resource('subjects', AdminSubjectController::class);

        // 3. Quản lý Giảng viên (Hồ sơ, Khoa & Chuyên ngành)
        Route::resource('lecturers', AdminLecturerController::class);

        // 4. Quản lý Sinh viên (Mã tự động, xem chi tiết, reset mật khẩu, auto xếp kỳ 1)
        Route::resource('students', AdminStudentController::class);
        Route::post('/students/{student}/reset-password', [AdminStudentController::class, 'resetPassword'])
            ->name('students.resetPassword');

        // 4. Quản lý Lớp học danh nghĩa (Theo khóa tuyển sinh, phân công GV phụ trách, quản lý SV trong lớp)
        Route::post('/classes/cohorts', [ClassController::class, 'storeCohort'])->name('classes.cohorts.store');
        Route::post('/classes/{class}/assign-lecturer', [ClassController::class, 'assignLecturer'])->name('classes.assignLecturer');
        Route::post('/classes/{class}/students/add', [ClassController::class, 'addStudent'])->name('classes.students.add');
        Route::delete('/classes/{class}/students/{student}', [ClassController::class, 'removeStudent'])->name('classes.students.remove');
        Route::put('/classes/{class}/students/{student}', [ClassController::class, 'updateStudent'])->name('classes.students.update');
        Route::resource('classes', ClassController::class)->except('show');

        // 5. Quản lý Điểm (Tra cứu theo MSSV/Tên, hiển thị chuyên ngành & khung từng kỳ, điểm QT/GK/CK/Tổng/GPA/ABCDF/Đạt-Rớt, tùy chỉnh điểm)
        Route::get('/grades', [AdminGradeController::class, 'index'])->name('grades.index');
        Route::post('/grades/update-score', [AdminGradeController::class, 'updateScore'])->name('grades.updateScore');

        // 6. Điểm danh và Báo cáo (Lấy dữ liệu từ giảng viên, lọc theo chuyên ngành, môn học, lớp HP, ngày tháng)
        Route::get('/attendances', [AdminAttendanceController::class, 'index'])->name('attendances.index');

        // 7. Quản lý Học kỳ & Đợt Đăng ký học phần
        Route::get('/semesters', [SemesterController::class, 'index'])->name('semesters.index');
        Route::post('/semesters', [SemesterController::class, 'store'])->name('semesters.store');
        Route::put('/semesters/{semester}', [SemesterController::class, 'update'])->name('semesters.update');
        Route::post('/semesters/{semester}/toggle', [SemesterController::class, 'toggleRegistration'])->name('semesters.toggle');

        // 8. Quản lý Mở Lớp Học Phần (Lọc theo kỳ & chuyên ngành, gán random giảng viên theo chuyên ngành)
        Route::get('/course-sections', [CourseSectionManagementController::class, 'index'])->name('course-sections.index');
        Route::post('/course-sections', [CourseSectionManagementController::class, 'store'])->name('course-sections.store');
        Route::put('/course-sections/{section}', [CourseSectionManagementController::class, 'update'])->name('course-sections.update');
        Route::delete('/course-sections/{section}', [CourseSectionManagementController::class, 'destroy'])->name('course-sections.destroy');
        Route::post('/course-sections/open-by-curriculum', [CourseSectionManagementController::class, 'openByCurriculum'])->name('course-sections.open-by-curriculum');

        // Note: Đã bỏ admin.schedule và admin.scholarships theo yêu cầu của user
    });

    // ========================================================
    // PHÂN HỆ GIẢNG VIÊN (LECTURER)
    // ========================================================
    Route::prefix('lecturer')->name('lecturer.')->middleware('checkRole:lecturer')->group(function () {
        Route::get('/dashboard', fn () => redirect()->route('lecturer.schedule.index'))->name('dashboard');

        // 1. Lịch giảng dạy tuần (TKB)
        Route::get('/schedule', [ScheduleController::class, 'index'])->name('schedule.index');

        // 2. Điểm danh lớp học phần (từ menu điểm danh hoặc từ click trên TKB)
        Route::get('/attendances', [LecturerAttendanceController::class, 'index'])->name('attendances.index');
        Route::get('/attendances/{section}/take', [LecturerAttendanceController::class, 'take'])->name('attendances.take');
        Route::post('/attendances/{section}', [LecturerAttendanceController::class, 'store'])->name('attendances.store');

        // 3. Nhập bảng điểm học phần (dữ liệu đồng bộ sang Quản trị viên)
        Route::get('/grades', [LecturerGradeController::class, 'index'])->name('grades.index');
        Route::get('/grades/{section}', [LecturerGradeController::class, 'edit'])->name('grades.edit');
        Route::put('/grades/{section}', [LecturerGradeController::class, 'update'])->name('grades.update');
    });

    // ========================================================
    // PHÂN HỆ SINH VIÊN (STUDENT)
    // ========================================================
    Route::prefix('student')->name('student.')->middleware('checkRole:student')->group(function () {
        Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');

        // 1. Thông tin chung
        Route::get('/profile', [StudentProfileController::class, 'show'])->name('profile');
        Route::post('/profile/contact', [StudentProfileController::class, 'updateContact'])->name('profile.updateContact');

        // 2. Học tập
        Route::get('/schedule', [ScheduleController::class, 'index'])->name('schedule.index');
        Route::get('/grades', [StudentGradeController::class, 'index'])->name('grades.index');

        // 3. Đăng ký học phần (Kỳ 1 auto do admin, các kỳ sau sinh viên tự đăng ký)
        Route::get('/curriculum', [CurriculumController::class, 'index'])->name('curriculum.index');
        Route::get('/subjects', [StudentSubjectController::class, 'index'])->name('subjects.index');
        Route::post('/subjects/{subject?}', [StudentSubjectController::class, 'register'])->name('subjects.register');
        Route::delete('/subjects/{subject}', [StudentSubjectController::class, 'unregister'])->name('subjects.unregister');

        // 4. Học phí & VietQR
        Route::get('/tuition', [StudentTuitionController::class, 'index'])->name('tuition.index');
        Route::post('/tuition/{invoice}/simulate-payment', [StudentTuitionController::class, 'simulatePayment'])->name('tuition.simulate');

        // Note: Đã bỏ student.scholarships theo yêu cầu
    });
});
