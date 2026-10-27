<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\Lecturer;
use App\Models\Specialization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LecturerController extends Controller
{
    public function index(Request $request): View
    {
        $query = Lecturer::with(['faculty', 'specialization', 'headedFaculty']);

        if ($request->filled('faculty_id')) {
            $query->where('faculty_id', $request->faculty_id);
        }

        if ($request->filled('specialization_id')) {
            $query->where('specialization_id', $request->specialization_id);
        }

        if ($request->filled('keyword')) {
            $kw = trim($request->keyword);
            $query->where(function ($q) use ($kw) {
                $q->where('full_name', 'like', "%{$kw}%")
                  ->orWhere('lecturer_code', 'like', "%{$kw}%")
                  ->orWhere('email', 'like', "%{$kw}%")
                  ->orWhere('phone', 'like', "%{$kw}%");
            });
        }

        $lecturers = $query->orderBy('full_name')->paginate(20)->withQueryString();

        $faculties = Faculty::orderBy('name')->get();
        $specializations = Specialization::orderBy('name')->get();

        $totalLecturers = Lecturer::count();
        $totalPhd = Lecturer::where('degree', 'like', '%Tiến sĩ%')
            ->orWhere('degree', 'like', '%TS%')
            ->count();

        return view('admin.lecturers.index', compact(
            'lecturers',
            'faculties',
            'specializations',
            'totalLecturers',
            'totalPhd'
        ));
    }

    public function create(Request $request): View
    {
        $faculties = Faculty::orderBy('name')->get();
        $specializations = Specialization::orderBy('name')->get();

        // Tự động tạo gợi ý mã giảng viên tiếp theo
        $maxId = Lecturer::max('id') ?? 0;
        $nextCode = 'GV' . str_pad($maxId + 1, 3, '0', STR_PAD_LEFT);
        while (Lecturer::where('lecturer_code', $nextCode)->exists()) {
            $maxId++;
            $nextCode = 'GV' . str_pad($maxId + 1, 3, '0', STR_PAD_LEFT);
        }

        $selectedFacultyId = $request->query('faculty_id');

        return view('admin.lecturers.create', [
            'lecturer' => new Lecturer(),
            'faculties' => $faculties,
            'specializations' => $specializations,
            'nextCode' => $nextCode,
            'selectedFacultyId' => $selectedFacultyId,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'lecturer_code' => ['nullable', 'string', 'max:20', 'unique:lecturers,lecturer_code'],
            'full_name' => ['required', 'string', 'max:100'],
            'degree' => ['required', 'string', 'max:50'],
            'faculty_id' => ['required', 'exists:faculties,id'],
            'specialization_id' => ['nullable', 'exists:specializations,id'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:100', 'unique:users,email', 'unique:lecturers,email'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $code = $validated['lecturer_code'];
        if (empty($code)) {
            $maxId = Lecturer::max('id') ?? 0;
            $code = 'GV' . str_pad($maxId + 1, 3, '0', STR_PAD_LEFT);
            while (Lecturer::where('lecturer_code', $code)->exists()) {
                $maxId++;
                $code = 'GV' . str_pad($maxId + 1, 3, '0', STR_PAD_LEFT);
            }
        }

        $faculty = Faculty::findOrFail($validated['faculty_id']);

        DB::transaction(function () use ($validated, $code, $faculty) {
            $user = User::create([
                'username' => $code,
                'name' => $validated['full_name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password'] ?? '12345678'),
                'role' => 'lecturer',
                'is_active' => true,
            ]);

            Lecturer::create([
                'user_id' => $user->id,
                'lecturer_code' => $code,
                'full_name' => $validated['full_name'],
                'degree' => $validated['degree'],
                'faculty_id' => $faculty->id,
                'faculty_name' => $faculty->name,
                'specialization_id' => $validated['specialization_id'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'],
            ]);
        });

        if ($request->filled('from_faculty')) {
            return redirect()->route('admin.faculties.show', $faculty)
                ->with('success', "Đã thêm giảng viên [{$validated['full_name']}] vào khoa {$faculty->name} thành công.");
        }

        return redirect()->route('admin.lecturers.index')
            ->with('success', "Đã thêm giảng viên [{$validated['full_name']}] thành công.");
    }

    public function edit(Lecturer $lecturer): View
    {
        $faculties = Faculty::orderBy('name')->get();
        $specializations = Specialization::orderBy('name')->get();

        return view('admin.lecturers.edit', compact('lecturer', 'faculties', 'specializations'));
    }

    public function update(Request $request, Lecturer $lecturer): RedirectResponse
    {
        $validated = $request->validate([
            'lecturer_code' => ['required', 'string', 'max:20', Rule::unique('lecturers', 'lecturer_code')->ignore($lecturer->id)],
            'full_name' => ['required', 'string', 'max:100'],
            'degree' => ['required', 'string', 'max:50'],
            'faculty_id' => ['required', 'exists:faculties,id'],
            'specialization_id' => ['nullable', 'exists:specializations,id'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => [
                'required', 'email', 'max:100',
                Rule::unique('lecturers', 'email')->ignore($lecturer->id),
                Rule::unique('users', 'email')->ignore($lecturer->user_id),
            ],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $faculty = Faculty::findOrFail($validated['faculty_id']);
        $relievedDeanFacultyName = null;

        DB::transaction(function () use ($validated, $lecturer, $faculty, &$relievedDeanFacultyName) {
            // Nếu giảng viên này đang là Trưởng khoa ở khoa khác và nay bị chuyển sang khoa mới
            $headedFaculty = Faculty::where('dean_id', $lecturer->id)->first();
            if ($headedFaculty && $headedFaculty->id != $faculty->id) {
                $headedFaculty->update(['dean_id' => null]);
                $relievedDeanFacultyName = $headedFaculty->name;
            }

            $userData = [
                'name' => $validated['full_name'],
                'email' => $validated['email'],
                'username' => $validated['lecturer_code'],
            ];

            if (!empty($validated['password'])) {
                $userData['password'] = Hash::make($validated['password']);
            }

            if ($lecturer->user) {
                $lecturer->user->update($userData);
            }

            $lecturer->update([
                'lecturer_code' => $validated['lecturer_code'],
                'full_name' => $validated['full_name'],
                'degree' => $validated['degree'],
                'faculty_id' => $faculty->id,
                'faculty_name' => $faculty->name,
                'specialization_id' => $validated['specialization_id'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'],
            ]);
        });

        $msg = "Đã cập nhật thông tin giảng viên [{$lecturer->full_name}] thành công.";
        if ($relievedDeanFacultyName) {
            $msg .= " Lưu ý: Do chuyển công tác sang khoa {$faculty->name}, chức danh Trưởng khoa tại {$relievedDeanFacultyName} đã được tự động giải phóng.";
        }

        return redirect()->route('admin.lecturers.index')
            ->with('success', $msg);
    }

    public function destroy(Lecturer $lecturer): RedirectResponse
    {
        if ($lecturer->headedFaculty) {
            return back()->with('error', "Không thể xóa giảng viên [{$lecturer->full_name}] vì đang giữ chức vụ Trưởng khoa {$lecturer->headedFaculty->name}. Vui lòng chuyển chức danh trước.");
        }

        if ($lecturer->sections()->count() > 0) {
            return back()->with('error', "Không thể xóa giảng viên [{$lecturer->full_name}] vì đã được phân công dạy các lớp học phần.");
        }

        DB::transaction(function () use ($lecturer) {
            $user = $lecturer->user;
            $lecturer->delete();
            if ($user) {
                $user->delete();
            }
        });

        return redirect()->route('admin.lecturers.index')
            ->with('success', "Đã xóa giảng viên thành công.");
    }
}
