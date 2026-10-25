# Website quản lý sinh viên - lớp học

Đồ án môn học được xây dựng bằng Laravel 12, PHP 8.2 trở lên, MySQL 8, Blade và Bootstrap 5. Các trang Admin và Student dùng layout Spark Admin với menu riêng theo vai trò; trang đăng nhập giữ giao diện riêng.

## Chức năng

- Đăng nhập, đăng xuất và chuyển trang theo vai trò `admin` hoặc `student`.
- Khôi phục mật khẩu qua mã OTP 6 chữ số gửi bằng Node.js/Nodemailer; mã hết hạn sau 15 phút và chỉ dùng một lần.
- Admin quản lý lớp học, môn học, tài khoản sinh viên; ghi nhận điểm và điểm danh.
- Student xem các môn học, đăng ký/hủy đăng ký và xem điểm.
- Tìm kiếm, phân trang danh sách lớp, môn học và sinh viên.
- Dữ liệu demo có một tài khoản admin, hai lớp, ba môn và năm sinh viên.

## Chạy nhanh trên Windows

Cần một MySQL 8 server đang chạy (Laragon: bật MySQL từ menu, hoặc XAMPP/WAMP). Tạo hai cơ sở dữ liệu:

```powershell
mysql -u root -e "CREATE DATABASE quanly_sinh_vien CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE DATABASE quanly_sinh_vien_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Sau đó mở PowerShell tại thư mục dự án:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Mở `http://127.0.0.1:8000`.

Nếu đã có file `.env`, không cần chép đè. Cấu hình mặc định dùng MySQL tại `127.0.0.1:3306`, database `quanly_sinh_vien`, người dùng `root` với mật khẩu trống (mặc định của Laragon); đổi `DB_USERNAME`/`DB_PASSWORD` nếu máy bạn khác. Admin dùng bản Bootstrap và Bootstrap Icons đi kèm tại `public/vendor/spark-admin`; các trang đăng nhập và Student tải Bootstrap 5 từ CDN nên cần kết nối Internet để hiển thị CSS và menu thu gọn.

### Chuyển dữ liệu từ SQLite cũ sang MySQL

Ai còn giữ `database/database.sqlite` từ phiên bản trước có thể chuyển toàn bộ dữ liệu sang MySQL bằng:

```powershell
php database/scripts/migrate_sqlite_to_mysql.php
```

Script chạy một lần, sao chép các bảng nghiệp vụ và `password_reset_tokens`, `sessions` sang database MySQL hiện tại của `.env` (xóa dữ liệu cũ trong các bảng đó trước khi chèn).

Khi triển khai, đặt `APP_ENV=production`, `APP_DEBUG=false`, dùng HTTPS và bảo đảm `SESSION_SECURE_COOKIE=true`.

### Cấu hình gửi email đặt lại mật khẩu

Luồng khôi phục mật khẩu gồm hai bước: yêu cầu OTP 6 chữ số, xác thực OTP rồi đặt mật khẩu mới. Laravel lưu hash OTP có thời hạn 15 phút, giới hạn 5 lần nhập sai và cooldown gửi lại 60 giây. Dịch vụ Node.js/Nodemailer gửi email qua SMTP; cần Node.js 20 trở lên và thông tin SMTP hợp lệ. Không commit các giá trị bí mật.

1. Tạo một chuỗi bí mật dùng chung và đặt cùng giá trị vào `NODEMAILER_SHARED_SECRET` trong `.env`:

   ```powershell
   php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
   ```

2. Với Gmail, bật xác minh 2 bước cho tài khoản Google rồi tạo **Mật khẩu ứng dụng** trong phần Bảo mật tài khoản Google. Không dùng mật khẩu Google thông thường. Điền cùng địa chỉ Gmail vào `SMTP_USER` và `MAIL_FROM_ADDRESS`, dán mật khẩu ứng dụng vào `SMTP_PASSWORD` (không có khoảng trắng), và giữ `SMTP_HOST=smtp.gmail.com`, `SMTP_PORT=587`, `SMTP_SECURE=false`. Dịch vụ dùng STARTTLS trên cổng 587.
3. Đặt `APP_URL` bằng địa chỉ mà người nhận sẽ mở. Mặc định local trong `.env.example` là `http://127.0.0.1:8000`; khi chạy thật, đổi thành domain HTTPS của ứng dụng.
4. Cài và chạy dịch vụ gửi mail:

   ```powershell
   Set-Location services\password-mailer
   npm ci --ignore-scripts
   .\install-startup-task.ps1
   ```

   Script đăng ký Scheduled Task cho tài khoản Windows hiện tại, khởi chạy ẩn khi đăng nhập và thử chạy ngay. Node.js cần nằm trong `PATH`; script dùng đường dẫn Node tìm được lúc cài đặt. Dịch vụ tự thử khởi động lại tối đa 3 lần nếu thoát với lỗi. Để gỡ cấu hình tự khởi động:

   ```powershell
   Unregister-ScheduledTask -TaskName Nhom10-PasswordMailer -Confirm:$false
   ```

5. Chạy Laravel như hướng dẫn phía trên. Dịch vụ Nodemailer chỉ bind vào `127.0.0.1:3001`; Laravel ký OTP bằng HMAC trước khi gửi sang dịch vụ. Log dịch vụ nằm tại `storage\logs\password-mailer-startup.log`.

Có thể chạy test riêng cho dịch vụ email bằng `npm test` tại `services\password-mailer`. Nếu người dùng không nhận email, kiểm tra log của hai tiến trình và cấu hình SMTP; phản hồi của form luôn giống nhau cho email có/không có tài khoản để tránh dò email.

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

Bộ test dùng database MySQL riêng `quanly_sinh_vien_test` (khai báo trong `phpunit.xml`) và tự tạo lại bảng qua `RefreshDatabase`, nên không ảnh hưởng dữ liệu trình diễn.

Kiểm tra cú pháp PHP:

```powershell
Get-ChildItem app,database,routes,tests -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
```

## Tài liệu báo cáo

Nội dung giới thiệu đề tài, thiết kế cơ sở dữ liệu, chức năng theo vai trò, phân công nhóm và hướng dẫn chạy được trình bày tại [docs/BAO_CAO_DO_AN.md](docs/BAO_CAO_DO_AN.md).
