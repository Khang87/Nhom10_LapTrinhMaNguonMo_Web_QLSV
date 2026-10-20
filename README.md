# Website quản lý sinh viên - lớp học

Đồ án môn học được xây dựng bằng Laravel 12, PHP 8.2 trở lên, SQLite, Blade và Bootstrap 5. Các trang Admin và Student dùng layout Spark Admin với menu riêng theo vai trò; trang đăng nhập giữ giao diện riêng.

## Chức năng

- Đăng nhập, đăng xuất và chuyển trang theo vai trò `admin` hoặc `student`.
- Admin quản lý lớp học, môn học, tài khoản sinh viên; ghi nhận điểm và điểm danh.
- Student xem các môn học, đăng ký/hủy đăng ký và xem điểm.
- Tìm kiếm, phân trang danh sách lớp, môn học và sinh viên.
- Dữ liệu demo có một tài khoản admin, hai lớp, ba môn và năm sinh viên.

## Chạy nhanh trên Windows

Mở PowerShell tại thư mục dự án:

```powershell
composer install
Copy-Item .env.example .env
if (-not (Test-Path database\database.sqlite)) { New-Item -ItemType File database\database.sqlite | Out-Null }
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Mở `http://127.0.0.1:8000`.

Nếu đã có file `.env`, không cần chép đè. Cấu hình mặc định dùng SQLite tại `database/database.sqlite`; các giá trị `DB_HOST`, `DB_PORT`, `DB_USERNAME` và `DB_PASSWORD` không cần điền. Admin dùng bản Bootstrap và Bootstrap Icons đi kèm tại `public/vendor/spark-admin`; các trang đăng nhập và Student tải Bootstrap 5 từ CDN nên cần kết nối Internet để hiển thị CSS và menu thu gọn.

Khi triển khai, đặt `APP_ENV=production`, `APP_DEBUG=false`, dùng HTTPS và bảo đảm `SESSION_SECURE_COOKIE=true`.

### Tài khoản mẫu

| Vai trò | Email | Mật khẩu |
|---|---|---|
| Admin | `admin@gmail.com` | `12345678` |
| Student | `sinhvien1@gmail.com` đến `sinhvien5@gmail.com` | `12345678` |

Các mật khẩu chỉ dành cho môi trường trình diễn local. Hãy đổi chúng nếu triển khai ra môi trường dùng thật.

## Kiểm thử

```powershell
php artisan test
```

Kiểm tra cú pháp PHP:

```powershell
Get-ChildItem app,database,routes,tests -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
```

## Tài liệu báo cáo

Nội dung giới thiệu đề tài, thiết kế cơ sở dữ liệu, chức năng theo vai trò, phân công nhóm và hướng dẫn chạy được trình bày tại [docs/BAO_CAO_DO_AN.md](docs/BAO_CAO_DO_AN.md).
