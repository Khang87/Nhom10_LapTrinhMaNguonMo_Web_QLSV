<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Major;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MajorController extends Controller
{
    public function index(Request $request): View
    {
        $departmentId = $request->integer('department_id') ?: null;

        $majors = Major::with('department')
            ->withCount(['subjects', 'studyPlans', 'classes', 'students'])
            ->when($departmentId, fn ($query) => $query->where('department_id', $departmentId))
            ->orderBy('code')
            ->paginate(10)
            ->withQueryString();

        return view('admin.majors.index', [
            'majors' => $majors,
            'departments' => Department::orderBy('name')->get(),
            'departmentId' => $departmentId,
        ]);
    }

    public function create(): View
    {
        return view('admin.majors.create', [
            'major' => new Major,
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Major::create($this->validatedData($request));

        return redirect()->route('admin.majors.index')->with('success', 'Đã thêm chuyên ngành.');
    }

    public function edit(Major $major): View
    {
        return view('admin.majors.edit', [
            'major' => $major,
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Major $major): RedirectResponse
    {
        $major->update($this->validatedData($request, $major));

        return redirect()->route('admin.majors.index')->with('success', 'Đã cập nhật chuyên ngành.');
    }

    public function destroy(Major $major): RedirectResponse
    {
        $plans = $major->studyPlans()->count();

        if ($plans > 0) {
            return redirect()->route('admin.majors.index')
                ->with('error', "Chuyên ngành đang có khung chương trình đào tạo với {$plans} môn. Xóa các môn trong khung trước khi xóa chuyên ngành.");
        }

        $major->delete();

        return redirect()->route('admin.majors.index')->with('success', 'Đã xóa chuyên ngành.');
    }

    private function validatedData(Request $request, ?Major $major = null): array
    {
        return $request->validate([
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('majors', 'code')->ignore($major?->id),
            ],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
