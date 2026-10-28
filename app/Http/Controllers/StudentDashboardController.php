<?php

namespace App\Http\Controllers;

use App\Models\ClassSchedule;
use App\Models\CurriculumFramework;
use App\Models\Enrollment;
use App\Models\SectionAttendance;
use App\Models\Semester;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StudentDashboardController extends Controller
{
    /**
     * Display student portal home dashboard with academic overview,
     * today's schedule, enrolled courses, attendance, and tuition summary.
     */
    public function index(): View
    {
        $user = Auth::user();
        $student = $user->student;

        // 1. Học kỳ hiện tại
        $semesters = Semester::orderBy('order_index')->get();
        $currentSemester = $semesters->first(fn($s) => $s->is_registration_open) ?? $semesters->last();
        $currentSemesterCode = $currentSemester?->semester_code ?? 'HK1_2026_2027';

        // 2. Danh sách các học phần sinh viên đang học trong học kỳ này
        $currentEnrollments = $student
            ? $student->enrollments()
                ->with(['section.course', 'section.lecturer'])
                ->whereHas('section', fn($q) => $q->where('semester_code', $currentSemesterCode))
                ->get()
            : collect();

        $enrolledCredits = $currentEnrollments->sum(fn($e) => $e->section?->course?->credits ?? 0);

        // 3. Tiến độ học tập & GPA toàn khóa
        $totalEarnedCredits = 0;
        $totalFrameworkCredits = 0;
        $cumulativeGpa = 0.0;
        $academicRank = 'Chưa xếp loại';

        if ($student) {
            $curriculumItems = collect();
            if ($student->specialization_id) {
                $curriculumItems = CurriculumFramework::with('course')
                    ->where('specialization_id', $student->specialization_id)
                    ->get();
            }
            if ($curriculumItems->isEmpty() && $student->specialization_name) {
                $curriculumItems = CurriculumFramework::with('course')
                    ->where('specialization_name', $student->specialization_name)
                    ->get();
            }

            $totalFrameworkCredits = $curriculumItems->sum(fn($i) => $i->course?->credits ?? 3);

            $allGradedEnrollments = $student->enrollments()
                ->whereNotNull('total_score_4')
                ->with('section.course')
                ->get();

            $totalWeightedPoints4 = 0.0;
            $gradedCredits = 0;
            foreach ($allGradedEnrollments as $enr) {
                $c = $enr->section?->course?->credits ?? 3;
                if ($enr->isPassed()) {
                    $totalEarnedCredits += $c;
                }
                $totalWeightedPoints4 += ($enr->total_score_4 * $c);
                $gradedCredits += $c;
            }

            if ($gradedCredits > 0) {
                $cumulativeGpa = round($totalWeightedPoints4 / $gradedCredits, 2);
            }

            $academicRank = match (true) {
                $cumulativeGpa >= 3.6 => 'Xuất sắc',
                $cumulativeGpa >= 3.2 => 'Giỏi',
                $cumulativeGpa >= 2.5 => 'Khá',
                $cumulativeGpa >= 2.0 => 'Trung bình',
                $totalEarnedCredits > 0 => 'Yếu',
                default => 'Chưa xếp loại',
            };
        }

        $completionRate = $totalFrameworkCredits > 0 ? round(($totalEarnedCredits / $totalFrameworkCredits) * 100, 1) : 0.0;

        // 4. Lịch học hôm nay & tuần này
        $todayDate = now()->toDateString();
        $startOfWeek = now()->startOfWeek(Carbon::MONDAY)->toDateString();
        $endOfWeek = now()->endOfWeek(Carbon::SUNDAY)->toDateString();

        $enrolledSectionIds = $currentEnrollments->pluck('section_id')->filter();
        
        $todaySchedules = ClassSchedule::whereIn('section_id', $enrolledSectionIds)
            ->whereDate('schedule_date', $todayDate)
            ->orderBy('start_period')
            ->get();

        $thisWeekSchedulesCount = ClassSchedule::whereIn('section_id', $enrolledSectionIds)
            ->whereBetween('schedule_date', [$startOfWeek, $endOfWeek])
            ->count();

        // 5. Thống kê Điểm danh
        $attendanceRecords = $student
            ? SectionAttendance::where('student_id', $student->id)
                ->whereIn('section_id', $enrolledSectionIds)
                ->get()
            : collect();

        $presentCount = $attendanceRecords->where('status', 'present')->count();
        $absentCount = $attendanceRecords->where('status', 'absent')->count();
        $totalAttendanceSessions = $attendanceRecords->count();
        $attendanceRate = $totalAttendanceSessions > 0 ? round(($presentCount / $totalAttendanceSessions) * 100, 1) : 100.0;

        // 6. Công nợ học phí kỳ này
        $currentInvoice = $student
            ? $student->tuitionInvoices()->where('semester_code', $currentSemesterCode)->first()
            : null;

        $tuitionStatus = $currentInvoice ? $currentInvoice->status : 'UNPAID';
        $tuitionDebt = $currentInvoice ? $currentInvoice->debt_amount : ($enrolledCredits * 550000);

        return view('student.dashboard', compact(
            'user',
            'student',
            'currentSemester',
            'currentSemesterCode',
            'currentEnrollments',
            'enrolledCredits',
            'totalEarnedCredits',
            'totalFrameworkCredits',
            'cumulativeGpa',
            'completionRate',
            'academicRank',
            'todaySchedules',
            'thisWeekSchedulesCount',
            'presentCount',
            'absentCount',
            'totalAttendanceSessions',
            'attendanceRate',
            'currentInvoice',
            'tuitionStatus',
            'tuitionDebt'
        ));
    }
}
