<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CurriculumFramework;
use App\Models\Specialization;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubjectController extends Controller
{
    /**
     * Quản lý môn học theo Chuyên ngành & Chương trình khung
     */
    public function index(Request $request): View
    {
        $specializations = Specialization::with('faculty')->orderBy('name')->get();
        $selectedSpecId = $request->query('specialization_id');

        $activeSpec = null;
        if ($selectedSpecId) {
            $activeSpec = $specializations->firstWhere('id', $selectedSpecId);
        }
        if (!$activeSpec) {
            $activeSpec = $specializations->first();
        }

        $curriculumBySemester = [];
        for ($sem = 1; $sem <= 8; $sem++) {
            $curriculumBySemester[$sem] = collect();
        }

        if ($activeSpec) {
            $items = CurriculumFramework::with('course')
                ->where('specialization_id', $activeSpec->id)
                ->orderBy('semester_order')
                ->get()
                ->unique(fn($item) => $item->semester_order . '_' . $item->course_id);

            for ($sem = 1; $sem <= 8; $sem++) {
                $curriculumBySemester[$sem] = $items->where('semester_order', $sem)->values();
            }
        }

        $allCourses = Course::orderBy('course_code')->get();
        $activeTab = (int) $request->query('semester', 1);

        return view('admin.subjects.index', compact(
            'specializations',
            'activeSpec',
            'curriculumBySemester',
            'allCourses',
            'activeTab'
        ));
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('admin.subjects.index');
    }

    public function show($subject = null): RedirectResponse
    {
        return redirect()->route('admin.subjects.index');
    }

    public function edit($subject = null): RedirectResponse
    {
        return redirect()->route('admin.subjects.index');
    }

    /**
     * Thêm học phần vào kỳ của chuyên ngành
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'specialization_id' => ['required', 'exists:specializations,id'],
            'course_id' => ['nullable', 'exists:courses,id'],
            'semester_order' => ['required', 'integer', 'min:1', 'max:8'],
            'is_mandatory' => ['required', 'boolean'],
            // Nếu tạo môn học mới trực tiếp
            'new_course_code' => ['nullable', 'string', 'max:20', 'unique:courses,course_code'],
            'new_course_name' => ['nullable', 'string', 'max:150'],
            'new_credits' => ['nullable', 'integer', 'min:1', 'max:15'],
        ]);

        $courseId = $validated['course_id'] ?? null;

        // Nếu người dùng nhập thông tin môn mới
        if (!$courseId && !empty($validated['new_course_code'])) {
            $newCourse = Course::create([
                'course_code' => strtoupper($validated['new_course_code']),
                'course_name' => $validated['new_course_name'] ?? $validated['new_course_code'],
                'credits' => $validated['new_credits'] ?? 3,
                'tuition_fee_per_credit' => 550000,
            ]);
            Subject::updateOrCreate(
                ['code' => $newCourse->course_code],
                ['name' => $newCourse->course_name, 'credits' => $newCourse->credits]
            );
            $courseId = $newCourse->id;
        }

        if (!$courseId) {
            return back()->with('error', 'Vui lòng chọn học phần có sẵn hoặc nhập thông tin học phần mới.');
        }

        $spec = Specialization::findOrFail($validated['specialization_id']);

        $existingItem = CurriculumFramework::with('course')
            ->where('specialization_id', $spec->id)
            ->where('course_id', $courseId)
            ->first();

        if ($existingItem) {
            $cName = $existingItem->course?->course_name ?? 'Học phần này';
            return back()->with('error', "Học phần [{$cName}] đã có trong Chương trình khung của ngành (đang được xếp ở Học kỳ {$existingItem->semester_order}). Không thể thêm trùng lặp giữa các kỳ!");
        }

        CurriculumFramework::create([
            'specialization_id' => $spec->id,
            'specialization_name' => $spec->name,
            'course_id' => $courseId,
            'semester_order' => $validated['semester_order'],
            'is_mandatory' => (bool) $validated['is_mandatory'],
        ]);

        return redirect()->route('admin.subjects.index', [
            'specialization_id' => $spec->id,
            'semester' => $validated['semester_order'],
        ])->with('success', 'Đã thêm học phần vào Kỳ ' . $validated['semester_order'] . ' thành công.');
    }

    /**
     * Cập nhật thông tin học phần trong chương trình khung (kỳ học, bắt buộc/tự chọn)
     */
    public function update(Request $request, $subject = null): RedirectResponse
    {
        $targetId = $subject 
            ?? $request->route('subject') 
            ?? $request->route('id') 
            ?? $request->input('curriculum_id') 
            ?? $request->input('id');

        if (is_object($targetId)) {
            $targetId = $targetId->id ?? null;
        }

        if (!$targetId) {
            return back()->with('error', 'Không tìm thấy thông tin học phần cần cập nhật.');
        }

        $item = CurriculumFramework::with('course')->find($targetId);
        if (!$item) {
            // Thử tìm theo course_id nếu $targetId là mã môn học
            $item = CurriculumFramework::with('course')->where('course_id', $targetId)->first();
        }

        if (!$item) {
            return back()->with('error', "Không tìm thấy học phần (ID: {$targetId}) trong chương trình khung.");
        }

        $validated = $request->validate([
            'semester_order' => ['required', 'integer', 'min:1', 'max:8'],
            'is_mandatory' => ['required', 'boolean'],
        ]);

        // Kiểm tra nếu chuyển kỳ thì kỳ đích đã có môn học này chưa
        if ((int)$validated['semester_order'] !== (int)$item->semester_order) {
            $duplicate = CurriculumFramework::where('specialization_id', $item->specialization_id)
                ->where('course_id', $item->course_id)
                ->where('semester_order', $validated['semester_order'])
                ->where('id', '!=', $item->id)
                ->exists();

            if ($duplicate) {
                return back()->with('error', 'Không thể chuyển: Học phần [' . ($item->course?->course_name ?? 'này') . '] đã tồn tại trong Học kỳ ' . $validated['semester_order'] . ' rồi.');
            }
        }

        $item->update([
            'semester_order' => $validated['semester_order'],
            'is_mandatory' => (bool) $validated['is_mandatory'],
        ]);

        return redirect()->route('admin.subjects.index', [
            'specialization_id' => $item->specialization_id,
            'semester' => $validated['semester_order'],
        ])->with('success', 'Đã cập nhật học phần trong chương trình khung thành công.');
    }

    /**
     * Xóa học phần khỏi kỳ của chuyên ngành
     */
    public function destroy($id = null): RedirectResponse
    {
        $targetId = $id ?? request()->route('subject') ?? request()->route('id');
        if (is_object($targetId)) {
            $targetId = $targetId->id ?? null;
        }

        $item = CurriculumFramework::findOrFail($targetId);
        $specId = $item->specialization_id;
        $semester = $item->semester_order;

        $item->delete();

        return redirect()->route('admin.subjects.index', [
            'specialization_id' => $specId,
            'semester' => $semester,
        ])->with('success', 'Đã xóa học phần khỏi chương trình khung.');
    }
}
