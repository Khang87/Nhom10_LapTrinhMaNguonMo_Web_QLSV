# Spec: `attendance-schedule`

Nhóm yêu cầu 7 (Thời khóa biểu theo tuần/học kỳ cho giảng viên và sinh viên; điểm danh
trực tuyến; thống kê tỷ lệ chuyên cần). Phụ thuộc: `enrollment`. Block: `Attendance`,
`Admin\AttendanceController`, `attendances` (re-key).

## Objective

Ba thứ, dùng chung một nguồn sự kiện:

- **Thời khóa biểu**: giảng viên thấy lịch dạy của mình, sinh viên thấy lịch học của mình,
  theo tuần (lưới 2–CN × tiết) và theo học kỳ.
- **Điểm danh trực tuyến**: giảng viên mở một buổi học, điểm danh lớp tại chỗ.
- **Tỷ lệ chuyên cần**: trên mỗi lớp học phần và trên mỗi sinh viên, làm đầu vào cho điểm
  chuyên cần và cảnh báo học vụ của `grading`.

Thành công = tiết Thứ 3 tuần này của lớp TN01, giảng viên mở màn hình điểm danh, chọn
Vắng cho 2 sinh viên, và tỷ lệ chuyên cần của họ giảm ngay trên chính màn hình đó.

## Schema

### `lecture_slots` — lịch lên lớp của một lớp học phần

```php
$table->foreignId('course_offering_id')->constrained()->cascadeOnDelete();
$table->unsignedTinyInteger('week_day');        // 2=Thứ 2 ... 7=Chủ nhật (ISO, bỏ 1)
$table->time('start_time');
$table->time('end_time');
$table->string('room', 50)->nullable();
$table->date('starts_on')->nullable();          // tuần bắt đầu áp dụng (null = từ đầu kỳ)
$table->date('ends_on')->nullable();
$table->unique(['course_offering_id', 'week_day', 'start_time'], 'slot_unique');
```

Một offering có thể có nhiều slot (VD Thứ 3 + Thứ 5 cùng 07:00). Không lưu "số tiết/tuần"
— suy ra từ `count()`.

### `attendance_sessions` — một buổi học diễn ra trên thực tế

```php
$table->foreignId('course_offering_id')->constrained()->cascadeOnDelete();
$table->foreignId('lecture_slot_id')->nullable()->constrained()->nullOnDelete();
$table->date('session_date');
$table->text('note')->nullable();
$table->foreignId('recorded_by')->constrained('users');           // giảng viên điểm danh
$table->unique(['course_offering_id', 'session_date'], 'session_date_unique');
```

### `attendances` — re-key, một dòng cho mỗi (buổi, sinh viên)

```php
$table->foreignId('attendance_session_id')->constrained()->cascadeOnDelete();
$table->foreignId('course_offering_id')->constrained()->cascadeOnDelete();  // redundant, để lọc nhanh
$table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
$table->enum('status', ['PRESENT', 'ABSENT']);
$table->unique(['attendance_session_id', 'enrollment_id'], 'attendance_session_student_unique');
// drop unique (user_id, subject_id, date) và cột subject_id ở migration riêng
```

`status` chỉ `PRESENT`/`ABSENT` như hệ thống hiện có — **không** tự thêm "muộn"/"có phép"
vì đó là trạng thái chưa được yêu cầu. Muốn mở rộng sau: thêm enum value, không đổi schema.

## Tỷ lệ chuyên cần (`app/Support/AttendanceRate.php`)

```php
public static function forEnrollment(Enrollment $e): array
    // ['sessions' => int, 'present' => int, 'rate' => ?float]  rate = present/sessions*100, round 1
public static function warningThreshold(): float   // 80.0 — config('academic.min_attendance_rate')
```

- Mẫu số = số buổi **đã điểm danh** của offering đó, không phải số buổi dự kiến trong kỳ:
  điểm danh chưa đủ dữ liệu thì chưa được tính vào tỷ lệ (tránh phạt sinh viên vì giảng
  viên quên điểm danh). Buổi chưa điểm danh được đếm riêng thành "chưa điểm danh (n)".
- tỷ lệ `null` khi chưa có buổi nào ⇒ UI hiển thị "Chưa có dữ liệu", không hiển thị 0%.
- `rate < 80%` ⇒ cờ cảnh báo đỏ, và `GradingScale::progression()` nhận giá trị này để gợi ý
  `AT_RISK`.

## Điểm danh trực tuyến

- `Teacher\AttendanceController` (mới) thay `Admin\AttendanceController`:
  `GET /teacher/course-offerings/{offering}/attendance` — lưới mặc định của tuần hiện tại,
  mỗi ô = một slot → nút "Điểm danh". `GET .../attendance/session/create?date=...` →
  form điểm danh nhanh (toàn lớp mặc định **Có mặt**, giảng viên chỉ việc bỏ check người
  vắng).
- Màn hình điểm danh hiển thị: họ tên + avatar + mã SV (theo quy ước định danh con người),
  checkbox "Vắng" từng dòng, ô ghi chú buổi học, "Lưu tất cả". Lưu = upsert theo
  `(session, enrollment)` ⇒ điểm danh lại buổi đó sửa chứ không nhân đôi.
- Sinh viên chỉ xem, không tự điểm danh (điểm danh "trực tuyến" = giảng viên điểm danh trên
  web, không phải sinh viên tự check-in bằng QR — xem Open questions).

## Thời khóa biểu

- `GET /student/timetable?semester=` — lưới 6 cột (Thứ 2–Thứ 7) × hàng tiết, mỗi ô hiện
  `môn · nhóm · phòng`. Chỉ sinh ra từ `lecture_slots` của các enrollment ACTIVE; slot của
  lớp đã `DROPPED` bị loại.
- `GET /teacher/timetable?semester=` — cùng lưới, nguồn là offering `teacher_id = mình`;
  ô click vào màn hình điểm danh của buổi đó.
- `GET /admin/timetable` — chọn lớp học phần để xem, và "Xuất CSV" reuse `App\Support\Csv`.
- "Theo học kỳ": filter `semester_id`, mặc định kỳ `is_active`. Xung đột lịch (một sinh
  viên hai lớp cùng slot time) phát hiện bằng `TimetableService::conflicts()` và cảnh báo
  ở trang đăng ký, không chặn cứng.

## Acceptance criteria

- [ ] Tạo 2 slot (Thứ 3 07:00, Thứ 5 07:00) cho một offering ⇒ TKB hiện đúng 2 ô.
- [ ] Điểm danh buổi Thứ 3 cho 15 sinh viên ⇒ 15 dòng `attendances`, tỷ lệ = 100%.
- [ ] Bỏ check 3 sinh viên ⇒ tỷ lệ của họ = 80%, của người khác vẫn 100%.
- [ ] Điểm danh lại cùng `(offering, session_date)` ⇒ cập nhật, số dòng không tăng.
- [ ] Chưa buổi nào ⇒ "Chưa có dữ liệu", không phải 0%.
- [ ] Giảng viên B mở màn hình điểm danh của lớp giảng viên A ⇒ 403.
- [ ] Sinh viên đã hủy lớp không còn thấy slot của lớp đó trên TKB.

## Verify

```
php artisan test --filter=AttendanceScheduleTest
php artisan test --filter=AttendanceRateTest    # unit
```
Dựng fixture: 1 offering + 2 slot + 3 enrollment, điểm danh 2 buổi (1 vắng) để kiểm tỷ lệ
75% / 100%. Kiểm tay qua browser trên `/student/timetable`.

## Out of scope

QR / GPS check-in, sinh viên xin phép vắng có duyệt, quản lý phòng học và lịch trùng phòng,
nghỉ lễ tự động loại buổi.

## Đã chốt (duyệt 2026-10-25)

Phạm vi điểm danh là **giảng viên thao tác trên web**, trạng thái chỉ `PRESENT`/`ABSENT`;
không QR check-in, không "Đi muộn"/"Vắng có phép". Hai mục này nằm trong Out of scope một
cách có chủ đích — cần mở rộng thì thêm enum value, không đổi schema.
