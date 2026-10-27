<?php

namespace App\Http\Controllers;

use App\Models\ClassSchedule;
use App\Models\CourseSection;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    /**
     * Display weekly timetable according to HUIT portal specifications.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        // 1. Xác định ngày và tuần
        $dateInput = $request->input('date', now()->toDateString());
        try {
            $selectedDate = Carbon::parse($dateInput);
        } catch (\Exception $e) {
            $selectedDate = now();
        }

        $startOfWeek = $selectedDate->copy()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = $selectedDate->copy()->endOfWeek(Carbon::SUNDAY);

        // 2. Danh sách 7 ngày trong tuần
        $weekDays = [];
        for ($i = 0; $i < 7; $i++) {
            $day = $startOfWeek->copy()->addDays($i);
            $dayIso = $day->dayOfWeekIso; // 1 = Thứ 2 ... 7 = Chủ nhật
            $dayLabel = match ($dayIso) {
                1 => 'Thứ 2',
                2 => 'Thứ 3',
                3 => 'Thứ 4',
                4 => 'Thứ 5',
                5 => 'Thứ 6',
                6 => 'Thứ 7',
                7 => 'Chủ nhật',
            };

            $weekDays[] = [
                'date' => $day->toDateString(),
                'day_name' => $dayLabel,
                'header_title' => $dayLabel . ' ' . $day->format('d/m/Y'),
                'is_today' => $day->isToday(),
            ];
        }

        // 3. Navigation Links
        $prevWeekDate = $startOfWeek->copy()->subWeek()->toDateString();
        $nextWeekDate = $startOfWeek->copy()->addWeek()->toDateString();
        $currentWeekDate = now()->toDateString();

        // 4. Lọc Radio: all (tất cả), study (lịch học), exam (lịch thi)
        $filter = $request->input('filter', 'all');

        // 5. Query dữ liệu lịch từ Database SQLite theo Role
        $query = ClassSchedule::query()
            ->forDateRange($startOfWeek->toDateString(), $endOfWeek->toDateString());

        if ($user->isStudent()) {
            $student = $user->student;
            $enrolledSectionIds = $student ? $student->enrollments()->pluck('section_id') : collect();
            $classCode = $student?->class_code ?? $user->studentClass?->class_code;

            $query->where(function ($q) use ($enrolledSectionIds, $classCode) {
                if ($enrolledSectionIds->isNotEmpty()) {
                    $q->whereIn('section_id', $enrolledSectionIds);
                }
                if ($classCode) {
                    $q->orWhere('class_group_name', $classCode);
                }
            });
        } elseif ($user->isLecturer()) {
            $lecturer = $user->lecturer;
            $query->where(function ($q) use ($lecturer, $user) {
                if ($lecturer) {
                    $q->where('lecturer_id', $lecturer->id)
                      ->orWhere('lecturer_name', 'like', '%' . $lecturer->full_name . '%');
                } else {
                    $q->where('lecturer_name', 'like', '%' . $user->name . '%');
                }
            });
        }

        // Áp dụng bộ lọc lịch học / lịch thi
        if ($filter === 'study') {
            $query->where('schedule_type', '!=', 'EXAM');
        } elseif ($filter === 'exam') {
            $query->where('schedule_type', 'EXAM');
        }

        $schedules = $query->orderBy('schedule_date')->orderBy('start_period')->get();

        // 6. Nhóm lịch vào ma trận 3 ca (MORNING, AFTERNOON, EVENING) x 7 ngày
        $shifts = [
            'MORNING' => [
                'name' => 'Sáng',
                'periods' => 'Tiết 1 - 6',
            ],
            'AFTERNOON' => [
                'name' => 'Chiều',
                'periods' => 'Tiết 7 - 12',
            ],
            'EVENING' => [
                'name' => 'Tối',
                'periods' => 'Tiết 13 - 16',
            ],
        ];

        $matrix = [
            'MORNING' => [],
            'AFTERNOON' => [],
            'EVENING' => [],
        ];

        foreach ($weekDays as $wDay) {
            $d = $wDay['date'];
            $matrix['MORNING'][$d] = collect();
            $matrix['AFTERNOON'][$d] = collect();
            $matrix['EVENING'][$d] = collect();
        }

        foreach ($schedules as $sched) {
            $d = $sched->schedule_date->toDateString();
            $shift = $sched->shift ?: 'MORNING';
            if (isset($matrix[$shift][$d])) {
                $matrix[$shift][$d]->push($sched);
            }
        }

        return view('schedules.index', compact(
            'weekDays',
            'shifts',
            'matrix',
            'startOfWeek',
            'endOfWeek',
            'selectedDate',
            'prevWeekDate',
            'nextWeekDate',
            'currentWeekDate',
            'filter'
        ));
    }

    /**
     * Update schedule status (Lecturer: report suspended, move online, revert; Admin: full edit).
     */
    public function updateStatus(Request $request, ClassSchedule $schedule): RedirectResponse
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isLecturer()) {
            abort(403, 'Bạn không có quyền thay đổi trạng thái buổi học.');
        }

        $validated = $request->validate([
            'schedule_type' => ['required', 'in:THEORY,PRACTICE,ONLINE,EXAM,SUSPENDED'],
            'meeting_link' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $schedule->update([
            'schedule_type' => $validated['schedule_type'],
            'meeting_link' => $validated['meeting_type'] ?? $validated['meeting_link'] ?? null,
            'note' => $validated['note'] ?? null,
        ]);

        $statusLabel = match ($validated['schedule_type']) {
            'SUSPENDED' => 'Tạm ngưng / Báo nghỉ',
            'ONLINE' => 'Học trực tuyến',
            'PRACTICE' => 'Thực hành',
            'EXAM' => 'Lịch thi',
            default => 'Lý thuyết',
        };

        return back()->with('success', "Đã cập nhật trạng thái buổi học sang [{$statusLabel}] thành công!");
    }
}
