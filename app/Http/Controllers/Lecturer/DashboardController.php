<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\GradeAppeal;
use App\Models\ScheduleChangeRequest;
use App\Models\SectionAttendance;
use App\Models\Semester;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display Lecturer Dashboard with comprehensive KPIs, today's schedule,
     * academic advisor summaries, and course sections progress.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $lecturer = $user->lecturer;

        // 1. Học kỳ hiện tại
        $currentSemester = Semester::where('is_registration_open', true)->first()
            ?? Semester::where('semester_code', 'HK1_2026_2027')->first()
            ?? Semester::orderBy('id')->first();
        $currentSemesterCode = $currentSemester?->semester_code ?? 'HK1_2026_2027';

        // 2. Các lớp học phần giảng viên đang phụ trách trong kỳ này
        $sections = $lecturer
            ? CourseSection::with(['course', 'enrollments.student'])
                ->withCount('enrollments')
                ->where('lecturer_id', $lecturer->id)
                ->where('semester_code', $currentSemesterCode)
                ->get()
            : collect();

        // 3. KPI: Tổng số sinh viên giảng viên đang dạy
        $totalStudentsCount = $sections->sum('enrollments_count');

        // 4. KPI: Tỷ lệ chuyên cần bình quân của các lớp học phần
        $allSectionIds = $sections->pluck('id');
        $totalAttendances = SectionAttendance::whereIn('section_id', $allSectionIds)->count();
        $presentAttendances = SectionAttendance::whereIn('section_id', $allSectionIds)->where('status', 'present')->count();
        $avgAttendanceRate = $totalAttendances > 0 ? round(($presentAttendances / $totalAttendances) * 100, 1) : 100.0;

        // 5. KPI: Tiến độ nộp bảng điểm
        $sectionsWithGrades = $sections->filter(fn($sec) => $sec->isGradeLocked())->count();

        // 6. Lớp Cố vấn học tập (CVHT / Chủ nhiệm)
        $homeroomClasses = $lecturer
            ? $lecturer->homeroomClasses()
                ->with(['specialization.faculty'])
                ->get()
            : collect();
        $totalHomeroomStudents = $homeroomClasses->sum(fn($c) => $c->students_count);

        // 7. Lịch giảng dạy hôm nay
        $today = now();
        $todaySchedules = $lecturer
            ? ClassSchedule::where('lecturer_id', $lecturer->id)
                ->whereDate('schedule_date', $today->toDateString())
                ->orderBy('start_period')
                ->get()
            : collect();

        // 8. Lịch giảng dạy tuần này
        $startOfWeek = $today->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $endOfWeek = $today->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();
        $thisWeekSchedules = $lecturer
            ? ClassSchedule::where('lecturer_id', $lecturer->id)
                ->forDateRange($startOfWeek, $endOfWeek)
                ->orderBy('schedule_date')
                ->orderBy('start_period')
                ->get()
            : collect();

        // 9. Số lượng khiếu nại / phúc khảo điểm chờ xử lý
        $pendingAppealsCount = GradeAppeal::whereIn('section_id', $allSectionIds)
            ->where('status', 'PENDING')
            ->count();

        // 10. Số lượng yêu cầu đổi lịch chờ duyệt
        $pendingScheduleRequestsCount = $lecturer
            ? ScheduleChangeRequest::where('lecturer_id', $lecturer->id)->where('status', 'PENDING')->count()
            : 0;

        return view('lecturer.dashboard', compact(
            'user',
            'lecturer',
            'currentSemester',
            'currentSemesterCode',
            'sections',
            'totalStudentsCount',
            'avgAttendanceRate',
            'sectionsWithGrades',
            'homeroomClasses',
            'totalHomeroomStudents',
            'todaySchedules',
            'thisWeekSchedules',
            'pendingAppealsCount',
            'pendingScheduleRequestsCount'
        ));
    }
}
