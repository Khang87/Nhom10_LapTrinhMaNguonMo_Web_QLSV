# Kế hoạch triển khai V2 — 8 module quản lý đào tạo

Spec: [CAPABILITY_MAP.md](../CAPABILITY_MAP.md) + 8 file `SPEC-*.md`.
Quyết định đã chốt 2026-10-25: phpspreadsheet + dompdf, bảng quy đổi hệ 4 5 dải + điểm liệt
4.0, bỏ `classes.room`, `users.class_id` → `nullOnDelete`, điểm danh chỉ `PRESENT`/`ABSENT`.

## Nguyên tắc thực hiện

1. **Schema đi trước UI.** Migration chia ba nhóm: *additive* (thêm bảng/cột, không phá gì)
   → *di cư dữ liệu* → *drop legacy*. Không có migration nào vừa tạo vừa xoá.
2. **Mỗi wave kết thúc xanh.** `php artisan test` phải pass ở cuối mỗi wave, cộng với kiểm
   tay màn hình mới. Không để repo ở trạng thái route 500 giữa hai wave.
3. **Không phá phần đã báo cáo.** Hai route `grades/export` và `attendances/export` giữ
   hành vi CSV cũ (test trong `ExtendedRequirementsTest` là hàng rào hồi quy); Excel/PDF là
   `format=` thêm vào, không phải thay thế.
4. **Logic nghiệp vụ thuần nằm ở `app/Support`.** Tiên quyết, trọng số, quy đổi, tỷ lệ
   chuyên cần, bộ lọc báo cáo — tất cả unit-test được không cần DB.
5. **UI theo bộ nhãn dùng chung** đã ghi trong memory dự án: "Thêm mới" + icon cộng,
   "Cập nhật" + icon check, một "Quay lại" góc trên phải, footer form không "Hủy", thanh lọc
   không nút.

## Thứ tự wave và lý do

| Wave | Module | Vì sao ở vị trí này |
|---|---|---|
| 0 | chuẩn bị | dependency + backup + `config/academic.php` phải có trước mọi thứ |
| 1 | `identity-roles` | `teacher` là tiền đề của mọi route `/teacher`; sửa `AuthController` sớm nhất để không crash |
| 2 | `catalog` | khoa / chuyên ngành / môn / tiên quyết là bảng tra mà mọi module khác join vào |
| 3 | `person-profiles` | hồ sơ + `academic_status` cần catalog để phân loại; Import Excel tách được nên đặt sau |
| 4 | `course-offerings` + di cư dữ liệu | **điểm không thể quay lại**: re-key `(user, subject)` → `(offering, user)` |
| 5 | `enrollment` | cần offering + tiên quyết; là cổng duy nhất sinh viên thao tác |
| 6 | `grading` | cần enrollment hợp lệ làm khoá ngoại |
| 7 | `attendance-schedule` | điểm danh cần `enrollment_id`; TKB đọc từ cùng offering |
| 8 | `reporting` | tổng hợp từ grading + attendance, làm cuối để số liệu đã ổn định |
| 9 | tài liệu | cập nhật ERD / Use Case / docx / README sau khi schema chốt, vẽ một lần |

Wave 2 và 3 **chồng được nhau** (khác bảng); wave 6 và 7 độc lập một phần nhưng vẫn làm tuần
tự vì `grading` lấy tỷ lệ chuyên cần làm ngưỡng cảnh báo học vụ.

## Rủi ro và cách giảm thiểu

| Rủi ro | Ảnh hưởng | Giảm thiểu |
|---|---|---|
| `ALTER TABLE ... MODIFY role ENUM` trên MySQL 8 | Nếu enum cũ bị thu hẹp, dữ liệu thành `''` | Migration chỉ **thêm** giá trị; test trên `quanly_sinh_vien_test` trước, rồi chạy `SELECT role, COUNT(*)` trên DB dev |
| Mất 15 điểm / 16 điểm danh khi re-key | Mối demo hỏng, ảnh hưởng báo cáo | `mysqldump` ở wave 0 + migration di cư chạy trong transaction + assert số dòng bằng `artisan tinker` trước khi drop |
| Hai request cùng chiếm chỗ cuối | Vượt sĩ số | `lockForUpdate()` + unique DB + bắt `QueryException` 1062 |
| Dompdf render dấu tiếng Việt sai | PDF nộp không dùng được | nhúng font DejaVu Sans vào `public/fonts`, kiểm tay một file PDF có dấu ngay ở wave 8, không chờ cuối kỳ |
| `AuthController` redirect theo role | 500 khi đăng nhập bằng teacher | bảng ánh xạ tường minh `DASHBOARDS` (wave 1), test phủ cả ba role |
| 35 test cũ giả định schema `(user_id, subject_id)` | Phải viết lại hàng loạt | sửa test **cùng wave** với migration, không dồn về cuối; `StudentManagementTest` là nơi chịu ảnh hưởng nặng nhất |
| phpoffice/phpspreadsheet kéo theo dependency lớn | `composer install` chậm trên Laragon | cài một lần ở wave 0, commit `composer.lock`, không nâng version giữa chừng |

## Điểm kiểm chứng giữa đường

- **Sau wave 1**: login bằng admin / teacher / student mỗi loại vào đúng dashboard; 403 chéo
  vai trò. `php artisan test` = 35 pass + test mới.
- **Sau wave 4** *(quan trọng nhất)*: `Enrollment::count() === 15`, `Grade::count() === 15`,
  `Attendance::count() === 16`; bảng `student_subject` không còn; trang `/admin/students` và
  `/admin/grades` vẫn render.
- **Sau wave 6**: bộ ba điểm mẫu CC=8 GK=7 CK=6 ⇒ 6.4 ⇒ chữ C ⇒ hệ 4 = 3.0; GPA hai môn
  (3 TC/4.0 + 2 TC/3.0) = 3.6.
- **Sau wave 8**: mở file `.xlsx` và `.pdf` xuất ra bằng Excel và trình đọc PDF thật.
- **Sau wave 9**: `python docs/diagrams/make_diagrams.py` chạy lại, `docs/BAO_CAO_NHOM_QLSV.docx`
  khớp schema mới (thêm `course_offerings`, `enrollments`, `semesters` vào ERD).

## Việc có thể làm song song

- Trong một wave: migration + model (một người) ∥ view + JS (người khác), khớp nhau ở
  interface `Reports`/`EnrollmentPolicy` đã khai báo trong spec.
- `tests/Unit` cho `GradingScale`, `EnrollmentPolicy`, `AttendanceRate` viết **trước**
  controller (red-green), vì con số kiểm chứng đã cố định trong spec.
- Seed dữ liệu mẫu cho giảng viên / khoa / học kỳ tách được khỏi luồng UI.
