
---

## Wave 10 — Màn hình bổ sung theo yêu cầu mở rộng

- [x] 10.1 Mở lớp học phần hàng loạt theo khung chương trình
  - Acceptance: chọn chuyên ngành + học kỳ thật + học kỳ đào tạo + mã nhóm; xem trước môn đã có
    lớp / chưa có lớp; mở các môn được chọn thành lớp học phần trong một lần.
  - Verify: feature test + kiểm tay bằng curl.
  - Files: `app/Support/CurriculumOfferings.php`,
    `app/Http/Controllers/Admin/CurriculumOfferingController.php`,
    `resources/views/admin/curriculum-offerings/index.blade.php`
  - Done 2026-10-27: `CurriculumOfferings::preview()` ghép `study_plans` của (chuyên ngành, kỳ
    đào tạo) với `course_offerings` của học kỳ thật đang chọn ⇒ mỗi môn có `group_code` dự kiến,
    `group_taken` (mã nhóm đã bị môn đó dùng trong kỳ) và `other_groups` (nhóm đang có).
    `open()` bỏ qua dòng `group_taken` rồi tạo phần còn lại trong một transaction, giảng viên để
    `null` (phân công sau bằng form một lớp). Route GET/POST
    `admin/course-offerings/open-by-plan`; bảng xem trước có checkbox per môn (môn đã mở thì
    disabled), ô "Sĩ số tối đa" (0 = không giới hạn) và checkbox "Mở cho sinh viên đăng ký" giống
    form lớp học phần; link vào màn hình đặt ở header trang "Quản lý lớp học phần".
  - Evidence: `tests/Feature/CurriculumOfferingTest.php` 7 test (suite 230 test, 1080 assertions)
    — xem trước "2 môn · 6 TC · mở thêm được 1 lớp." với WEB101 đã có TN01 và DB101 "Chưa mở";
    chỉ đúng môn của kỳ đào tạo được chọn (kỳ 1 = WEB101, kỳ 2 = SEC201 ELECTIVE); mở môn đã chọn
    ⇒ `TN02`/40 TC/is_open, `teacher_id` null, môn đã có lớp không nhân đôi; đơn gửi vẫn liệt kê
    môn đã chiếm nhóm ⇒ chỉ tạo 1 lớp; toàn bộ môn đã phủ ⇒ flash `error` "Không mở được lớp nào…"
    và không tạo dòng nào; thiếu `subjects` ⇒ 422, `max_students` 70000 ⇒ 422 kèm đúng câu giải
    thích dùng ở form lớp; giảng viên/sinh viên ⇒ 403. Kiểm tay curl trên DB dev: khung KTPM kỳ 1
    hiện WEB101 badge "TN01, TN02" (checkbox disabled) và GTA101 "Chưa mở", POST nhóm TN03 sĩ số
    35 ⇒ flash "Đã mở 1 lớp học phần nhóm TN03 cho Kỹ thuật phần mềm (học kỳ đào tạo 1) trong Học
    kỳ 1 năm học 2026-2027." và một dòng `course_offerings` (đã xóa lại sau khi kiểm).
    `?group_code[]=` (mảng) không làm vỡ trang: controller chỉ nhận chuỗi, ngược lại dùng TN01.
  - Deviation: mã nhóm nằm trên thanh lọc vì nó quyết định môn nào "đã có lớp" (cùng môn có thể
    mở thêm nhóm khác); spec ý tưởng chỉ nói "mở theo khung". Sĩ số và trạng thái mở cổng áp
    dụng chung cho cả lô, không nhập riêng từng môn.

- [x] 10.2 Học phí theo tín chỉ và mã QR VietQR
  - Acceptance: admin phát hành một thông báo học phí mỗi học kỳ cho toàn bộ sinh viên đang học,
    theo số tín chỉ đã đăng ký; sinh viên xem số phải nộp, mã QR và nội dung chuyển khoản; kế
    toán ghi nhận đã thu / hủy ghi nhận.
  - Verify: feature test + kiểm tay bằng curl.
  - Files: `database/migrations/2026_11_08_000001_create_tuition_invoices_table.php`,
    `app/Models/TuitionInvoice.php`, `app/Support/Tuition.php`,
    `app/Http/Controllers/Admin/TuitionController.php`,
    `app/Http/Controllers/Student/TuitionController.php`,
    `resources/views/admin/tuition/index.blade.php`, `resources/views/student/tuition.blade.php`
  - Done 2026-10-27: bảng `tuition_invoices` một dòng cho (sinh viên, học kỳ) — `credits`,
    `amount`, `content` (nội dung chuyển khoản), `paid_at`/`collected_by`/`note`. Đơn giá một tín
    chỉ lấy từ `config('academic.tuition_per_credit')` (biến `ACADEMIC_TUITION_PER_CREDIT`), mặc
    định 0 = chưa cấu hình ⇒ nút "Phát hành" disabled và controller từ chối, hệ thống không tự bịa
    số tiền. `Tuition::declare()` nhóm `enrollments` ACTIVE theo sinh viên trong học kỳ,
    `firstOrNew` rồi chỉ ghi khi tín chỉ thay đổi: thông báo đã thu bị đóng băng, thông báo chưa
    thu được làm mới vì sinh viên còn quyền thêm/hủy môn tới hết cổng đăng ký. `content` dựng từ
    mã số sinh viên (`QLSV<mã số>-HK<kỳ>`), cắt 25 ký tự vì VietQR/ngân hàng cắt ngầm; mã chưa có
    thì fallback `SV<id>`. Ảnh QR là URL động của `img.vietqr.io` (kèm `amount` + `addInfo`), chỉ
    tồn tại khi cấu hình đủ `TUITION_BANK_CODE`/`TUITION_BANK_ACCOUNT`. Trang admin có lọc theo học
    kỳ, 3 thẻ thống kê (đã phát hành / đã thu / còn chưa thu) và nút thu — hủy thu; trang sinh viên
    hiện từng kỳ, khi kỳ hiện hành chưa phát hành thì báo số tín chỉ đang học kèm số tạm tính.
    Sidebar: thêm "Học phí" cho admin và student. Danh sách admin chia trang 10 thông báo, còn
    thẻ "Đã thu"/"Còn chưa thu" tính bằng SUM trên cả học kỳ nên không đổi theo trang.
  - Evidence: `tests/Feature/TuitionTest.php` 11 test (suite 242 test, 1170 assertions) — 7 TC
    kỳ này × 350.000 đ = 2.450.000 đ, môn đã hủy và môn ở kỳ khác không tính; nội dung chuyển
    khoản đúng 25 ký tự khi mã số dài tối đa và không rời ký tự `-` thừa; declare chạy 2 lần ⇒
    `created 2 / refreshed 0` rồi `0 / 0`; thêm môn ⇒ `refreshed 1`, sau khi đã thu ⇒ không đổi
    `credits`; `qr_url` null khi chưa cấu hình ngân hàng và đúng
    `https://img.vietqr.io/image/vietcombank-1234567890-compact2.png?amount=1050000&addInfo=QLSV20240001-HK1`
    khi có; trang admin hiển thị "350.000 đ", "1.050.000 đ", "Chưa thu" → "Đã thu" (kèm `note`,
    người thu) → "Hủy ghi nhận thu"; sinh viên chỉ thấy thông báo của mình; giảng viên/sinh viên
    gọi route admin ⇒ 403; xóa sinh viên ⇒ xóa thông báo; 12 thông báo ⇒ trang 1 chỉ 10 dòng
    nhưng thẻ tiền vẫn "12.600.000 đ · 12 sinh viên" và trang 2 hiện 2 dòng còn lại. Kiểm tay curl trên DB dev với mức tạm
    380.000 đ/TC: phát hành 5 thông báo HK1-2026-2027 (11 TC = 4.180.000 đ, còn chưa thu
    16.340.000 đ), ghi nhận thu 1 sinh viên ⇒ thẻ "Đã thu 4.180.000 đ · 1 sinh viên", trang
    `/student/tuition` hiển thị ảnh QR `...compact2.png?amount=4180000&addInfo=QLSV20240001-HK1`.
    Cấu hình và dữ liệu kiểm đã gỡ lại sau khi quay.
  - Deviation: ý tưởng gốc (Manguon) có thêm trạng thái "đã nộp một phần" và kỳ hạn nộp; ở đây mỗi
    thông báo chỉ có 2 trạng thái chưa thu / đã thu vì schema điểm danh - học vụ chưa có khái niệm
    hạn nộp hay thu một phần.

- [x] 10.3 Kiểm chứng lại hai ý tưởng "reset mật khẩu" và "trang chi tiết sinh viên"
  - Acceptance: xác nhận hai ý tưởng này đã được hệ thống phủ trước khi tính tới màn hình mới.
  - Done 2026-10-27: quên mật khẩu bằng OTP 6 số (Nodemailer) gồm 5 route có throttle
    `password.request/email/verify/reset/update`, link "Quên mật khẩu?" trên trang đăng nhập,
    10 test trong `tests/Feature/PasswordResetTest.php`; admin còn đổi được mật khẩu trực tiếp ở
    form tài khoản/sinh viên (`AccountController`, `StudentController`). Trang hồ sơ
    `/admin/students/{id}` đã có: thông tin cá nhân, lớp hành chính - khóa - khoa - chuyên ngành,
    4 thẻ thống kê (GPA hệ 4, điểm TB hệ 10, tín chỉ tích lũy, tỷ lệ chuyên cần), danh sách lớp học
    phần đã đăng ký, bảng kết quả học tập và khối xét duyệt học vụ kèm gợi ý trạng thái.
  - Evidence: curl `GET /admin/students/4` trên DB dev trả 200 với "GPA hệ 4 3.50 Khá · Điểm TB hệ
    10 7.44 · 8 / 16 TC · Tỷ lệ chuyên cần 75% Có mặt 3/4 buổi" và dòng "Gợi ý: Cảnh báo học vụ
    (AT_RISK) — Tỷ lệ chuyên cần 75% dưới ngưỡng 80%."; suite 242 test xanh.
  - Kết luận: không xây màn hình mới cho hai ý tưởng này. Trong danh sách ý tưởng, TKB theo tuần,
    mở lớp hàng loạt và học phí là ba thứ còn thiếu phải xây; hai ý tưởng còn lại đã có sẵn từ các
    wave trước.

- [x] 10.4 Seeder dữ liệu mẫu dày phủ mọi màn hình (25 giảng viên, 16 môn)
  - Acceptance: `php artisan db:seed` chạy hết trên môi trường sạch, dựng đủ dữ liệu cho từng
    màn hình đã xây, chạy lại không tạo bản sao và không tạo dữ liệu mâu thuẫn quy tắc đăng ký.
  - Done 2026-10-27: viết lại `database/seeders/DatabaseSeeder.php` theo schema main (bản cũ ghi
    vào cột `attendances.user_id/subject_id/date` đã bị wave 7 bỏ nên `db:seed` hỏng). Dữ liệu:
    1 quản trị + 25 giảng viên + 24 sinh viên, 3 khoa - 4 chuyên ngành - 16 môn, 33 dòng khung
    chương trình, 5 lớp hành chính, hai học kỳ (kỳ trước đã đóng + kỳ hiện hành đang mở cổng),
    5 lớp kỳ trước + 14 lớp kỳ này, mỗi lớp một lịch lên lớp, 4 buổi điểm danh đã diễn ra, điểm
    ba cột thành phần. Mật khẩu chỉ băm một lần cho 50 tài khoản.
    Ba quyết định giữ cho dữ liệu tự thuận: (1) vị trí dòng trong danh sách lớp quyết định
    (ngày, ca) theo công thức `i % 5 + 2` và `intdiv(i, 5)` nên không hai lớp nào trùng giờ,
    (2) 20240007 trượt OOP101 nên chỉ đăng ký ADT101 + MOB201 là hai môn không cần tiên quyết,
    (3) môn song hành SEC201 luôn đăng ký kèm NET101 trong cùng kỳ. Điểm danh lấy lịch do chính
    seeder tạo (`$slotsByOffering`) chứ không `sole()` trên `course_offering_id`, vì một lớp ngoài
    đời thực có thể có nhiều lịch.
    Các lớp không ai đăng ký tồn tại có chủ đích để màn hình chọn lớp hiển thị trạng thái ngoại lệ:
    AI101 (thiếu tiên quyết), DB101 nhóm 2 (hết chỗ), CLO201 nhóm 2 (chưa mở cổng), BIG201 (chưa
    phân công giảng viên). Ba sinh viên 20240005/20240003/20240007 nghỉ có chủ đích để tỷ lệ chuyên
    cần rơi xuống 50%/75%/75%, dưới ngưỡng 80% của màn hình học vụ.
  - Evidence: `tests/Feature/DemoDataTest.php` 13 test (suite 255 test, 3416 assertions) — đếm danh
    mục, ba trạng thái người dùng, hai học kỳ đúng một kỳ đang chạy, 19 lớp - 19 lịch,
    `TimetableService::conflicts()` rỗng, mọi đăng ký ACTIVE của kỳ này qua `EnrollmentPolicy::check`
    với `already_active = false`, lớp chưa mở/hết chỗ/thiếu tiên quyết không có ai đứng tên,
    sinh viên đình chỉ - nghỉ học chỉ có DROPPED và không có điểm danh, mọi dòng điểm danh khớp
    lớp - đăng ký - buổi và buổi rơi đúng thứ của lịch, mọi dòng điểm có `enrollment_id` và không
    công bố khi thiếu điểm cuối kỳ, `AttendanceRate` 50/75/100, chạy seeder hai lần số dòng bằng
    nhau, lớp có sẵn lịch thừa vẫn seed được, và 17 màn hình admin/giảng viên/sinh viên trả 200.
    Kiểm chứng bằng cách đặt lỗi: sửa `lectureDate()` thành `$weekDay % 7` ⇒ test điểm danh đỏ;
    thêm WEB101 vào danh sách của 20240007 ⇒ test policy đỏ; đổi `$slotsByOffering` thành `sole()`
    ⇒ test lịch thừa đỏ. Cả ba đã trả lại code và xanh. curl trên DB dev sau `db:seed`: toàn bộ
    màn hình 200, hồ sơ 20240005 hiện "50,3% dưới ngưỡng 80%".
  - Deviation: bản Manguon có sẵn dữ liệu mẫu nhưng schema khác nên chỉ tham khảo cách chia vai
    trò - học kỳ; ở đây dựng lại toàn bộ trên schema main, vẫn chỉ có PRESENT/ABSENT và trọng số
    10/20/70. DB dev giữ các dòng cũ do seeder trước để lại (9 đăng ký, 4 lịch) nên tỷ lệ chuyên
    cần hiển thị 50,3% thay vì 50,0%; muốn sạch cần `migrate:fresh --seed` — chưa chạy vì sẽ xóa
    dữ liệu người dùng gõ tay trên môi trường dev.

## Wave 11 — Thống kê & báo cáo (nhóm yêu cầu 8)

- [x] 11.1 `ReportFilter` + `Reports`: bốn bảng số dựng từ dữ liệu đào tạo
  - Acceptance: một bộ lọc (kỳ, khoa, chuyên ngành, lớp, khóa, giới tính, tình trạng học tập)
    cho bốn báo cáo thống kê sinh viên / phổ điểm / chuyên cần / xếp hạng GPA; mọi con số tính
    lại từ `users`, `enrollments`, `grades`, `attendance_sessions`, không có bảng trung gian.
  - Done 2026-10-27: `app/Support/ReportFilter.php` là value object đọc từ query, bỏ mọi giá trị
    không phải số nguyên dương thay vì báo lỗi (thanh lọc luôn có ô trống), mặc định kỳ hiện hành.
    `app/Support/Reports.php` trả `[title, header, rows]` cho cả màn hình lẫn file xuất. Phổ điểm
    lấy khoảng từ `GradingScale::buckets()` (dẫn xuất từ `FAIL_FLOOR` + `BANDS`) nên bảng khoảng
    và thang chữ không thể lệch nhau. Điểm TB chuyên cần là trung bình cộng tỷ lệ từng cá nhân,
    không phải tỷ lệ số buổi — hai con số này hay bị nhầm nên test khóa chặt bằng số tuyệt đối.
- [x] 11.2 `ReportExporter` CSV/XLSX/PDF
  - Acceptance: cùng một bảng số tải ra được ba định dạng, file đúng định dạng thật (không phải
    đổi tên), tiếng Việt có dấu hiển thị đúng trong PDF.
  - Done 2026-10-27: khế ước `ReportExporter::export(filename, report)` + `ReportFormats::make()`
    để controller không biết định dạng. `CsvExporter` giữ nguyên khế ước TSV + BOM UTF-8 của
    `App\Support\Csv` vì hai test hồi quy viết từ trước khi có Excel/PDF. `XlsxExporter` dùng
    phpspreadsheet (tiêu đề đậm, freeze pane, số nguyên/thập phân giữ kiểu số, số liệu kiểu chuỗi
    như MSSV ghi dạng `@` để Excel không cắt số 0 đầu). `PdfExporter` nhúng DejaVu Sans, tự chuyển
    khổ ngang từ cột thứ bảy.
- [x] 11.3 Màn hình thống kê + route
  - Acceptance: admin và giảng viên cùng dùng một màn hình, có tab bốn báo cáo, thanh lọc và ba nút xuất.
  - Done 2026-10-27: `admin/reports/{type}` và `teacher/reports/{type}` trỏ vào một view
    `resources/views/reports/index.blade.php`; `resources/views/reports/pdf.blade.php` là bản in
    đứng độc lập. Cột có "(%)" tự vẽ thanh progress. Sidebar Spark có mục cho cả hai vai trò.
- [x] 11.4 Phân quyền phạm vi + mở rộng export cũ sang ba định dạng
  - Acceptance: giảng viên chỉ thấy số liệu lớp mình; các route export đã tồn tại (điểm, điểm
    danh, đăng ký học phần, thời khóa biểu) nhận thêm `format=xlsx|pdf` mà không đổi hành vi CSV.
  - Done 2026-10-27: `Teacher\ReportController` kế thừa controller admin, chỉ ghi đè cách dựng bộ
    lọc (`forOfferings()`), danh sách ô lọc và route base. `ReportFilter::$offeringIds` phân biệt
    `null` (không giới hạn) và `[]` (chưa được phân công lớp nào) — nếu chỉ kiểm tra `!== []` thì
    giảng viên chưa có lớp sẽ nhìn thấy số liệu toàn trường. Bốn controller export cũ đổi sang
    dựng `[title, header, rows]` rồi gọi `ReportFormats::download()`, mặc định vẫn là CSV để
    không phá liên kết và test đang có. Nút xuất chung đóng vào `x-export-buttons`.
  - Evidence: `tests/Feature/ReportingTest.php` 14 test — census đếm tuyệt đối theo sáu nhóm,
    phổ điểm cộng dồn đúng sĩ số, chuyên cần trung bình các tỷ lệ (85,7), xếp hạng GPA chính xác
    từng dòng, một bộ lọc hẹp thì cả bốn bảng cùng hẹp, ba định dạng trả đúng magic byte
    (`PK` / `%PDF` / BOM) với tên file không dấu, type/định dạng lạ trả 404 chứ không 500,
    giảng viên B thấy trang rỗng thay vì số liệu của giảng viên A, và màn admin chặn 403 với
    giảng viên/sinh viên. Hai màn hình cũ cũng được kiểm cùng ba định dạng.
  - Deviation: giảng viên chưa được phân công lớp nào nhận báo cáo rỗng (HTTP 200, dòng
    "Bộ lọc hiện tại chưa có dữ liệu.") thay vì 403, vì phạm vi nằm trong tài khoản chứ không
    nằm trong URL nên không có đối tượng để so quyền.
