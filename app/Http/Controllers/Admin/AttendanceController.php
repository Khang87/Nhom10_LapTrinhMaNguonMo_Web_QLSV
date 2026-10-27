<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\SectionAttendance;
use App\Models\Semester;
use App\Models\Specialization;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    /**
     * Báo cáo Điểm danh Sinh viên
     * Hỗ trợ thống kê chuyên cần theo Học kỳ, Chuyên ngành, Lớp học phần
     * Cảnh báo sinh viên có nguy cơ cấm thi do vắng quá 20% số buổi
     */
    public function index(Request $request): View
    {
        // 1. Danh sách Học kỳ
        $semesters = Semester::orderByDesc('order_index')->get();
        if ($semesters->isEmpty()) {
            $semesters = CourseSection::select('semester_code')
                ->distinct()
                ->orderByDesc('semester_code')
                ->get()
                ->map(fn ($s) => (object) [
                    'semester_code' => $s->semester_code,
                    'semester_name' => $s->semester_code,
                ]);
        }

        // Học kỳ đang chọn (mặc định lấy HK1_2026_2027 hoặc học kỳ đầu tiên)
        $selectedSemester = $request->query('semester');
        if (!$selectedSemester) {
            $hasDefault = $semesters->firstWhere('semester_code', 'HK1_2026_2027');
            $selectedSemester = $hasDefault ? 'HK1_2026_2027' : ($semesters->first()?->semester_code ?? 'HK1_2026_2027');
        }

        // 2. Danh sách Chuyên ngành & Học phần
        $specializations = Specialization::orderBy('name')->get();
        $selectedSpecId = $request->query('specialization_id');
        $selectedSpec = $selectedSpecId ? $specializations->firstWhere('id', $selectedSpecId) : null;
        $keyword = trim((string) $request->query('keyword'));
        $selectedSectionId = $request->query('section_id');

        // 3. Truy vấn danh sách các lớp học phần trong học kỳ
        $sectionsQuery = CourseSection::with(['course', 'lecturer'])
            ->withCount('enrollments')
            ->where('semester_code', $selectedSemester);

        if ($selectedSpec) {
            $sectionsQuery->where(function ($q) use ($selectedSpec) {
                $q->where('specialization_name', $selectedSpec->name)
                  ->orWhereNull('specialization_name');
            });
        }

        if ($keyword !== '') {
            $sectionsQuery->where(function ($q) use ($keyword) {
                $q->where('section_code', 'like', "%{$keyword}%")
                  ->orWhereHas('course', function ($cq) use ($keyword) {
                      $cq->where('course_name', 'like', "%{$keyword}%")
                         ->orWhere('course_code', 'like', "%{$keyword}%");
                  })
                  ->orWhereHas('lecturer', function ($lq) use ($keyword) {
                      $lq->where('full_name', 'like', "%{$keyword}%");
                  });
            });
        }

        $sections = $sectionsQuery->orderBy('section_code')->get();

        // 4. Thống kê số buổi và tỷ lệ chuyên cần cho từng lớp học phần
        $sectionIds = $sections->pluck('id');
        $attendancesGrouped = SectionAttendance::whereIn('section_id', $sectionIds)
            ->selectRaw('section_id, count(distinct attendance_date) as session_count, count(*) as total_records, sum(case when status = "present" then 1 else 0 end) as present_records')
            ->groupBy('section_id')
            ->get()
            ->keyBy('section_id');

        foreach ($sections as $sec) {
            $stat = $attendancesGrouped->get($sec->id);
            $sec->session_count = $stat ? (int) $stat->session_count : 0;
            $sec->total_attendance_records = $stat ? (int) $stat->total_records : 0;
            $sec->present_attendance_records = $stat ? (int) $stat->present_records : 0;
            $sec->attendance_rate = $sec->total_attendance_records > 0
                ? round(($sec->present_attendance_records / $sec->total_attendance_records) * 100, 1)
                : null;
        }

        // 5. Thống kê KPI tổng quan toàn trường trong kỳ
        $totalSectionsCount = $sections->count();
        $recordedSectionsCount = $sections->where('session_count', '>', 0)->count();
        $totalPresentRecords = $sections->sum('present_attendance_records');
        $totalAttendanceRecords = $sections->sum('total_attendance_records');
        $overallAttendanceRate = $totalAttendanceRecords > 0
            ? round(($totalPresentRecords / $totalAttendanceRecords) * 100, 1)
            : 0.0;

        // Tính sinh viên vắng nhiều toàn kỳ (vắng >= 20% các buổi đã điểm danh)
        $warningStudentsTotal = SectionAttendance::whereIn('section_id', $sectionIds)
            ->selectRaw('student_id, count(distinct attendance_date) as total_sessions, sum(case when status = "absent" then 1 else 0 end) as absent_count')
            ->groupBy('student_id')
            ->havingRaw('total_sessions >= 2 and (absent_count / total_sessions) >= 0.20')
            ->get()
            ->count();

        // 6. Xử lý khi xem chi tiết một Lớp học phần
        $activeSection = null;
        $attendanceDate = null;
        $recordedDates = collect();
        $attendanceRecords = collect();
        $totalStudents = 0;
        $presentCount = 0;
        $absentCount = 0;
        $attendanceRate = 0.0;
        $warningStudents = collect();

        if ($selectedSectionId) {
            $activeSection = CourseSection::with(['course', 'lecturer', 'enrollments.student.specialization'])
                ->find($selectedSectionId);
        }

        if ($activeSection) {
            // Danh sách các ngày giảng viên đã thực hiện điểm danh
            $recordedDates = SectionAttendance::where('section_id', $activeSection->id)
                ->select('attendance_date')
                ->distinct()
                ->orderByDesc('attendance_date')
                ->pluck('attendance_date')
                ->map(fn ($d) => Carbon::parse($d)->toDateString());

            // Xác định ngày điểm danh đang xem
            $dateInput = $request->query('date');
            if ($dateInput) {
                try {
                    $attendanceDate = Carbon::parse($dateInput)->toDateString();
                } catch (\Exception $e) {
                    $attendanceDate = now()->toDateString();
                }
            } elseif ($recordedDates->isNotEmpty()) {
                $attendanceDate = $recordedDates->first();
            } else {
                $attendanceDate = now()->toDateString();
            }

            // Danh sách sinh viên ghi danh
            $enrollments = $activeSection->enrollments;
            $totalStudents = $enrollments->count();

            // Lấy dữ liệu điểm danh ngày được chọn
            $savedAttendances = SectionAttendance::where('section_id', $activeSection->id)
                ->whereDate('attendance_date', $attendanceDate)
                ->get()
                ->keyBy('student_id');

            // Thống kê lịch sử tất cả các buổi của lớp này để tính tỷ lệ vắng tích lũy của từng sinh viên
            $studentAttendanceSummary = SectionAttendance::where('section_id', $activeSection->id)
                ->selectRaw('student_id, count(distinct attendance_date) as attended_sessions, sum(case when status = "absent" then 1 else 0 end) as absent_sessions')
                ->groupBy('student_id')
                ->get()
                ->keyBy('student_id');

            $totalClassSessions = $recordedDates->count();

            // Kết hợp thông tin sinh viên và trạng thái
            $attendanceRecords = $enrollments->map(function ($enrollment) use ($savedAttendances, $studentAttendanceSummary, $totalClassSessions) {
                $student = $enrollment->student;
                $record = $savedAttendances->get($student?->id);
                $summary = $studentAttendanceSummary->get($student?->id);

                $absentSessions = $summary ? (int) $summary->absent_sessions : 0;
                $absentRate = $totalClassSessions > 0 ? round(($absentSessions / $totalClassSessions) * 100, 1) : 0;
                $isWarning = $totalClassSessions >= 2 && $absentRate >= 20.0;

                return [
                    'student' => $student,
                    'status' => $record ? $record->status : 'absent',
                    'is_recorded' => $record !== null,
                    'note' => $record?->note,
                    'updated_at' => $record?->updated_at,
                    'absent_sessions' => $absentSessions,
                    'total_sessions' => $totalClassSessions,
                    'absent_rate' => $absentRate,
                    'is_warning' => $isWarning,
                ];
            });

            // Lọc ra các sinh viên có nguy cơ cấm thi trong lớp này
            $warningStudents = $attendanceRecords->filter(fn ($r) => $r['is_warning']);

            $presentCount = $attendanceRecords->where('status', 'present')->where('is_recorded', true)->count();
            $absentCount = $attendanceRecords->where('status', 'absent')->where('is_recorded', true)->count();
            $attendanceRate = ($presentCount + $absentCount) > 0
                ? round(($presentCount / ($presentCount + $absentCount)) * 100, 1)
                : 0.0;
        }

        return view('admin.attendances.index', compact(
            'semesters',
            'selectedSemester',
            'specializations',
            'selectedSpecId',
            'selectedSpec',
            'keyword',
            'sections',
            'totalSectionsCount',
            'recordedSectionsCount',
            'overallAttendanceRate',
            'warningStudentsTotal',
            'activeSection',
            'selectedSectionId',
            'attendanceDate',
            'recordedDates',
            'attendanceRecords',
            'totalStudents',
            'presentCount',
            'absentCount',
            'attendanceRate',
            'warningStudents'
        ));
    }
}
