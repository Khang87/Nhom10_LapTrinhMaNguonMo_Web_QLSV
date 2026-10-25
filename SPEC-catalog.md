# Spec: `catalog`

Nhóm yêu cầu 4 (Môn học, tín chỉ, tiên quyết/song hành, khung chương trình đào tạo).
Phụ thuộc: `identity-roles`. Block: `subjects`, `DatabaseSeeder`.

## Objective

Quản trị tri thức đào tạo tĩnh (không gắn với học kỳ, không gắn với người):

- **Khoa** (`departments`) và **chuyên ngành** (`majors`) — hai mức phân loại bắt buộc
  cho yêu cầu 2 (phân loại sinh viên) và yêu cầu 8 (thống kê theo khoa).
- **Môn học** (`subjects`) giữ mã, tên, tín chỉ; thêm thuộc tính chuyên ngành và mô tả.
- **Môn tiên quyết** / **môn song hành** — quan hệ giữa hai môn, dùng cho `enrollment`.
- **Khung chương trình đào tạo** (`study_plans`) — môn nào thuộc chuyên ngành nào, dự kiến
  bố trí ở học kỳ thứ mấy trong khoá, bắt buộc hay tự chọn.

Thành công = admin khai báo được "Lập trình Web (WEB101) tiên quyết cho Cơ sở dữ liệu
(DB101)" và hệ thống đọc được quan hệ đó ở màn hình đăng ký học phần.

## Schema

```php
// departments
$table->string('code', 20)->unique();      // "CNTT"
$table->string('name', 150);               // "Khoa Công nghệ thông tin"

// majors
$table->foreignId('department_id')->constrained()->restrictOnDelete();
$table->string('code', 20)->unique();      // "HTTT"
$table->string('name', 150);

// subjects  (bổ sung cột, không tạo bảng mới)
$table->foreignId('major_id')->nullable()->constrained('majors')->nullOnDelete();
$table->text('description')->nullable();
$table->unsignedTinyInteger('total_sessions')->nullable();  // số tiết dự kiến, dùng cho TKB

// subject_prerequisites
$table->foreignId('subject_id')->constrained()->cascadeOnDelete();
$table->foreignId('prerequisite_subject_id')->constrained('subjects')->cascadeOnDelete();
$table->unique(['subject_id', 'prerequisite_subject_id'], 'subject_prereq_unique');

// subject_concurrents  (cấu trúc giống hệt, tên bảng + unique riêng)
$table->unique(['subject_id', 'concurrent_subject_id'], 'subject_concurrent_unique');

// study_plans  = khung chương trình đào tạo theo chuyên ngành
$table->foreignId('major_id')->constrained('majors')->cascadeOnDelete();
$table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
$table->unsignedTinyInteger('plan_semester');   // học kỳ thứ mấy trong khoá (1..8)
$table->enum('kind', ['REQUIRED', 'ELECTIVE'])->default('REQUIRED');
$table->unique(['major_id', 'subject_id'], 'study_plan_unique');
```

`restrictOnDelete` trên `majors.department_id`: xoá khoa đang còn chuyên ngành là lỗi
được báo cáo, không phải im lặng mất dữ liệu.

## Quy tắc nghiệp vụ (đặt ở `app/Support/PrerequisiteRules.php`)

1. Một môn **không được là tiên quyết của chính nó** (`subject_id !== prerequisite_subject_id`).
2. Chuỗi tiên quyết **không được có chu trình** — kiểm tra bằng DFS trước khi lưu, thông
   báo chỉ ra đường dẫn vi phạm (VD: `WEB101 → DB101 → WEB101`).
3. Môn song hành là quan hệ **hai chiều có chủ đích**: khai báo A↔B một lần, model đọc cả
   hai chiều. Không tự động chèn bản ghi ngược (dễ thành chu trình vô nghĩa).
4. `study_plans` không chặn `subjects` chưa gắn chuyên ngành: một môn chung có thể xuất
   hiện trong khung của nhiều chuyên ngành (unique theo `(major_id, subject_id)`).
5. Xoá môn đang có quan hệ tiên quyết: `cascadeOnDelete` là chủ đích — xoá môn thì
   mọi ràng buộc referencing mất theo; UI phải hiện số ràng buộc bị ảnh hưởng ở bước xác nhận.

## Interfaces (Eloquent)

```php
// Subject
public function prerequisites(): BelongsToMany;   // ->subject_prerequisites
public function concurrents(): BelongsToMany;     // ->subject_concurrents
public function studyPlans(): HasMany;            // ->study_plans

// app/Support/PrerequisiteRules
public static function assertAcyclic(int $subjectId, int $prerequisiteId): void;
public static function blockingPrerequisites(User $student, Subject $subject, Collection $passed): array;
```

`blockingPrerequisites()` là hợp đồng mà `SPEC-enrollment.md` tiêu thụ: trả về mảng
`[{code, name, required_score, actual_score}]` rỗng nghĩa là đủ điều kiện.

## UI

Ba màn hình admin, dùng chung layout Spark + form bộ nhãn chuẩn:

- `/admin/departments` — resource index/create/edit, xoá kèm xác nhận.
- `/admin/majors` — như trên + bộ lọc theo khoa.
- `/admin/subjects` — mở rộng form hiện có (`form.blade.php`) thêm: chuyên ngành (select),
  mô tả, số tiết. Trong trang sửa môn, hai khối "Môn tiên quyết" và "Môn song hành":
  select multi thêm/xoá ngay bằng AJAX fragment (tái dùng cơ chế `rows.blade.php` của danh sách sinh viên).
- `/admin/study-plans` — bảng chọn chuyên ngành, thêm dòng `(môn, học kỳ dự kiến, loại)`,
  nút "Cập nhật" cho toàn khung. Sắp xếp theo `plan_semester`.

## Acceptance criteria

- [ ] Admin tạo khoa → chuyên ngành thuộc khoa → môn thuộc chuyên ngành.
- [ ] Thêm môn tiên quyết tạo thành chu trình bị chặn và hiện nhãn lỗi chỉ rõ đường dẫn.
- [ ] Môn song hành khai báo một chiều được đọc đúng ở cả hai chiều.
- [ ] Khung CTĐT một chuyên ngành liệt kê đủ môn theo học kỳ dự kiến, tổng tín chỉ hiển thị.
- [ ] Xoá khoa còn chuyên ngành bị chặn với thông báo, không xoá âm thầm.

## Verify

```
php artisan test --filter=CatalogTest
```
`tests/Feature/CatalogTest.php`: CRUD ba thực thể, from-chặn-cycle, unique
`(major_id, subject_id)`, phân quyền (teacher không vào được `/admin/subjects`).
Seed thêm 2 khoa, 2 chuyên ngành, 6 môn, cặp tiên quyết WEB101→DB101.

## Out of scope

Học phí, nhóm/lớp theo môn (thuộc `course-offerings`), duyệt khung CTĐT theo workflow.

## Open questions

1. `total_sessions` có cần không, hay suy ra từ tín chỉ (1 TC = 15 tiết)? Spec đang để
   nullable để TKB dùng tín chỉ nếu bạn không muốn thêm cột.
2. `kind` REQUIRED/ELECTIVE có đủ, hay cần "tự chọn có điều kiện"?
