<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseOffering;
use App\Models\LectureSlot;
use App\Models\Semester;
use App\Support\Csv;
use App\Support\TimetableService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Trang thời khóa biểu của quản trị viên: chọn một lớp học phần để xem lưới tuần và tải CSV.
 */
class TimetableController extends Controller
{
    public function index(Request $request): View
    {
        [$semester, $offerings, $offering] = $this->selection($request);

        return view('admin.timetable.index', [
            'semesters' => TimetableService::semesters(),
            'semester' => $semester,
            'offerings' => $offerings,
            'offering' => $offering,
            'grid' => TimetableService::grid($this->slots($offering)),
        ]);
    }

    public function export(Request $request): Response
    {
        [, , $offering] = $this->selection($request);

        $rows = $this->slots($offering)->map(fn (LectureSlot $slot) => [
            $slot->weekDayLabel(),
            $slot->timeRange(),
            $slot->offering?->subject?->code ?? '',
            $slot->offering?->subject?->name ?? '',
            $slot->offering?->group_code ?? '',
            $slot->room ?: ($slot->offering?->room ?? ''),
            $slot->offering?->teacher?->name ?? '',
            $slot->offering?->semester?->slug ?? '',
        ])->all();

        return Csv::download(
            'thoi-khoa-bieu-'.($offering?->semester?->slug ?? 'khong-ky').'-'.($offering?->group_code ?? 'lop').'.csv',
            ['Ngày', 'Giờ', 'Mã môn', 'Môn học', 'Nhóm', 'Phòng', 'Giảng viên', 'Học kỳ'],
            $rows
        );
    }

    /**
     * @return array{0: Semester|null, 1: Collection<int, CourseOffering>, 2: CourseOffering|null}
     */
    private function selection(Request $request): array
    {
        $semester = TimetableService::semesterFor($request);

        $offerings = CourseOffering::with(['subject', 'teacher', 'semester'])
            ->where('semester_id', $semester?->id)
            ->orderBy('subject_id')
            ->orderBy('group_code')
            ->get();

        $selected = $request->query('offering_id');
        $offering = filled($selected)
            ? $offerings->firstWhere('id', (int) $selected)
            : $offerings->first();

        return [$semester, $offerings, $offering];
    }

    /**
     * @return Collection<int, LectureSlot>
     */
    private function slots(?CourseOffering $offering): Collection
    {
        return $offering === null ? collect() : TimetableService::forOffering($offering->id);
    }
}
