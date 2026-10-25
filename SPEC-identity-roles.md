# Spec: `identity-roles`

Nhóm yêu cầu 1 (Phân quyền). Phụ thuộc: không. Block: `AuthController::login`,
`CheckRole`, `routes/web.php`, `layouts/app.blade.php` (nav).

## Objective

Thêm vai trò **Giảng viên** vào hệ thống và tách rõ ba vùng làm việc:

- **Admin** — quản lý toàn hệ thống: tài khoản (admin, giảng viên, sinh viên), khoa,
  chuyên ngành, môn học, khung chương trình đào tạo, học kỳ, lớp hành chính, lớp học
  phần, thống kê – báo cáo.
- **Giảng viên** — nhập điểm và điểm danh cho các lớp học phần được phân công; xem thời
  khóa biểu của mình; chỉ thấy lớp mình phụ trách.
- **Sinh viên** — xem thời khóa biểu, điểm, lịch sử học tập; đăng ký / hủy học phần.

Thành công = một giảng viên đăng nhập được, vào đúng dashboard, chỉ thấy lớp của mình,
và sinh viên/admin không vào được route giảng viên (403).

## Schema

`users.role`: `enum('admin','student')` → `enum('admin','teacher','student')`, default
`student`.ALTER qua migration `2026_11_01_000001_add_teacher_role_to_users_table.php`
(vì MySQL không sửa enum ẩn định, migration phải `->change()` với định nghĩa đầy đủ).

Không thêm package quyền. `App\Http\Middleware\CheckRole` giữ nguyên cơ chế
([CheckRole.php:11](file:///D:/laragon/www/Nhom10_Web_QLSV/app/Http/Middleware/CheckRole.php#L11)),
chỉ cần gọi `->middleware('checkRole:teacher')`. Lý do: ba vai trò là đường thẳng,
SPatie/permission là chi phí không mua lại được cái gì.

## Routes & interfaces

```php
Route::prefix('teacher')->name('teacher.')->middleware('checkRole:teacher')->group(...);
```

| Route | Name | Controller |
|---|---|---|
| `GET /teacher` | `teacher.dashboard` | `Teacher\DashboardController@index` |
| `GET /admin/accounts` | `admin.accounts.index` | `Admin\AccountController@index` |
| `GET /admin/accounts/create` … | `admin.accounts.*` | `Admin\AccountController` (resource) |

`AuthController::login` đổi từ `role.'.dashboard'` sang bảng ánh xạ tường minh để
missing route không còn là lỗi 500:

```php
private const DASHBOARDS = [
    'admin'   => 'admin.dashboard',
    'teacher' => 'teacher.dashboard',
    'student' => 'student.dashboard',
];
```

`layouts/app.blade.php`: nav render theo `auth()->user()->role` (đoạn `@unless` đang loại
trang login/forgot giữ nguyên). Dashboard admin thêm ô "Quản lý tài khoản".

## Quản lý tài khoản (Admin)

`Admin\AccountController` CRUD trên `users`, một form dùng chung ba vai trò:

- role bắt buộc `required|in:admin,teacher,student`.
- `student_code` chỉ hiện khi role = student; `teacher_code` chỉ hiện khi role = teacher;
  `class_id` chỉ hiện khi role = student. Validate server-side: null hoá cột không thuộc
  vai trò được chọn, không dựa vào việc field bị ẩn.
- Mật khẩu: `required` khi tạo, `nullable` khi sửa (để nguyên nếu bỏ trống) — giữ đúng
  quy tắc đang có ở `Admin/StudentController.php:111-130`.
- Xoá tài khoản có dữ liệu nghiệp vụ: chặn với sinh viên đã có `enrollments`/`grades`
  (báo lỗi, đề nghị đổi `academic_status` sang `DROPPED`). `cascadeOnDelete` hiện tại sẽ
  xoá âm thầm toàn bộ điểm — chấp nhận được cho dữ liệu mẫu nhưng phải có cảnh báo xác nhận.

## UI consistency

Áp dụng bộ nhãn dùng chung: create = "Thêm mới" + icon cộng, edit = "Cập nhật" + icon
check, một "Quay lại" góc trên phải, footer form không có "Hủy", thanh lọc không có nút.
Bảng tài khoản có cột "Vai trò" hiển thị cả mã (`admin` / `teacher` / `student`) và nhãn
tiếng Việt, tìm kiếm theo tên/email/code giữ nguyên cơ chế AJAX fragment đã có.

## Acceptance criteria

- [ ] Đăng nhập bằng tài khoản `teacher` không còn ném `RouteNotFoundException`.
- [ ] `checkRole:teacher` trả 403 với admin và student; `checkRole:admin` trả 403 với teacher.
- [ ] Admin tạo được giảng viên, sửa được role, đổi được mật khẩu không cần mật khẩu cũ.
- [ ] Không tạo được hai tài khoản trùng email (ràng buộc unique sẵn có của `users.email` vẫn còn hiệu lực).
- [ ] 35 test cũ (`php artisan test`) vẫn pass nguyên.

## Verify

```
php artisan make:migration add_teacher_role_to_users_table   # rồi sửa ->change()
php artisan migrate
php artisan test --filter=IdentityRolesTest
```
Test mới: `tests/Feature/IdentityRolesTest.php` — redirect theo role, 403 chéo vai trò,
CRUD account, validate theo role. Kiểm tay: login `admin@gmail.com` / `12345678` mở
`/admin/accounts`, tạo 2 giảng viên `giangvien1@gmail.com` `12345678`, login bằng giảng
viên → `/teacher`.

## Out of scope

Tự động ghi danh giảng viên vào lớp học phần (thuộc `course-offerings`), log đăng nhập,
2FA, soft delete.

## Open questions

1. Có cần trang "đổi mật khẩu của chính mình" cho giảng viên/sinh viên không? (đặt lại
   bằng OTP đã có sẵn cho mọi email.)
2. `teacher_code` (mã giảng viên) có thật sự cần, hay email là định danh đủ? Spec đang
   giả định **có** vì cột này được dùng làm nhãn trong phân công giảng viên.
