# Todo — V2 quản lý đào tạo (8 module)

Ký hiệu: `[ ]` chưa làm · `[~]` đang làm · `[x]` xong + verify.
Quy ước branch/commit: một commit cho mỗi task, message tiếng Việt theo module.

## Wave 0 — Chuẩn bị

- [ ] 0.1 Cài dependency Excel/PDF
  - Acceptance: `composer show` có `phpoffice/phpspreadsheet` và `barryvdh/laravel-dompdf`;
    `composer.lock` được commit.
  - Verify: `php -r "require 'vendor/autoload.php'; echo class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class) ? 'ok' : 'fail';"` in `ok`.
  - Files: `composer.json`, `composer.lock`

- [ ] 0.2 Backup DB dev trước migration
  - Acceptance: `storage/app/backup-pre-v2.sql` tồn tại, không vào git.
  - Verify: `mysqldump -u root quanly_sinh_vien > storage/app/backup-pre-v2.sql && wc -l` > 100 dòng.
  - Files: `.gitignore`

- [ ] 0.3 Tạo `config/academic.php`
  - Acceptance: chứa `pass_score=5.0`, `fail_floor=4.0`, `max_credits_per_term=14`,
    `min_attendance_rate=80`, `weights=[10,20,70]`.
  - Verify: `php artisan tinker --execute="echo config('academic.pass_score');"` in `5`.
  - Files: `config/academic.php`

## Wave 1 — `identity-roles`

- [ ] 1.1 Migration mở rộng enum `users.role` thêm `teacher`
  - Acceptance: `SHOW COLUMNS FROM users LIKE 'role'` liệt kê `admin,teacher,student`; 7 dòng
    cũ giữ nguyên role.
  - Verify: `php artisan migrate` + `tests/Feature/IdentityRolesTest.php::test_existing_roles_survive`.
  - Files: `database/migrations/*_add_teacher_role_to_users_table.php`

- [ ] 1.2 Sửa redirect theo role bằng bảng ánh xạ
  - Acceptance: login teacher → `/teacher`, không `RouteNotFoundException`.
  - Verify: `php artisan test --filter=IdentityRolesTest`.
  - Files: `app/Http/Controllers/AuthController.php`

- [ ] 1.3 Nhóm route + middleware `checkRole:teacher`
  - Acceptance: admin/student truy cập `/teacher/*` → 403; teacher truy cập `/admin/*` → 403.
  - Verify: `php artisan test --filter=IdentityRolesTest`.
  - Files: `routes/web.php`, `app/Http/Controllers/Teacher/DashboardController.php`

- [ ] 1.4 CRUD tài khoản cho admin (ba role, validate theo role)
  - Acceptance: tạo được giảng viên, sửa role, đổi mật khẩu tuỳ chọn; field không thuộc role bị
    null hoá server-side.
  - Verify: feature test tạo/sửa/xoá + kiểm tay `/admin/accounts`.
  - Files: `app/Http/Controllers/Admin/AccountController.php`, `resources/views/admin/accounts/*`

- [ ] 1.5 Nav + layout theo role
  - Acceptance: sidebar hiện mục riêng cho teacher (Lớp của tôi, Điểm danh, TKB).
  - Verify: kiểm tay + `spark layout is role specific` test cũ vẫn pass.
  - Files: `resources/views/layouts/spark.blade.php`

## Wave 2 — `catalog`

- [ ] 2.1 Migration `departments` + `majors`
  - Acceptance: xoá khoa còn chuyên ngành bị chặn (`restrictOnDelete`).
  - Verify: `php artisan test --filter=CatalogTest`.
  - Files: `database/migrations/*_create_catalog_tables.php`, `app/Models/{Department,Major}.php`

- [ ] 2.2 Cột mới trên `subjects` (`major_id`, `description`, `total_sessions`)
  - Acceptance: form môn học chọn được chuyên ngành; dữ liệu 3 môn cũ không mất.
  - Verify: `php artisan test --filter=CatalogTest`.
  - Files: migration, `app/Http/Controllers/Admin/SubjectController.php`, `resources/views/admin/subjects/*`

- [ ] 2.3 `subject_prerequisites` + `subject_concurrents` + chặn chu trình
  - Acceptance: `WEB101 → DB101 → WEB101` bị từ chối kèm thông báo đường dẫn; không tự tham
    chiếu chính nó.
  - Verify: `tests/Unit/PrerequisiteRulesTest.php` (3 case cycle) + feature test thêm/xoá.
  - Files: `app/Support/PrerequisiteRules.php`, `resources/views/admin/subjects/*`

- [ ] 2.4 `study_plans` — khung chương trình đào tạo theo chuyên ngành
  - Acceptance: một chuyên ngành liệt kê môn theo `plan_semester`, tổng tín chỉ đúng; unique
    `(major_id, subject_id)`.
  - Verify: `php artisan test --filter=CatalogTest`.
  - Files: `app/Models/StudyPlan.php`, `app/Http/Controllers/Admin/StudyPlanController.php`,
    `resources/views/admin/study-plans/*`

- [ ] 2.5 Trang admin cho khoa / chuyên ngành / khung CTĐT
  - Acceptance: đủ index/create/edit theo bộ nhãn dùng chung, không có nút "Hủy" trong form.
  - Verify: kiểm tay ba màn hình qua browser.
  - Files: `resources/views/admin/departments/*`, `resources/views/admin/majors/*`

## Wave 3 — `person-profiles`

- [ ] 3.1 Cột hồ sơ trên `users` + `academic_status`
  - Acceptance: `student_code`, `teacher_code`, `dob`, `gender`, `hometown`, `phone`,
    `academic_status`; `class_id` đổi sang `nullOnDelete`.
  - Verify: `php artisan test --filter=PersonProfileTest` (kèm case xoá lớp không xoá sinh viên).
  - Files: `database/migrations/*_add_profile_fields_to_users_table.php`, `app/Support/PersonLabels.php`

- [ ] 3.2 Form + trang chi tiết sinh viên
  - Acceptance: lưu và hiển thị đủ field; trang `/admin/students/{id}` có khối học phần, điểm,
    chuyên cần (placeholder cho tới wave 5–7).
  - Verify: `php artisan test --filter=PersonProfileTest`.
  - Files: `app/Http/Controllers/Admin/StudentController.php`, `resources/views/admin/students/*`

- [ ] 3.3 Bộ lọc khóa / khoa / chuyên ngành / tình trạng học tập
  - Acceptance: select lọc không có nút; mỗi tổ hợp trả đúng tập dòng; giá trị chỉ hiện khi có
    dữ liệu thật.
  - Verify: feature test theo fixture 6 sinh viên.
  - Files: `resources/views/admin/students/index.blade.php`

- [ ] 3.4 `StudentExport` ra `.xlsx`
  - Acceptance: mở bằng Excel đúng dấu, ngày `dd/mm/yyyy`, SĐT giữ số 0 đầu.
  - Verify: test assert file sinh ra đọc lại được bằng PhpSpreadsheet + kiểm tay.
  - Files: `app/Support/StudentExport.php`

- [ ] 3.5 `StudentImport` từ `.xlsx` (create/update/skip kèm lý do)
  - Acceptance: 100 dòng → 100 user; chạy lại → 0 created / 100 updated; trùng email khác mã SV
    bị skip, không nổ 500; mã lớp lạ bị skip.
  - Verify: `tests/Fixtures/students_import.xlsx` + feature test; kiểm tay upload.
  - Files: `app/Support/StudentImport.php`, `resources/views/admin/students/import.blade.php`

## Wave 4 — `course-offerings` + di cư dữ liệu *(điểm không thể quay lại)*

- [ ] 4.1 Migration `semesters` + `course_offerings` + `enrollments` (additive)
  - Acceptance: unique `(subject_id, semester_id, group_code)`; đúng một kỳ `is_active` do
    service buộc.
  - Verify: `php artisan migrate` trên DB dev, `php artisan test --filter=CourseOfferingTest`.
  - Files: `database/migrations/*_create_course_offerings.php`, `app/Models/{Semester,CourseOffering,Enrollment}.php`

- [ ] 4.2 Cột mới trên `classes` (bỏ `room`, thêm `intake_year`, `department_id`, `major_id`,
  `adviser_id`, `semester_id`)
  - Acceptance: form lớp hành chính chọn GVCN chỉ toàn teacher; `room` biến mất khỏi UI.
  - Verify: `php artisan test --filter=CourseOfferingTest`; 2 lớp cũ còn nguyên.
  - Files: migration, `app/Http/Controllers/Admin/ClassController.php`, `resources/views/admin/classes/*`

- [ ] 4.3 Migration di cư `student_subject`/`grades` → `enrollments`/`grades` mới
  - Acceptance: sau di cư `Enrollment::count()===15`, `Grade::count()===15` với
    `final_score===score` cũ; chạy hai lần không tạo bản sao.
  - Verify: `php artisan test --filter=LegacyDataMigrationTest` (RefreshDatabase + fixture 15
    dòng) + tinker đếm trên DB dev.
  - Files: `database/migrations/*_migrate_legacy_relations.php`

- [ ] 4.4 Migration drop `student_subject` + cột cũ `grades.score`
  - Acceptance: `SHOW TABLES` không còn `student_subject`; toàn bộ test pass.
  - Verify: `php artisan migrate:fresh --seed` vẫn chạy sạch.
  - Files: `database/migrations/*_drop_legacy_relations.php`

- [ ] 4.5 CRUD lớp học phần + phân công giảng viên
  - Acceptance: một môn mở 2 nhóm cùng kỳ; trùng nhóm bị chặn bằng thông báo; chọn role sai bị
    từ chối; sĩ số `0` = không giới hạn.
  - Verify: `php artisan test --filter=CourseOfferingTest`.
  - Files: `app/Http/Controllers/Admin/CourseOfferingController.php`,
    `app/Support/OfferingAssignment.php`, `resources/views/admin/course-offerings/*`

- [ ] 4.6 Trang `/teacher/my-offerings`
  - Acceptance: giảng viên chỉ thấy offering `teacher_id` của mình; admin thấy tất cả.
  - Verify: feature test phân quyền.
  - Files: `app/Http/Controllers/Teacher/OfferingController.php`, `resources/views/teacher/*`

## Wave 5 — `enrollment`

- [ ] 5.1 `EnrollmentPolicy` thuần (6 nhánh) + unit test trước
  - Acceptance: mỗi nhánh trả đúng mã lý do; policy không query DB.
  - Verify: `php artisan test --filter=EnrollmentPolicyTest` (red → green).
  - Files: `app/Support/EnrollmentPolicy.php`, `tests/Unit/EnrollmentPolicyTest.php`

- [ ] 5.2 Cổng đăng ký theo học kỳ
  - Acceptance: ngoài `[registration_starts_at, registration_ends_at]` mọi POST → 422 "Cổng đăng
    ký đã đóng"; `offering.is_open=false` cũng chặn.
  - Verify: feature test dùng `$this->travelTo()`.
  - Files: `app/Models/Semester.php`, `routes/web.php`

- [ ] 5.3 Đăng ký có `lockForUpdate` + bắt error 1062
  - Acceptance: nhóm còn 1 chỗ, hai request song song ⇒ đúng 1 thành công; chỗ cuối không bị
    vượt sĩ số.
  - Verify: feature test gọi hai request liên tiếp + assert `seatsLeft()`.
  - Files: `app/Http/Controllers/Student/CourseOfferingController.php`

- [ ] 5.4 Trang đăng ký học phần cho sinh viên
  - Acceptance: offering không đủ điều kiện vẫn hiện, nút "Đăng ký" disabled kèm lý do in dưới
    dòng; khối "Đăng ký của tôi" hiện tổng tín chỉ đang học / đã tích luỹ.
  - Verify: kiểm tay + feature test render.
  - Files: `resources/views/student/course-offerings/index.blade.php`

- [ ] 5.5 Hủy đăng ký theo quy tắc dữ liệu
  - Acceptance: hủy trong cổng và chưa có điểm danh/điểm ⇒ `DROPPED`, `seatsLeft()`+1; đã có
    dữ liệu ⇒ từ chối; điểm cũ không bị xoá.
  - Verify: feature test cả hai nhánh.
  - Files: `app/Http/Controllers/Student/EnrollmentController.php`

- [ ] 5.6 Admin đăng ký / hủy hộ (ghi `enrolled_by`)
  - Acceptance: bỏ qua bước kiểm tra cổng, vẫn kiểm sĩ số + tiên quyết; có vết ai đăng ký.
  - Verify: feature test.
  - Files: `app/Http/Controllers/Admin/EnrollmentController.php`

## Wave 6 — `grading`

- [ ] 6.1 `GradingScale` + unit test phủ biên
  - Acceptance: CC=8 GK=7 CK=6 ⇒ 6.4 ⇒ C ⇒ 3.0; `final=4.5` ⇒ F; 8.4999 ⇒ B; GPA (3TC/4.0 +
    2TC/3.0) ⇒ 3.6; điểm liệt 4.0.
  - Verify: `php artisan test --filter=GradingScaleTest`.
  - Files: `app/Support/GradingScale.php`, `tests/Unit/GradingScaleTest.php`

- [ ] 6.2 Re-key `grades`: ba cột điểm + `enrollment_id` + `is_published` + `updated_by`
  - Acceptance: unique `(course_offering_id, user_id)`; 15 dòng cũ thành `final_score`.
  - Verify: `php artisan test --filter=GradingTest`.
  - Files: `database/migrations/*_rebuild_grades_table.php`, `app/Models/Grade.php`

- [ ] 6.3 Grid nhập điểm cho giảng viên
  - Acceptance: chỉ giảng viên phụ trách lưu được; ô trống ⇒ `null`; điểm tổng kết chỉ đọc;
    lưu một mảng nhiều dòng.
  - Verify: feature test quyền + upsert.
  - Files: `app/Http/Controllers/Teacher/GradeController.php`, `resources/views/teacher/grades/*`

- [ ] 6.4 Công bố điểm
  - Acceptance: trước publish sinh viên thấy "Chưa công bố"; sau publish thấy đúng điểm.
  - Verify: feature test.
  - Files: `routes/web.php`, `resources/views/student/grades/index.blade.php`

- [ ] 6.5 Bảng điểm + GPA của sinh viên (hệ 4, hệ 10, xếp loại)
  - Acceptance: mỗi môn hiện CC/GK/CK/Tổng kết/Chữ/Hệ 4; đầu trang hiện TB tích luỹ và số tín
    chỉ đã đạt.
  - Verify: feature test với fixture 3 môn đã biết kết quả.
  - Files: `app/Http/Controllers/Student/GradeController.php`

- [ ] 6.6 Nút "Xét duyệt" ghi `academic_status` + lý do gợi ý
  - Acceptance: lên lớp / cảnh báo học vụ / đủ điều kiện tốt nghiệp theo `progression()`; admin
    xác nhận, hệ thống không tự đổi.
  - Verify: feature test ba nhánh ngưỡng.
  - Files: `app/Http/Controllers/Admin/AcademicStatusController.php`

## Wave 7 — `attendance-schedule`

- [ ] 7.1 Migration `lecture_slots`
  - Acceptance: một offering có 2 slot Thứ 3 + Thứ 5; unique `(offering, week_day, start_time)`.
  - Verify: `php artisan test --filter=AttendanceScheduleTest`.
  - Files: `database/migrations/*_create_lecture_slots.php`, `app/Models/LectureSlot.php`

- [ ] 7.2 Migration `attendance_sessions` + re-key `attendances`
  - Acceptance: 16 dòng cũ thành `(session, enrollment)`; điểm danh lại cùng buổi ⇒ update,
    không tăng số dòng.
  - Verify: `php artisan test --filter=AttendanceScheduleTest` + tinker đếm 16.
  - Files: migration, `app/Models/{AttendanceSession,Attendance}.php`

- [ ] 7.3 `AttendanceRate` + unit test
  - Acceptance: 1 vắng/4 buổi ⇒ 75%; chưa buổi nào ⇒ `null` (UI "Chưa có dữ liệu").
  - Verify: `php artisan test --filter=AttendanceRateTest`.
  - Files: `app/Support/AttendanceRate.php`

- [ ] 7.4 Màn hình điểm danh nhanh cho giảng viên
  - Acceptance: mặc định "Có mặt", bỏ check người vắng, lưu một lần; chỉ teacher phụ trách.
  - Verify: feature test + kiểm tay.
  - Files: `app/Http/Controllers/Teacher/AttendanceController.php`,
    `resources/views/teacher/attendance/*`

- [ ] 7.5 Thời khóa biểu tuần cho sinh viên và giảng viên
  - Acceptance: lưới Thứ 2–Thứ 7, đúng 2 ô cho 2 slot; lớp đã `DROPPED` biến mất; phát hiện
    trùng giờ.
  - Verify: feature test render + `TimetableService::conflicts()`.
  - Files: `app/Support/TimetableService.php`, `resources/views/student/timetable.blade.php`,
    `resources/views/teacher/timetable.blade.php`

- [ ] 7.6 Gắn tỷ lệ chuyên cần vào `grading` (cảnh báo học vụ)
  - Acceptance: tỷ lệ < 80% xuất hiện trong lý do gợi ý `AT_RISK`.
  - Verify: feature test.
  - Files: `app/Support/GradingScale.php`

## Wave 8 — `reporting`

- [ ] 8.1 `ReportFilter` + `Reports` (census, scoreDistribution, attendanceSummary, gpaRanking)
  - Acceptance: mỗi con số tính từ bảng nguồn, đối chiếu được bằng truy vấn tay.
  - Verify: `php artisan test --filter=ReportingTest` với fixture 6 sinh viên điểm cố định.
  - Files: `app/Support/Reports/*.php`, `app/Support/ReportFilter.php`

- [ ] 8.2 Trang thống kê admin với bộ lọc dùng chung
  - Acceptance: bốn bảng trên một trang, lọc theo kỳ/khoa/khóa/chuyên ngành/giới tính/tình
    trạng; không có nút trong thanh lọc.
  - Verify: kiểm tay + feature test.
  - Files: `app/Http/Controllers/Admin/ReportController.php`, `resources/views/admin/reports/*`

- [ ] 8.3 `ReportExporter`: Xlsx + Pdf + Csv sau `App\Support\Csv`
  - Acceptance: `format=xlsx|pdf|csv` trên `/admin/reports/{type}`; CSV vẫn như cũ để không vỡ
    2 test hồi quy; PDF đúng tiếng Việt có dấu, font DejaVu.
  - Verify: `php artisan test --filter=ExtendedRequirementsTest` (hồi quy) + mở tay `.xlsx`
    và `.pdf`.
  - Files: `app/Support/Exports/*.php`, `resources/views/reports/pdf/*`, `public/fonts/*`

- [ ] 8.4 Phân quyền báo cáo cho giảng viên (chỉ lớp mình)
  - Acceptance: teacher xem báo cáo lớp mình = 200, lớp khác = 403, `/admin/reports` = 403.
  - Verify: feature test.
  - Files: `routes/web.php`

## Wave 9 — Tài liệu & dữ liệu mẫu

- [ ] 9.1 Viết lại `DatabaseSeeder` đủ bộ V2
  - Acceptance: 2 khoa, 2 chuyên ngành, 6 môn, 2 giảng viên, 2 học kỳ, 6 offering có slot, 15
    enrollment, điểm ba cột, điểm danh 4 buổi; `migrate:fresh --seed` chạy sạch.
  - Verify: `php artisan migrate:fresh --seed` + `php artisan test`.
  - Files: `database/seeders/*`

- [ ] 9.2 Vẽ lại ERD + Use Case theo schema mới
  - Acceptance: ERD có `semesters`, `course_offerings`, `enrollments`, `lecture_slots`,
    `attendance_sessions`; use case thêm tác nhân Giảng viên.
  - Verify: `python docs/diagrams/make_diagrams.py` chạy không lỗi, ảnh mở ra xem được.
  - Files: `docs/diagrams/make_diagrams.py`

- [ ] 9.3 Cập nhật báo cáo `.md` và `.docx`
  - Acceptance: mục tiêu/công nghệ/yêu cầu mở rộng phản ánh V2; số test và assertion cập nhật
    đúng; MỤC LỤC khớp tiêu đề.
  - Verify: mở docx, kiểm tra ảnh + bảng; `python` dump không còn chuỗi cũ.
  - Files: `docs/BAO_CAO_DO_AN.md`, `docs/BAO_CAO_NHOM_QLSV.docx`

- [ ] 9.4 Cập nhật README chạy dự án
  - Acceptance: hướng dẫn `composer install`, `migrate:fresh --seed`, tài khoản mẫu ba role.
  - Verify: đọc lại theo đúng lệnh, không lệnh nào fail.
  - Files: `README.md`
