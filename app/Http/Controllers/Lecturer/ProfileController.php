<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\CourseSection;
use App\Models\Semester;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Hiển thị trang Thông tin cá nhân của Giảng viên.
     */
    public function show(): View
    {
        $user = Auth::user();
        $lecturer = $user->lecturer;

        if (!$lecturer) {
            abort(404, 'Không tìm thấy hồ sơ giảng viên tương ứng.');
        }

        $lecturer->load([
            'faculty',
            'specialization',
            'headedFaculty',
            'homeroomClasses.specialization',
            'sections.course',
            'sections.semester'
        ]);

        // Đếm tổng số sinh viên trong các lớp CVHT
        $totalHomeroomStudents = 0;
        foreach ($lecturer->homeroomClasses as $homeroomClass) {
            $totalHomeroomStudents += $homeroomClass->students_count;
        }

        // Lấy học kỳ hiện tại
        $currentSemester = Semester::where('is_registration_open', true)->first()
            ?? Semester::where('semester_code', 'HK1_2026_2027')->first()
            ?? Semester::orderBy('id')->first();
        $currentSemesterCode = $currentSemester?->semester_code ?? 'HK1_2026_2027';

        // Lớp học phần đang giảng dạy trong kỳ này
        $activeSections = CourseSection::with(['course', 'enrollments'])
            ->withCount('enrollments')
            ->where('lecturer_id', $lecturer->id)
            ->where('semester_code', $currentSemesterCode)
            ->get();

        return view('lecturer.profile.index', compact(
            'user',
            'lecturer',
            'totalHomeroomStudents',
            'currentSemester',
            'activeSections'
        ));
    }

    /**
     * Cập nhật thông tin liên hệ và đổi mật khẩu cho Giảng viên.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $lecturer = $user->lecturer;

        if (!$lecturer) {
            abort(404, 'Không tìm thấy hồ sơ giảng viên tương ứng.');
        }

        $validated = $request->validate([
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => [
                'required', 'email', 'max:100',
                Rule::unique('users', 'email')->ignore($user->id),
                Rule::unique('lecturers', 'email')->ignore($lecturer->id),
            ],
            'current_password' => ['nullable', 'required_with:new_password', 'string'],
            'new_password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ], [
            'email.required' => 'Vui lòng cung cấp địa chỉ email liên hệ.',
            'email.email' => 'Địa chỉ email không đúng định dạng.',
            'email.unique' => 'Địa chỉ email này đã được sử dụng bởi một tài khoản khác.',
            'current_password.required_with' => 'Vui lòng nhập mật khẩu hiện tại để xác nhận đổi mật khẩu.',
            'new_password.min' => 'Mật khẩu mới phải có ít nhất 6 ký tự.',
            'new_password.confirmed' => 'Xác nhận mật khẩu mới không trùng khớp.',
        ]);

        // Nếu người dùng yêu cầu đổi mật khẩu
        if (!empty($validated['new_password'])) {
            if (!Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'Mật khẩu hiện tại không chính xác.'])->withInput();
            }
        }

        DB::transaction(function () use ($user, $lecturer, $validated) {
            $user->email = $validated['email'];
            if (!empty($validated['new_password'])) {
                $user->password = Hash::make($validated['new_password']);
            }
            $user->save();

            $lecturer->update([
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
            ]);
        });

        return back()->with('success', 'Đã cập nhật thông tin cá nhân và mật khẩu thành công!');
    }
}
