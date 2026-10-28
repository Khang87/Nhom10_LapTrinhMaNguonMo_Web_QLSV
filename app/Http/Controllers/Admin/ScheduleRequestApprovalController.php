<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;
use App\Models\ScheduleChangeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduleRequestApprovalController extends Controller
{
    /**
     * Danh sách yêu cầu Báo nghỉ / Dạy bù / Chuyển Online của Giảng viên
     */
    public function index(Request $request): View
    {
        $status = $request->input('status', 'all');

        $query = ScheduleChangeRequest::with(['lecturer', 'section.course', 'schedule']);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $requests = $query->orderBy('created_at', 'desc')->get();

        $pendingCount = ScheduleChangeRequest::where('status', 'PENDING')->count();
        $approvedCount = ScheduleChangeRequest::where('status', 'APPROVED')->count();
        $rejectedCount = ScheduleChangeRequest::where('status', 'REJECTED')->count();

        return view('admin.schedule_requests.index', compact(
            'requests',
            'status',
            'pendingCount',
            'approvedCount',
            'rejectedCount'
        ));
    }

    /**
     * Phê duyệt yêu cầu thay đổi lịch giảng dạy
     */
    public function approve(ScheduleChangeRequest $scheduleRequest, Request $request): RedirectResponse
    {
        $adminNote = $request->input('admin_note', 'Đã phê duyệt');

        // Áp dụng cập nhật vào TKB ClassSchedule
        if ($scheduleRequest->request_type === 'CANCEL_SESSION' && $scheduleRequest->schedule) {
            $scheduleRequest->schedule->update([
                'schedule_type' => 'SUSPENDED',
                'note' => 'Báo nghỉ: ' . ($scheduleRequest->reason ?? 'Buổi học tạm hoãn theo lịch công tác của GV'),
            ]);
        } elseif ($scheduleRequest->request_type === 'ONLINE_SESSION' && $scheduleRequest->schedule) {
            $scheduleRequest->schedule->update([
                'schedule_type' => 'ONLINE',
                'room_info' => 'Online: ' . ($scheduleRequest->meeting_link ?: 'Phòng học trực tuyến'),
                'meeting_link' => $scheduleRequest->meeting_link,
                'note' => 'Học Online: ' . ($scheduleRequest->meeting_link ?? ''),
            ]);
        } elseif ($scheduleRequest->request_type === 'MAKEUP_SESSION') {
            ClassSchedule::create([
                'section_id' => $scheduleRequest->section_id,
                'course_id' => $scheduleRequest->section?->course_id,
                'lecturer_id' => $scheduleRequest->lecturer_id,
                'section_code' => $scheduleRequest->section?->section_code,
                'subject_name' => $scheduleRequest->section?->course?->course_name ?? 'Môn học bù',
                'lecturer_name' => $scheduleRequest->lecturer?->full_name ?? 'Giảng viên',
                'schedule_date' => $scheduleRequest->target_date,
                'start_period' => $scheduleRequest->start_period ?? 1,
                'end_period' => $scheduleRequest->end_period ?? 3,
                'room_info' => $scheduleRequest->room_name ?? 'Phòng học bù',
                'schedule_type' => 'THEORY',
                'note' => 'Dạy bù cho buổi học ngày ' . ($scheduleRequest->original_date ? $scheduleRequest->original_date->format('d/m/Y') : ''),
            ]);
        }

        $scheduleRequest->update([
            'status' => 'APPROVED',
            'admin_note' => $adminNote,
        ]);

        return back()->with('success', "Đã phê duyệt yêu cầu [{$scheduleRequest->getTypeName()}] của Giảng viên {$scheduleRequest->lecturer?->full_name}. Lịch giảng dạy và TKB sinh viên đã được cập nhật tự động.");
    }

    /**
     * Từ chối yêu cầu thay đổi lịch giảng dạy
     */
    public function reject(ScheduleChangeRequest $scheduleRequest, Request $request): RedirectResponse
    {
        $adminNote = $request->input('admin_note', 'Không duyệt yêu cầu');

        $scheduleRequest->update([
            'status' => 'REJECTED',
            'admin_note' => $adminNote,
        ]);

        return back()->with('success', "Đã từ chối yêu cầu của Giảng viên {$scheduleRequest->lecturer?->full_name}.");
    }
}
