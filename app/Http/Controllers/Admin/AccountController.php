<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\StudentClass;
use App\Models\User;
use App\Support\PersonLabels;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $search = trim((string) $request->query('search'));
        $role = (string) $request->query('role');

        $accounts = User::query()
            ->with(['studentClass.major', 'department'])
            ->when(in_array($role, User::ROLES, true), fn ($query) => $query->where('role', $role))
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('student_code', 'like', "%{$search}%")
                    ->orWhere('teacher_code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            }))
            ->orderBy('role')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'rows' => view('admin.accounts.rows', compact('accounts'))->render(),
                'pagination' => $accounts->render()->toHtml(),
                'total' => $accounts->total(),
            ]);
        }

        return view('admin.accounts.index', compact('accounts', 'search', 'role'));
    }

    public function create(): View
    {
        return view('admin.accounts.create', [
            'account' => new User(['role' => 'student']),
            'classes' => StudentClass::orderBy('class_code')->get(),
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data['password'] = Hash::make($data['password']);
        User::create($data);

        return redirect()->route('admin.accounts.index')->with('success', 'Đã thêm tài khoản.');
    }

    public function edit(User $account): View
    {
        return view('admin.accounts.edit', [
            'account' => $account,
            'classes' => StudentClass::orderBy('class_code')->get(),
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $account): RedirectResponse
    {
        $data = $this->validatedData($request, $account);

        if ($account->id === $request->user()->id && $data['role'] !== 'admin') {
            return back()->with('error', 'Bạn không thể tự rời khỏi vai trò Quản trị viên đang đăng nhập.');
        }

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }
        $account->update($data);

        return redirect()->route('admin.accounts.index')->with('success', 'Đã cập nhật tài khoản.');
    }

    public function destroy(Request $request, User $account): RedirectResponse
    {
        if ($account->id === $request->user()->id) {
            return back()->with('error', 'Bạn không thể xóa tài khoản đang đăng nhập.');
        }

        $records = $account->grades()->count() + $account->attendances()->count() + $account->enrollments()->count();
        if ($records > 0) {
            return back()->with(
                'error',
                "Tài khoản đã có {$records} bản ghi điểm hoặc điểm danh. Xoá tài khoản sẽ mất toàn bộ dữ liệu này."
            );
        }

        $account->delete();

        return redirect()->route('admin.accounts.index')->with('success', 'Đã xóa tài khoản.');
    }

    private function validatedData(Request $request, ?User $account = null): array
    {
        $code = ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9._-]+$/'];

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($account?->id),
            ],
            'password' => [$account ? 'nullable' : 'required', 'string', 'min:8'],
            'role' => ['required', Rule::in(User::ROLES)],
            'class_id' => ['nullable', 'exists:classes,id'],
            'student_code' => array_merge($code, [Rule::unique('users', 'student_code')->ignore($account?->id)]),
            'teacher_code' => array_merge($code, [Rule::unique('users', 'teacher_code')->ignore($account?->id)]),
            'degree' => ['nullable', 'string', 'max:60'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'phone' => ['nullable', 'regex:/^0[0-9]{8,10}$/'],
            'dob' => ['nullable', 'date'],
            'gender' => ['nullable', Rule::in(PersonLabels::genderCodes())],
        ], [
            'student_code.unique' => 'Mã sinh viên này đã được sử dụng.',
            'teacher_code.unique' => 'Mã giảng viên này đã được sử dụng.',
            'student_code.regex' => 'Mã chỉ gồm chữ, số và dấu chấm, gạch nối.',
            'teacher_code.regex' => 'Mã chỉ gồm chữ, số và dấu chấm, gạch nối.',
            'phone.regex' => 'Số điện thoại phải bắt đầu bằng 0 và có 10–11 chữ số.',
            'gender.in' => 'Giới tính không hợp lệ.',
        ]);

        // Một tài khoản chỉ mang hồ sơ của đúng một vai trò: rời vai trò nào thì bỏ các trường của vai trò đó.
        if ($data['role'] !== 'student') {
            $data['class_id'] = null;
            $data['student_code'] = null;
        }

        if ($data['role'] !== 'teacher') {
            $data['teacher_code'] = null;
            $data['degree'] = null;
            $data['department_id'] = null;
        }

        return $data;
    }
}
