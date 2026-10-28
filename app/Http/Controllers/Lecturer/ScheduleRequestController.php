<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;
use App\Models\CourseSection;
use App\Models\ScheduleChangeRequest;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ScheduleRequestController extends Controller
{
    /**
     * Danh sách các yêu cầu báo nghỉ, dạy bù, học online của giảng viên
     */
    public function index(): View
    {
        $user = Auth::user();
        $lecturer = $user->lecturer;

        $requests = $lecturer
            ? ScheduleChangeRequest::with(['section.course', 'schedule'])
                ->where('lecturer_id', $lecturer->id)
                ->orderBy('created_at', 'desc')
                ->get()
            : collect();

        return view('lecturer.requests.index', compact('requests', 'lecturer'));
    }

    /**
     * Form tạo yêu cầu mới
     */
    public function create(Request $request): View
    {
        $user = Auth::user();
        $lecturer = $user->lecturer;

        $sections = $lecturer
            ? CourseSection::with('course')
                ->where('lecturer_id', $lecturer->id)
                ->orderBy('section_code')
                ->get()
            : collect();

        $selectedSectionId = $request->input('section_id', $sections->first()?->id);
        $selectedDate = $request->input('date', now()->toDateString());

        return view('lecturer.requests.create', compact('sections', 'selectedSectionId', 'selectedDate', 'lecturer'));
    }

    /**
     * Lưu yêu cầu gửi lên Ban Quản trị / Phòng Đào tạo
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $lecturer = $user->lecturer;

        if (!$lecturer) {
            abort(403, 'Tài khoản không phải là giảng viên.');
        }

        $validated = $request->validate([
            'section_id' => ['required', 'exists:course_sections,id'],
            'request_type' => ['required', 'in:CANCEL_SESSION,MAKEUP_SESSION,ONLINE_SESSION'],
            'original_date' => ['nullable', 'date'],
            'target_date' => ['nullable', 'date'],
            'shift' => ['nullable', 'in:MORNING,AFTERNOON,EVENING'],
            'room_name' => ['nullable', 'string', 'max:100'],
            'meeting_link' => ['nullable', 'string', 'max:255'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $section = CourseSection::findOrFail($validated['section_id']);

        // Tìm lịch học tương ứng nếu có
        $schedule = null;
        if (!empty($validated['original_date'])) {
            $schedule = ClassSchedule::where('section_id', $section->id)
                ->whereDate('schedule_date', $validated['original_date'])
                ->first();
        }

        ScheduleChangeRequest::create([
            'lecturer_id' => $lecturer->id,
            'section_id' => $section->id,
            'schedule_id' => $schedule?->id,
            'request_type' => $validated['request_type'],
            'original_date' => $validated['original_date'] ?? null,
            'target_date' => $validated['target_date'] ?? null,
            'shift' => $validated['shift'] ?? 'MORNING',
            'start_period' => $section->start_period ?? 1,
            'end_period' => $section->end_period ?? 3,
            'room_name' => $validated['room_name'] ?? $section->room_name,
            'meeting_link' => $validated['meeting_link'] ?? null,
            'reason' => $validated['reason'],
            'status' => 'PENDING',
        ]);

        return redirect()->route('lecturer.requests.index')
            ->with('success', 'Đã gửi yêu cầu thay đổi lịch giảng dạy thành công lên Ban Quản trị / Phòng Đào tạo để phê duyệt.');
    }
}
