<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(): View
    {
        $departments = Department::withCount('majors')->orderBy('code')->paginate(10);

        return view('admin.departments.index', compact('departments'));
    }

    public function create(): View
    {
        return view('admin.departments.create', ['department' => new Department]);
    }

    public function store(Request $request): RedirectResponse
    {
        Department::create($this->validatedData($request));

        return redirect()->route('admin.departments.index')->with('success', 'Đã thêm khoa.');
    }

    public function edit(Department $department): View
    {
        return view('admin.departments.edit', compact('department'));
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $department->update($this->validatedData($request, $department));

        return redirect()->route('admin.departments.index')->with('success', 'Đã cập nhật khoa.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        $majors = $department->majors()->count();

        if ($majors > 0) {
            return redirect()->route('admin.departments.index')
                ->with('error', "Khoa còn {$majors} chuyên ngành. Chuyển hoặc xóa các chuyên ngành đó trước khi xóa khoa.");
        }

        $department->delete();

        return redirect()->route('admin.departments.index')->with('success', 'Đã xóa khoa.');
    }

    private function validatedData(Request $request, ?Department $department = null): array
    {
        return $request->validate([
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('departments', 'code')->ignore($department?->id),
            ],
            'name' => ['required', 'string', 'max:150'],
        ]);
    }
}
