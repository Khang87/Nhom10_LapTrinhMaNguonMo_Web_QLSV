<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\CourseSection;
use App\Models\Enrollment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class GradeController extends Controller
{
    /**
     * Danh sách các lớp học phần giảng viên đang giảng dạy và trạng thái nhập điểm
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $lecturer = $user->lecturer;

        $sections = CourseSection::with(['course', 'enrollments'])
            ->withCount('enrollments')
            ->when($lecturer, fn ($q) => $q->where('lecturer_id', $lecturer->id))
            ->orderBy('semester_code', 'desc')
            ->orderBy('section_code')
            ->get();

        return view('lecturer.grades.index', compact('sections', 'lecturer'));
    }

    /**
     * Giao diện nhập bảng điểm học phần cho lớp học phần
     */
    public function edit(CourseSection $section): View
    {
        $user = Auth::user();
        $lecturer = $user->lecturer;

        if ($lecturer && $section->lecturer_id && $section->lecturer_id !== $lecturer->id && !$user->isAdmin()) {
            abort(403, 'Bạn không được phân công giảng dạy lớp học phần này.');
        }

        $section->load(['course', 'lecturer', 'enrollments.student.user']);

        return view('lecturer.grades.edit', compact('section'));
    }

    /**
     * Lưu bảng điểm học phần từ giảng viên
     */
    public function update(CourseSection $section, Request $request): RedirectResponse
    {
        $user = Auth::user();
        $lecturer = $user->lecturer;

        if ($lecturer && $section->lecturer_id && $section->lecturer_id !== $lecturer->id && !$user->isAdmin()) {
            abort(403, 'Bạn không được phân công giảng dạy lớp học phần này.');
        }

        $validated = $request->validate([
            'grades' => ['required', 'array'],
            'grades.*.enrollment_id' => ['required', 'exists:enrollments,id'],
            'grades.*.process_score' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'grades.*.midterm_score' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'grades.*.final_score' => ['nullable', 'numeric', 'min:0', 'max:10'],
        ]);

        $updatedCount = 0;
        foreach ($validated['grades'] as $gradeData) {
            $enrollment = Enrollment::where('id', $gradeData['enrollment_id'])
                ->where('section_id', $section->id)
                ->first();

            if ($enrollment) {
                $enrollment->process_score = $gradeData['process_score'] !== null && $gradeData['process_score'] !== '' 
                    ? (float) $gradeData['process_score'] : null;
                $enrollment->midterm_score = $gradeData['midterm_score'] !== null && $gradeData['midterm_score'] !== '' 
                    ? (float) $gradeData['midterm_score'] : null;
                $enrollment->final_score = $gradeData['final_score'] !== null && $gradeData['final_score'] !== '' 
                    ? (float) $gradeData['final_score'] : null;

                $enrollment->calculateGrade();
                $enrollment->save();
                $updatedCount++;
            }
        }

        return redirect()->route('lecturer.grades.edit', $section)
            ->with('success', "Đã lưu và cập nhật điểm thành công cho {$updatedCount} sinh viên của lớp học phần [{$section->section_code} - {$section->course?->course_name}]. Dữ liệu đã đồng bộ sang Quản trị viên.");
    }
}
