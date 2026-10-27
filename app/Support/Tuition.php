<?php

namespace App\Support;

use App\Models\CourseOffering;
use App\Models\Semester;
use App\Models\TuitionInvoice;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Học phí tính theo tín chỉ (ý tưởng màn hình lấy từ hệ thống cũ): nhà trường cấu hình đơn giá
 * một tín chỉ, còn số tiền của từng sinh viên là tín chỉ đang học nhân lên, phát hành thành một
 * thông báo theo học kỳ để sinh viên chuyển khoản kèm nội dung đối chiếu.
 */
final class Tuition
{
    /** Số tiền một tín chỉ; 0 nghĩa là nhà trường chưa cấu hình mức thu. */
    public static function perCredit(): int
    {
        return (int) config('academic.tuition_per_credit');
    }

    public static function amountFor(int $credits): int
    {
        return $credits * self::perCredit();
    }

    /** Tín chỉ của các lớp đang học trong một học kỳ (lớp đã hủy không tính). */
    public static function creditsFor(User $student, Semester $semester): int
    {
        return (int) $student->offerings()
            ->wherePivot('status', 'ACTIVE')
            ->where('course_offerings.semester_id', $semester->id)
            ->with('subject')
            ->get()
            ->sum(fn (CourseOffering $offering) => (int) ($offering->subject?->credits ?? 0));
    }

    /**
     * Nội dung chuyển khoản: đủ để đối chiếu tự động mà vẫn nằm trong giới hạn 25 ký tự của
     * ngân hàng, vì VietQR cắt phần vượt quá mà không báo lỗi.
     */
    public static function contentFor(User $student, Semester $semester): string
    {
        return rtrim(substr('QLSV'.($student->student_code ?: 'SV'.$student->id).'-HK'.$semester->term, 0, 25), '-');
    }

    /**
     * Phát hành (hoặc làm mới) thông báo học phí cho mọi sinh viên đang học trong học kỳ.
     * Thông báo đã thu không bao giờ bị đổi số tiền; thông báo chưa thu được cập nhật theo
     * đăng ký hiện tại vì sinh viên vẫn còn quyền thêm/hủy môn tới hết cổng đăng ký.
     *
     * @return array{created: int, refreshed: int}
     */
    public static function declare(Semester $semester): array
    {
        $creditsByStudent = DB::table('enrollments')
            ->join('course_offerings', 'course_offerings.id', '=', 'enrollments.course_offering_id')
            ->join('subjects', 'subjects.id', '=', 'course_offerings.subject_id')
            ->where('enrollments.status', 'ACTIVE')
            ->where('course_offerings.semester_id', $semester->id)
            ->groupBy('enrollments.user_id')
            ->selectRaw('enrollments.user_id, SUM(subjects.credits) AS credits')
            ->pluck('credits', 'user_id');

        $students = User::whereIn('id', $creditsByStudent->keys()->all())->get()->keyBy('id');
        $created = 0;
        $refreshed = 0;

        DB::transaction(function () use ($creditsByStudent, $students, $semester, &$created, &$refreshed) {
            foreach ($creditsByStudent as $studentId => $credits) {
                $student = $students->get((int) $studentId);
                if ($student === null) {
                    continue;
                }

                $credits = (int) $credits;
                $invoice = TuitionInvoice::firstOrNew([
                    'user_id' => $student->id,
                    'semester_id' => $semester->id,
                ]);

                if ($invoice->isPaid()) {
                    continue;
                }

                $isNew = ! $invoice->exists;

                if (! $isNew && $invoice->credits === $credits) {
                    continue;
                }

                $invoice->credits = $credits;
                $invoice->amount = self::amountFor($credits);
                $invoice->content ??= self::contentFor($student, $semester);
                $invoice->save();

                $isNew ? $created++ : $refreshed++;
            }
        });

        return ['created' => $created, 'refreshed' => $refreshed];
    }

    /**
     * Ảnh QR động của VietQR cho một thông báo; null khi chưa cấu hình tài khoản nhận tiền.
     */
    public static function qrUrl(TuitionInvoice $invoice): ?string
    {
        $bank = (array) config('academic.tuition_bank');

        if (blank($bank['code'] ?? null) || blank($bank['account'] ?? null)) {
            return null;
        }

        return 'https://img.vietqr.io/image/'.$bank['code'].'-'.$bank['account'].'-compact2.png?'
            .http_build_query(['amount' => $invoice->amount, 'addInfo' => $invoice->content], '', '&');
    }
}
