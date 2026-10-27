<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseOffering;
use App\Models\Enrollment;
use App\Models\Semester;
use App\Models\User;
use App\Support\EnrollmentRegistrar;
use App\Support\ReportFormats;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class EnrollmentController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.enrollments.index', [
            'enrollments' => $this->filtered($request)->paginate(10)->withQueryString(),
            'semesters' => Semester::orderByDesc('academic_year')->orderByDesc('term')->get(),
            'students' => User::where('role', 'student')->orderBy('name')->get(),
            'offerings' => CourseOffering::with(['subject', 'semester'])
                ->orderBy('subject_id')->orderBy('group_code')->get(),
            'semesterId' => $request->integer('semester_id') ?: null,
        ]);
    }

    public function export(Request $request): Response
    {
        return ReportFormats::download(
            $request,
            'dang-ky-hoc-phan-'.today()->toDateString(),
            $this->report($request)
        );
    }

    /**
     * @return array{title: string, header: list<string>, rows: list<list<mixed>>}
     */
    private function report(Request $request): array
    {
        $rows = $this->filtered($request)->get()->map(fn (Enrollment $enrollment) => [
            $enrollment->user->name,
            $enrollment->user->person_code,
            $enrollment->offering->subject?->code,
            $enrollment->offering->subject?->name,
            $enrollment->offering->group_code,
            $enrollment->offering->semester?->slug,
            $enrollment->status,
            $enrollment->registeredByLabel(),
            $enrollment->dropped_at?->format('d/m/Y H:i'),
        ])->all();

        return [
            'title' => 'Danh sách đăng ký học phần',
            'header' => ['Sinh viên', 'Mã SV', 'Mã môn', 'Môn học', 'Nhóm', 'Học kỳ', 'Trạng thái', 'Người thực hiện', 'Ngày hủy'],
            'rows' => $rows,
        ];
    }

    /**
     * Đăng ký hộ: bỏ qua cổng thời gian theo đúng quy tắc nghiệp vụ, nhưng vẫn chạy
     * năm quy tắc còn lại (sĩ số, tiên quyết, song hành, tín chỉ, trùng lớp).
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'course_offering_id' => ['required', 'exists:course_offerings,id'],
        ]);

        $student = User::where('role', 'student')->findOrFail($data['user_id']);
        $offering = CourseOffering::findOrFail($data['course_offering_id']);

        EnrollmentRegistrar::enroll($student, $offering, $request->user(), gateRequired: false);

        return redirect()->route('admin.enrollments.index')
            ->with('success', 'Đã đăng ký hộ '.$student->name.' vào '.$offering->label().'.');
    }

    /**
     * Hủy hộ là quyền override duy nhất khi lớp đã có điểm danh/điểm; dữ liệu cũ vẫn giữ.
     */
    public function drop(Request $request, Enrollment $enrollment): RedirectResponse
    {
        if (! $enrollment->isActive()) {
            return redirect()->route('admin.enrollments.index')
                ->with('error', 'Học phần này không còn ở trạng thái đang học.');
        }

        $enrollment->update(['status' => 'DROPPED', 'dropped_at' => now()]);

        return redirect()->route('admin.enrollments.index')
            ->with('success', 'Đã hủy đăng ký hộ '.$enrollment->user->name.' ở '.$enrollment->offering->label().'.');
    }

    /**
     * Bảng và bản xuất dùng chung một bộ lọc để hai nguồn số liệu không lệch nhau.
     */
    private function filtered(Request $request): Builder
    {
        $semesterId = $request->integer('semester_id') ?: null;

        return Enrollment::with(['user', 'offering.subject', 'offering.semester', 'registeredBy'])
            ->when($semesterId, fn (Builder $query) => $query->whereHas('offering',
                fn (Builder $offering) => $offering->where('semester_id', $semesterId)))
            ->when($request->filled('user_id'),
                fn (Builder $query) => $query->where('user_id', $request->integer('user_id')))
            ->latest('id');
    }
}
