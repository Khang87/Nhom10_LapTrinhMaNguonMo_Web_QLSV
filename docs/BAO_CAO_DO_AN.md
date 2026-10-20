# Báo cáo đồ án: Website quản lý sinh viên - lớp học

## 1. Giới thiệu đề tài

Đề tài xây dựng một website nhỏ hỗ trợ quản lý lớp học, môn học và sinh viên. Quản trị viên theo dõi thông tin học tập, ghi nhận điểm và điểm danh; sinh viên đăng ký môn học và tự xem kết quả của mình.

### Mục tiêu

- Áp dụng Laravel, mô hình MVC, Blade Template và Eloquent ORM.
- Thiết kế cơ sở dữ liệu quan hệ dùng SQLite để dễ cài đặt trên máy cá nhân.
- Phân quyền hai vai trò ở phía máy chủ.
- Thực hiện các luồng quản lý dữ liệu cơ bản với validate, thông báo và phân trang.
- Giữ giao diện dễ hiểu, sử dụng Bootstrap 5 và các thành phần tiêu chuẩn.

## 2. Công nghệ sử dụng

| Thành phần | Công nghệ |
|---|---|
| Backend | PHP 8.2+, Laravel 12 |
| Giao diện | Blade Template, Bootstrap 5 |
| Cơ sở dữ liệu | SQLite |
| Kiểm thử | PHPUnit thông qua `php artisan test` |

## 3. Thiết kế cơ sở dữ liệu

### Các bảng nghiệp vụ

| Bảng | Dữ liệu chính | Ghi chú |
|---|---|---|
| `classes` | `class_code`, `class_name`, `room` | Mỗi lớp có nhiều sinh viên; `class_code` được thêm để hiển thị mã lớp trên giao diện. |
| `users` | `name`, `email`, `password`, `role`, `class_id` | `role` gồm `admin` hoặc `student`; `class_id` có thể để trống. Mật khẩu được hash. |
| `subjects` | `code`, `name`, `credits` | Mã môn học duy nhất. |
| `student_subject` | `user_id`, `subject_id` | Bảng trung gian đăng ký môn; cặp sinh viên/môn học không được trùng. |
| `attendances` | `user_id`, `subject_id`, `date`, `status` | Trạng thái `present` hoặc `absent`; mỗi sinh viên có tối đa một bản ghi cho một môn trong một ngày. |
| `grades` | `user_id`, `subject_id`, `score` | Bảng bổ sung để hiện thực yêu cầu xem điểm; điểm hợp lệ từ 0 đến 10, mỗi sinh viên/môn học có một điểm hiện tại. |

Laravel đồng thời sử dụng các bảng hỗ trợ mặc định như `sessions`, `cache`, `jobs` và `password_reset_tokens`.

### Quan hệ

- `classes` 1 - N `users`: một lớp có nhiều sinh viên; sinh viên có thể chưa được xếp lớp.
- `users` N - N `subjects`: thông qua `student_subject`.
- `users` 1 - N `attendances`; `subjects` 1 - N `attendances`.
- `users` 1 - N `grades`; `subjects` 1 - N `grades`.
- Khóa ngoại nghiệp vụ dùng xóa dây chuyền. Khi xóa lớp trên giao diện, ứng dụng chuyển sinh viên về trạng thái chưa xếp lớp trước để không xóa tài khoản.

## 4. Chức năng theo vai trò

### Admin

- Đăng nhập và truy cập trang quản trị.
- Thêm, sửa, xóa, tìm kiếm và phân trang lớp học.
- Quản lý môn học và sinh viên.
- Nhập/cập nhật điểm cho sinh viên đã đăng ký môn học; điểm được kiểm tra trong khoảng 0-10.
- Điểm danh các môn sinh viên đã đăng ký theo ngày và xem tổng số có mặt/vắng.

### Student

- Đăng nhập vào trang sinh viên.
- Xem môn học đang mở, đăng ký môn và hủy môn đã đăng ký.
- Không thể đăng ký cùng một môn hai lần.
- Chỉ xem điểm thuộc tài khoản của mình.

## 5. Phân công nhóm 4 người

| Thành viên | Phần việc |
|---|---|
| Trưởng nhóm | Thiết kế cơ sở dữ liệu, đăng nhập/phân quyền, quản lý Git và tích hợp các phần. |
| Thành viên 2 | CRUD lớp học, CRUD môn học và giao diện quản trị. |
| Thành viên 3 | Quản lý sinh viên, đăng ký/hủy môn học và giao diện sinh viên. |
| Thành viên 4 | Điểm danh, quản lý điểm/báo cáo, kiểm thử và hoàn thiện tài liệu. |

## 6. Hướng dẫn chạy nhanh trên máy local

Yêu cầu PHP 8.2 trở lên, Composer và extension `pdo_sqlite`.

```powershell
composer install
Copy-Item .env.example .env
if (-not (Test-Path database\database.sqlite)) { New-Item -ItemType File database\database.sqlite | Out-Null }
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Truy cập `http://127.0.0.1:8000`. Tài khoản admin mẫu là `admin@gmail.com`, mật khẩu `12345678`; mật khẩu của năm tài khoản sinh viên mẫu cũng là `12345678`.

Khi triển khai ra Internet, cần cấu hình HTTPS, `APP_DEBUG=false` và cookie phiên `SESSION_SECURE_COOKIE=true`; tài khoản mẫu chỉ dùng để trình diễn local.

Chạy kiểm thử:

```powershell
php artisan test
```
