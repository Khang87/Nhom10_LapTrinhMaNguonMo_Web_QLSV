<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StudentClass;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $search = trim((string) $request->query('search'));
        $students = User::query()
            ->where('role', 'student')
            ->with('studentClass')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'rows' => view('admin.students.rows', compact('students'))->render(),
                'pagination' => $students->render()->toHtml(),
                'total' => $students->total(),
            ]);
        }

        return view('admin.students.index', compact('students', 'search'));
    }

    public function create(): View
    {
        return view('admin.students.create', [
            'student' => new User,
            'classes' => StudentClass::orderBy('class_code')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data['password'] = Hash::make($data['password']);
        $data['role'] = 'student';
        $data['avatar'] = $this->storeAvatar($request);
        User::create($data);

        return redirect()->route('admin.students.index')->with('success', 'Đã thêm sinh viên.');
    }

    public function edit(User $student): View
    {
        abort_unless($student->role === 'student', 404);

        return view('admin.students.edit', [
            'student' => $student,
            'classes' => StudentClass::orderBy('class_code')->get(),
        ]);
    }

    public function update(Request $request, User $student): RedirectResponse
    {
        abort_unless($student->role === 'student', 404);

        $data = $this->validatedData($request, $student);
        unset($data['password']);
        if ($avatar = $this->storeAvatar($request)) {
            $this->deleteAvatar($student->avatar);
            $data['avatar'] = $avatar;
        }
        $student->update($data);

        return redirect()->route('admin.students.index')->with('success', 'Đã cập nhật sinh viên.');
    }

    public function destroy(User $student): RedirectResponse
    {
        abort_unless($student->role === 'student', 404);
        $this->deleteAvatar($student->avatar);
        $student->delete();

        return redirect()->route('admin.students.index')->with('success', 'Đã xóa sinh viên.');
    }

    private function storeAvatar(Request $request): ?string
    {
        return $request->hasFile('avatar')
            ? $request->file('avatar')->store('avatars', 'public')
            : null;
    }

    private function deleteAvatar(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }

    private function validatedData(Request $request, ?User $student = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($student?->id),
            ],
            'password' => [$student ? 'nullable' : 'required', 'string', 'min:8'],
            'class_id' => ['nullable', 'exists:classes,id'],
            'avatar' => [
                'nullable',
                File::image()
                    ->types(['jpg', 'jpeg', 'png', 'webp'])
                    ->max(2 * 1024),
            ],
        ]);
    }
}
