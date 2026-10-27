# Spec: `grading`

Nhóm yêu cầu 6 (Điểm thành phần chuyên cần/bài tập/giữa kỳ/cuối kỳ; điểm trung bình môn;
GPA hệ 4 và hệ 10; tổng kết, xét lên lớp / tốt nghiệp). Phụ thuộc: `enrollment`.
Block: `Grade`, `Admin\GradeController`, `Student\GradeController`, `Teacher\GradeController`,
`GradeBook`, `grades` (re-key).

## Quyết định đã chốt (từ vòng duyệt phạm vi)

**Trọng số mẫu cố định**, không cấu hình được bởi admin:

| Thành phần | Trọng số | Cột lưu | Thang nhập |
|---|---|---|---|
| Chuyên cần | 10% | `attendance_score` | 0 – 10 |
| Giữa kỳ | 20% | `midterm_score` | 0 – 10 |
| Cuối kỳ | 70% | `final_score` | 0 – 10 |

Yêu cầu gốc liệt kê 4 thành phần (chuyên cần, bài tập, giữa kỳ, cuối kỳ) nhưng phương án
đã duyệt gộp "bài tập" vào điểm chuyên cần/thành phần 1 — nên hệ thống lưu **3 cột**.
Nếu muốn tách thành 4 cột với trọng số 10/10/20/60 thì chỉ sửa `GradingScale::WEIGHTS`
và thêm một cột, không đổi phần còn lại của spec.

## Schema

```php
Schema::table('grades', function (Blueprint $table) {
    $table->foreignId('course_offering_id')->constrained()->cascadeOnDelete();
    $table->foreignId('enrollment_id')->nullable()->constrained()->cascadeOnDelete();
    $table->decimal('attendance_score', 4, 2)->nullable();
    $table->decimal('midterm_score', 4, 2)->nullable();
    $table->decimal('final_score', 4, 2)->nullable();
    $table->boolean('is_published')->default(false);
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->unique(['course_offering_id', 'user_id'], 'grade_offering_unique');
    // cột `score` và unique (user_id, subject_id) cũ đã bị drop ở migration 2026_11_04_000004
});
```

`enrollment_id` để `nullable` vì 15 dòng điểm cũ được dựng trước khi bảng `enrollments` có
quan hệ rõ ràng; migration nối lại theo cặp `(course_offering_id, user_id)`, ưu tiên đăng ký
`ACTIVE`. `GradeBook::current()` khớp theo `course_offering_id` chứ không theo `enrollment_id`,
để dữ liệu di cư không âm thầm biến mất khỏi bảng điểm sinh viên.

Ba cột `nullable` vì giảng viên nhập dần theo tuần; điểm tổng kết **không lưu** — tính từ
trọng số, để về sau đổi trọng số không phải backfill dữ liệu.

## Tính toán (`app/Support/GradingScale.php`) — mọi con số ở một file

```php
public const WEIGHTS = ['attendance' => 0.10, 'midterm' => 0.20, 'final' => 0.70];
public const COMPONENT_LABELS = ['attendance' => 'Chuyên cần', 'midterm' => 'Giữa kỳ', 'final' => 'Cuối kỳ'];
public const PASS_SCORE = 5.0;
public const FAIL_FLOOR = 4.0;      // điểm liệt: cuối kỳ < 4.0 ⇒ F, dù CC/GK cao
public const AT_RISK_GPA = 2.0;     // ngưỡng cảnh báo học vụ

public static function components(): array;  // [{key, label, weight%}] — 3 màn hình dùng chung cột
public static function subjectScore(?float $a, ?float $m, ?float $f): ?float;  // round 2, null khi chưa nhập cột nào
public static function toLetterAndFour(float $subjectScore, ?float $final = null): array;  // ['letter' => 'A', 'four' => 4.0]
public static function bandLabel(float $subjectScore, ?float $final = null): string;        // "C (3.00)"
public static function gpa(iterable $rows): ?float;   // Σ(điểm4 × tc) / Σ(tc), round 2
public static function averageTen(iterable $rows): ?float;  // TB hệ 10 theo tín chỉ
public static function classification(float $averageTen, bool $hasFailingSubject = false): string;
public static function progression(array $context): array;  // {status, reasons[]}
```

`GradingScale` là hàm thuần — không query, không session. Lớp đọc `App\Support\GradeBook`
mới thêm ở wave này gom mọi truy vấn: `current()` (điểm các lớp đang học), `history()` (mọi
môn đã học), `summary()` (GPA / hệ 10 / tín chỉ tích luỹ / số môn F / xếp loại),
`review()` (summary + tỷ lệ chuyên cần + tín chỉ khung CTĐT + gợi ý xét duyệt),
`isFailing()`. Controller không tự tính điểm.

**Quy đổi hệ 4 cố định** (hệ 10 → chữ → hệ 4), chọn để nhất quán với điểm đạt 5.0:

| Điểm tổng kết môn (hệ 10) | Chữ | Hệ 4 |
|---|---|---|
| 8.5 – 10.0 | A | 4.0 |
| 7.0 – 8.4 | B | 3.5 |
| 6.0 – 6.9 | C | 3.0 |
| 5.0 – 5.9 | D | 2.5 |
| 0.0 – 4.9 | F | 0.0 |

GPA hệ 4 = `Σ(hệ4 × tín chỉ) / Σ(tín chỉ)`, làm tròn 2 chữ số, chỉ tính môn đã
`is_published`. Trung bình hệ 10 toàn khoá dùng cùng trọng số tín chỉ trên điểm tổng kết môn.

Hai khoảng 4.0 – 4.9 và 5.0 trở lên chỉ là hệ quả của thang; **điểm liệt là luật riêng**:
cuối kỳ < `FAIL_FLOOR` = 4.0 ⇒ F, bất kể tổng kết hệ 10 (CC=10, GK=10, CK=3.9 ⇒ tổng kết
5.73 nhưng vẫn là F, trong khi cùng tổng kết đó với CK=4.5 chỉ là D). Ngưỡng 4.0 là con số
đã được duyệt ở vòng duyệt phạm vi.

**Xếp loại học lực** (theo trung bình hệ 10 toàn khoá, kèm điều kiện không có môn F):

| Khoảng | Nhãn |
|---|---|
| ≥ 9.0 và không có F | Xuất sắc |
| 8.0 – 8.9 và không có F | Giỏi |
| 6.5 – 7.9 và không có F | Khá |
| 5.0 – 6.4 | Trung bình |
| < 5.0 | Yếu |

**Xét lên lớp / tốt nghiệp** (`GradingScale::progression()`), trả về `{status, reasons[]}`:

- Đủ điều kiện lên lớp/năm học tiếp: không có môn F trong kỳ vừa kết thúc.
- Cảnh báo học vụ (`AT_RISK`): GPA hệ 4 < 2.0 **hoặc** ≥ 2 môn F **hoặc** tỷ lệ chuyên cần
  toàn kỳ < 80% (đọc từ `attendance-schedule`).
- Đủ điều kiện tốt nghiệp (gợi ý): đã tích luỹ ≥ tổng tín chỉ khung CTĐT của chuyên ngành
  (`study_plans` count) và không còn môn F chưa học lại.
- Hệ thống **chỉ gợi ý**; admin xác nhận bằng nút "Xét duyệt" ghi `users.academic_status`
  + `academic_status_updated_at`. Lý do: quyết định thật thuộc hội đồng, phần mềm không
  tự đuổi học.

## Nhập điểm

- `Teacher\GradeController` (mới): `edit` hiển lưới theo offering của giảng viên kèm sĩ số,
  trung bình lớp, số môn F; `update` lưu ba cột cho toàn lớp trong một transaction
  (`Grade::updateOrCreate` theo `(course_offering_id, user_id)`), `publish` công bố cả lớp.
  Grid nhập liệu dạng bảng: một dòng / sinh viên, ba ô input số + điểm tổng kết / chữ / hệ 4
  chỉ đọc, nút "Lưu tất cả" (POST một mảng). Ô trống lưu `null` — giảng viên cần gỡ được một
  điểm nhập nhầm, nên `null` có nghĩa "chưa có điểm", không phải "giữ điểm cũ".
- Quyền: chỉ giảng viên phụ trách offering (`teacher_id`, không thì 403), admin sửa được mọi
  nơi và `updated_by` được ghi lại. Sinh viên **chỉ đọc**, và chỉ khi `is_published`.
- Publish: nút "Công bố điểm" đặt `is_published = true` cho cả lớp — dạng all-or-nothing,
  không công bố nhỏ lẻ từng dòng; từ chối và báo "Chưa thể công bố: N sinh viên còn thiếu
  điểm cuối kỳ" khi vẫn còn sinh viên chưa có `final_score`.
- Admin nhập tay tại `/admin/grades`: điểm admin ghi là điểm đã được phép công bố nên
  `is_published = true` ngay; ba ô đều trống ⇒ lỗi "Cần nhập ít nhất một cột điểm."; muốn
  lưu phải có đăng ký `ACTIVE` ở lớp đó.
- Validate: `nullable|numeric|between:0,10` cho từng cột qua khoá mảng
  `attendance_score.{user_id}`; `final_score` bắt buộc trước khi publish.

## UI

- `/teacher/course-offerings/{offering}/grades` — `resources/views/teacher/grades/grid`
  (lưới nhập điểm, sĩ số, trung bình lớp, đếm số môn F, nhãn "Chuyên cần (10%)").
- `/teacher/course-offerings` — thêm cột "Thao tác" với nút "Nhập điểm" vào lưới trên.
- `/student/grades` — `Student\GradeController::index`: khối đầu trang "Trung bình tích lũy:
  hệ 4 = …, hệ 10 = …, tín chỉ đã tích lũy = … TC, xếp loại = …" và dòng "Đang có N môn F
  chưa học lại.".
- `/student/grades/history` — "lịch sử học tập": mọi offering đã học kể cả đã hủy/môn học
  lại, thêm cột "Trạng thái lớp" (`Đang học (ACTIVE)` / `Đã hủy (DROPPED)`).
- `resources/views/student/grades/table` — partial bảng điểm dùng chung cho ba màn hình
  sinh viên và cho dashboard; khi chưa publish mọi con số ẩn đi và chỉ còn badge
  "Chưa công bố".
- `/admin/grades` — bộ lọc kỳ / khoa / sinh viên + nút "Tìm kiếm", dòng tóm tắt GPA trên
  tập đang lọc, form nhập điểm tay, "Tải CSV" mang theo bộ lọc.
- `/admin/students/{student}` — card "Xét duyệt học vụ": một dòng số liệu
  `GPA hệ 4 = …, hệ 10 = …, tín chỉ tích lũy = 8/16 TC, môn F = N`, badge
  `Gợi ý: Cảnh báo học vụ (AT_RISK)`, danh sách lý do và select trạng thái + nút "Xét duyệt".
- Sidebar Spark: sinh viên có thêm mục "Lịch sử học tập"; mục "Lớp của tôi" của giảng viên
  được coi là đang mở cho cả `teacher.course-offerings.*`.

## Acceptance criteria

- [x] Nhập CC=8, GK=7, CK=6 ⇒ tổng kết = `0.8 + 1.4 + 4.2 = 6.4` ⇒ chữ C, hệ 4 = 3.0.
- [x] Điểm liệt: CK=3.9 (<4.0) ⇒ tổng kết 5.73 vẫn là F, hệ 4 = 0.0, dù CC/GK = 10.
- [x] GPA hai môn (3 TC điểm 4.0, 2 TC điểm 3.0) = `18/5 = 3.6`.
- [x] Sinh viên chưa publish không thấy điểm; sau publish thấy đúng dữ liệu.
- [x] Giảng viên A không lưu được điểm offering của giảng viên B (403).
- [x] Ô `midterm_score` để trống ⇒ `null`, điểm tổng kết hiển thị "—" thay vì 0.
- [x] Nút "Xét duyệt" ghi `academic_status` và hiện lý do gợi ý.

## Verify

```
php artisan test --filter=GradingScaleTest     # unit, phủ hết bảng quy đổi + biên 8.5/7.0/6.0/5.0/4.0
php artisan test --filter=GradingTest          # feature: quyền, publish, grid update, xét học vụ
```
Biên làm tròn: test `8.4999 → B`, `5.0 → D`, `4.99 → F`, `subjectScore(5, 5, 9.99) = 8.49`,
`subjectScore(10, 10, 3.9) = 5.73`.

## Deviations đã ghi nhận khi triển khai

- Bảng duyệt vòng phạm vi ghi điểm liệt `<5.0`; bản triển khai dùng `FAIL_FLOOR = 4.0` đúng
  theo câu "điểm liệt cuối kỳ <4.0" trong chính quyết định đó. Nếu muốn siết về 5.0 thì chỉ
  sửa một hằng số.
- `subjectScore()` nhận `?float` cho cả ba cột (spec ghi `float $f`) vì cột cuối kỳ cũng nhập
  dần; chưa nhập cột nào ⇒ `null` thay vì 0.
- `progression()` dùng `credits_required = null` (chứ không phải 0) khi sinh viên chưa có lớp
  hành chính/chương trình, để không suy ra "đủ tín chỉ tốt nghiệp" từ một con số rỗng.
- Tỷ lệ chuyên cần hiện đọc từ bảng `attendances` hiện có; `attendance-schedule` (wave 7) sẽ
  thay nguồn dữ liệu, công thức trong `GradeBook::attendanceRate()` giữ nguyên.

## Out of scope

Weighting cấu hình được, quy đổi theo quy chế tín chỉ 4.0 có A+/B+/C+ (9 bậc), nhập điểm
hàng loạt bằng Excel (hoãn — cần `reporting` quyết xong dependency), phúc tra.

## Open questions

1. Điểm bài tập tách riêng (4 cột, trọng số 10/10/20/60) — chỉ sửa `GradingScale::WEIGHTS`
   và thêm một cột nếu bạn đổi ý sau này.
