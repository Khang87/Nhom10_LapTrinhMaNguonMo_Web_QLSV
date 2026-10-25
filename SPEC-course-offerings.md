# Spec: `course-offerings`

Nhóm yêu cầu 3 (Lớp hành chính theo khóa/khoa + lớp học phần theo kỳ + phân công giảng
viên + sĩ số & hạn mức đăng ký). Phụ thuộc: `catalog`, `person-profiles`.
Block: `StudentClass`, `Admin\ClassController`, ba bảng khoá theo `subject_id`.

## Objective

Tách hai khái niệm đang bị dồn vào một bảng `classes`:

- **Lớp hành chính** (`classes`, đã có, đổi nghĩa rõ hơn): tập sinh viên cùng khóa tuyển
  sinh, thuộc một khoa/chuyên ngành, có giảng viên chủ nhiệm. Ổn định nhiều năm.
- **Lớp học phần** (`course_offerings`, bảng mới): một môn học được mở trong một học kỳ
  cụ thể, có mã nhóm, giảng viên phụ trách, phòng học, sĩ số tối đa. Mỗi kỳ có thể mở
  nhiều nhóm cho cùng một môn.

Thành công = cùng môn "Lập trình Web" mở 2 nhóm (TN01 do giảng viên A, TN02 do giảng viên
B) trong HK1/2025-2026, mỗi nhóm giới hạn 30 sinh viên, và toàn bộ điểm/điểm danh gắn với
nhóm chứ không gắn với môn.

## Schema

### `semesters` (học kỳ)

```php
$table->string('academic_year', 20);          // "2025-2026"
$table->unsignedTinyInteger('term');          // 1 | 2 | 3
$table->string('slug', 40)->unique();          // "HK1-2025-2026" (sinh bởi accessor, lưu để tìm kiếm)
$table->date('starts_at');
$table->date('ends_at');
$table->timestamp('registration_starts_at')->nullable();  // cổng đăng ký mở
$table->timestamp('registration_ends_at')->nullable();    // cổng đăng ký đóng
$table->boolean('is_active')->default(false);
```

Đúng một `semesters.is_active = true` tại một thời điểm — buộc bằng service
`Semester::activate($id)` trong transaction, không bằng constraint.

### `classes` (lớp hành chính) — bổ sung cột

```php
$table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
$table->foreignId('major_id')->nullable()->constrained()->nullOnDelete();
$table->foreignId('adviser_id')->nullable()->constrained('users')->nullOnDelete();
$table->unsignedSmallInteger('intake_year')->nullable();   // 2023
$table->foreignId('semester_id')->nullable();              // kỳ hiện tại để mặc định TKB
```

`class_code` unique giữ nguyên. `room` trên lớp hành chính **bỏ** (duyệt 2026-10-25): phòng
thuộc về buổi học, không thuộc về tập sinh viên.

### `course_offerings` (lớp học phần)

```php
$table->foreignId('subject_id')->constrained()->cascadeOnDelete();
$table->foreignId('semester_id')->constrained('semesters')->restrictOnDelete();
$table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
$table->string('group_code', 20);              // "TN01"
$table->string('room', 50)->nullable();
$table->unsignedSmallInteger('max_students')->default(0);  // 0 = không giới hạn
$table->boolean('is_open')->default(true);     // cho phép đăng ký nếu cổng kỳ cũng mở
$table->unique(['subject_id', 'semester_id', 'group_code'], 'offering_unique');
```

`enrolled_count` **không lưu** — luôn `enrollments()->where('status','ACTIVE')->count()`
để không lệch. Controller nào cần hiển thị phải eager-load `count`.

### `enrollments` (thay `student_subject`)

```php
$table->foreignId('course_offering_id')->constrained()->cascadeOnDelete();
$table->foreignId('user_id')->constrained()->cascadeOnDelete();
$table->enum('status', ['ACTIVE', 'DROPPED'])->default('ACTIVE');
$table->timestamp('dropped_at')->nullable();
$table->unique(['course_offering_id', 'user_id'], 'enrollment_unique');
```

Giữ `id` (thay vì pivot không khoá) vì `grades` và `attendances` sẽ tham chiếu
`enrollment_id` — một bản ghi học phần là một đối tượng nghiệp vụ có vòng đời.

## Di cư dữ liệu

Theo bước 3 của `CAPABILITY_MAP.md#kế-hoạch-giữ-dữ-liệu`: một `course_offering` cho mỗi
subject cũ (`group_code = 'TN01'`, `semester_id = kỳ is_active`, `teacher_id = null`,
`max_students = 0` không giới hạn để 15 đăng ký cũ không vượt sĩ số), copy
`student_subject` → `enrollments(status ACTIVE)`, re-key `grades` sang
`(course_offering_id, user_id)` với `final_score = score`. `attendances` được re-key ở
migration của `attendance-schedule` vì nó cần bảng `attendance_sessions` tồn tại trước.
`student_subject` chỉ bị drop ở migration cuối.

## Interfaces

```php
// CourseOffering
public function seatsLeft(): int                 // max_students === 0 ? PHP_INT_MAX : max - enrolled
public function isRegistrationOpen(): bool       // is_open && semester cổng đang mở

// app/Support/OfferingAssignment
public static function assertTeacher(User $teacher): void;      // role === 'teacher'
public static function assertGroupFree(CourseOffering $o): void; // trùng (subject, semester, group)
```

`isRegistrationOpen()` là hợp đồng duy nhất mà `SPEC-enrollment.md` gọi để quyết định cổng.

## UI

- `/admin/semesters` — CRUD học kỳ, đặt kỳ_active, đặt hai mốc cổng đăng ký
  (`datetime-local`), nút "Cập nhật".
- `/admin/classes` — form hiện có thêm: khoa, chuyên ngành, GVCN (select giảng viên),
  khóa tuyển sinh. Bảng thêm cột "Khoa", "Khóa", "GVCN", "Sĩ số".
- `/admin/course-offerings` — bảng: môn, kỳ, nhóm, giảng viên, phòng, `đã đăng ký / tối đa`
  (progress bar theo `%`), cột "Trạng thái" = `Mở`/`Đóng`. Form: chọn môn → chọn kỳ →
  nhóm → giảng viên (chỉ role teacher) → phòng → sĩ số tối đa → `is_open`.
- `/teacher/my-offerings` — giảng viên chỉ thấy offering của `teacher_id = mình`, từ đây
  vào nhập điểm và điểm danh (`grading`, `attendance-schedule`).
- Xoá offering: chặn nếu đã có `enrollments` ACTIVE hoặc đã có điểm; đề nghị "Đóng lớp".

## Acceptance criteria

- [ ] Một môn mở được ≥2 nhóm trong cùng kỳ, cùng `teacher_id` khác `group_code`.
- [ ] Trùng `(subject, semester, group_code)` bị chặn bằng thông báo, không phải SQL error.
- [ ] `enrolled_count` phản ánh đúng số `ACTIVE`, và giảm ngay sau khi sinh viên hủy.
- [ ] Phân công giảng viên không cho chọn role admin/student.
- [ ] Sau migration dữ liệu: 15 enrollments / 15 grades / 16 attendances ở bảng mới,
      `student_subject` đã drop, 35 test cũ pass.

## Verify

```
php artisan test --filter=CourseOfferingTest
php artisan tinker --execute="echo App\Models\Enrollment::count(), ' ', App\Models\Grade::count(), ' ', App\Models\Attendance::count();"
```
Feature test: unique offering, seatsLeft, chặn xoá có dữ liệu, phân quyền
(admin tạo, teacher chỉ đọc lớp mình). Unit test cho `OfferingAssignment`.

## Out of scope

Khung giờ lên lớp chi tiết (`lecture_slots` thuộc `attendance-schedule`), tính học phí,
xét điều kiện mở nhóm theo số lượng tối thiểu.

## Open questions

1. `max_students = 0` nghĩa là "không giới hạn" — đồng ý, hay bắt buộc nhập sĩ số > 0?
2. Một lớp hành chính có cần auto-đăng ký sinh viên vào offering dành riêng cho lớp đó
   (đăng ký hàng loạt bởi admin)? Spec hiện không làm: sinh viên tự đăng ký qua cổng.
