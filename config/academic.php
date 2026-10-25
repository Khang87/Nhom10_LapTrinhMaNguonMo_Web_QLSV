<?php

/*
 * Ngưỡng nghiệp vụ ngoài thang điểm. Điểm số, trọng số, bảng quy đổi hệ 4 và ngưỡng
 * GPA cảnh báo học vụ thuộc app/Support/GradingScale.php — không nhân bản sang đây.
 */

return [
    'max_credits_per_term' => (int) env('ACADEMIC_MAX_CREDITS_PER_TERM', 14),
    'min_attendance_rate' => (float) env('ACADEMIC_MIN_ATTENDANCE_RATE', 80),
];
