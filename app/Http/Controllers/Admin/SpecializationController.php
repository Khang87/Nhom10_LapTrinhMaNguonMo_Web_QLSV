<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CurriculumFramework;
use App\Models\Specialization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SpecializationController extends Controller
{
    public function index(Request $request): View
    {
        $query = Specialization::with('faculty')
            ->withCount(['students', 'lecturers', 'curriculumItems']);

        $selectedFacultyId = $request->query('faculty_id');
        $search = trim((string) $request->query('search', ''));

        if ($selectedFacultyId === 'unassigned') {
            $query->whereNull('faculty_id');
        } elseif (!empty($selectedFacultyId)) {
            $query->where('faculty_id', $selectedFacultyId);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $specializations = $query->orderBy('name')->get();

        $faculties = \App\Models\Faculty::withCount('specializations')->orderBy('name')->get();
        $unassignedCount = Specialization::whereNull('faculty_id')->count();
        $totalSpecializationsCount = Specialization::count();

        $selectedFaculty = (!empty($selectedFacultyId) && $selectedFacultyId !== 'unassigned')
            ? $faculties->firstWhere('id', (int) $selectedFacultyId)
            : null;

        return view('admin.specializations.index', compact(
            'specializations', 
            'faculties', 
            'selectedFacultyId',
            'selectedFaculty',
            'unassignedCount',
            'totalSpecializationsCount',
            'search'
        ));
    }

    public function create(): View
    {
        $faculties = \App\Models\Faculty::orderBy('name')->get();

        return view('admin.specializations.create', [
            'specialization' => new Specialization(),
            'faculties' => $faculties,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:specializations,code'],
            'name' => ['required', 'string', 'max:100'],
            'faculty_id' => ['required', 'exists:faculties,id'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $faculty = \App\Models\Faculty::findOrFail($validated['faculty_id']);
        $validated['faculty_name'] = $faculty->name;

        Specialization::create($validated);

        return redirect()->route('admin.specializations.index')
            ->with('success', 'Đã thêm chuyên ngành mới thành công.');
    }

    public function edit(Specialization $specialization): View
    {
        $faculties = \App\Models\Faculty::orderBy('name')->get();

        return view('admin.specializations.edit', compact('specialization', 'faculties'));
    }

    public function update(Request $request, Specialization $specialization): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('specializations', 'code')->ignore($specialization->id)],
            'name' => ['required', 'string', 'max:100'],
            'faculty_id' => ['required', 'exists:faculties,id'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $faculty = \App\Models\Faculty::findOrFail($validated['faculty_id']);
        $validated['faculty_name'] = $faculty->name;

        $specialization->update($validated);

        return redirect()->route('admin.specializations.index')
            ->with('success', 'Đã cập nhật chuyên ngành thành công.');
    }

    public function destroy(Specialization $specialization): RedirectResponse
    {
        if ($specialization->students()->count() > 0) {
            return back()->with('error', 'Không thể xóa chuyên ngành đã có sinh viên đăng ký.');
        }

        $specialization->delete();

        return redirect()->route('admin.specializations.index')
            ->with('success', 'Đã xóa chuyên ngành thành công.');
    }

    /**
     * Xem và cấu hình chương trình khung theo kỳ của chuyên ngành
     */
    public function curriculum(Specialization $specialization): View
    {
        $items = CurriculumFramework::with('course')
            ->where('specialization_id', $specialization->id)
            ->orderBy('semester_order')
            ->get();

        $curriculumBySemester = [];
        for ($i = 1; $i <= 8; $i++) {
            $curriculumBySemester[$i] = $items->where('semester_order', $i)->values();
        }

        $allCourses = Course::orderBy('course_code')->get();

        return view('admin.specializations.curriculum', compact('specialization', 'curriculumBySemester', 'allCourses'));
    }

    /**
     * Thêm học phần vào kỳ của chuyên ngành
     */
    public function addCurriculumItem(Request $request, Specialization $specialization): RedirectResponse
    {
        $validated = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'semester_order' => ['required', 'integer', 'min:1', 'max:8'],
            'is_mandatory' => ['required', 'boolean'],
        ]);

        $exists = CurriculumFramework::where('specialization_id', $specialization->id)
            ->where('course_id', $validated['course_id'])
            ->where('semester_order', $validated['semester_order'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'Học phần này đã tồn tại trong kỳ ' . $validated['semester_order'] . ' của chuyên ngành.');
        }

        CurriculumFramework::create([
            'specialization_id' => $specialization->id,
            'specialization_name' => $specialization->name,
            'course_id' => $validated['course_id'],
            'semester_order' => $validated['semester_order'],
            'is_mandatory' => $validated['is_mandatory'],
        ]);

        return back()->with('success', 'Đã thêm học phần vào kỳ ' . $validated['semester_order'] . '.');
    }

    /**
     * Cập nhật học phần trong chương trình khung
     */
    public function updateCurriculumItem(Request $request, CurriculumFramework $item): RedirectResponse
    {
        $validated = $request->validate([
            'semester_order' => ['required', 'integer', 'min:1', 'max:8'],
            'is_mandatory' => ['required', 'boolean'],
        ]);

        $item->update($validated);

        return back()->with('success', 'Đã cập nhật thông tin học phần trong chương trình khung.');
    }

    /**
     * Xóa học phần khỏi chương trình khung của chuyên ngành
     */
    public function removeCurriculumItem(CurriculumFramework $item): RedirectResponse
    {
        $item->delete();

        return back()->with('success', 'Đã gỡ học phần khỏi chương trình khung.');
    }
}
