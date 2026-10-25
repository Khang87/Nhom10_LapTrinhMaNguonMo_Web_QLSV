# Spec: `reporting`

Nhóm yêu cầu 8 (Thống kê & báo cáo theo khoa/khóa/giới tính/tình trạng học tập; phổ điểm;
xuất Excel hoặc PDF). Phụ thuộc: `grading`, `attendance-schedule`, `person-profiles`.
Block: `Admin\GradeController::export`, `Admin\AttendanceController::export`, `App\Support\Csv`.

## Objective

Admin có một trang thống kê trả lời được bốn câu hỏi bằng số liệu thật trên dữ liệu mẫu,
và mọi bảng trên trang đó xuất được ra file mang nộp cho trường.

Thành công = chọn "Khoa CNTT / khóa 2023 / HK1-2025-2026" và nhận được: phân bố sinh viên
theo giới tính + tình trạng học tập, phổ điểm 5 khoảng của từng môn, top lớp có tỷ lệ
chuyên cần thấp nhất, kèm hai nút xuất file mở được bằng Excel và PDF.

## Kiến trúc: truy vấn tập hợp trong `app/Support/Reports`

```php
final class Reports
{
    public static function studentCensus(ReportFilter $f): Collection;
    // groupCountBy(['gender', 'academic_status']) + theo lớp/khoa/khóa
    public static function scoreDistribution(ReportFilter $f): Collection;
    // bucket 0-3.9 | 4-4.9 | 5-5.9 | 6-6.9 | 7-8.4 | 8.5-10, đếm theo môn và theo toàn khóa
    public static function attendanceSummary(ReportFilter $f): Collection;
    // theo lớp học phần: số buổi, TB tỷ lệ chuyên cần, % sinh viên < ngưỡng 80
    public static function gpaRanking(ReportFilter $f, int $limit = 20): Collection;
}
```

`ReportFilter` value object: `semester_id?`, `department_id?`, `intake_year?`,
`major_id?`, `class_id?`, `gender?`, `academic_status?`. Một bộ lọc dùng chung cho cả
bốn báo cáo — tránh bốn màn hình bốn cách lọc khác nhau.

Nguyên tắc: mọi con số do `Reports` sinh ra **phải tính lại từ bảng nguồn** (grades,
enrollments, attendances, users), không có bảng "điểm đã tổng hợp" trung gian. Số liệu
trên trang thống kê và số liệu trong file xuất ra là một query, không phải hai đường.

## Quyền

| Vai trò | Được xem |
|---|---|
| Admin | toàn bộ, mọi bộ lọc |
| Giảng viên | báo cáo của **lớp mình phụ trách** (chỉ semester + offering, bỏ lọc khoa/khóa) |
| Sinh viên | chỉ bảng điểm và tỷ lệ chuyên cần của chính mình (không có trang thống kê) |

## Xuất file

Hai driver, cùng một interface để controller không biết định dạng:

```php
interface ReportExporter { public function export(string $title, array $header, iterable $rows): SymfonyResponse; }
class XlsxExporter implements ReportExporter;   // phpoffice/phpspreadsheet
class PdfExporter   implements ReportExporter;   // barryvdh/laravel-dompdf, view Blade làm template
class CsvExporter   implements ReportExporter;   // giữ App\Support\Csv hiện tại, không phụ thuộc mới
```

- `GET /admin/reports/{type}?{filters}&format=xlsx|pdf|csv` — `type` trong
  `census|scores|attendance|ranking`. Route đặt sau `checkRole:admin`.
- Mỗi trang thống kê có cụm nút "Xuất Excel" / "Xuất PDF" ở góc trên, **không** có nút lọc
  (theo bộ nhãn dùng chung).
- PDF: khổ A4 ngang cho bảng phổ điểm, dọc cho danh sách; font Unicode — `Dompdf` chỉ render
  UTF-8 đúng khi nhúng font (VD DejaVu Sans trong `public/fonts`). Rủi ro: tiếng Việt có dấu
  trong PDF là điểm hay lỗi; bắt buộc kiểm tay một file PDF có dấu trước khi coi là xong.
- Excel: `.xlsx` thật, hàng tiêu đề đậm, đóng băng (freeze) hàng 1, cột điểm định dạng số
  `0.00`, cột ngày `dd/mm/yyyy`. Header khai báo tường minh trong `header()`, không suy từ
  tên cột DB.
- CSV: giữ nguyên hành vi TSV + BOM UTF-8 hiện tại để không phá 2 bài test đã có ở
  `ExtendedRequirementsTest`.

## Báo cáo kế thừa

`Admin\GradeController::export` và `Admin\AttendanceController::export` (đang dùng
`App\Support\Csv`) đổi nội bộ sang `CsvExporter` để hai test cũ vẫn xanh, đồng thời thêm
`format=xlsx|pdf` cho cùng đường dẫn. Đây là cách duy nhất để "mở rộng" mà không "viết lại
phần đã báo cáo".

## Acceptance criteria

- [ ] Bộ lọc khoa/khóa/giới tính/tình trạng trả về số đếm khớp với truy vấn tay trên DB.
- [ ] Phổ điểm 6 khoảng cho một môn: tổng số sinh viên các khoảng = sĩ số có điểm công bố.
- [ ] Bảng chuyên cần: TB tỷ lệ = trung bình các tỷ lệ cá nhân, không phải tổng số buổi.
- [ ] `format=xlsx` tải file mở được bằng Excel, dấu tiếng Việt đúng, không bị mất số 0 đầu
      ở cột SĐT (ép kiểu text cho cột đó).
- [ ] `format=pdf` render đúng tiếng Việt có dấu và không tràn bảng ở khổ A4.
- [ ] Giảng viên truy cập `/admin/reports` → 403; xem báo cáo lớp mình → 200; xem báo cáo
      lớp người khác → 403.
- [ ] `php artisan test` vẫn 35 test cũ pass + test mới.

## Verify

```
composer require phpoffice/phpspreadsheet barryvdh/laravel-dompdf
php artisan test --filter=ReportingTest
php artisan test --filter=ExtendedRequirementsTest   # hồi quy CSV cũ
```
`ReportingTest`: dựng fixture 6 sinh viên với điểm đã biết, assert từng con số của
`Reports` (dữ liệu cố định ⇒ so sánh tuyệt đối, không so bằng `%` dung sai). Kiểm tay:
mở file `.xlsx` và `.pdf` xuất ra từ trang thống kê — đây là bắt buộc, không tin test đơn vị
cho phần font.

## Out of scope

Dashboard biểu đồ tương tác thời gian thực (Chart.js) — có thể thêm vào cùng trang nhưng
không phải yêu cầu; báo cáo định kỳ gửi email tự động; log xuất file; Excel nhiều sheet.

## Open questions

1. Biểu đồ: spec đang dùng **bảng số + thanh tỷ lệ CSS**, không thêm thư viện chart. Muốn
   vẽ biểu đồ cột thì phải quyết định thêm Chart.js (dependency frontend).
2. Có cần "báo cáo theo giảng viên" (Điểm TB / tỷ lệ chuyên cần lớp họ phụ trách) không?
   Đề bài không nêu, thêm ~30 dòng nếu cần.
