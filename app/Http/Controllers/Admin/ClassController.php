<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Major;
use App\Models\StudentClass;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ClassController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));

        $classes = StudentClass::query()
            ->with(['department', 'major', 'adviser'])
            ->withCount('students')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('class_code', 'like', "%{$search}%")
                        ->orWhere('class_name', 'like', "%{$search}%");
                });
            })
            ->orderBy('class_code')
            ->paginate(10)
            ->withQueryString();

        return view('admin.classes.index', compact('classes', 'search'));
    }

    public function create(): View
    {
        return view('admin.classes.create', ['class' => new StudentClass] + $this->options());
    }

    public function store(Request $request): RedirectResponse
    {
        StudentClass::create($this->validatedData($request));

        return redirect()->route('admin.classes.index')->with('success', 'Đã thêm lớp học.');
    }

    public function edit(StudentClass $class): View
    {
        return view('admin.classes.edit', ['class' => $class] + $this->options());
    }

    public function update(Request $request, StudentClass $class): RedirectResponse
    {
        $class->update($this->validatedData($request, $class));

        return redirect()->route('admin.classes.index')->with('success', 'Đã cập nhật lớp học.');
    }

    public function destroy(StudentClass $class): RedirectResponse
    {
        DB::transaction(function () use ($class) {
            $class->students()->update(['class_id' => null]);
            $class->delete();
        });

        return redirect()->route('admin.classes.index')->with('success', 'Đã xóa lớp học.');
    }

    /**
     * @return array{departments: Collection, majors: Collection, advisers: Collection}
     */
    private function options(): array
    {
        return [
            'departments' => Department::orderBy('name')->get(),
            'majors' => Major::with('department')->orderBy('name')->get(),
            'advisers' => User::where('role', 'teacher')->orderBy('name')->get(),
        ];
    }

    private function validatedData(Request $request, ?StudentClass $class = null): array
    {
        $data = $request->validate([
            'class_code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('classes', 'class_code')->ignore($class?->id),
            ],
            'class_name' => ['required', 'string', 'max:100'],
            'intake_year' => ['nullable', 'integer', 'min:1990', 'max:'.(now()->year + 10)],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'major_id' => ['nullable', 'integer', 'exists:majors,id'],
            'adviser_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('role', 'teacher')],
            'semester_id' => ['nullable', 'integer', 'exists:semesters,id'],
        ], [
            'class_code.unique' => 'Mã lớp này đã tồn tại.',
            'intake_year.min' => 'Khóa tuyển sinh phải từ năm 1990 trở đi.',
            'intake_year.max' => 'Khóa tuyển sinh không được lớn hơn năm '.(now()->year + 10).'.',
            'adviser_id.exists' => 'Giảng viên chủ nhiệm phải là tài khoản có vai trò Giảng viên.',
        ]);

        $this->assertMajorBelongsToDepartment($data);

        return $data;
    }

    /**
     * Chuyên ngành phải thuộc đúng khoa đã chọn, nếu không hồ sơ lớp sẽ tự mâu thuẫn.
     */
    private function assertMajorBelongsToDepartment(array $data): void
    {
        if (empty($data['department_id']) || empty($data['major_id'])) {
            return;
        }

        $belongsToDepartment = Major::whereKey($data['major_id'])
            ->where('department_id', $data['department_id'])
            ->exists();

        if (! $belongsToDepartment) {
            throw ValidationException::withMessages([
                'major_id' => 'Chuyên ngành đã chọn không thuộc khoa này.',
            ]);
        }
    }
}
