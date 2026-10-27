<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Support\ReportFormats;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Điểm danh là thao tác của giảng viên (SPEC-attendance-schedule 7.4); trang admin chỉ đọc
 * và xuất báo cáo, nên ở đây không còn form ghi.
 */
class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $date = $this->date($request);
        $sessions = $this->sessionsOn($date);

        return view('admin.attendances.index', [
            'date' => $date,
            'sessions' => $sessions,
            'presentCount' => $this->countByStatus($sessions, Attendance::PRESENT),
            'absentCount' => $this->countByStatus($sessions, Attendance::ABSENT),
        ]);
    }

    public function export(Request $request): Response
    {
        $date = $this->date($request);

        return ReportFormats::download($request, 'diem-danh-'.$date, $this->report($date));
    }

    /**
     * @return array{title: string, header: list<string>, rows: list<list<mixed>>}
     */
    private function report(string $date): array
    {
        $rows = $this->sessionsOn($date)
            ->flatMap(fn (AttendanceSession $session) => $session->records
                ->map(fn (Attendance $record) => [
                    $record->enrollment?->user?->name ?? '(đã xóa)',
                    $record->enrollment?->user?->email ?? '',
                    $session->offering?->subject?->code ?? '',
                    $session->offering?->subject?->name ?? '',
                    $session->session_date->format('d/m/Y'),
                    $record->statusLabel(),
                ]))
            ->all();

        return [
            'title' => 'Điểm danh ngày '.Carbon::parse($date)->format('d/m/Y'),
            'header' => ['Sinh viên', 'Email', 'Mã môn', 'Môn học', 'Ngày', 'Trạng thái'],
            'rows' => $rows,
        ];
    }

    /**
     * Các buổi của một ngày kèm lớp, người điểm danh và từng dòng điểm danh.
     *
     * @return Collection<int, AttendanceSession>
     */
    private function sessionsOn(string $date): Collection
    {
        return AttendanceSession::with([
            'offering.subject',
            'recorder',
            'records.enrollment.user',
        ])
            ->whereDate('session_date', $date)
            ->orderBy('course_offering_id')
            ->get();
    }

    /**
     * @param  Collection<int, AttendanceSession>  $sessions
     */
    private function countByStatus(Collection $sessions, string $status): int
    {
        return $sessions->sum(fn (AttendanceSession $session) => $session->records->where('status', $status)->count());
    }

    private function date(Request $request): string
    {
        $date = $request->query('date', today()->toDateString());
        validator(['date' => $date], ['date' => ['required', 'date']])->validate();

        return $date;
    }
}
