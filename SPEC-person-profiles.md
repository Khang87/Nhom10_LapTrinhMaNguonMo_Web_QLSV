# Spec: `person-profiles`

Nhóm yêu cầu 2 (Quản lý sinh viên: CRUD + hồ sơ chi tiết + phân loại + Import/Export Excel).
Phụ thuộc: `identity-roles`, `catalog` (khoa/chuyên ngành để phân loại). Block:
`Admin\StudentController`, `users`.

## Objective

Mỗi sinh viên là một hồ sơ đủ để in ra phiếu, và mỗi giảng viên đủ để liên hệ:

- Định danh: **mã sinh viên** (`student_code`) / **mã giảng viên** (`teacher_code`).
- Cá nhân: họ tên (`name`), ngày sinh (`dob`), giới tính (`gender`), quê quán
  (`hometown`), email (`email`), số điện thoại (`phone`).
- Phân loại: lớp hành chính (`class_id`) → khóa tuyển sinh (`classes.intake_year`),
  khoa (`classes.department_id`), chuyên ngành (`classes.major_id`).
- Tình trạng học tập: `academic_status` (xem dưới).
- Import/Export Excel danh sách sinh viên.

Thành công = upload một file `.xlsx` 100 dòng hợp lệ, hệ thống tạo đúng 100 sinh viên,
dòng trùng mã sinh viên được cập nhật thay vì tạo bản sao, và file export mở lại được
bằng Excel với tiếng Việt không lỗi font.

## Schema

Cột thêm trên `users` (migration `2026_11_01_000002_add_profile_fields_to_users_table.php`),
tất cả `nullable` để không phá 7 dòng đang có:

```php
$table->string('student_code', 20)->nullable()->unique();
$table->string('teacher_code', 20)->nullable()->unique();
$table->date('dob')->nullable();
$table->enum('gender', ['MALE', 'FEMALE', 'OTHER'])->nullable();
$table->string('hometown', 200)->nullable();
$table->string('phone', 20)->nullable();
$table->enum('academic_status', [
    'STUDYING', 'AT_RISK', 'SUSPENDED', 'GRADUATED', 'DROPPED',
])->default('STUDYING');
```

`class_id` hiện đang `cascadeOnDelete` ([0001_01_01_000000:29](file:///D:/laragon/www/Nhom10_Web_QLSV/database/migrations/0001_01_01_000000_create_users_table.php#L29)):
đổi sang `nullOnDelete` trong migration này (duyệt 2026-10-25). Xoá một lớp hành chính không
được phép xoá âm thầm hồ sơ sinh viên.

`gender`/`academic_status` lưu mã UPPER_SNAKE; ánh xạ nhãn tiếng Việt đặt ở
`app/Support/PersonLabels.php` (`MALE => 'Nam'`, `STUDYING => 'Đang học'`, …) và bộ lọc
chỉ liệt kê giá trị thực tế đang tồn tại trong dữ liệu.

## Validation

```php
'student_code' => ['nullable', 'string', 'max:20', Rule::unique('users', 'student_code')->ignore($user)],
'dob'         => ['nullable', 'date', 'before:'.now()->subYears(15)->toDateString()],
'gender'      => ['nullable', Rule::in(array_keys(PersonLabels::GENDERS))],
'phone'       => ['nullable', 'regex:/^0[0-9]{8,10}$/'],
'hometown'    => ['nullable', 'string', 'max:200'],
```

Mặc định mật khẩu khi import: `12345678` như bộ seed hiện tại và ghi rõ trên
mẫu Excel — không sinh mật khẩu ngẫu nhiên vì đồ án cần đăng nhập được ngay.

## Import / Export Excel

**Dependency đã duyệt ngày 2026-10-25**: `composer require phpoffice/phpspreadsheet`.
Lý do: `.xlsx` upload bắt buộc phải đọc bằng thư viện OOXML; TSV-as-CSV
([Csv.php:12](file:///D:/laragon/www/Nhom10_Web_QLSV/app/Support/Csv.php#L12)) chỉ đủ cho
hướng export.

Hai service thuần, không phụ thuộc HTTP, đặt ở `app/Support/`:

```php
StudentImport::parseUploadedFile(UploadedFile $file): Collection   // -> rows
StudentImport::persist(Collection $rows): ImportResult             // created/updated/skipped
StudentExport::toXlsx(Collection $students, string $path): void
```

Quy tắc import:
1. Đọc sheet đầu tiên, dòng 1 là hàng tiêu đề. Ánh xạ cột cố định theo mẫu tải xuống
   (Mã SV, Họ tên, Email, Ngày sinh, Giới tính, Quê quán, SĐT, Mã lớp, Tình trạng).
2. Dòng thiếu `Email` hoặc `Họ tên` → skip kèm lý do, liệt kê trong báo cáo kết quả.
3. Trùng `student_code` → **cập nhật** hồ sơ (không đụng `password`), trùng `email` mà
   khác `student_code` → skip (ràng buộc unique của `users.email`).
4. `Mã lớp` không tồn tại → tạo `classes` mới hay skip? Spec chọn **skip kèm lý do**:
   import sinh viên không được âm thầm sinh ra lớp hành chính.
5. Toàn bộ chạy trong một transaction, giới hạn 500 dòng/lần upload,
   `25MB` max, chỉ chấp nhận `.xlsx`.

Mẫu Excel tải xuống do cùng thư viện sinh ra, có sẵn 2 dòng ví dụ — tránh tình huống
người dùng tự đoán tên cột.

## UI

- `/admin/students` (đã có): giữ tìm kiếm AJAX, thêm bộ lọc **khóa tuyển sinh**,
  **khoa**, **chuyên ngành**, **tình trạng học tập** (chỉ là select, thanh lọc không nút).
- Trang chi tiết `/admin/students/{user}`: hồ sơ + avatar (đã có) + khối "Lớp học phần đã
  đăng ký", "Kết quả học tập", "Tỷ lệ chuyên cần" — đọc từ `enrollment`/`grading`,
  route này là đích của ô "Xem" trong bảng.
- Form create/edit: bỏ các field không thuộc role student (kế thừa quy tắc
  `SPEC-identity-roles.md`), thêm ngày sinh/giới tính/quê quán/SĐT/mã SV.
- Header trang sinh viên: hai nút "Import Excel" và "Xuất Excel" cạnh "Thêm mới".
  Panel import: tải mẫu, chọn file, xem kết quả `created / updated / skipped + lý do`.

## Acceptance criteria

- [ ] CRUD sinh viên đầy đủ các field hồ sơ, dữ liệu hiển thị đúng sau khi lưu.
- [ ] Bộ lọc theo khóa/khoa/chuyên ngành/tình trạng trả về đúng tập hợp (test by fixture).
- [ ] Import 100 dòng hợp lệ → 100 user; chạy lại file đó → 0 created, 100 updated.
- [ ] Dòng trùng email khác mã SV bị skip và hiện trong báo cáo, không nổ 500.
- [ ] File export mở bằng Excel: đúng BOM/UTF-8, ngày sinh dạng dd/MM/yyyy, số điện thoại
  không bị mất số 0 đầu.
- [ ] Xoá lớp hành chính không xoá sinh viên (`nullOnDelete`).

## Verify

```
composer require phpoffice/phpspreadsheet
php artisan test --filter=PersonProfileTest
```
Fixture: `tests/Fixtures/students_import.xlsx` tạo bằng một script one-shot trong
`tests/Fixtures/make_students_xlsx.php` (chạy tay khi cần đổi fixture). Kiểm tay:
upload qua UI `/admin/students/import`.

## Out of scope

Import điểm (thuộc `grading`), cổng thông tin phụ huynh, OCR giấy tờ.

## Open questions

1. `student_code` do admin nhập tay hay hệ thống sinh tự động theo cấu trúc
   `CTK<khóa><số thứ tự>`? Spec đang để nhập tay + gợi ý tự sinh khi trống.
2. Tình trạng học tập: admin tự đổi, hay chỉ đổi bằng nút "Xét duyệt" trong `grading`?
