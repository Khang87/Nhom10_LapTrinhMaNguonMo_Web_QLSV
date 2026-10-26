<?php

namespace App\Support;

use App\Models\CourseOffering;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Ghi đăng ký học phần: một transaction duy nhất, có lockForUpdate trên hàng lớp học phần
 * để hai request cùng giành chỗ cuối không bao giờ cùng thắng.
 */
class EnrollmentRegistrar
{
    /**
     * $actor là người bấm nút: sinh viên tự đăng ký thì chính họ, quản trị viên đăng ký hộ
     * thì quản trị viên — lưu vào enrolled_by để truy vết. $gateRequired = false chỉ dành
     * cho đường đăng ký hộ, mọi quy tắc khác vẫn chạy.
     *
     * @throws HttpException 422 kèm đúng lý do nghiệp vụ
     */
    public static function enroll(User $student, CourseOffering $offering, ?User $actor = null, bool $gateRequired = true): Enrollment
    {
        try {
            return DB::transaction(function () use ($student, $offering, $actor, $gateRequired) {
                $locked = CourseOffering::whereKey($offering->id)->lockForUpdate()->firstOrFail();

                return self::write($student, $locked, $actor ?? $student, $gateRequired);
            });
        } catch (UniqueConstraintViolationException) {
            // Lưới thứ hai sau unique (course_offering_id, user_id): đổi thành thông báo, không thành 500.
            throw new HttpException(422, 'Bạn đã đăng ký lớp này');
        }
    }

    private static function write(User $student, CourseOffering $offering, User $actor, bool $gateRequired): Enrollment
    {
        $verdict = EnrollmentPolicy::check($student, $offering, EnrollmentContext::for($student, $offering), $gateRequired);

        if (! $verdict['ok']) {
            throw new HttpException(422, EnrollmentPolicy::reasonText($verdict));
        }

        return Enrollment::updateOrCreate(
            ['course_offering_id' => $offering->id, 'user_id' => $student->id],
            ['status' => 'ACTIVE', 'dropped_at' => null, 'enrolled_by' => $actor->id],
        );
    }
}
