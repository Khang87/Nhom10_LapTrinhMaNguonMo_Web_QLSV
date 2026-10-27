<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Major;
use App\Models\Semester;
use App\Models\StudentClass;
use App\Support\PersonLabels;
use App\Support\ReportFilter;
use App\Support\ReportFormats;
use App\Support\Reports;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * SCREEN THỐNG KÊ & BÁO CÁO (nhóm yêu cầu 8): một bộ lọc, bốn bảng số, ba định dạng file.
 * Lớp con chỉ cần ghi đè cách dựng bộ lọc và danh sách ô lọc — mọi con số và mọi nút xuất
 * đi qua đúng một đường.
 */
class ReportController extends Controller
{
    public function show(Request $request, string $type): View
    {
        $filter = $this->filter($request);

        return view('reports.index', [
            'type' => $type,
            'report' => $this->report($request, $type),
            'filter' => $filter,
            'filterOptions' => $this->filterOptions(),
            'routeBase' => $this->routeBase(),
        ]);
    }

    public function export(Request $request, string $type): Response
    {
        $report = $this->report($request, $type);

        return ReportFormats::download(
            $request,
            $report['title'].' '.$this->period($request),
            $report,
            'xlsx',
        );
    }

    /**
     * @return array{title: string, header: list<string>, rows: list<list<mixed>>}
     */
    private function report(Request $request, string $type): array
    {
        $report = Reports::show($type, $this->filter($request));
        abort_if($report === null, 404, 'Không có báo cáo này.');

        return $report;
    }

    /**
     * Admin thấy toàn hệ thống và lọc được theo khoa/khóa; màn hình hẹp hơn thì ghi đè.
     */
    protected function filter(Request $request): ReportFilter
    {
        return ReportFilter::fromRequest($request, Semester::current()?->id);
    }

    /**
     * @return array<string, array{label: string, blank: string, options: array<int|string, string>}>
     */
    protected function filterOptions(): array
    {
        return [
            'semester_id' => [
                'label' => 'Học kỳ',
                'blank' => 'Mọi học kỳ',
                'options' => Semester::orderByDesc('academic_year')->orderByDesc('term')
                    ->get()
                    ->mapWithKeys(fn (Semester $s) => [$s->id => $s->label.($s->is_active ? ' (hiện hành)' : '')])
                    ->all(),
            ],
            'department_id' => [
                'label' => 'Khoa',
                'blank' => 'Mọi khoa',
                'options' => Department::orderBy('name')->get()->mapWithKeys(fn ($d) => [$d->id => $d->name.' ('.$d->code.')'])->all(),
            ],
            'major_id' => [
                'label' => 'Chuyên ngành',
                'blank' => 'Mọi chuyên ngành',
                'options' => Major::orderBy('name')->get()->mapWithKeys(fn ($m) => [$m->id => $m->name])->all(),
            ],
            'class_id' => [
                'label' => 'Lớp',
                'blank' => 'Mọi lớp',
                'options' => StudentClass::orderBy('class_code')->get()->mapWithKeys(fn ($c) => [$c->id => $c->label()])->all(),
            ],
            'intake_year' => [
                'label' => 'Khóa',
                'blank' => 'Mọi khóa',
                'options' => StudentClass::distinct()->orderByDesc('intake_year')->pluck('intake_year')
                    ->mapWithKeys(fn ($y) => [$y => 'Khóa '.$y])->all(),
            ],
            'gender' => ['label' => 'Giới tính', 'blank' => 'Mọi giới tính', 'options' => PersonLabels::GENDERS],
            'academic_status' => ['label' => 'Tình trạng học tập', 'blank' => 'Mọi tình trạng', 'options' => PersonLabels::ACADEMIC_STATUSES],
        ];
    }

    protected function routeBase(): string
    {
        return 'admin.reports';
    }

    /**
     * Tên file mang nộp cần biết nó thuộc kỳ nào khi nhìn vào thư mục, không cần mở ra.
     */
    private function period(Request $request): string
    {
        return $this->filter($request)->semester()?->slug ?? 'mọi kỳ';
    }
}
