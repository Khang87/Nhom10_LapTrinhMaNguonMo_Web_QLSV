<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Major;
use App\Models\Semester;
use App\Support\CurriculumOfferings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Màn hình "Mở lớp theo khung": xem trước những môn của khung chương trình chưa có lớp trong
 * một học kỳ, rồi mở tất cả chúng thành lớp học phần chỉ trong một lần gửi.
 */
class CurriculumOfferingController extends Controller
{
    public function index(Request $request): View
    {
        $semesters = Semester::orderByDesc('academic_year')->orderByDesc('term')->get();
        $majors = Major::with('department')->orderBy('name')->get();

        $semester = $semesters->firstWhere('id', (int) $request->query('semester_id'))
            ?: Semester::current();
        $major = $majors->firstWhere('id', (int) $request->query('major_id'));
        $planSemester = (int) ($request->integer('plan_semester') ?: 1);
        $groupCode = $this->groupCode($request);

        $rows = $semester === null || $major === null
            ? collect()
            : CurriculumOfferings::preview($major, $semester, $planSemester, $groupCode);

        return view('admin.curriculum-offerings.index', [
            'semesters' => $semesters,
            'majors' => $majors,
            'semester' => $semester,
            'major' => $major,
            'planSemester' => $planSemester,
            'groupCode' => $groupCode,
            'maxStudents' => $request->integer('max_students') ?: 0,
            'rows' => $rows,
            'openable' => $rows->reject(fn (array $row) => $row['group_taken']),
            'credits' => CurriculumOfferings::credits($rows),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'semester_id' => ['required', 'integer', 'exists:semesters,id'],
            'major_id' => ['required', 'integer', 'exists:majors,id'],
            'plan_semester' => ['required', 'integer', 'between:1,8'],
            'group_code' => ['required', 'string', 'max:20'],
            'max_students' => ['required', 'integer', 'between:0,65535'],
            'subjects' => ['required', 'array', 'min:1'],
            'subjects.*' => ['required', 'integer', 'exists:subjects,id'],
        ], [
            'subjects.required' => 'Chọn ít nhất một môn trong khung để mở lớp.',
            'max_students.between' => 'Sĩ số tối đa từ 0 tới 65535; nhập 0 khi lớp không giới hạn sĩ số.',
            'group_code.required' => 'Nhóm lớp là bắt buộc, ví dụ TN01.',
        ]);

        $semester = Semester::findOrFail($data['semester_id']);
        $major = Major::findOrFail($data['major_id']);

        $opened = CurriculumOfferings::open(
            $major,
            $semester,
            (int) $data['plan_semester'],
            array_map('intval', $data['subjects']),
            [
                'group_code' => $data['group_code'],
                'max_students' => (int) $data['max_students'],
                'is_open' => $request->boolean('is_open'),
            ],
        );

        if ($opened === 0) {
            return back()->with(
                'error',
                'Không mở được lớp nào: mọi môn bạn chọn đã có lớp nhóm '.$data['group_code'].' trong '.$semester->label.'.'
            );
        }

        return redirect()->route('admin.course-offerings.index')->with(
            'success',
            'Đã mở '.$opened.' lớp học phần nhóm '.$data['group_code'].' cho '.$major->name
                .' (học kỳ đào tạo '.$data['plan_semester'].') trong '.$semester->label.'.'
        );
    }

    /**
     * `?group_code[]=` vẫn là input từ ngoài — chỉ nhận chuỗi để trang không vỡ khi in giá trị.
     */
    private function groupCode(Request $request): string
    {
        $value = $request->query('group_code');

        return is_string($value) && filled($value) ? $value : 'TN01';
    }
}
