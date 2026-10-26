<?php

namespace App\Support;

use App\Models\CourseOffering;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class OfferingAssignment
{
    /**
     * Lớp học phần chỉ giao cho giảng viên: admin/sinh viên không đứng tên lớp.
     */
    public static function assertTeacher(User $teacher): void
    {
        if ($teacher->role !== 'teacher') {
            throw ValidationException::withMessages([
                'teacher_id' => 'Tài khoản "'.($teacher->person_code ?? $teacher->email)
                    .'" không phải giảng viên nên không thể phụ trách lớp học phần.',
            ]);
        }
    }

    /**
     * Một môn, trong một học kỳ, chỉ có thể có một nhóm mang mã đã chọn.
     */
    public static function assertGroupFree(CourseOffering $offering): void
    {
        $taken = CourseOffering::where('subject_id', $offering->subject_id)
            ->where('semester_id', $offering->semester_id)
            ->where('group_code', $offering->group_code)
            ->whereKeyNot($offering->id ?? 0)
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages([
                'group_code' => 'Nhóm '.$offering->group_code.' của môn '
                    .$offering->subject?->code.' đã được mở trong học kỳ này.',
            ]);
        }
    }
}
