<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ReportFilter;
use App\Support\Reports;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Feature\Concerns\OfferingFixtures;
use Tests\TestCase;

/**
 * Nhóm yêu cầu 8: bốn con số của trang thống kê. Fixture bảy sinh viên với điểm và điểm danh
 * đã biết trước nên mọi assertion là số tuyệt đối, không so theo dung sai.
 */
class ReportingTest extends TestCase
{
    use OfferingFixtures, RefreshDatabase;

    private const BUCKET_LABELS = ['Dưới 4.0', '4.0–4.9', '5.0–5.9', '6.0–6.9', '7.0–8.4', '8.5–10.0'];

    public function test_census_splits_students_by_every_requested_dimension(): void
    {
        $fixture = $this->fixture();
        $census = Reports::census(new ReportFilter(semesterId: $fixture['semester']->id));

        $this->assertSame(['Nhóm', 'Giá trị', 'Số sinh viên', 'Tỷ lệ (%)'], $census['header']);
        $this->assertSame([
            'Khóa' => ['2024' => [7, 100.0]],
            'Giới tính' => ['Khác' => [1, 14.3], 'Nam' => [4, 57.1], 'Nữ' => [2, 28.6]],
            'Tình trạng học tập' => ['Cảnh báo học vụ' => [2, 28.6], 'Đang học' => [5, 71.4]],
            'Lớp' => ['CN24' => [7, 100.0]],
            'Khoa' => ['Khoa Công nghệ thông tin' => [7, 100.0]],
            'Chuyên ngành' => ['Ngành Kỹ thuật phần mềm' => [7, 100.0]],
        ], $this->groupRows($census['rows']));
    }

    public function test_score_histogram_counts_published_grades_only_and_adds_up_to_headcount(): void
    {
        $fixture = $this->fixture();
        $scores = Reports::scores(new ReportFilter(semesterId: $fixture['semester']->id));
        $rows = collect($scores['rows'])->keyBy(fn (array $row) => Str::startsWith($row[0], 'HTT101') ? 'HTT101' : 'WEB101');

        $this->assertSame(
            array_merge(['Lớp học phần', 'Số sinh viên có điểm'], self::BUCKET_LABELS, ['Điểm TB']),
            $scores['header']
        );
        $this->assertSame(6, $rows['WEB101'][1]);
        $this->assertSame([1, 1, 1, 1, 1, 1], array_slice($rows['WEB101'], 2, 6));
        $this->assertSame(6.08, $rows['WEB101'][8]);

        $this->assertSame(2, $rows['HTT101'][1]);
        $this->assertSame([0, 0, 0, 1, 0, 1], array_slice($rows['HTT101'], 2, 6));
        $this->assertSame(7.5, $rows['HTT101'][8]);

        // Điều kiện nghiệp vụ: tổng các khoảng đúng bằng sĩ số có điểm công bố của lớp đó.
        foreach ($scores['rows'] as $row) {
            $this->assertSame($row[1], array_sum(array_slice($row, 2, 6)), 'Phổ điểm không khớp sĩ số: '.$row[0]);
        }
    }

    public function test_attendance_average_is_the_mean_of_personal_rates_not_the_session_count(): void
    {
        $fixture = $this->fixture();
        $attendance = Reports::attendance(new ReportFilter(semesterId: $fixture['semester']->id));
        $web = collect($attendance['rows'])->first(fn (array $row) => Str::startsWith($row[0], 'WEB101'));

        // Buổi 1 cả lớp đi học, buổi 2 hai em vắng: năm người 100% và hai người 50%
        // -> trung bình các tỷ lệ cá nhân, không phải tổng số buổi.
        $this->assertSame('WEB101 - Môn WEB101 · nhóm TN01 ('.$fixture['semester']->slug.')', $web[0]);
        $this->assertSame(7, $web[1]);
        $this->assertSame(2, $web[2]);
        $this->assertSame(85.7, $web[3]);
        $this->assertSame(2, $web[4]);

        $this->assertSame(
            ['Lớp học phần', 'Sĩ số', 'Số buổi đã điểm', 'TB chuyên cần (%)', 'Dưới ngưỡng'],
            $attendance['header']
        );
    }

    public function test_ranking_orders_students_by_credit_weighted_gpa(): void
    {
        $fixture = $this->fixture();
        $ranking = Reports::ranking(new ReportFilter(semesterId: $fixture['semester']->id));

        $this->assertSame(['#', 'Mã SV', 'Họ tên', 'GPA hệ 4', 'TB hệ 10', 'TC tích lũy', 'Xếp loại'], $ranking['header']);
        $this->assertSame([
            [1, '20240001', 'Lưu An', 4.0, 9.06, 5, 'Xuất sắc'],
            [2, '20240002', 'Bùi Bình', 3.3, 7.08, 5, 'Khá'],
            [3, '20240003', 'Đỗ Cường', 3.0, 6.4, 3, 'Trung bình'],
            [4, '20240004', 'Tạ Dân', 2.5, 5.5, 3, 'Trung bình'],
            [5, '20240005', 'Ngô Em', 0.0, 4.2, 0, 'Yếu'],
            [6, '20240006', 'Lý Hoa', 0.0, 3.5, 0, 'Yếu'],
        ], $ranking['rows']);
    }

    public function test_one_filter_reduces_every_report_the_same_way(): void
    {
        $fixture = $this->fixture();
        $males = new ReportFilter(semesterId: $fixture['semester']->id, gender: 'MALE');

        $census = $this->groupRows(Reports::census($males)['rows']);
        $this->assertSame(['Nam' => [4, 100.0]], $census['Giới tính']);

        $scores = Reports::scores($males)['rows'];
        $this->assertSame(4, $scores[1][1]);
        $this->assertSame([0, 0, 1, 1, 1, 1], array_slice($scores[1], 2, 6));
        $this->assertSame(7.2, $scores[1][8]);

        $this->assertCount(4, Reports::ranking($males)['rows']);
    }

    public function test_a_teacher_scope_is_built_from_offerings_and_drops_the_catalog_filters(): void
    {
        $fixture = $this->fixture();
        $scoped = (new ReportFilter(
            semesterId: $fixture['semester']->id,
            departmentId: 1,
            gender: 'MALE',
        ))->forOfferings([$fixture['htt']->id]);

        $this->assertSame($fixture['semester']->id, $scoped->semesterId);
        $this->assertNull($scoped->departmentId);
        $this->assertNull($scoped->gender);

        $scores = Reports::scores($scoped)['rows'];
        $this->assertCount(1, $scores);
        $this->assertTrue(Str::startsWith($scores[0][0], 'HTT101'));
        $this->assertSame(2, $scores[0][1]);

        // Lọc theo lớp của giảng viên chỉ còn hai em đăng ký HTT101, dù chúng thuộc mọi khoa.
        $census = $this->groupRows(Reports::census($scoped)['rows']);
        $this->assertSame(['2024' => [2, 100.0]], $census['Khóa']);
        $this->assertSame(['CN24' => [2, 100.0]], $census['Lớp']);
    }

    public function test_show_resolves_the_four_types_and_rejects_an_unknown_one(): void
    {
        $fixture = $this->fixture();
        $filter = new ReportFilter(semesterId: $fixture['semester']->id);

        $this->assertSame(['census', 'scores', 'attendance', 'ranking'], array_keys(Reports::TYPES));
        foreach (array_keys(Reports::TYPES) as $type) {
            $report = Reports::show($type, $filter);
            $this->assertIsArray($report);
            $this->assertNotEmpty($report['title']);
            $this->assertNotEmpty($report['header']);
            $this->assertNotEmpty($report['rows']);
        }
        $this->assertNull(Reports::show('unknown', $filter));
    }

    public function test_the_admin_screen_shows_the_report_and_the_three_export_buttons(): void
    {
        $fixture = $this->fixture();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.reports.show', 'scores').'?semester_id='.$fixture['semester']->id)
            ->assertOk()
            ->assertSee('Phổ điểm theo lớp học phần')
            ->assertSee('Xuất Excel')
            ->assertSee('Xuất PDF')
            ->assertSee('6,08')
            ->assertSee('Thống kê sinh viên')
            ->assertSee('Xếp hạng GPA');
    }

    public function test_each_format_downloads_a_real_file_with_the_vietnamese_name_slugged(): void
    {
        $fixture = $this->fixture();
        $admin = User::factory()->create(['role' => 'admin']);

        $expected = [
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'pdf' => 'application/pdf',
            'csv' => 'text/csv; charset=UTF-8',
        ];
        $signature = ['xlsx' => 'PK', 'pdf' => '%PDF', 'csv' => "\xEF\xBB\xBF"];

        foreach ($expected as $format => $contentType) {
            $response = $this->actingAs($admin)->get(
                route('admin.reports.export', [
                    'type' => 'census',
                    'format' => $format,
                    'semester_id' => $fixture['semester']->id,
                ])
            );

            $response->assertOk()->assertHeader('Content-Type', $contentType);
            $response->assertDownload('phan-bo-sinh-vien-theo-bo-loc-hk1-2025-2026.'.$format);
            $bytes = $format === 'xlsx' ? $response->streamedContent() : $response->content();
            $this->assertStringStartsWith($signature[$format], $bytes, "File {$format} không đúng định dạng thật.");
        }
    }

    public function test_unknown_report_type_or_format_is_not_a_server_error(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.reports.show', 'salary'))->assertNotFound();
        $this->actingAs($admin)->get(route('admin.reports.export', ['type' => 'census', 'format' => 'docx']))->assertNotFound();
    }

    public function test_a_teacher_only_ever_sees_the_classes_they_stand(): void
    {
        $fixture = $this->fixture();
        $other = $this->teacher('Giảng viên B');
        $admin = User::factory()->create(['role' => 'admin']);
        $student = $fixture['students']['20240001'];

        $this->actingAs($admin)->get(route('teacher.reports.show', 'attendance'))->assertForbidden();
        $this->actingAs($student)->get(route('admin.reports.show', 'census'))->assertForbidden();

        $mine = $this->actingAs($fixture['teacher'])->get(route('teacher.reports.show', 'attendance'))
            ->assertOk()
            ->assertSee('WEB101 - Môn WEB101')
            ->assertSee('85,7');

        // Báo cáo của người khác không tồn tại trong phạm vi của giảng viên: bảng rỗng, không phải 500.
        $this->actingAs($other)
            ->get(route('teacher.reports.show', 'attendance'))
            ->assertOk()
            ->assertSee('Bộ lọc hiện tại chưa có dữ liệu.')
            ->assertDontSee('85,7');

        // Ô lọc khoa/khóa chỉ dành cho admin.
        $this->actingAs($fixture['teacher'])->get(route('admin.reports.show', 'census'))
            ->assertForbidden();
        $mine->assertDontSee('name="department_id"', false);
    }

    /**
     * Bảy sinh viên, hai lớp học phần, điểm và điểm danh đã biết trước:
     * WEB101 (3 TC) có sáu điểm công bố + một dòng chưa công bố, HTT101 (2 TC) có hai điểm.
     *
     * @return array<string, mixed>
     */
    private function fixture(): array
    {
        $web = $this->subjectWith('WEB101', 3);
        $htt = $this->subjectWith('HTT101', 2);
        $class = $this->adminClass($this->majorWithPlan($web, $htt));
        $semester = $this->gate();
        $teacher = $this->teacher('Giảng viên A');
        $webOffering = $this->offering($web, $semester, 'TN01', 0, ['teacher_id' => $teacher->id]);
        $httOffering = $this->offering($htt, $semester, 'TN01', 0, ['teacher_id' => $teacher->id]);

        $roster = [
            ['20240001', 'Lưu An', 'MALE', 'STUDYING', 9.1, 9.0],
            ['20240002', 'Bùi Bình', 'MALE', 'STUDYING', 7.8, 6.0],
            ['20240003', 'Đỗ Cường', 'MALE', 'AT_RISK', 6.4, null],
            ['20240004', 'Tạ Dân', 'MALE', 'STUDYING', 5.5, null],
            ['20240005', 'Ngô Em', 'FEMALE', 'STUDYING', 4.2, null],
            ['20240006', 'Lý Hoa', 'FEMALE', 'AT_RISK', 3.5, null],
            ['20240007', 'Trần Minh', 'OTHER', 'STUDYING', 8.0, null],
        ];

        $students = [];
        foreach ($roster as [$code, $name, $gender, $status, $webScore, $httScore]) {
            $student = User::factory()->create([
                'role' => 'student',
                'name' => $name,
                'student_code' => $code,
                'gender' => $gender,
                'academic_status' => $status,
                'class_id' => $class->id,
            ]);
            $students[$code] = $student;

            $enrollment = $this->enroll($student, $webOffering);
            $this->grade($student, $webOffering, $webScore, $code !== '20240007');

            if ($httScore !== null) {
                $this->enroll($student, $httOffering);
                $this->grade($student, $httOffering, $httScore);
            }

            $present = ! in_array($code, ['20240002', '20240007'], true);
            $this->attend($enrollment, '2026-01-05');
            $this->attend($enrollment, '2026-01-12', $present ? 'PRESENT' : 'ABSENT');
        }

        return [
            'semester' => $semester,
            'class' => $class,
            'teacher' => $teacher,
            'web' => $webOffering,
            'htt' => $httOffering,
            'students' => $students,
        ];
    }

    /**
     * @param  list<list<mixed>>  $rows
     * @return array<string, array<string, array{0: int, 1: float|null}>>
     */
    private function groupRows(array $rows): array
    {
        $grouped = [];
        foreach ($rows as [$label, $value, $count, $share]) {
            $grouped[$label][$value] = [$count, $share];
        }

        return $grouped;
    }
}
