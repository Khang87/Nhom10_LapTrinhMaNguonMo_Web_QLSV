# Spec: `grading`

Nhóm yêu cầu 6 (Điểm thành phần chuyên cần/bài tập/giữa kỳ/cuối kỳ; điểm trung bình môn;
GPA hệ 4 và hệ 10; tổng kết, xét lên lớp / tốt nghiệp). Phụ thuộc: `enrollment`.
Block: `Grade`, `Admin\GradeController`, `StudentGradeController`, `grades` (re-key).

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
    $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
    $table->decimal('attendance_score', 4, 2)->nullable();
    $table->decimal('midterm_score', 4, 2)->nullable();
    $table->decimal('final_score', 4, 2)->nullable();
    $table->boolean('is_published')->default(false);
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->unique(['course_offering_id', 'user_id'], 'grade_offering_student_unique');
    // drop cột `score` và unique (user_id, subject_id) cũ ở migration riêng, sau bước di cư
});
```

Ba cột `nullable` vì giảng viên nhập dần theo tuần; điểm tổng kết **không lưu** — tính từ
trọng số, để về sau đổi trọng số không phải backfill dữ liệu.

## Tính toán (`app/Support/GradingScale.php`) — mọi con số ở một file

```php
public const WEIGHTS = ['attendance' => 0.10, 'midterm' => 0.20, 'final' => 0.70];
public const PASS_SCORE = 5.0;
public const FAIL_ALL_BAND_MIN = 4.0;   // điểm liệt: thành phần cuối kỳ < 4.0 => F

public static function subjectScore(?float $a, ?float $m, float $f): ?float;  // round 2
public static function toLetterAndFour(float $subjectScore): array;           // ['A', 4.0]
public static function gpa(iterable $rows): ?float;   // Σ(điểm4 × tc) / Σ(tc), round 2
public static function averageTen(iterable $rows): ?float;  // TB hệ 10 theo tín chỉ
public static function classification(float $averageTen, ?float $gpa): string;
```

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

- `Teacher\GradeController` (mới): `index` theo offering của giảng viên, `update` lưu 3 cột.
  Grid nhập liệu dạng bảng: một dòng / sinh viên, ba ô input số + điểm tổng kết chỉ đọc,
  nút "Lưu tất cả" (POST một mảng). Ô trống lưu `null` — giảng viên cần gỡ được một điểm
  nhập nhầm, nên `null` có nghĩa "chưa có điểm", không phải "giữ điểm cũ".
- Quyền: chỉ giảng viên phụ trách offering (`teacher_id`), admin sửa được mọi nơi và
  `updated_by` được ghi lại. Sinh viên **chỉ đọc**, và chỉ khi `is_published`.
- Publish: nút "Công bố điểm" đặt `is_published = true` cho cả lớp; trước đó sinh viên thấy
  "Chưa công bố", không thấy số.
- Validate: `nullable|numeric|between:0,10` cho từng cột; `final_score` bắt buộc trước khi
  publish.

## UI

- `/teacher/course-offerings/{offering}/grades` — grid nhập điểm, sĩ số, trung bình lớp,
  đếm số môn F.
- `/student/grades` — viết lại từ `StudentGradeController`: bảng theo kỳ, cột Điểm CC/GK/CK/
  Tổng kết/Chữ/Hệ 4, khối đầu trang "Trung bình tích lũy: hệ 4 = …, hệ 10 = …, tín chỉ đã
  tích lũy = …, xếp loại = …".
- `/student/grades/history` — "lịch sử học tập": mọi offering đã học kể cả đã hủy/môn học lại.
- `/admin/grades` — giữ màn hình hiện có nhưng thêm bộ lọc kỳ / khoa và cột GPA; nút
  "Xét duyệt" trong trang chi tiết sinh viên.

## Acceptance criteria

- [ ] Nhập CC=8, GK=7, CK=6 ⇒ tổng kết = `0.8 + 1.4 + 4.2 = 6.4` ⇒ chữ C, hệ 4 = 3.0.
- [ ] `final_score` = 4.5 (<4.9) ⇒ F, hệ 4 = 0.0, dù CC/GK cao.
- [ ] GPA hai môn (3 TC điểm 4.0, 2 TC điểm 3.0) = `18/5 = 3.6`.
- [ ] Sinh viên chưa publish không thấy điểm; sau publish thấy đúng dữ liệu.
- [ ] Giảng viên A không lưu được điểm offering của giảng viên B (403).
- [ ] Ô `midterm_score` để trống ⇒ `null`, điểm tổng kết hiển thị "—" thay vì 0.
- [ ] Nút "Xét duyệt" ghi `academic_status` và hiện lý do gợi ý.

## Verify

```
php artisan test --filter=GradingScaleTest     # unit, phủ hết bảng quy đổi + biên 8.5/7.0/6.0/5.0/4.0
php artisan test --filter=GradingTest          # feature: quyền, publish, grid update
```
Biên làm tròn: test `8.4999 → B`, `5.0 → D`, `4.99 → F`, `round(6.444,2)`.

## Out of scope

Weighting cấu hình được, quy đổi theo quy chế tín chỉ 4.0 có A+/B+/C+ (9 bậc), nhập điểm
hàng loạt bằng Excel (hoãn — cần `reporting` quyết xong dependency), phúc tra.

## Open questions

1. Điểm bài tập tách riêng (4 cột, trọng số 10/10/20/60) — chỉ sửa `GradingScale::WEIGHTS`
   và thêm một cột nếu bạn đổi ý sau này.
