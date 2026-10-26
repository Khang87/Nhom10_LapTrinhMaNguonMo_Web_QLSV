<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseOffering;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\User;
use App\Support\OfferingAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CourseOfferingController extends Controller
{
    public function index(Request $request): View
    {
        $semesterId = $request->integer('semester_id') ?: null;

        $offerings = CourseOffering::query()
            ->with(['subject', 'semester', 'teacher'])
            ->withCount(['enrollments as active_count' => fn ($query) => $query->where('status', 'ACTIVE')])
            ->when($semesterId, fn ($query) => $query->where('semester_id', $semesterId))
            ->orderByDesc('semester_id')
            ->orderBy('subject_id')
            ->orderBy('group_code')
            ->paginate(10)
            ->withQueryString();

        return view('admin.course-offerings.index', [
            'offerings' => $offerings,
            'semesters' => Semester::orderByDesc('academic_year')->orderByDesc('term')->get(),
            'semesterId' => $semesterId,
        ]);
    }

    public function create(): View
    {
        return view('admin.course-offerings.create', ['offering' => new CourseOffering] + $this->options());
    }

    public function store(Request $request): RedirectResponse
    {
        CourseOffering::create($this->validatedData($request));

        return redirect()->route('admin.course-offerings.index')
            ->with('success', 'Đã thêm lớp học phần.');
    }

    public function edit(CourseOffering $course_offering): View
    {
        return view('admin.course-offerings.edit', ['offering' => $course_offering] + $this->options());
    }

    public function update(Request $request, CourseOffering $course_offering): RedirectResponse
    {
        $course_offering->update($this->validatedData($request, $course_offering));

        return redirect()->route('admin.course-offerings.index')
            ->with('success', 'Đã cập nhật lớp học phần.');
    }

    public function destroy(CourseOffering $course_offering): RedirectResponse
    {
        $active = $course_offering->enrollments()->where('status', 'ACTIVE')->count();
        $graded = $course_offering->grades()->count();

        if ($active > 0 || $graded > 0) {
            return redirect()->route('admin.course-offerings.index')->with('error', 'Lớp '
                .$course_offering->label().' chưa xóa được vì đang có '.$active
                .' sinh viên học phần và '.$graded.' cột điểm. Hãy Đóng lớp để giữ lại dữ liệu.');
        }

        $course_offering->delete();

        return redirect()->route('admin.course-offerings.index')
            ->with('success', 'Đã xóa lớp học phần.');
    }

    /**
     * @return array{subjects: Collection, semesters: Collection, teachers: Collection}
     */
    private function options(): array
    {
        return [
            'subjects' => Subject::orderBy('code')->get(),
            'semesters' => Semester::orderByDesc('academic_year')->orderByDesc('term')->get(),
            'teachers' => User::where('role', 'teacher')->orderBy('name')->get(),
        ];
    }

    private function validatedData(Request $request, ?CourseOffering $offering = null): array
    {
        $data = $request->validate([
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'semester_id' => ['required', 'integer', 'exists:semesters,id'],
            'group_code' => ['required', 'string', 'max:20'],
            'teacher_id' => ['nullable', 'integer', 'exists:users,id'],
            'room' => ['nullable', 'string', 'max:50'],
            'max_students' => ['required', 'integer', 'between:0,65535'],
        ], [
            'max_students.between' => 'Sĩ số tối đa từ 0 tới 65535; nhập 0 khi lớp không giới hạn sĩ số.',
            'group_code.required' => 'Nhóm lớp là bắt buộc, ví dụ TN01.',
        ]);

        $data['is_open'] = $request->boolean('is_open');

        if (filled($data['teacher_id'] ?? null)) {
            OfferingAssignment::assertTeacher(User::findOrFail($data['teacher_id']));
        }

        OfferingAssignment::assertGroupFree(($offering ?? new CourseOffering)->fill($data));

        return $data;
    }
}
