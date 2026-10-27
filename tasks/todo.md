
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
