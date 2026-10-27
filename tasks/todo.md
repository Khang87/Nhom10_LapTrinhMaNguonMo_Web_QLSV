# Todo — V2 quản lý đào tạo (8 module)

Ký hiệu: `[ ]` chưa làm · `[~]` đang làm · `[x]` xong + verify.
Quy ước branch/commit: một commit cho mỗi task, message tiếng Việt theo module.

## Wave 0 — Chuẩn bị

- [x] 0.1 Cài dependency Excel/PDF
  - Acceptance: `composer show` có `phpoffice/phpspreadsheet` và `barryvdh/laravel-dompdf`;
    `composer.lock` được commit.
  - Verify: `php -r "require 'vendor/autoload.php'; echo class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class) ? 'ok' : 'fail';"` in `ok`.
  - Kết quả 2026-10-25: `xlsx-ok pdf-ok` (phpspreadsheet 5.10.0, laravel-dompdf 3.1.2),
    `php artisan test` vẫn 35 passed / 226 assertions.
  - Files: `composer.json`, `composer.lock`

- [x] 0.2 Backup DB dev trước migration
  - Acceptance: `storage/app/backup-pre-v2.sql` tồn tại, không vào git.
  - Kết quả 2026-10-25: 435 dòng dump, `git check-ignore` xác nhận bị bỏ qua qua
    `storage/app/.gitignore`.
  - Files: không (artifact cục bộ)

- [x] 0.3 Tạo `config/academic.php`
  - Acceptance: `max_credits_per_term=14`, `min_attendance_rate=80`; trọng số và ngưỡng GPA
    KHÔNG nằm ở đây mà thuộc `GradingScale` (một nguồn sự thật duy nhất cho thang điểm).
  - Kết quả 2026-10-25: `artisan tinker` in `14 80`.
  - Files: `config/academic.php`

## Wave 1 — `identity-roles`

- [x] 1.1 Migration mở rộng enum `users.role` thêm `teacher`
  - Acceptance: `SHOW COLUMNS FROM users LIKE 'role'` liệt kê `admin,teacher,student`; 7 dòng
    cũ giữ nguyên role.
  - Verify: `php artisan migrate` + `php artisan test --filter=IdentityRolesTest`.
  - Files: `database/migrations/*_add_teacher_role_to_users_table.php`
  - Done 2026-10-26: migration `2026_11_01_000001` chạy DONE, `SELECT role, COUNT(*)` trả về
    `admin:1, student:6` trước khi seed và `admin:1, teacher:2, student:6` sau khi seed.

- [x] 1.2 Sửa redirect theo role bằng bảng ánh xạ
  - Acceptance: login teacher → `/teacher`, không `RouteNotFoundException`.
  - Verify: `php artisan test --filter=IdentityRolesTest`.
  - Files: `app/Http/Controllers/AuthController.php`
  - Done 2026-10-26: `User::dashboardRoute()` + `roleLabel()`; đăng nhập `giangvien1@gmail.com`
    ngoài trình duyệt chuyển thẳng tới `/teacher/dashboard`.

- [x] 1.3 Nhóm route + middleware `checkRole:teacher`
  - Acceptance: admin/student truy cập `/teacher/*` → 403; teacher truy cập `/admin/*` → 403.
  - Verify: `php artisan test --filter=IdentityRolesTest`.
  - Files: `routes/web.php`, `app/Http/Controllers/Teacher/DashboardController.php`
  - Done 2026-10-26: 4 chiều 403 được test; `php artisan test` → 49 passed (272 assertions).

- [x] 1.4 CRUD tài khoản cho admin (ba role, validate theo role)
  - Acceptance: tạo được giảng viên, sửa role, đổi mật khẩu tuỳ chọn; field không thuộc role bị
    null hoá server-side.
  - Verify: feature test tạo/sửa/xoá + kiểm tay `/admin/accounts`.
  - Files: `app/Http/Controllers/Admin/AccountController.php`, `resources/views/admin/accounts/*`
  - Done 2026-10-26: kiểm tay tạo "ĐỗTest (teacher)" → "Đã thêm tài khoản.", lọc `?role=teacher`
    bằng AJAX (URL đổi, không tải lại, "N tài khoản."), xoá lại về 9 dòng; chặn tự xóa/tự hạ cấp
    chính mình và xóa tài khoản đã có dữ liệu.

- [x] 1.5 Nav + layout theo role
  - Acceptance: sidebar hiện mục riêng cho teacher (Lớp của tôi, Điểm danh, TKB).
  - Verify: kiểm tay + `spark layout is role specific` test cũ vẫn pass.
  - Files: `resources/views/layouts/spark.blade.php`
  - Done 2026-10-26 (một phần): teacher chỉ còn "TỔNG QUAN → Trang chủ", không lộ link admin;
    các mục Lớp của tôi/Điểm danh/TKB sẽ thêm ở Wave 6–8 khi route đó tồn tại.

- [x] 1.6 (phát sinh) Bỏ inline script vì CSP chặn
  - Lý do: `SecureHeaders` đặt `script-src 'self' https://cdn.jsdelivr.net`, nên `<script>` nội
    tuyến không chạy — làm hỏng cả toggle "Lớp" lẫn xem trước ảnh đại diện (lỗi có sẵn từ trước).
  - Đã chuyển sang `public/js/app.js`; thêm `blob:` vào `img-src` để `URL.createObjectURL` hiển thị
    được. Test chặn hồi quy: `test_forms_keep_javascript_out_of_inline_script_tags`.

## Wave 2 — `catalog`

- [x] 2.1 Migration `departments` + `majors`
  - Acceptance: xoá khoa còn chuyên ngành bị chặn (`restrictOnDelete`).
  - Verify: `php artisan test --filter=CatalogTest`.
  - Files: `database/migrations/*_create_catalog_tables.php`, `app/Models/{Department,Major}.php`
  - Done 2026-10-26: 3 migration chạy DONE trên MySQL; test `department with majors not deletable`
    + `major with study plan not deletable` pass. Browser: `/admin/departments` hiện cột
    "Chuyên ngành" = 2 (CNTT) / 0 (KHTN).

- [x] 2.2 Cột mới trên `subjects` (`major_id`, `description`, `total_sessions`)
  - Acceptance: form môn học chọn được chuyên ngành; dữ liệu 3 môn cũ không mất.
  - Verify: `php artisan test --filter=CatalogTest`.
  - Files: migration, `app/Http/Controllers/Admin/SubjectController.php`, `resources/views/admin/subjects/*`
  - Done 2026-10-26: `major_id` nullable + `nullOnDelete` nên 3 môn cũ vẫn nguyên; seeder nâng lên
    6 môn, `/admin/subjects` hiện "Chưa phân chuyên ngành" cho GTA101.

- [x] 2.3 `subject_prerequisites` + `subject_concurrents` + chặn chu trình
  - Acceptance: `WEB101 → DB101 → WEB101` bị từ chối kèm thông báo đường dẫn; không tự tham
    chiếu chính nó.
  - Verify: `tests/Unit/PrerequisiteRulesTest.php` (3 case cycle) + feature test thêm/xoá.
  - Files: `app/Support/PrerequisiteRules.php`, `resources/views/admin/subjects/*`
  - Done 2026-10-26 (lạc hướng so với plan): 3 case cycle + `blockingPrerequisites` đặt trong
    `tests/Feature/CatalogTest.php` chạy qua HTTP thật thay vì unit test riêng, vì đó đúng hành vi
    người dùng gặp. Browser: thêm tiên quyết DB101 cho WEB101 → banner
    "Xếp tiên quyết sẽ tạo vòng lặp: WEB101 → DB101 → WEB101. …", dữ liệu không đổi;
    song hành SEC201 ↔ NET101 đọc được ở cả hai chiều.

- [x] 2.4 `study_plans` — khung chương trình đào tạo theo chuyên ngành
  - Acceptance: một chuyên ngành liệt kê môn theo `plan_semester`, tổng tín chỉ đúng; unique
    `(major_id, subject_id)`.
  - Verify: `php artisan test --filter=CatalogTest`.
  - Files: `app/Models/StudyPlan.php`, `app/Http/Controllers/Admin/StudyPlanController.php`,
    `resources/views/admin/study-plans/*`
  - Done 2026-10-26: `/admin/study-plans?major_id=1` = "Tổng tín chỉ: 16 · 5 môn", sắp theo học kỳ
    1→4; thêm NET101 học kỳ 5 → "18 · 6 môn" rồi xóa lại về 16 ("Đã xóa môn khỏi khung
    chương trình."). Test trùng môn báo lỗi `subject_id`; bulk PUT đổi học kỳ ADT101 chạy thật.

- [x] 2.5 Trang admin cho khoa / chuyên ngành / khung CTĐT
  - Acceptance: đủ index/create/edit theo bộ nhãn dùng chung, không có nút "Hủy" trong form.
  - Verify: kiểm tay ba màn hình qua browser.
  - Files: `resources/views/admin/departments/*`, `resources/views/admin/majors/*`
  - Done 2026-10-26: 5 route (`/admin/departments`, `/admin/majors`, `/admin/study-plans` + các
    route create/edit) trả 200, không trang nào có chữ "Hủy" hay inline script; mọi form có
    "Quay lại"; filter "Lọc theo khoa" trả "Chưa có chuyên ngành nào." khi chọn KHTN; console
    sạch lỗi.
  - Deviation: giữ nút "Tìm kiếm" trên filter bar (memory muốn thanh lọc không nút) để đồng bộ
    với các màn hình đã có trong repo.

## Wave 3 — `person-profiles`

- [x] 3.1 Cột hồ sơ trên `users` + `academic_status`
  - Acceptance: `student_code`, `teacher_code`, `dob`, `gender`, `hometown`, `phone`,
    `academic_status`; `class_id` đổi sang `nullOnDelete`.
  - Verify: `php artisan test --filter=PersonProfileTest` (kèm case xoá lớp không xoá sinh viên).
  - Files: `database/migrations/*_add_profile_fields_to_users_table.php`, `app/Support/PersonLabels.php`
  - Done 2026-10-26: `2026_11_03_000001` + `2026_11_03_000002` chạy DONE trên MySQL. Hai case
    "deleting a class …" pass, trong đó case gọi thẳng `$class->delete()` chứng minh
    `nullOnDelete` (sinh viên còn, `class_id` = null). Bộ nhãn tiếng Việt gom về `PersonLabels`
    (`Nữ (FEMALE)`, `Đang học (STUDYING)`), mật khẩu khởi điểm import = `12345678`.
  - Deviation: kéo migration phân loại `classes` (`intake_year`, `department_id`, `major_id`) từ
    4.2 sang Wave 3 vì bộ lọc 3.3 cần; `room`, `adviser_id`, `semester_id` vẫn ở 4.2.

- [x] 3.2 Form + trang chi tiết sinh viên
  - Acceptance: lưu và hiển thị đủ field; trang `/admin/students/{id}` có khối học phần, điểm,
    chuyên cần (placeholder cho tới wave 5–7).
  - Verify: `php artisan test --filter=PersonProfileTest`.
  - Files: `app/Http/Controllers/Admin/StudentController.php`, `resources/views/admin/students/*`
  - Done 2026-10-26: form edit có đủ 11 field (`student_code`, `dob`, `gender`, `hometown`,
    `phone`, `academic_status`, lớp, avatar), nút "Cập nhật" + "Quay lại", mật khẩu để trống giữ
    nguyên hash (test `updates profile without touching password`). Hồ sơ
    `/admin/students/6` hiển thị `30/11/2006`, `Nữ (FEMALE)`, `Cảnh báo học vụ (AT_RISK)`, khối
    "Lớp học phần đã đăng ký" (3 môn), "Kết quả học tập" (7.0–8.0), "Tỷ lệ chuyên cần"
    (4/6 buổi = 66.7%). Sinh viên chưa xếp lớp (`20240006`) hiện "Chưa xếp lớp",
    "Chưa đăng ký môn học nào", "Chưa có điểm", "Chưa có dữ liệu điểm danh" — không lỗi.

- [x] 3.3 Bộ lọc khóa / khoa / chuyên ngành / tình trạng học tập
  - Acceptance: select lọc không có nút; mỗi tổ hợp trả đúng tập dòng; giá trị chỉ hiện khi có
    dữ liệu thật.
  - Verify: feature test theo fixture 6 sinh viên.
  - Files: `resources/views/admin/students/index.blade.php`
  - Done 2026-10-26: `test_filters_narrow_the_list_by_intake_department_major_and_status` (52
    assertion) pass; ma trận lọc kiểm qua browser trên DB dev — `academic_status=AT_RISK` → 1
    (20240005), `intake_year=2024` → 6, `department_id=CNTT` → 6, `major_id=KTPM` → 4,
    `class_id=none` → 1, `major_id=KTPM&academic_status=AT_RISK` → 1. Select "Khoa" chỉ liệt kê
    CNTT (chưa có lớp nào thuộc KHTN).
  - Deviation: thêm bộ lọc thứ năm "Lớp" kèm giá trị "Chưa xếp lớp" (ngoài 4 bộ lọc trong spec)
    và giữ nút "Tìm kiếm" cho đồng bộ với các màn hình khác.

- [x] 3.4 `StudentExport` ra `.xlsx`
  - Acceptance: mở bằng Excel đúng dấu, ngày `dd/mm/yyyy`, SĐT giữ số 0 đầu.
  - Verify: test assert file sinh ra đọc lại được bằng PhpSpreadsheet + kiểm tay.
  - Files: `app/Support/StudentExport.php`
  - Done 2026-10-26: `/admin/students/export` trả
    `application/vnd…spreadsheetml.sheet`, `danh-sach-sinh-vien-2026-10-26.xlsx` (7.187 byte);
    test đọc lại bằng `IOFactory::load`: header đúng 9 cột, `0912345678` giữ số 0 (ghi
    `DataType::TYPE_STRING`), `09/05/2006`, `Nam`, `Đang học`, ô lớp trống cho sinh viên chưa xếp.
    Export đi theo bộ lọc đang chọn (`export follows the active filters`). Sửa lỗi off-by-one
    trong `decorate()` từng sinh thêm một dòng rỗng ở cuối file.

- [x] 3.5 `StudentImport` từ `.xlsx` (create/update/skip kèm lý do)
  - Acceptance: 100 dòng → 100 user; chạy lại → 0 created / 100 updated; trùng email khác mã SV
    bị skip, không nổ 500; mã lớp lạ bị skip.
  - Verify: `tests/Fixtures/students_import.xlsx` + feature test; kiểm tay upload.
  - Files: `app/Support/StudentImport.php`, `resources/views/admin/students/import.blade.php`
  - Done 2026-10-26: fixture 100 dòng → `assertDatabaseCount('users', 101)`, nạp lại lần hai →
    "Nhập 100 dòng: thêm mới 0, cập nhật 100, bỏ qua 0.". Upload thật qua UI bằng file 6 dòng
    (1 hợp lệ + 5 lỗi): "Nhập 5 dòng: thêm mới 1, cập nhật 0, bỏ qua 4." kèm từng lý do
    ("Thiếu họ tên hoặc email.", "Lớp CTK99X không tồn tại.",
    "Email sinhvien1@gmail.com đã thuộc mã sinh viên khác (20240001).",
    "Ngày sinh \"31/02/2006\" không hợp lệ."); nạp lại chính file → "thêm mới 0, cập nhật 1".
    Tài khoản import xong đăng nhập được bằng `12345678` (`Hash::check` = true). File sai đuôi
    và 501 dòng bị chặn ngay ở validate, không có 500.
  - Deviation: fixture `.xlsx` dựng trong bộ nhớ bằng `xlsx()`/`hundredRows()` thay vì commit
    `tests/Fixtures/students_import.xlsx`; script probe upload tạm đặt ở `storage/app/` và đã xoá.

## Wave 4 — `course-offerings` + di cư dữ liệu *(điểm không thể quay lại)*

- [x] 4.1 Migration `semesters` + `course_offerings` + `enrollments` (additive)
  - Acceptance: unique `(subject_id, semester_id, group_code)`; đúng một kỳ `is_active` do
    service buộc.
  - Evidence: `2026_11_04_000001` (409.56ms) tạo 3 bảng; `Semester::activate()` bọc
    `DB::transaction` và test `activating a semester keeps exactly one active` khẳng định còn
    đúng 1 kỳ active; `buildSlug` → `HK2-2025-2026`, `label` → `Học kỳ 2 năm học 2025-2026`;
    `offering_unique` chặn trùng nhóm (bắt `UniqueConstraintViolationException`); `seatsLeft()`
    chỉ đếm enrollment `ACTIVE`, `max_students=0` → `PHP_INT_MAX`; cổng cần cả cửa sổ kỳ lẫn
    `is_open` (dùng `travelTo`).
  - Deviation: `Semester::active()` va tên với `scopeActive()` nên helper tĩnh đổi thành
    `Semester::current()`.

- [x] 4.2 Cột mới trên `classes` (bỏ `room`, thêm `intake_year`, `department_id`, `major_id`,
  `adviser_id`, `semester_id`)
  - Acceptance: form lớp hành chính chọn GVCN chỉ toàn teacher; `room` biến mất khỏi UI.
  - Evidence: `2026_11_04_000002` thêm `adviser_id`/`semester_id` (nullOnDelete) và drop
    `classes.room`; `Schema::hasColumn('classes','room') === false`; danh sách lớp hiển thị
    Khóa/Khoa/GVCN/Sĩ số và test asserts `assertStringNotContainsString('Phòng')`; GVCN lưu dạng
    `Nguyễn Văn Đạt (GV01)`; chọn advisor là student bị `assertSessionHasErrors('adviser_id')`;
    chuyên ngành không thuộc khoa bị chặn `major_id`.
  - Deviation: `intake_year/department_id/major_id` vốn đã có từ Wave 2 (migration
    `2026_11_03_000002`) nên 4.2 chỉ bổ sung GVCN + bỏ `room`.

- [x] 4.3 Migration di cư `student_subject`/`grades` → `enrollments`/`grades` mới
  - Acceptance: sau di cư `Enrollment::count()===15`, `Grade::count()===15` với
    `final_score===score` cũ; chạy hai lần không tạo bản sao.
  - Evidence: DB dev sau `migrate` — 6 offering nhóm TN01, 15 enrollment ACTIVE, 15 grade đã
    gắn `course_offering_id` và `final_score === score`; chạy lại `up()` không tăng số bản ghi
    (dùng `insertOrIgnore` + `whereNull('course_offering_id')`). Dump trước khi chạy:
    `storage/app/backup-before-wave4.sql` (35.583 byte).
  - Deviation: `up()` chỉ tạo học kỳ + offering khi thực sự có dữ liệu cũ (`hasLegacyData()`);
    DB mới tinh không bị "bịa" một học kỳ ảo — học kỳ đầu giờ do seeder/admin tạo. Vì thế
    `LegacyDataMigrationTest` (đã commit? chưa) bị xoá ở 4.4: sau khi drop cột `subject_id/score`
    thì không còn dựng được dữ liệu đầu vào kiểu cũ.

- [x] 4.4 Migration drop `student_subject` + cột cũ `grades.score`
  - Acceptance: `SHOW TABLES` không còn `student_subject`; toàn bộ test pass.
  - Verify: `php artisan migrate:fresh --seed` chạy sạch (15 migration, migrate 213.19ms cho
    `000004`); `Schema::hasTable('student_subject') === false`, `grades` chỉ còn
    `course_offering_id` (NOT NULL) + `final_score` (NOT NULL) và unique
    `grade_offering_unique (course_offering_id, user_id)`; toàn suite **96 passed (544 assertions)**.
  - Refactor đi kèm (Step D): `Grade` bỏ `subject_id/score` + accessor `subject` qua
    `offering.subject`; `User` bỏ pivot `subjects()`, thay bằng `offerings()/enrollments()/enrolledSubjects()`;
    `Admin/GradeController`, `StudentGradeController`, `StudentDashboardController`,
    `Admin/StudentController::show`, `Admin/AccountController`, `Admin/AttendanceController`,
    `PrerequisiteRules::blockingPrerequisites` đều đọc theo offering; `StudentSubjectController`
    + `views/student/subjects` bị xoá, thay bằng `StudentOfferingController` với route
    `student.offerings.index|register|unregister` (`/student/course-offerings`); seeder dựng
    1 học kỳ có cửa sổ đăng ký + 5 lớp học phần (2 nhóm WEB101, 1 nhóm đóng) và ghi
    enrollment/grade qua offering. Điểm danh vẫn theo `subject_id` tới Wave 7.
  - Đã xác minh trên trình duyệt: `/admin/grades` (cột Nhóm + Học kỳ, 15 dòng),
    `/admin/attendances` (15 dòng sinh viên × môn đang học), `/student/course-offerings`
    (hủy NET101 → còn 6 TC → đăng ký lại → còn 15 enrollment, 0 DROPPED, không tạo bản trùng),
    `/student/grades`, `/student/dashboard`, `/admin/students/4` (3 thẻ hồ sơ).
  - Deviation: MySQL không cho drop `grades_user_id_subject_id_unique` vì FK `user_id` đang dùng
    nó → migration tạo `grades_user_id_index` trước rồi mới drop index cũ.

- [x] 4.5 CRUD lớp học phần + phân công giảng viên
  - Acceptance: một môn mở 2 nhóm cùng kỳ; trùng nhóm bị chặn bằng thông báo; chọn role sai bị
    từ chối; sĩ số `0` = không giới hạn.
  - Verify: `php artisan test --filter=CourseOfferingTest`.
  - Files: `app/Http/Controllers/Admin/CourseOfferingController.php`,
    `app/Support/OfferingAssignment.php`, `resources/views/admin/course-offerings/*`
  - Done 2026-10-26: `/admin/semesters` (index/create/edit + POST activate) và
    `/admin/course-offerings` (resource, bỏ `show`). Browser: tạo HK2-2026-2027 có cổng
    05/01/2027 08:00 → 20/01/2027 17:00 ("Đã thêm học kỳ."), tạo lại cùng (năm, kỳ) báo
    "Học kỳ này của năm học đã tồn tại.", nhập một mốc cổng báo "Cổng đăng ký cần cả mốc đóng…",
    mở thêm SEC201 nhóm TN02 → trùng TN02 bị chặn "Nhóm TN02 của môn SEC201 đã được mở trong học
    kỳ này.", sửa WEB101 TN02 thành A105/Đóng rồi khôi phục A103/Mở, xóa lớp có 5 sinh viên +
    5 cột điểm bị từ chối "…chưa xóa được vì đang có 5 sinh viên học phần và 5 cột điểm. Hãy
    Đóng lớp…", xóa SEC201 TN02 trống thì thành công. Bảng lớp hiện `5/30`, `5 / Không giới hạn`,
    badge `Mở`/`Đóng` và progress bar theo tỷ lệ sĩ số (đỏ khi đầy — chưa dựng lớp đầy ngoài
    trình duyệt).
  - Evidence test: `CourseOfferingTest` thêm 17 test (HTTP thật) — toàn bộ
    **113 passed (638 assertions)**, chạy hai lần liên tiếp không rung lắc.
  - Deviation: `term` chỉ nhận 1–3 (theo schema trong SPEC); cổng đăng ký bắt buộc đủ hai mốc và
    đóng sau mở (SPEC không nêu, thêm vì một mốc lẻ khiến cổng mở vô hạn); xóa học kỳ đang hiện
    hành cũng bị chặn để không rơi vào trạng thái "không kỳ nào"; `max_students` giới hạn
    0–65535 đúng kiểu `unsignedSmallInteger`; `Semester::updating` dựng lại `slug` khi đổi năm
    học/kỳ để mã kỳ không lệch dữ liệu.
  - Deviation (dữ liệu mẫu): `DatabaseSeeder` đổi mã nhóm `01/02` → `TN01/TN02` cho khớp ví dụ
    trong SPEC và dữ liệu di cư; DB dev dựng lại bằng `migrate:fresh --seed`
    (dump trước đó: `storage/app/backup-before-wave4-crud.sql`, 36.547 byte, không vào git).
  - Deviation (test): `assertNames()` trong `PersonProfileTest` chỉ soi `<tbody>` của bảng kết quả
    vì email random của admin hiển thị trên thanh bên, có thể chứa chuỗi con trùng email kiểm thử
    — đây là nguyên nhân bài "filters narrow the list…" rung lắc ở các lần chạy trước.

- [x] 4.6 Trang `/teacher/my-offerings`
  - Acceptance: giảng viên chỉ thấy offering `teacher_id` của mình; admin thấy tất cả.
  - Verify: feature test phân quyền.
  - Files: `app/Http/Controllers/Teacher/OfferingController.php`, `resources/views/teacher/*`
  - Done 2026-10-26: `User::assignedOfferings()` + `Teacher\OfferingController::index` lọc theo
    `teacher_id` của người đăng nhập. Browser với `giangvien1@gmail.com`:
    `/teacher/my-offerings` hiện đúng 2 lớp (WEB101 TN01 A101 5/30, DB101 TN01 A102 5/30), không
    thấy WEB101 TN02/NET101/SEC201 của giảng viên khác và lớp chưa phân công;
    `/admin/course-offerings` trả 403. Dashboard giảng viên thay cảnh báo tĩnh bằng khối
    "Lớp học phần của tôi (2)" có link "Xem chi tiết"; sidebar thêm mục "GIẢNG DẠY → Lớp của tôi"
    (nối tiếp 1.5). Test: `a teacher lists only the offerings assigned to them`,
    `a teacher cannot reach the offering admin screens`.

## Wave 5 — `enrollment`

- [x] 5.1 `EnrollmentPolicy` thuần (6 nhánh) + unit test trước
  - Acceptance: mỗi nhánh trả đúng mã lý do; policy không query DB.
  - Verify: `php artisan test --filter=EnrollmentPolicyTest` (red → green).
  - Files: `app/Support/EnrollmentPolicy.php`, `tests/Unit/EnrollmentPolicyTest.php`
  - Done 2026-10-26: `EnrollmentPolicy::check()` trả `['ok'=>bool,'reasons'=>[{code,message}]]`
    cho 6 nhánh (GATE_CLOSED, MAX_CREDITS, PREREQUISITE_MISSING, CONCURRENT_MISSING, CLASS_FULL,
    ALREADY_REGISTERED) + `checkDrop()` (GATE_CLOSED, DATA_EXISTS) và `reasonText()` ghép bằng " · ".
    Ngữ cảnh ở `EnrollmentContext` (`for`, `collect` nhiều lớp chỉ vài truy vấn, `passedSubjects`,
    `accumulatedCredits`, `forDrop`); mọi truy vấn dồn hết ra ngoài policy.
  - Evidence: 14 unit test, 30 assertions, green. `test_the_policy_never_touches_the_database`
    bật `enableQueryLog()` rồi assert log rỗng sau cả 6 nhánh; fixture dựng bằng `forceFill` +
    `setRelation` nên không cần DB.

- [x] 5.2 Cổng đăng ký theo học kỳ
  - Acceptance: ngoài `[registration_starts_at, registration_ends_at]` mọi POST → 422 "Cổng đăng
    ký đã đóng"; `offering.is_open=false` cũng chặn.
  - Verify: feature test dùng `$this->travelTo()`.
  - Files: `app/Models/Semester.php`, `routes/web.php`
  - Done 2026-10-26: cổng do `CourseOffering::isRegistrationOpen()` quyết (kỳ mở cổng AND
    `offering.is_open`); từ chối phóng `HttpException(422)` và trang `errors/422.blade.php` in đúng
    thông điệp nghiệp vụ, nên cả POST lẫn UI cùng một lý do.
  - Evidence: `test_a_post_before_the_gate_opens_is_refused_with_422`,
    `test_a_post_after_the_gate_closes_is_refused_with_422` (travelTo +3 ngày),
    `test_a_closed_offering_is_refused_even_inside_the_gate` — cả ba assert 422, thấy
    "Cổng đăng ký đã đóng" và `Enrollment::count() === 0`. Browser: trang sinh viên hiển "Cổng đăng
    ký đang mở tới 09/11/2026 11:41", offering `is_open=0` hiện lý do "Cổng đăng ký đã đóng".
  - Deviation: giúp đỡ `gate()` đưa sang trait `OfferingFixtures` dùng chung cho mọi feature test;
    helper cũ dùng `[mặc định] + $extra` nên `array_merge()` để `$extra` thắng (union cũ silently
    giữ mốc mặc định, làm test "trước cổng" xanh giả).

- [x] 5.3 Đăng ký có `lockForUpdate` + bắt error 1062
  - Acceptance: nhóm còn 1 chỗ, hai request song song ⇒ đúng 1 thành công; chỗ cuối không bị
    vượt sĩ số.
  - Verify: feature test gọi hai request liên tiếp + assert `seatsLeft()`.
  - Files: `app/Http/Controllers/Student/CourseOfferingController.php`
  - Done 2026-10-26: ghi tập trung trong `EnrollmentRegistrar::enroll()` — `DB::transaction` bọc
    `CourseOffering::whereKey()->lockForUpdate()->firstOrFail()` rồi chạy policy trong lock, bắt
    `UniqueConstraintViolationException` (1062) đổi thành 422 "Bạn đã đăng ký lớp này". Controller
    chỉ còn một dòng gọi registrar; admin đăng ký hộ đi qua cùng đường này với `gateRequired: false`.
  - Evidence: `test_the_third_student_is_told_the_class_is_full` (2/2 chỗ, người thứ 3 → 422,
    `seatsLeft()===0`, đúng 2 enrollment) và `test_a_second_request_never_creates_two_active_enrollments`
    (POST lặp → 422, 1 enrollment).
  - Deviation: `seatsLeft()` trả `PHP_INT_MAX` khi `max_students=0` nên nhánh sĩ số không chặn lớp
    không giới hạn — nghiệp vụ đã chốt ở Wave 4, giữ nguyên.

- [x] 5.4 Trang đăng ký học phần cho sinh viên
  - Acceptance: offering không đủ điều kiện vẫn hiện, nút "Đăng ký" disabled kèm lý do in dưới
    dòng; khối "Đăng ký của tôi" hiện tổng tín chỉ đang học / đã tích luỹ.
  - Verify: kiểm tay + feature test render.
  - Files: `resources/views/student/course-offerings/index.blade.php`
  - Done 2026-10-26: `Student\CourseOfferingController::index()` gom ngữ cảnh cả danh sách bằng
    `EnrollmentContext::collect()` (không N+1) và `withCount(['enrollments as active_count'])`;
    lớp đã ACTIVE của chính mình bị loại khỏi "Học phần đang mở", lớp không đủ điều kiện vẫn hiện
    với `<button ... disabled>` + lý do `text-danger` in dưới tên môn. Tiêu đề in kỳ hiện hành và
    trạng thái cổng (đang mở tới … / đang đóng / chưa mở cổng).
  - Evidence: `test_the_registration_page_keeps_blocked_classes_visible_with_their_reason` (đếm
    đúng 2 nút disabled, thấy "Lớp đã đủ sĩ số" và "Cổng đăng ký đã đóng"),
    `test_the_registration_page_summarises_current_and_accumulated_credits` ("Tín chỉ đang học: 6 TC",
    "Tín chỉ đã tích luỹ: 5 TC"). Browser `/student/course-offerings`: "Đăng ký của tôi" 3 dòng có
    nút Hủy đăng ký, "Học phần đang mở" 4 form đăng ký + 1 nút disabled, 2 cột "Còn lại"/"Phòng"
    hiện đủ, console không lỗi.
  - Deviation: heading "Học phần đã đăng ký" (bản Wave 4) đổi thành "Đăng ký của tôi" theo đúng
    wording spec; route cũ `student.offerings.*` và controller `StudentOfferingController` bị xoá
    hoàn toàn, không giữ alias — sidebar/dashboard/layout đã trỏ `student.course-offerings.*`.
    Hai test cũ trong `StudentManagementTest` (đăng ký một lần + hủy, lớp đã đăng ký không hiện lại)
    viết lại theo route `/enroll` + 422 và wording màn mới;
    `test_student_cannot_register_into_a_full_offering` xoá vì `EnrollmentTest` đã phủ nghiệm hơn
    (đếm `seatsLeft()` và số enrollment, không chỉ nhìn flash).

- [x] 5.5 Hủy đăng ký theo quy tắc dữ liệu
  - Acceptance: hủy trong cổng và chưa có điểm danh/điểm ⇒ `DROPPED`, `seatsLeft()`+1; đã có
    dữ liệu ⇒ từ chối; điểm cũ không bị xoá.
  - Verify: feature test cả hai nhánh.
  - Files: `app/Http/Controllers/Student/EnrollmentController.php`
  - Done 2026-10-26: `drop()` chặn truy cập enrollment của người khác (403), bắt `checkDrop()` qua
    `EnrollmentContext::forDrop()` (cổng + `grades`/`attendances`), chỉ đổi `status` sang `DROPPED`
    kèm `dropped_at` — không xoá dòng nào, nên điểm và điểm danh còn nguyên.
  - Evidence: `test_a_student_drops_a_class_and_the_seat_is_returned_immediately` (0 → 1 chỗ),
    `test_a_drop_is_refused_once_the_class_has_data_and_the_data_survives` (flash "Đã có dữ liệu điểm
    danh/điểm, liên hệ giảng viên", 1 grade + 1 attendance vẫn còn), `test_a_drop_is_refused_after_the
    _gate_closes`, `test_a_student_cannot_touch_another_students_enrollment`. Browser: sinh viên 1 hủy
    lớp vừa đăng ký bị từ chối đúng vì có 1 dòng điểm danh môn WEB101.
  - Deviation: nhánh "đã đăng ký lớp này" chỉ tính trạng thái ACTIVE (spec ghi ACTIVE-or-DROPPED) để
    sinh viên học lại được lớp vừa lỡ tay hủy khi cổng còn mở — có test riêng
    `test_a_student_can_register_again_after_dropping_inside_the_gate`. Hủy ngoài cổng reuse nguyên
    văn câu "Cổng đăng ký đã đóng" thay vì bịa wording mới.

- [x] 5.6 Admin đăng ký / hủy hộ (ghi `enrolled_by`)
  - Acceptance: bỏ qua bước kiểm tra cổng, vẫn kiểm sĩ số + tiên quyết; có vết ai đăng ký.
  - Verify: feature test.
  - Files: `app/Http/Controllers/Admin/EnrollmentController.php`
  - Done 2026-10-26: migration `2026_11_05_000001_add_enrolled_by_to_enrollments_table`
    (`foreignId nullable ->constrained('users')->nullOnDelete()`); `Enrollment::registeredByLabel()`
    hiển "Sinh viên tự đăng ký" / "{Tên} đăng ký hộ" / "Chưa ghi nhận" cho dữ liệu cũ. Màn
    `/admin/enrollments` lọc theo học kỳ + sinh viên, form "Đăng ký hộ", nút "Hủy đăng ký" (override),
    "Tải CSV" 9 cột; sidebar Spark thêm mục "Đăng ký học phần" (`bi-journal-text`).
  - Evidence: migration chạy DB dev (394.85ms DONE). `test_an_admin_registers_on_behalf_across_a_closed_gate_and_leaves_a_trace`
    (cổng đã đóng vẫn ghi được, `enrolled_by` = id admin), `test_registering_on_behalf_still_respects_capacity_and_prerequisites`
    (422 "Lớp đã đủ sĩ số", 422 "Chưa học môn tiên quyết: DB101"), `test_an_admin_can_drop_on_behalf_and_the_data_is_kept`,
    `test_the_admin_enrollment_list_is_reachable_and_scopes_by_semester`, `test_a_student_cannot_reach_the_admin_enrollment_screen`.
    Browser: 200 + 10 dòng, export trả `Content-Type: text/csv`, tên file `dang-ky-hoc-phan-2026-10-26.csv`.
  - Deviation: hủy hộ là override duy nhất được phép bỏ qua cả từ chối "đã có dữ liệu" (điểm/điểm danh
    vẫn giữ); đăng ký hộ chỉ bỏ cổng, 5 quy tắc còn lại vẫn chạy. CSV (không phải .xlsx) cho danh sách
    này vì Wave 8 mới dựng exporter Excel/PDF dùng chung.

## Wave 6 — `grading`

- [x] 6.1 `GradingScale` + unit test phủ biên
  - Acceptance: CC=8 GK=7 CK=6 ⇒ 6.4 ⇒ C ⇒ 3.0; `final=4.5` ⇒ F; 8.4999 ⇒ B; GPA (3TC/4.0 +
    2TC/3.0) ⇒ 3.6; điểm liệt 4.0.
  - Verify: `php artisan test --filter=GradingScaleTest`.
  - Files: `app/Support/GradingScale.php`, `tests/Unit/GradingScaleTest.php`
  - Done 2026-10-27: một file duy nhất chứa WEIGHTS 10/20/70, `COMPONENT_LABELS`,
    `components()`, `subjectScore()`, `toLetterAndFour()`, `bandLabel()`, `gpa()`,
    `averageTen()`, `classification()`, `progression()`, `isPassing()`; ba hằng số
    `PASS_SCORE = 5.0`, `FAIL_FLOOR = 4.0`, `AT_RISK_GPA = 2.0`; ngưỡng 80% chuyên cần lấy từ
    `config('academic.min_attendance_rate')`.
  - Evidence: 14 unit test xanh, gồm `test_the_three_components_are_weighted_ten_twenty_seventy`
    (6.4), `test_the_letter_and_four_point_bands_follow_the_approved_table` (8.5/8.4999/5.0/4.99),
    `test_the_gpa_is_credit_weighted_on_the_four_point_scale` (18/5 = 3.6),
    `test_the_classification_reads_the_average_but_an_f_caps_it` (9.2 có môn F ⇒ Trung bình),
    `test_the_scale_never_touches_the_database` (GradingScale là hàm thuần),
    `test_the_pass_threshold_is_shared_with_the_enrollment_policy`.
  - Deviation: acceptance gốc chép "final=4.5 ⇒ F" theo ngưỡng 4.9 của bảng quy đổi; bản duyệt
    phạm vi ghi điểm liệt `<4.0` ⇒ 4.5 chỉ là D. Test
    `test_a_final_exam_below_the_floor_drops_the_subject_to_f` chốt 3.9 ⇒ F (tổng kết 5.73) và
    4.5 ⇒ D. `subjectScore()` nhận `?float` cho cột cuối kỳ (spec ghi `float`) vì nhập dần.

- [x] 6.2 Re-key `grades`: ba cột điểm + `enrollment_id` + `is_published` + `updated_by`
  - Acceptance: unique `(course_offering_id, user_id)`; 15 dòng cũ thành `final_score`.
  - Verify: `php artisan test --filter=GradingTest`.
  - Files: `database/migrations/*_rebuild_grades_table.php`, `app/Models/Grade.php`
  - Done 2026-10-27: migration `2026_11_06_000001_split_grades_into_component_scores.php` thêm
    `enrollment_id` (nullable, cascade), `attendance_score`, `midterm_score` (decimal 4,2),
    `is_published`, `updated_by` (nullOnDelete) rồi chuyển `final_score` sang nullable; unique
    `grade_offering_unique` đã có từ `2026_11_04_000004_drop_legacy_registration_and_score.php`.
    15 dòng cũ được backfill cùng một giá trị vào cả ba cột (trọng số cộng lại 1.0 nên tổng kết
    trả lại đúng điểm cũ) và đặt `is_published = true` để điểm đã hiển thị không bị ẩn đi.
    `Grade` có `subjectScore()`, `band()`, `letter()`, `fourScore()`, `isPassing()`,
    `isPublishable()`, `credits()` và attribute `subject` đọc qua `offering.subject`.
  - Evidence: chạy trên DB dev `quanly_sinh_vien`; vấn tin kiểm tra
    `SUM(enrollment_id IS NULL) = 0` và toàn bộ 15 dòng `is_published = 1`.
    `test_cc8_gk7_ck6_lands_on_6_4_which_is_a_c_and_3_0` xác nhận điểm cũ không tụt sau di cư.
  - Deviation: tên file migration là `split_grades_into_component_scores` thay vì
    `rebuild_grades_table` như hint; `enrollment_id` để nullable (dòng cũ nối lại bằng truy vấn
    join, ưu tiên đăng ký ACTIVE) thay vì bắt buộc ngay.

- [x] 6.3 Grid nhập điểm cho giảng viên
  - Acceptance: chỉ giảng viên phụ trách lưu được; ô trống ⇒ `null`; điểm tổng kết chỉ đọc;
    lưu một mảng nhiều dòng.
  - Verify: feature test quyền + upsert.
  - Files: `app/Http/Controllers/Teacher/GradeController.php`, `resources/views/teacher/grades/*`
  - Done 2026-10-27: `Teacher\GradeController::edit|update|publish`; `update` chạy trong
    `DB::transaction`, chỉ duyệt sinh viên có đăng ký ACTIVE của offering,
    `Grade::updateOrCreate(['course_offering_id', 'user_id'])` với ba cột `?? null` và ghi
    `enrollment_id` + `updated_by`. Lưới `teacher/grades/grid.blade.php` sinh cột từ
    `GradingScale::components()` (nhãn "Chuyên cần (10%)"), ô số `min=0 max=10 step=0.01`, ba
    cột Tổng kết/Chữ/Hệ 4 chỉ hiển thị (text, không phải ô nhập), nút "Lưu tất cả"
    (`bi-check-lg`) và nút "Công bố điểm" (`bi-megaphone`) disabled
    khi sĩ số 0; card thống kê + form publish đặt ngoài `<tbody>`; cột "Thao tác → Nhập điểm" thêm vào
    `/teacher/course-offerings` và sidebar "Lớp của tôi" highlight cho cả `teacher.course-offerings.*`.
  - Evidence: `test_a_teacher_opens_the_component_grid_of_their_own_offering`,
    `test_a_teacher_cannot_open_or_save_the_grid_of_another_teachers_offering` (403 cả GET lẫn POST),
    `test_a_teacher_saves_all_three_components_for_the_whole_class_in_one_request`,
    `test_an_empty_cell_clears_the_score_instead_of_keeping_the_old_value`,
    `test_a_row_with_no_score_yet_shows_a_dash_instead_of_zero`,
    `test_the_grid_header_reports_capacity_class_average_and_failed_subjects` (dùng `rowsOnly()`),
    `test_grades_are_only_written_for_students_of_that_offering`,
    `test_an_out_of_range_score_is_rejected_and_nothing_is_saved` (10.5 ⇒ lỗi `final_score.<id>`,
    không ghi dòng nào), `test_a_student_cannot_reach_the_teacher_grade_grid` (403).
    Browser: 200, "Sĩ số: 5", "Trung bình lớp: 7.60", badge "Đã công bố".
  - Deviation: ô trống là `null` theo đúng spec, nên lỗi validation theo khoá
    `attendance_score.<user_id>`; cột `nullable` không có trong dữ liệu gửi lên ⇒ controller đọc
    bằng `?? null` vì `$request->validate()` không trả key vắng mặt.

- [x] 6.4 Công bố điểm
  - Acceptance: trước publish sinh viên thấy "Chưa công bố"; sau publish thấy đúng điểm.
  - Verify: feature test.
  - Files: `routes/web.php`, `resources/views/student/grades/index.blade.php`
  - Done 2026-10-27: `POST /teacher/course-offerings/{offering}/grades/publish` đặt
    `is_published = true` cho cả lớp; từ chối khi còn sinh viên chưa có `final_score`
    ("Chưa thể công bố: N sinh viên còn thiếu điểm cuối kỳ."). Partial
    `student/grades/table.blade.php` ẩn mọi con số và in badge "Chưa công bố" khi
    `!is_published || subjectScore() === null`; dashboard, bảng điểm và lịch sử học tập cùng
    dùng partial này.
  - Evidence: `test_publishing_is_refused_while_the_final_exam_score_is_missing`,
    `test_students_only_see_their_scores_once_the_teacher_publishes_them` (trước: "Chưa công bố",
    không có "8.50"; sau: có điểm).
  - Deviation: publish là toàn lớp (all-or-nothing), không có công bố từng dòng; điểm admin nhập
    tay tại `/admin/grades` được coi là đã công bố ngay (`is_published = true`) vì admin là nơi
    sửa thay giảng viên.

- [x] 6.5 Bảng điểm + GPA của sinh viên (hệ 4, hệ 10, xếp loại)
  - Acceptance: mỗi môn hiện CC/GK/CK/Tổng kết/Chữ/Hệ 4; đầu trang hiện TB tích luỹ và số tín
    chỉ đã đạt.
  - Verify: feature test với fixture 3 môn đã biết kết quả.
  - Files: `app/Http/Controllers/Student/GradeController.php`
  - Done 2026-10-27: `Student\GradeController::index|history` (xóa `StudentGradeController`),
    lớp đọc mới `app/Support/GradeBook.php` (`current`, `history`, `summary`, `review`,
    `attendanceRate`, `requiredCredits`, `isFailing`); trang `/student/grades` in một dòng
    "Trung bình tích lũy: hệ 4 = …, hệ 10 = …, tín chỉ đã tích lũy = … TC, xếp loại = …" và
    "Đang có N môn F chưa học lại."; `/student/grades/history` thêm cột "Trạng thái lớp"
    (`Enrollment::statusLabel()` → `Đang học (ACTIVE)`); dashboard dùng `GradeBook::current`
    cho stat "Điểm đã công bố" + 5 dòng gần nhất.
  - Evidence: `test_the_student_table_shows_components_letter_and_four_point_score`,
    `test_the_student_page_summarises_gpa_credits_and_classification`,
    `test_the_history_page_keeps_dropped_classes_and_retakes`; `GradeBook::current()` khớp theo
    `course_offering_id` nên dòng điểm di cư không biến mất. Browser: `/student/dashboard`,
    `/student/grades`, `/student/grades/history` đều 200 với dữ liệu thật.
  - Deviation: GPA/tín chỉ tính trên các môn `is_published` của toàn bộ lịch sử (không chỉ kỳ
    hiện hành); "tín chỉ đã tích lũy" loại môn F.

- [x] 6.6 Nút "Xét duyệt" ghi `academic_status` + lý do gợi ý
  - Acceptance: lên lớp / cảnh báo học vụ / đủ điều kiện tốt nghiệp theo `progression()`; admin
    xác nhận, hệ thống không tự đổi.
  - Verify: feature test ba nhánh ngưỡng.
  - Files: `app/Http/Controllers/Admin/AcademicStatusController.php`
  - Done 2026-10-27: `POST /admin/students/{student}/academic-status` validate
    `Rule::in(PersonLabels::statusCodes())`, ghi `academic_status` +
    `academic_status_updated_at` (đã thêm vào fillable/casts của `User`), flash "Đã ghi nhận tình
    trạng … cho …". Card "Xét duyệt học vụ" trong `/admin/students/{id}` hiển thị dòng số liệu,
    badge "Gợi ý: … (STATUS)", danh sách lý do và select trạng thái + nút "Xét duyệt"
    (`bi-check-lg`); không có path nào tự đổi trạng thái.
  - Evidence: `test_the_student_page_suggests_a_review_and_the_admin_confirms_it`,
    `test_the_review_suggests_graduation_once_the_program_credits_are_reached`,
    `test_the_review_keeps_the_student_in_study_when_no_rule_fires`,
    `test_the_review_rejects_a_status_that_is_not_in_the_dictionary` (422),
    `test_a_teacher_cannot_open_the_academic_review_screen` (403). Browser: sinh viên 4 hiện
    "Gợi ý: Cảnh báo học vụ (AT_RISK)", lý do "Tỷ lệ chuyên cần 66.7% dưới ngưỡng 80%.",
    "tín chỉ tích lũy = 8/16 TC".
  - Deviation: `requiredCredits()` trả `null` (không phải 0) khi sinh viên chưa có lớp hành
    chính/chương trình — tránh suy ra "đủ tín chỉ tốt nghiệp" từ con số rỗng; tín chỉ khung
    CTĐT lấy qua `users.class_id → classes.major_id → study_plans → subjects.credits` vì
    `users` không có `major_id`.

## Wave 7 — `attendance-schedule`

- [x] 7.1 Migration `lecture_slots`
  - Acceptance: một offering có 2 slot Thứ 3 + Thứ 5; unique `(offering, week_day, start_time)`.
  - Verify: `php artisan test --filter=AttendanceScheduleTest`.
  - Files: `database/migrations/*_create_lecture_slots.php`, `app/Models/LectureSlot.php`
  - Done 2026-10-27: `2026_11_07_000001_create_lecture_slots_table.php` đúng schema spec
    (week_day tinyint, start/end time, room 50, starts_on/ends_on nullable, unique `slot_unique`);
    `LectureSlot` có `WEEK_DAYS`, `label()` ("Thứ 3 · 07:00–09:30"), `weekDayLabel()`,
    `timeRange()`; `CourseOffering::lectureSlots()`.
  - Evidence: `test_a_class_can_hold_several_weekly_slots` (2 slot Thứ 3 + Thứ 5),
    `test_the_same_day_and_start_time_is_taken_once_per_class`
    (UniqueConstraintViolationException), `test_two_classes_share_the_same_slot_time`
    (unique chỉ chặn trong phạm vi một lớp), `test_a_slot_can_be_limited_to_a_range_of_weeks`.
  - Deviation: spec ghi "2=Thứ 2 ... 7=Chủ nhật" nhưng cũng yêu cầu lưới "6 cột Thứ 2–Thứ 7";
    hai câu này mâu nhau nên chốt theo mã 2 = Thứ 2 ... 7 = Thứ 7 (6 ngày, không xếp lịch Chủ
    nhật) — đúng với "ISO, bỏ 1" và đúng với số cột lưới.

- [x] 7.2 Migration `attendance_sessions` + re-key `attendances`
  - Acceptance: 16 dòng cũ thành `(session, enrollment)`; điểm danh lại cùng buổi ⇒ update,
    không tăng số dòng.
  - Verify: `php artisan test --filter=AttendanceScheduleTest` + tinker đếm 16.
  - Files: migration, `app/Models/{AttendanceSession,Attendance}.php`
  - Done 2026-10-27: `2026_11_07_000002` tạo `attendance_sessions` (unique `session_date_unique`
    theo (lớp, ngày)); `2026_11_07_000003` đọc toàn bộ dòng cũ, dựng buổi học theo
    (lớp của đăng ký, ngày), ghi lại thành `(session, enrollment)` với `status` in hoa, rồi
    tạo lại bảng `attendances` theo schema mới (FK cascade, unique `attendance_session_student_unique`)
    và có `down()` khôi phục shape cũ. Model `Attendance` có hằng số `PRESENT`/`ABSENT`,
    `STATUS_LABELS`, `isPresent()`, `statusLabel()`; `AttendanceSession` có `offering`, `slot`,
    `recorder`, `records`. Người dùng đọc qua `User::attendances()` (hasManyThrough enrollments),
    `Enrollment::attendances()`, `CourseOffering::attendances()`; bỏ `Subject::attendances()`.
    Trang admin thành báo cáo đọc (index + CSV theo ngày), bỏ route POST `/admin/attendances`
    vì điểm danh thuộc giảng viên (7.4); `EnrollmentContext::forDrop()` khóa hủy theo
    `enrollment_id`.
  - Evidence: DB dev chạy migration — 16 dòng cũ thành 15 dòng mới trên 3 buổi (1 dòng bị loại
    vì sinh viên đó không còn đăng ký môn, `enrollment_id` là FK bắt buộc); vấn tin
    `group by status` ⇒ 11 PRESENT / 4 ABSENT. `/admin/attendances?date=2026-10-26` render
    "Điểm danh trong ngày (3 buổi)", `/admin/students/4` còn "Có mặt 2/3 buổi",
    `/student/dashboard` còn 2/1. `test_one_session_line_per_student_of_the_class` dựng đúng
    15 dòng cho một buổi; `test_attending_the_same_session_twice_updates_instead_of_duplicating`
    chốt số dòng không tăng. Toàn bộ 190 test xanh.
  - Deviation: `recorded_by` để nullable + nullOnDelete (spec để bắt buộc) vì dòng di cư từ dữ
    liệu cũ không có giảng viên nào ghi nhận, và xóa tài khoản không được xóa luôn lịch sử
    điểm danh.

- [x] 7.3 `AttendanceRate` + unit test
  - Acceptance: 1 vắng/4 buổi ⇒ 75%; chưa buổi nào ⇒ `null` (UI "Chưa có dữ liệu").
  - Verify: `php artisan test --filter=AttendanceRateTest`.
  - Files: `app/Support/AttendanceRate.php`
  - Done 2026-10-27: `forEnrollment()` trả `['sessions', 'present', 'rate', 'pending']`
    (rate = present/sessions×100, round 1, `null` khi chưa có buổi nào), `forStudent()` gộp mọi
    đăng ký, `warningThreshold()` đọc `config('academic.min_attendance_rate')`,
    `isBelowThreshold()` chấp `null` = chưa cảnh báo. `GradeBook::attendanceRate()` bị xóa —
    `review()` gọi thẳng `AttendanceRate::forStudent()`, thanh tiến độ trong
    `admin/students/show.blade.php` dùng `isBelowThreshold()` thay cho số 80 cứng.
  - Evidence: 6 test xanh — 3 buổi có mặt + 1 vắng ⇒ `75.0`, chưa buổi nào ⇒ `rate = null`,
    buổi lớp đã chấm nhưng sinh viên vào trễ chưa có dòng ⇒ `pending = 1` còn tỷ lệ vẫn tính
    trên buổi đã chấm, buổi không ai được chấm ⇒ không cộng vào `sessions` lẫn `pending`,
    ngưỡng 80.0 (`79.9` cảnh báo, `80.0` không).
  - Deviation: `pending` (buổi chưa điểm danh) là khóa thêm ngoài ba khóa của spec vì spec yêu
    cầu UI đếm riêng "chưa điểm danh (n)".

- [x] 7.4 Màn hình điểm danh nhanh cho giảng viên
  - Acceptance: mặc định "Có mặt", bỏ check người vắng, lưu một lần; chỉ teacher phụ trách.
  - Verify: feature test + kiểm tay.
  - Files: `app/Http/Controllers/Teacher/AttendanceController.php`,
    `resources/views/teacher/attendance/*`
  - Done 2026-10-27: ba route `teacher/course-offerings/{offering}/attendance` (lưới tuần),
    `.../attendance/session/create` (form một buổi), `.../attendance/session` (POST).
    Lưới tuần đọc `lecture_slots` theo `?week=`, ô chỉ hiện trong khoảng `starts_on`/`ends_on`,
    mỗi ô có nhãn "Đã điểm danh x/y" hoặc "Chưa điểm danh" và nút mở form của đúng ngày đó.
    Form mặc định "Có mặt" (input ẩn `statuses[id]=PRESENT`), checkbox "Vắng buổi này" ghi đè
    thành `ABSENT`; `store()` upsert `(offering, session_date)` rồi upsert từng dòng theo
    `(session, enrollment)` nên chấm lại không tăng số dòng; chỉ `enrollments()->active()`;
    `lecture_slot_id` chỉ nhận slot của chính lớp; `note` tối đa 2000. `authorizeTeacher()`
    chặn `teacher_id` khác ⇒ 403 cho mọi action. Nút "Điểm danh" thêm vào
    `teacher/offerings/index.blade.php` cạnh "Nhập điểm".
  - Evidence: `tests/Feature/TeacherAttendanceTest.php` 13 test xanh (suite 210 test, 995
    assertions) — 15 sinh viên lưu một lần ⇒ 15 dòng `PRESENT`, bỏ check ở buổi thứ 5 ⇒ 80%
    (đúng ngưỡng, không cảnh báo) và 60% cho người vắng 2/5 buổi ⇒ badge "Dưới ngưỡng 80%",
    chấm lại cùng ngày ⇒ 1 session/2 dòng + `note`, chưa buổi nào ⇒ "Chưa có dữ liệu" (không
    phải 0%), giảng viên B mở lớp giảng viên A ⇒ 403 trên cả ba route, sinh viên ⇒ 403,
    gửi `statuses` = `LATE` ⇒ 422, sinh viên `DROPPED` không được chấm. Kiểm tay bằng curl với
    `giangvien1@gmail.com`: lưới tuần 26/10 hiện "Thứ 4 · 07:00–09:30 A101", POST buổi
    28/10 ⇒ 3 `ABSENT`/2 `PRESENT`, mở lại hiện "Buổi này đã điểm danh 5/5 sinh viên",
    badge "Đã điểm danh 5/5", trung bình lớp 50%, `/teacher/course-offerings/3/attendance`
    (lớp giảng viên 2) ⇒ 403.
  - Deviation: thêm `AttendanceRate::forEnrollments()` (2 truy vấn cho cả lớp) vì lưới và form
    cần tỷ lệ của từng người; gọi `forEnrollment()` cho từng sinh viên là 3N truy vấn.
    Bên ngoài spec: điều hướng tuần (`?week=`, "Tuần này"), `?slot=` để mở form từ ô lịch và
    ô lịch không có slot thì hiện "—".
  - Caveat: Blade không compile được thuộc tính bound chứa ngoặc đơn trong chuỗi PHP,
    `:title="'Lớp học phần của tôi ('.$n.')'"` sinh mã lỗi cú pháp ⇒ 500 khi trang có dữ liệu
    (test cũ chỉ cover trang rỗng nên không phát hiện). Viết thành
    `title="Lớp học phần của tôi ({{ $n }})"`. Đã sửa ở `teacher/dashboard.blade.php` và
    `teacher/attendance/session.blade.php`, thêm test `teacher_dashboard_lists_the_classes...`.

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
