# Spec: `enrollment`

Nhóm yêu cầu 5 (Đăng ký học phần: mở/đóng cổng theo học kỳ; đăng ký/hủy trong thời gian
cho phép; kiểm tra tiên quyết + sĩ số tối đa). Phụ thuộc: `course-offerings`, `catalog`.
Block: `StudentSubjectController` (viết lại), `student/subjects/index.blade.php`.

## Objective

Sinh viên tự đăng ký / hủy lớp học phần trong một cửa sổ thời gian, và hệ thống từ chối
một cách **có lý do hiển thị được** mọi trường hợp không hợp lệ.

Thành công = hai sinh viên bấm "Đăng ký" cùng lúc vào nhóm còn 1 chỗ: đúng một người vào,
người kia nhận "Lớp đã đầy", không có trạng thái 2 người/1 chỗ.

## Quy tắc nghiệp vụ (duy nhất một chỗ: `app/Support/EnrollmentPolicy.php`)

`EnrollmentPolicy::check(User $student, CourseOffering $offering, array $context): array`
trả về `['ok' => bool, 'reasons' => [...]]`. Thứ tự kiểm tra và mã lỗi:

| # | Điều kiện | Thông báo tiếng Việt |
|---|---|---|
| 1 | Cổng kỳ: `now()` trong `[registration_starts_at, registration_ends_at]` và `offering.is_open` và `CourseOffering::isRegistrationOpen()` | "Cổng đăng ký đã đóng" |
| 2 | Tín chỉ: tổng `credits` đang ACTIVE + môn này ≤ `MAX_CREDITS_PER_TERM` (hằng số 14, đặt ở `config('academic.max_credits_per_term')`) | "Vượt quá số tín chỉ tối đa trong học kỳ" |
| 3 | Tiên quyết: mọi `prerequisites()` của môn phải có điểm tổng kết ≥ `PASS_SCORE` (5.0) ở một offering đã kết thúc | "Chưa học môn tiên quyết: WEB101" (liệt kê qua `PrerequisiteRules::blockingPrerequisites`) |
| 4 | Song hành: với mỗi `concurrents()`, hoặc đã có điểm đạt, hoặc phải có enrollment ACTIVE cùng kỳ | "Phải đăng ký song hành với môn: DB101" |
| 5 | Sĩ số: `seatsLeft() > 0` (`max_students = 0` ⇒ bỏ qua) | "Lớp đã đủ sĩ số" |
| 6 | Trùng: không có enrollment ACTIVE hoặc đã DROPPED trong kỳ này ở cùng offering | "Bạn đã đăng ký lớp này" |

`EnrollmentPolicy` là pure function trên dữ liệu đã load — không query trong policy — để
unit test bằng fixture mảng, không cần DB.

## Hủy đăng ký

Chỉ cho phép khi: cổng còn mở **và** lớp chưa có bản ghi `attendances` nào cho sinh viên
đó **và** chưa có `grades`. Nếu không, từ chối: "Đã có dữ liệu điểm danh/điểm, liên hệ
giảng viên". Khi hủy: `status = DROPPED`, `dropped_at = now()`, chỗ trống được hoàn lại
ngay vì `seatsLeft()` đếm theo `ACTIVE`. Điểm/điểm danh cũ **giữ lại** (không xoá) để lịch
sử học tập trung thực.

## Tính đúng đắn khi cạnh tranh chỗ

```php
DB::transaction(function () use ($student, $offering) {
    $locked = CourseOffering::whereKey($offering->id)->lockForUpdate()->firstOrFail();
    $verdict = EnrollmentPolicy::check($student, $locked, EnrollmentContext::for($student, $locked));
    abort_unless($verdict['ok'], 422, implode(' · ', $verdict['reasons']));
    $locked->enrollments()->create(['user_id' => $student->id, 'status' => 'ACTIVE']);
});
```

`lockForUpdate()` trên hàng `course_offerings` là cơ chế chống hai request cùng chiếm
chỗ cuối. Unique `(course_offering_id, user_id)` là lưới thứ hai; controller phải bắt
`QueryException` code 1062 và đổi thành thông báo "Bạn đã đăng ký lớp này".

## Interfaces / Routes

| Method | URI | Action | Quyền |
|---|---|---|---|
| `GET` | `/student/course-offerings` | `Student\CourseOfferingController@index` | student, cổng mở/đóng đều xem được |
| `POST` | `/student/course-offerings/{offering}/enroll` | `@enroll` | student |
| `DELETE` | `/student/enrollments/{enrollment}` | `@drop` | student (owns) |
| `GET` | `/admin/enrollments` | `Admin\EnrollmentController@index` | admin (đọc + export) |

Trang sinh viên hiển thị hai khu: "Học phần đang mở" (danh sách offering theo kỳ active,
kèm cột Tín chỉ / Sĩ số còn lại / trạng thái điều kiện) và "Đăng ký của tôi" (tổng tín chỉ,
tổng số tín chỉ đã tích luỹ). offering không đủ điều kiện vẫn hiện, nút "Đăng ký" disabled
và lý do in ngay dưới dòng — không ẩn đi, vì ẩn khiến người dùng nghĩ hệ thống lỗi.

Thay thế route cũ: `student.subjects.index` / `student.subjects.register` /
`student.subjects.unregister` ([routes/web.php](file:///D:/laragon/www/Nhom10_Web_QLSV/routes/web.php))
được đổi tên theo `course-offerings`/`enrollments`; giữ alias tên cũ một phiên bản nếu
báo cáo đã chụp màn hình route.

## Acceptance criteria

- [ ] Ngoài cửa sổ thời gian: mọi POST trả 422 "Cổng đăng ký đã đóng", link mờ đi.
- [ ] Đăng ký môn chưa học tiên quyết → bị chặn, thông báo nêu đúng mã môn thiếu.
- [ ] Môn song hành chưa đăng ký cùng kỳ → bị chặn.
- [ ] Nhóm `max_students = 2` với 2 sinh viên: người thứ 3 nhận "Lớp đã đủ sĩ số".
- [ ] Hủy → `seatsLeft()` tăng 1 ngay; dữ liệu điểm danh đã có → từ chối hủy.
- [ ] UniqueDB không cho hai enrollment ACTIVE trùng `(offering, user)` kể cả 2 request song song.
- [ ] Trang "Đăng ký của tôi" hiện đúng tổng tín chỉ đang học và tổng tín chỉ tích lũy.

## Verify

```
php artisan test --filter=EnrollmentTest
php artisan test --filter=EnrollmentPolicyTest   # unit, không DB
```
`tests/Unit/EnrollmentPolicyTest.php` phủ 6 nhánh quy tắc bằng fixture mảng.
`tests/Feature/EnrollmentTest.php` phủ cổng thời gian (`travel()`), route, ownership,
thông báo 422. Kiểm tay: `/student/course-offerings` với 2 user, 1 chỗ còn lại.

## Out of scope

Đăng ký theo lượt ưu tiên (đăng ký sớm), xử lý đơn xin đăng ký ngoài cổng có duyệt, tính học
phí tự động.

## Open questions

1. `MAX_CREDITS_PER_TERM = 14` là con số tự chọn cho demo — có số thật theo quy chế thì
   thay mỗi `config/academic.php` (schema không đổi).
2. Ngoài cổng đăng ký, admin có được đăng ký hộ sinh viên không? Spec hiện **có**
   (`Admin\EnrollmentController@store` bỏ qua bước kiểm tra cổng, vẫn kiểm sĩ số và
   tiên quyết), ghi `enrolled_by` để truy vết.
