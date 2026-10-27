<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\Lecturer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FacultyController extends Controller
{
    public function index(): View
    {
        $faculties = Faculty::with('dean')
            ->withCount(['specializations', 'lecturers'])
            ->orderBy('name')
            ->get();

        return view('admin.faculties.index', compact('faculties'));
    }

    public function create(): View
    {
        $lecturers = Lecturer::with(['faculty', 'headedFaculty'])->orderBy('full_name')->get();

        return view('admin.faculties.create', [
            'faculty' => new Faculty(),
            'lecturers' => $lecturers,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:faculties,code'],
            'name' => ['required', 'string', 'max:150'],
            'dean_id' => ['nullable', 'exists:lecturers,id'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100'],
            'office_location' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($validated) {
            $faculty = Faculty::create($validated);

            if (!empty($validated['dean_id'])) {
                // Nếu giảng viên được bổ nhiệm đang là Trưởng khoa ở khoa khác -> giải phóng chức vụ ở khoa cũ
                Faculty::where('dean_id', $validated['dean_id'])
                    ->where('id', '!=', $faculty->id)
                    ->update(['dean_id' => null]);

                // Điều chuyển giảng viên được bổ nhiệm về trực thuộc khoa mới này
                Lecturer::where('id', $validated['dean_id'])->update([
                    'faculty_id' => $faculty->id,
                    'faculty_name' => $faculty->name,
                ]);
            }
        });

        return redirect()->route('admin.faculties.index')
            ->with('success', 'Đã thêm khoa mới thành công.');
    }

    public function show(Faculty $faculty): View
    {
        $faculty->load([
            'dean.specialization',
            'specializations' => function ($q) {
                $q->withCount(['students', 'lecturers', 'curriculumItems'])->orderBy('name');
            },
            'lecturers' => function ($q) {
                $q->with('specialization')->orderBy('full_name');
            },
        ]);

        return view('admin.faculties.show', compact('faculty'));
    }

    public function edit(Faculty $faculty): View
    {
        // Lấy tất cả giảng viên trong trường để linh hoạt phân công hoặc điều chuyển làm Trưởng khoa
        $lecturers = Lecturer::with(['faculty', 'headedFaculty'])->orderBy('full_name')->get();

        return view('admin.faculties.edit', compact('faculty', 'lecturers'));
    }

    public function update(Request $request, Faculty $faculty): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('faculties', 'code')->ignore($faculty->id)],
            'name' => ['required', 'string', 'max:150'],
            'dean_id' => ['nullable', 'exists:lecturers,id'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100'],
            'office_location' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($validated, $faculty) {
            $newDeanId = $validated['dean_id'] ?? null;

            $faculty->update($validated);

            if (!empty($newDeanId)) {
                // Nếu giảng viên được bổ nhiệm đang là Trưởng khoa ở khoa khác -> giải phóng chức vụ ở khoa cũ
                Faculty::where('dean_id', $newDeanId)
                    ->where('id', '!=', $faculty->id)
                    ->update(['dean_id' => null]);

                // Cập nhật khoa công tác của giảng viên mới về khoa này
                Lecturer::where('id', $newDeanId)->update([
                    'faculty_id' => $faculty->id,
                    'faculty_name' => $faculty->name,
                ]);
            }
        });

        return redirect()->route('admin.faculties.index')
            ->with('success', 'Đã cập nhật thông tin khoa thành công.');
    }

    public function destroy(Faculty $faculty): RedirectResponse
    {
        $name = $faculty->name;

        \Illuminate\Support\Facades\DB::transaction(function () use ($faculty) {
            // Giải phóng Trưởng khoa
            $faculty->update(['dean_id' => null]);

            // Gỡ bỏ liên kết các chuyên ngành thuộc khoa này (chuyển về chưa phân khoa)
            $faculty->specializations()->update([
                'faculty_id' => null,
                'faculty_name' => 'Chưa phân khoa',
            ]);

            // Gỡ bỏ liên kết các giảng viên thuộc khoa này
            $faculty->lecturers()->update([
                'faculty_id' => null,
                'faculty_name' => 'Chưa phân khoa',
            ]);

            $faculty->delete();
        });

        return redirect()->route('admin.faculties.index')
            ->with('success', "Đã xóa khoa [{$name}] thành công. Các chuyên ngành và giảng viên của khoa đã được giải phóng về trạng thái chưa phân khoa.");
    }
}
