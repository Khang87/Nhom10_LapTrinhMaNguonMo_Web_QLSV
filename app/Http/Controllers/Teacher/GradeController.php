<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\CourseOffering;
use App\Models\Grade;
use App\Models\User;
use App\Support\GradingScale;
use Illuminate\Database\Eloquent\Collection as UserCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Lưới nhập điểm thành phần (10/20/70) cho lớp học phần mà giảng viên được phân công.
 * Điểm tổng kết và chữ chỉ đọc: chúng là hệ quả của ba cột nhập vào, không phải số liệu thứ tư.
 */
class GradeController extends Controller
{
    public function edit(Request $request, CourseOffering $offering): View
    {
        $this->authorizeTeacher($request, $offering);

        $grades = $offering->grades()->get()->keyBy('user_id');
        $rows = $this->students($offering)->map(fn (User $student) => [
            'student' => $student,
            'grade' => $grades->get($student->id),
        ]);
        $scored = $rows->map(fn (array $row) => $row['grade']?->subjectScore())->filter();

        return view('teacher.grades.grid', [
            'offering' => $offering->load(['subject', 'semester']),
            'rows' => $rows,
            'components' => GradingScale::components(),
            'size' => $rows->count(),
            'capacity' => $offering->max_students,
            'classAverage' => $scored->isEmpty() ? null : round($scored->avg(), 2),
            'failingCount' => $rows->filter(fn (array $row) => $row['grade']?->letter() === 'F')->count(),
            'isPublished' => $grades->isNotEmpty() && $grades->every(fn (Grade $grade) => $grade->is_published),
        ]);
    }

    public function update(Request $request, CourseOffering $offering): RedirectResponse
    {
        $this->authorizeTeacher($request, $offering);

        $data = $request->validate([
            'attendance_score' => ['array'],
            'attendance_score.*' => ['nullable', 'numeric', 'between:0,10'],
            'midterm_score' => ['array'],
            'midterm_score.*' => ['nullable', 'numeric', 'between:0,10'],
            'final_score' => ['array'],
            'final_score.*' => ['nullable', 'numeric', 'between:0,10'],
        ]);

        // Chỉ lưu những sinh viên của lớp; id lạ trong payload bị bỏ qua chứ không tạo dòng điểm mới.
        $enrollmentIds = $offering->enrollments()->active()->pluck('id', 'user_id');
        $teacherId = $request->user()->id;

        DB::transaction(function () use ($data, $enrollmentIds, $offering, $teacherId) {
            foreach ($enrollmentIds as $userId => $enrollmentId) {
                Grade::updateOrCreate(
                    ['course_offering_id' => $offering->id, 'user_id' => $userId],
                    [
                        'attendance_score' => $data['attendance_score'][$userId] ?? null,
                        'midterm_score' => $data['midterm_score'][$userId] ?? null,
                        'final_score' => $data['final_score'][$userId] ?? null,
                        'enrollment_id' => $enrollmentId,
                        'updated_by' => $teacherId,
                    ],
                );
            }
        });

        return redirect()
            ->route('teacher.course-offerings.grades', $offering)
            ->with('success', 'Đã lưu điểm của '.$enrollmentIds->count().' sinh viên.');
    }

    /**
     * Công bố cả lớp một lần: điểm chưa đủ cột cuối kỳ thì chưa đáng để sinh viên nhìn thấy.
     */
    public function publish(Request $request, CourseOffering $offering): RedirectResponse
    {
        $this->authorizeTeacher($request, $offering);

        $grades = $offering->grades()->get()->keyBy('user_id');
        $missing = $this->students($offering)
            ->reject(fn (User $student) => $grades->get($student->id)?->isPublishable() === true);

        $redirect = redirect()->route('teacher.course-offerings.grades', $offering);

        if ($missing->isNotEmpty()) {
            return $redirect->with('error', 'Chưa thể công bố: '.$missing->count().' sinh viên còn thiếu điểm cuối kỳ.');
        }

        $offering->grades()->update(['is_published' => true]);

        return $redirect->with('success', 'Đã công bố điểm của lớp '.$offering->label().'.');
    }

    /**
     * Sĩ số hiện hành, sắp theo tên để giảng viên dò danh sách trên lớp.
     *
     * @return UserCollection<int, User>
     */
    private function students(CourseOffering $offering): UserCollection
    {
        return User::query()
            ->whereIn('id', $offering->enrollments()->active()->pluck('user_id'))
            ->orderBy('name')
            ->get();
    }

    private function authorizeTeacher(Request $request, CourseOffering $offering): void
    {
        abort_unless((int) $offering->teacher_id === (int) $request->user()->id, 403);
    }
}
