<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Admin\ReportController as BaseReportController;
use App\Models\Semester;
use App\Support\PersonLabels;
use App\Support\ReportFilter;
use Illuminate\Http\Request;

/**
 * Giảng viên dùng lại nguyên bộ máy báo cáo của admin, chỉ khác phạm vi: số liệu gói trong
 * các lớp họ đứng tên và không có ô lọc khoa/khóa/lớp. Phạm vi lấy từ tài khoản đang đăng
 * nhập chứ không lấy từ mã lớp gửi lên, nên đổi query string cũng không xem được lớp người khác.
 */
class ReportController extends BaseReportController
{
    protected function filter(Request $request): ReportFilter
    {
        return ReportFilter::fromRequest($request, Semester::current()?->id)
            ->forOfferings($request->user()->assignedOfferings()->pluck('id')->all());
    }

    protected function filterOptions(): array
    {
        return [
            'gender' => ['label' => 'Giới tính', 'blank' => 'Mọi giới tính', 'options' => PersonLabels::GENDERS],
            'academic_status' => ['label' => 'Tình trạng học tập', 'blank' => 'Mọi tình trạng', 'options' => PersonLabels::ACADEMIC_STATUSES],
        ];
    }

    protected function routeBase(): string
    {
        return 'teacher.reports';
    }
}
