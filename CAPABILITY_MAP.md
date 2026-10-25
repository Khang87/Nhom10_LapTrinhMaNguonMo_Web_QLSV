# Capability Map: Hệ thống Quản lý Đào tạo Đại học (V2)

Tài liệu này là thoả thuận phạm vi đã được duyệt cho yêu cầu 8 nhóm. Mọi spec module
(`SPEC-<module-id>.md`) truy chiếu về một module id ở đây. Module id dùng kebab-case,
chọn một lần, không đổi tên giữa chừng.

## Bản đồ module

| Module id | Responsibility | Depends on |
|---|---|---|
| `identity-roles` | Tài khoản, phiên đăng nhập, ba vai trò admin/teacher/student, phân quyền | — |
| `person-profiles` | Hồ sơ sinh viên & giảng viên, phân lớp theo khóa/khoa/chuyên ngành, Import/Export Excel | `identity-roles` |
| `catalog` | Môn học, tín chỉ, môn tiên quyết/song hành, khung chương trình đào tạo theo chuyên ngành | `identity-roles` |
| `course-offerings` | Học kỳ, lớp hành chính, lớp học phần theo kỳ, phân công giảng viên, sĩ số & hạn mức | `catalog`, `person-profiles` |
| `enrollment` | Cổng đăng ký học phần theo kỳ, đăng ký/hủy, kiểm tra tiên quyết + sĩ số | `course-offerings`, `catalog` |
| `grading` | Điểm thành phần (10/20/70), điểm trung bình môn, GPA hệ 4 & hệ 10, xét lên lớp/tốt nghiệp | `enrollment` |
| `attendance-schedule` | Thời khóa biểu theo tuần/học kỳ, điểm danh trực tuyến, tỷ lệ chuyên cần | `enrollment` |
| `reporting` | Thống kê theo khoa/khóa/giới tính/tình trạng học tập, phổ điểm, xuất Excel & PDF | `grading`, `attendance-schedule`, `person-profiles` |

Build order: `identity-roles` + `catalog` → `course-offerings` → `enrollment` →
`grading` → `attendance-schedule` → `reporting`; `person-profiles` chạy song song với
`catalog` sau khi `identity-roles` xong.

## Hiện trạng (đã kiểm chứng bằng code, 2026-10-25)

| Đối tượng | Hiện trạng | Hệ quả |
|---|---|---|
| `users.role` | `enum('admin','student')` ([0001_01_01_000000_create_users_table.php:28](file:///D:/laragon/www/Nhom10_Web_QLSV/database/migrations/0001_01_01_000000_create_users_table.php#L28)) | Chưa có giảng viên |
| `AuthController::login` | `redirect()->route(role.'.dashboard')` ([AuthController.php:36](file:///D:/laragon/www/Nhom10_Web_QLSV/app/Http/Controllers/AuthController.php#L36)) | Thêm role `teacher` mà thiếu route là crash ngay khi đăng nhập |
| `grades` | một cột `decimal('score',4,2)`, unique `(user_id, subject_id)` ([2026_10_20_000001_create_academic_tables.php:37-44](file:///D:/laragon/www/Nhom10_Web_QLSV/database/migrations/2026_10_20_000001_create_academic_tables.php#L37)) | Không có điểm thành phần, không có GPA |
| `student_subject` / `attendances` / `grades` | tất cả khoá theo `(user_id, subject_id)` với `cascadeOnDelete` | Không biểu diễn được "môn học mở nhiều nhóm, nhiều kỳ, nhiều giảng viên" |
| `classes` | `class_code`, `class_name`, `room` | Không có khóa tuyển sinh, khoa, chuyên ngành, GVCN |
| `subjects` | `code`, `name`, `credits` | Không có tiên quyết/song hành, không thuộc chuyên ngành nào |
| `App\Support\Csv` | TSV phân tách bằng tab + BOM UTF-8 ([Csv.php:12](file:///D:/laragon/www/Nhom10_Web_QLSV/app/Support/Csv.php#L12)) | Mở được bằng Excel nhưng **không phải** `.xlsx` chuẩn, không import được file `.xlsx` |
| `composer.json` | chỉ `laravel/framework`, `laravel/tinker` | Chưa có thư viện Excel/PDF |

Kết luận kiến trúc: **không thể vá dần**. Chìa khoá `subject_id` trong ba bảng nghiệp vụ
phải được thay bằng `course_offering_id`. Đây là thay đổi schema, không phải thay đổi UI.

## Lược đồ đích (target schema)

Bảng mới: `departments` (khoa), `majors` (chuyên ngành), `study_plans` (khung CTĐT),
`subject_prerequisites`, `subject_concurrents`, `semesters` (học kỳ),
`course_offerings` (lớp học phần), `enrollments` (đăng ký học phần).

Bảng mới (nhóm điểm danh / thời khóa biểu): `attendance_sessions` (một buổi điểm danh),
`lecture_slots` (khung giờ lên lớp của một lớp học phần) — nguồn của thời khóa biểu.

Bảng đổi khoá: `attendances` (unique `attendance_session_id + enrollment_id`, giữ
`course_offering_id` để lọc nhanh), `grades` (unique `course_offering_id + user_id`, ba cột
điểm thành phần).

Bảng bị xoá: `student_subject` (được thay bởi `enrollments`).

Cột thêm: `users` (+`teacher_code`, `student_code`, `dob`, `gender`, `hometown`, `phone`,
`academic_status`), `classes` (+`intake_year`, `department_id`, `major_id`, `adviser_id`,
`semester_id`), `subjects` (+`major_id`, `description`, `total_sessions`).

Quy ước: giá trị enum lưu dạng `UPPER_SNAKE` (mã kỹ thuật), nhãn tiếng Việt chỉ nằm ở
mảng ánh xạ trong `app/Support/` và được hiển thị cả mã trong ô quản trị.

## Kế hoạch giữ dữ liệu

Dữ liệu dev hiện có (đo trực tiếp trên `quanly_sinh_vien`): 7 users (1 admin + 6 student),
2 classes, 3 subjects, 15 `student_subject`, 16 `attendances`, 15 `grades`.

1. **Backup bắt buộc** trước khi chạy migration:
   `mysqldump -u root quanly_sinh_vien > storage/app/backup-pre-v2.sql`
2. Migration chia 3 bước, mỗi bước một file, chạy được độc lập và có `down()`:
   - `..._create_catalog_tables.php` — thêm bảng khoa/chuyên ngành/khung CTĐT/tiên quyết,
     cộng cột vào `users`, `classes`, `subjects`. Thuần thêm, không phá dữ liệu.
   - `..._create_course_offerings.php` — tạo `semesters`, `course_offerings`, `enrollments`,
     `lecture_slots` chưa dùng.
   - `..._migrate_legacy_relations.php` — với mỗi `subject` cũ tạo một `course_offering`
     trong học kỳ `is_active`, rồi copy `student_subject` → `enrollments`,
     `grades` → `grades` (ba cột điểm: `final_score = score`, hai cột kia `null`);
     với `attendances`, gom mỗi giá trị `(subject, date)` cũ thành một `attendance_sessions`
     rồi phát sinh dòng `attendances` theo cặp `(session, enrollment)`.
     Wrap trong một transaction; **chỉ** xoá `student_subject` và cột cũ ở migration
     `..._drop_legacy_relations.php` chạy sau khi đã kiểm tra số dòng.
3. Kiểm chứng chuyển tiếp: `php artisan tinker` đếm 15/16/15 ở bảng mới, so với bảng cũ.
4. Seed (`DatabaseSeeder`) được viết lại để sinh bộ dữ liệu mẫu đầy đủ cho cả 8 module;
   `php artisan migrate:fresh --seed` là đường khôi phục nếu migration dữ liệu sai.

## Quy ước chung (dùng cho mọi spec module)

**Stack:** Laravel 12.70 · PHP 8.4.12 (`D:/laragon/bin/php/php-8.4.12-nts-Win32-vs17-x64/php.exe`)
· MySQL 8.4.3 `quanly_sinh_vien` (test: `quanly_sinh_vien_test`) · Blade + Spark Admin
layout + Bootstrap 5 · PHPUnit 11 + `RefreshDatabase`.

**Lệnh:**
```
php artisan migrate                     # áp schema
php artisan migrate:fresh --seed        # dựng lại dữ liệu mẫu
php artisan test                        # toàn bộ test (hiện: 35 passed, 226 assertions)
php artisan test --filter=GradingTest   # một file test
composer require <package>              # thêm dependency (phải xin trước)
npm test                                # test dịch vụ email tại services/password-mailer
```

**Cấu trúc:** `app/Http/Controllers/{Admin,Teacher,Student}` · `app/Models` ·
`app/Support` (logic tính toán thuần, không phụ thuộc HTTP) · `database/migrations` ·
`database/seeders` · `resources/views/{admin,teacher,student}/**` ·
`tests/Feature/<ModuleTest>.php`.

**Code style:** controller mỏng — validate trong method, gọi `app/Support/*` cho quy tắc
nghiệp vụ, model trả về quan hệ Eloquent. Validation rule tiếng Việt không bắt buộc;
message mặc định. Ví dụ chuẩn:

```php
// app/Support/GradingScale.php
final class GradingScale
{
    public const WEIGHTS = ['attendance' => 0.10, 'midterm' => 0.20, 'final' => 0.70];

    public static function subjectScore(?float $attendance, ?float $midterm, float $final): ?float
    {
        if ($attendance === null || $midterm === null) {
            return null;
        }

        return round($attendance * self::WEIGHTS['attendance']
            + $midterm * self::WEIGHTS['midterm']
            + $final * self::WEIGHTS['final'], 2);
    }
}
```

**Testing:** mỗi module có ít nhất một `tests/Feature/<ModuleId>Test.php`; rule nghiệp vụ
(tiên quyết, sĩ số, trọng số, quy đổi hệ 4, tỷ lệ chuyên cần) có test đơn vị riêng trong
`tests/Unit`. Test tính năng mới phải chạy cùng 35 test cũ, không được bỏ test cũ.

**Boundaries:**
- *Always:* chạy `php artisan test` trước khi xong việc; validate input ở boundary;
  mọi cột điểm nhập tay có `numeric` + `between:0,10`; UI theo đúng bộ nhãn dùng chung
  ("Thêm mới" + icon cộng, "Cập nhật" + icon check, một "Quay lại" góc trên phải, form
  không có nút "Hủy", thanh lọc không có nút).
- *Ask first:* thêm dependency Composer/NPM; thay đổi `down()` xoá bảng đã có dữ liệu;
  đổi cấu trúc route đang dùng ở bài báo cáo; sửa `phpunit.xml`.
- *Never:* commit secrets (`.env`, `NODEMAILER_SHARED_SECRET`); để `cascadeOnDelete` âm thầm
  xoá hồ sơ sinh viên khi xoá lớp (đã đổi thành `nullOnDelete` — duyệt 2026-10-25); chạy
  migration phá dữ liệu khi chưa có `mysqldump` backup; tắt hook; tự sáng tạo nhãn tiếng
  Việt trái với danh sách yêu cầu.

## Quyết định đã chốt (duyệt 2026-10-25)

1. **Dependency**: `phpoffice/phpspreadsheet` (đọc/ghi `.xlsx`) và `barryvdh/laravel-dompdf`
   (PDF) — **đã duyệt**, cài bằng `composer require`.
2. **Bảng quy đổi hệ 4** 5 dải (A 8.5–10/4.0 · B 7.0–8.4/3.5 · C 6.0–6.9/3.0 ·
   D 5.0–5.9/2.5 · F <5.0/0.0) kèm điểm liệt cuối kỳ <4.0 — **đã duyệt**, tập trung một chỗ
   duy nhất trong `app/Support/GradingScale.php`.
3. **Hành vi dữ liệu**: bỏ `classes.room`, đổi `users.class_id` sang `nullOnDelete` —
   **đã duyệt**.
4. **Điểm danh**: chỉ `PRESENT`/`ABSENT`, giảng viên thao tác trên web, không QR, không
   trạng thái phụ — **đã duyệt**.

## Open questions (còn lại, không chặn Phase 2)

1. `academic_status` do admin xác nhận bằng nút "Xét duyệt" (như spec đang làm) hay hệ
   thống tự đổi?
2. `student_code` nhập tay hay tự sinh `CTK<khóa><số thứ tự>`?
3. Biểu đồ phổ điểm: chỉ bảng số + thanh tỷ lệ CSS (mặc định trong spec) hay thêm Chart.js?
4. Có cần "báo cáo theo giảng viên" ngoài bốn báo cáo đã liệt kê?

