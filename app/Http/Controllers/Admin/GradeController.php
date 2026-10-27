<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseOffering;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Semester;
use App\Models\User;
use App\Support\Csv;
use App\Support\GradeBook;
use App\Support\GradingScale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * SCREEN ĐIỂM CỦA QUẢN TRỊ VIÊN: sửa hộ khi giảng viên nhập nhầm, lọc theo kỳ/khoa và xem
 * trung bình tích lũy của vùng đang lọc. Admin đi học mọi lớp nên không có ràng buộc giáo viên.
 */
class GradeController extends Controller
{
    public function index(Request $request): View
    {
        $grades = $this->filtered($request)->paginate(10)->withQueryString();

        return view('admin.grades.index', [
            'grades' => $grades,
            'summary' => GradeBook::summary($this->filtered($request)->get()),
            'students' => User::where('role', 'student')->orderBy('name')->get(),
            'offerings' => CourseOffering::with(['subject', 'semester'])
                ->orderBy('group_code')
                ->get(),
            'semesters' => Semester::orderByDesc('academic_year')->orderByDesc('term')->get(),
            'departments' => Department::orderBy('name')->get(),
            'components' => GradingScale::components(),
        ]);
    }

    public function export(Request $request): Response
    {
        // Điểm viết ra CSV dùng dấu phẩy thập phân kiểu Việt Nam để Excel mở là hiểu ngay.
        $score = fn (float|string|null $value): string => $value === null ? '' : number_format((float) $value, 2, ',', '');

        $rows = $this->filtered($request)->get()->map(function (Grade $grade) use ($score) {
            $total = $grade->subjectScore();

            return array_merge(
                [
                    $grade->user->name,
                    $grade->user->person_code,
                    $grade->subject?->code,
                    $grade->subject?->name,
                    $grade->offering->group_code,
                    $grade->offering->semester?->slug,
                    $grade->subject?->credits,
                ],
                array_map(fn (array $component): string => $score($grade->{$component['key']}), GradingScale::components()),
                [
                    $score($total),
                    $total === null ? '' : $grade->letter(),
                    $total === null ? '' : $score($grade->fourScore()),
                    $grade->is_published ? 'Đã công bố' : 'Chưa công bố',
                    $grade->updated_at?->format('d/m/Y H:i'),
                ],
            );
        });

        $headers = ['Sinh viên', 'Mã SV', 'Mã môn', 'Môn học', 'Nhóm', 'Học kỳ', 'Tín chỉ'];
        foreach (GradingScale::components() as $component) {
            $headers[] = $component['label'].' ('.$component['weight'].'%)';
        }

        return Csv::download(
            'diem-'.today()->toDateString().'.csv',
            [...$headers, 'Tổng kết', 'Chữ', 'Hệ 4', 'Trạng thái', 'Cập nhật lúc'],
            $rows
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'course_offering_id' => ['required', 'exists:course_offerings,id'],
            'attendance_score' => ['nullable', 'numeric', 'between:0,10'],
            'midterm_score' => ['nullable', 'numeric', 'between:0,10'],
            'final_score' => ['nullable', 'numeric', 'between:0,10'],
        ]);

        $student = User::where('role', 'student')->findOrFail($data['user_id']);
        $enrollment = Enrollment::where('user_id', $student->id)
            ->where('course_offering_id', $data['course_offering_id'])
            ->where('status', 'ACTIVE')
            ->first();

        if ($enrollment === null) {
            throw ValidationException::withMessages([
                'course_offering_id' => 'Sinh viên chưa đăng ký học phần này.',
            ]);
        }

        $scores = [
            'attendance_score' => $data['attendance_score'] ?? null,
            'midterm_score' => $data['midterm_score'] ?? null,
            'final_score' => $data['final_score'] ?? null,
        ];

        if (collect($scores)->every(fn (?float $score) => $score === null)) {
            throw ValidationException::withMessages([
                'final_score' => 'Cần nhập ít nhất một cột điểm.',
            ]);
        }

        Grade::updateOrCreate(
            ['user_id' => $student->id, 'course_offering_id' => $data['course_offering_id']],
            $scores + [
                'enrollment_id' => $enrollment->id,
                // Admin là nguồn dữ liệu thay thế giảng viên nên điểm của admin hiện ngay cho sinh viên.
                'is_published' => true,
                'updated_by' => $request->user()->id,
            ],
        );

        return redirect()->route('admin.grades.index')->with('success', 'Đã cập nhật điểm.');
    }

    public function destroy(Grade $grade): RedirectResponse
    {
        $grade->delete();

        return redirect()->route('admin.grades.index')->with('success', 'Đã xóa điểm.');
    }

    /**
     * Bảng, bản xuất và khối trung bình dùng chung bộ lọc để không lệch số liệu.
     */
    private function filtered(Request $request): Builder
    {
        return Grade::with(['user', 'offering.subject', 'offering.semester'])
            ->when($request->filled('semester_id'),
                fn (Builder $query) => $query->whereHas('offering',
                    fn (Builder $offering) => $offering->where('semester_id', $request->integer('semester_id'))))
            ->when($request->filled('department_id'),
                fn (Builder $query) => $query->whereHas('offering.subject.major',
                    fn (Builder $major) => $major->where('department_id', $request->integer('department_id'))))
            ->when($request->filled('user_id'),
                fn (Builder $query) => $query->where('user_id', $request->integer('user_id')))
            ->orderByDesc('updated_at');
    }
}
