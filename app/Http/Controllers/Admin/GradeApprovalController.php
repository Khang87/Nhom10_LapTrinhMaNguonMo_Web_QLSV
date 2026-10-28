<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseSection;
use App\Models\GradeAppeal;
use App\Models\Semester;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GradeApprovalController extends Controller
{
    /**
     * Danh sách phê duyệt bảng điểm các lớp học phần
     */
    public function index(Request $request): View
    {
        $status = $request->input('status', 'all'); // all, SUBMITTED, APPROVED, DRAFT
        $semesterCode = $request->input('semester', 'HK1_2026_2027');

        $semesters = Semester::orderBy('order_index')->get();

        $query = CourseSection::with(['course', 'lecturer'])
            ->withCount('enrollments')
            ->where('semester_code', $semesterCode);

        if ($status !== 'all') {
            $query->where('grade_status', $status);
        }

        $sections = $query->orderBy('grade_status', 'desc')
            ->orderBy('section_code')
            ->get();

        // Thống kê nhanh
        $submittedCount = CourseSection::where('semester_code', $semesterCode)->where('grade_status', 'SUBMITTED')->count();
        $approvedCount = CourseSection::where('semester_code', $semesterCode)->where('grade_status', 'APPROVED')->count();
        $draftCount = CourseSection::where('semester_code', $semesterCode)->where('grade_status', 'DRAFT')->count();

        return view('admin.grades.approvals', compact(
            'sections',
            'semesters',
            'semesterCode',
            'status',
            'submittedCount',
            'approvedCount',
            'draftCount'
        ));
    }

    /**
     * Xem chi tiết bảng điểm lớp học phần để kiểm tra và phê duyệt
     */
    public function show(CourseSection $section): View
    {
        $section->load(['course', 'lecturer', 'enrollments.student.user']);

        $enrollments = $section->enrollments;
        $gradeDistribution = [
            'A' => $enrollments->where('letter_grade', 'A')->count(),
            'B' => $enrollments->whereIn('letter_grade', ['B+', 'B'])->count(),
            'C' => $enrollments->whereIn('letter_grade', ['C+', 'C'])->count(),
            'D' => $enrollments->whereIn('letter_grade', ['D+', 'D'])->count(),
            'F' => $enrollments->where('letter_grade', 'F')->count(),
        ];
        $totalGraded = $enrollments->whereNotNull('total_score_10')->count();
        $totalPassed = $enrollments->where('total_score_10', '>=', 4.0)->count();
        $passRate = $totalGraded > 0 ? round(($totalPassed / $totalGraded) * 100, 1) : 0;
        $averageScore = $totalGraded > 0 ? round($enrollments->whereNotNull('total_score_10')->avg('total_score_10'), 2) : 0;

        return view('admin.grades.show', compact(
            'section',
            'gradeDistribution',
            'totalGraded',
            'totalPassed',
            'passRate',
            'averageScore'
        ));
    }

    /**
     * Phê duyệt chính thức bảng điểm lớp học phần
     */
    public function approve(CourseSection $section, Request $request): RedirectResponse
    {
        $section->update([
            'grade_status' => 'APPROVED',
            'grade_approved_at' => now(),
            'grade_note' => $request->input('admin_note', 'Phòng Đào tạo đã phê duyệt bảng điểm chính thức.'),
        ]);

        return back()->with('success', "Đã phê duyệt chính thức bảng điểm lớp học phần [{$section->section_code} - {$section->course?->course_name}]. Sinh viên hiện có thể tra cứu điểm chính thức.");
    }

    /**
     * Mở khóa bảng điểm để giảng viên chỉnh sửa lại
     */
    public function unlock(CourseSection $section, Request $request): RedirectResponse
    {
        $reason = $request->input('reason', 'Phòng Đào tạo mở khóa cho giảng viên chỉnh sửa lại điểm.');

        $section->update([
            'grade_status' => 'DRAFT',
            'grade_note' => $reason,
        ]);

        return back()->with('success', "Đã mở khóa bảng điểm lớp học phần [{$section->section_code}]. Giảng viên phụ trách hiện có thể cập nhật lại điểm.");
    }

    /**
     * Danh sách yêu cầu phúc khảo / thắc mắc điểm toàn trường
     */
    public function appealsIndex(Request $request): View
    {
        $status = $request->input('status', 'all');

        $query = GradeAppeal::with(['student', 'section.course', 'section.lecturer', 'enrollment']);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $appeals = $query->orderBy('created_at', 'desc')->get();

        return view('admin.grades.appeals', compact('appeals', 'status'));
    }

    /**
     * Phê duyệt kết quả phúc khảo và cập nhật điểm vào bảng enrollments
     */
    public function approveAppeal(GradeAppeal $appeal, Request $request): RedirectResponse
    {
        $enr = $appeal->enrollment;
        if ($appeal->new_score !== null && $enr) {
            match ($appeal->component) {
                'PROCESS' => $enr->process_score = $appeal->new_score,
                'MIDTERM' => $enr->midterm_score = $appeal->new_score,
                'FINAL' => $enr->final_score = $appeal->new_score,
                default => null,
            };

            $enr->calculateGrade();
            $enr->save();
        }

        $appeal->update([
            'status' => 'RESOLVED',
            'admin_approved' => true,
        ]);

        return back()->with('success', "Đã phê duyệt kết quả phúc khảo cho sinh viên [{$appeal->student?->student_code} - {$appeal->student?->full_name}]. Điểm số đã được cập nhật chính thức vào hệ thống.");
    }
}
