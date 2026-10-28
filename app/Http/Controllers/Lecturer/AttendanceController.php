<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;
use App\Models\CourseSection;
use App\Models\SectionAttendance;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    /**
     * Danh sách các lớp học phần và lịch dạy trong tuần của giảng viên
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $lecturer = $user->lecturer;

        // Các lớp học phần giảng viên phụ trách
        $sections = CourseSection::with(['course', 'enrollments.student'])
            ->withCount('enrollments')
            ->when($lecturer, fn ($q) => $q->where('lecturer_id', $lecturer->id))
            ->orderBy('semester_code', 'desc')
            ->orderBy('section_code')
            ->get();

        // Lịch dạy tuần hiện tại của giảng viên
        $startOfWeek = now()->startOfWeek(Carbon::MONDAY)->toDateString();
        $endOfWeek = now()->endOfWeek(Carbon::SUNDAY)->toDateString();

        $weekSchedules = ClassSchedule::with(['section.course'])
            ->forDateRange($startOfWeek, $endOfWeek)
            ->when($lecturer, fn ($q) => $q->where('lecturer_id', $lecturer->id))
            ->orderBy('schedule_date')
            ->orderBy('start_period')
            ->get();

        return view('lecturer.attendances.index', compact('sections', 'weekSchedules', 'lecturer'));
    }

    /**
     * Giao diện điểm danh cho một lớp học phần vào một ngày cụ thể
     */
    public function take(CourseSection $section, Request $request): View
    {
        $dateInput = $request->query('date', now()->toDateString());
        try {
            $attendanceDate = Carbon::parse($dateInput)->toDateString();
        } catch (\Exception $e) {
            $attendanceDate = now()->toDateString();
        }

        $section->load(['course', 'lecturer', 'enrollments.student.user']);

        // Lấy danh sách sinh viên ghi danh
        $enrollments = $section->enrollments;

        // Lấy dữ liệu điểm danh đã lưu của ngày này (nếu có)
        $existingAttendances = SectionAttendance::where('section_id', $section->id)
            ->whereDate('attendance_date', $attendanceDate)
            ->get()
            ->keyBy('student_id');

        return view('lecturer.attendances.take', compact('section', 'enrollments', 'attendanceDate', 'existingAttendances'));
    }

    /**
     * Lưu kết quả điểm danh của giảng viên
     */
    public function store(Request $request, CourseSection $section): RedirectResponse
    {
        $validated = $request->validate([
            'attendance_date' => ['required', 'date'],
            'statuses' => ['nullable', 'array'],
            'notes' => ['nullable', 'array'],
        ]);

        $attendanceDate = Carbon::parse($validated['attendance_date'])->toDateString();
        $statuses = $validated['statuses'] ?? [];
        $notes = $validated['notes'] ?? [];

        $enrollments = $section->enrollments;

        foreach ($enrollments as $enrollment) {
            $studentId = $enrollment->student_id;
            // Nếu có toggle ON -> present, ngược lại -> absent
            $status = isset($statuses[$studentId]) && ($statuses[$studentId] === 'present' || $statuses[$studentId] == '1')
                ? 'present'
                : 'absent';
            $note = $notes[$studentId] ?? null;

            // Sử dụng whereDate để so sánh ngày chính xác tuyệt đối trong SQLite (tránh lỗi UNIQUE constraint)
            $existing = SectionAttendance::where('section_id', $section->id)
                ->where('student_id', $studentId)
                ->whereDate('attendance_date', $attendanceDate)
                ->first();

            if ($existing) {
                $existing->update([
                    'status' => $status,
                    'note' => $note,
                    'attendance_date' => $attendanceDate,
                ]);
            } else {
                SectionAttendance::create([
                    'section_id' => $section->id,
                    'student_id' => $studentId,
                    'attendance_date' => $attendanceDate,
                    'status' => $status,
                    'note' => $note,
                ]);
            }
        }

        return redirect()->route('lecturer.attendances.take', [
            'section' => $section->id,
            'date' => $attendanceDate,
        ])->with('success', "Đã lưu điểm danh ngày [{$attendanceDate}] cho lớp học phần {$section->section_code} thành công!");
    }
}
