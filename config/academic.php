<?php

/*
 * Ngưỡng nghiệp vụ ngoài thang điểm. Điểm số, trọng số, bảng quy đổi hệ 4 và ngưỡng
 * GPA cảnh báo học vụ thuộc app/Support/GradingScale.php — không nhân bản sang đây.
 */

return [
    'max_credits_per_term' => (int) env('ACADEMIC_MAX_CREDITS_PER_TERM', 14),
    'min_attendance_rate' => (float) env('ACADEMIC_MIN_ATTENDANCE_RATE', 80),

    /*
     * Học phí tính theo tín chỉ. Mức 0 nghĩa là nhà trường chưa cấu hình: màn hình học phí khi đó
     * báo "chưa phát hành" chứ không tự bịa một số tiền.
     */
    'tuition_per_credit' => (int) env('ACADEMIC_TUITION_PER_CREDIT', 0),

    // Thông tin tài khoản nhận chuyển khoản, dùng để dựng ảnh QR của VietQR (không lưu ở DB).
    'tuition_bank' => [
        'code' => env('TUITION_BANK_CODE', ''),
        'account' => env('TUITION_BANK_ACCOUNT', ''),
    ],
];
