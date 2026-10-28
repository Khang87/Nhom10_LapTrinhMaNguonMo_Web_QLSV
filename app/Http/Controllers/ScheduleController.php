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
            ->with(['section.course', 'section.semester', 'course', 'lecturer'])
            ->forDateRange($startOfWeek->toDateString(), $endOfWeek->toDateString());

        if ($user->isStudent()) {
            $student = $user->student;
            $enrolledSections = $student
                ? $student->enrollments()
                    ->with(['section.course', 'section.lecturer', 'section.semester'])
                    ->get()
                    ->pluck('section')
                    ->filter()
                : collect();

            $enrolledSectionIds = $enrolledSections->pluck('id');

            // Tự động đồng bộ các buổi học trong tuần đang xem cho các học phần sinh viên ĐÃ ĐĂNG KÝ
            // QUY TẮC CỐT LÕI: Buổi học CHỈ được tạo nếu nằm trong thời gian bắt đầu và kết thúc của học phần!
            foreach ($enrolledSections as $sec) {
                $dayOfWeek = (int) $sec->day_of_week;
                // 2 = Thứ 2 (offset 0), 3 = Thứ 3 (offset 1), ..., 7 = Thứ 7 (offset 5), 8 = Chủ nhật (offset 6)
                $dayOffset = ($dayOfWeek >= 2 && $dayOfWeek <= 7) ? ($dayOfWeek - 2) : 6;
                $sessionDate = $startOfWeek->copy()->addDays($dayOffset)->toDateString();

                $secStartDate = $sec->getStartDate()->toDateString();
                $secEndDate = $sec->getEndDate()->toDateString();

                // Chỉ tạo buổi học nếu sessionDate nằm trong khoảng thời gian diễn ra học phần (chặn lịch vô tận)
                if ($sessionDate >= $secStartDate && $sessionDate <= $secEndDate) {
                    $exists = ClassSchedule::where('section_id', $sec->id)
                        ->whereDate('schedule_date', $sessionDate)
                        ->exists();

                    if (!$exists) {
                        ClassSchedule::create([
                            'section_id' => $sec->id,
                            'schedule_date' => $sessionDate,
                            'course_id' => $sec->course_id,
                            'lecturer_id' => $sec->lecturer_id,
                            'class_group_name' => $sec->specialization_name ?: ($student->class_code ?? 'Lớp HP'),
                            'section_code' => $sec->section_code,
                            'subject_name' => $sec->course?->course_name ?? 'Học phần',
                            'start_period' => $sec->start_period ?? 1,
                            'end_period' => $sec->end_period ?? 3,
                            'room_info' => $sec->room_name ?? 'A110 - Phòng máy tính - 140 Lê Trọng Tấn',
                            'lecturer_name' => $sec->lecturer?->full_name ?? 'Chưa phân công',
                            'shift' => ($sec->start_period ?? 1) <= 6 ? 'MORNING' : (($sec->start_period ?? 1) <= 12 ? 'AFTERNOON' : 'EVENING'),
                            'schedule_type' => 'THEORY',
                        ]);
                    }
                }
            }

            // QUAN TRỌNG: Thời khóa biểu của sinh viên CHỈ HIỂN THỊ CÁC HỌC PHẦN MÀ SINH VIÊN ĐÃ ĐĂNG KÝ!
            // Tuyệt đối không lấy lịch theo class_group_name (lớp sinh hoạt) để tránh rò rỉ lịch của lớp/ngành khác sang sinh viên!
            if ($enrolledSectionIds->isNotEmpty()) {
                $query->whereIn('section_id', $enrolledSectionIds);
            } else {
                // Sinh viên chưa đăng ký học phần nào -> Thời khóa biểu hiển thị rỗng
                $query->whereRaw('1 = 0');
            }
        } elseif ($user->isLecturer()) {
            $lecturer = $user->lecturer;
            $teachingSections = $lecturer
                ? CourseSection::with(['course', 'semester'])->where('lecturer_id', $lecturer->id)->get()
                : collect();
            $teachingSectionIds = $teachingSections->pluck('id');

            // Đồng bộ lịch dạy cho giảng viên theo thời hạn học phần
            foreach ($teachingSections as $sec) {
                $dayOfWeek = (int) $sec->day_of_week;
                $dayOffset = ($dayOfWeek >= 2 && $dayOfWeek <= 7) ? ($dayOfWeek - 2) : 6;
                $sessionDate = $startOfWeek->copy()->addDays($dayOffset)->toDateString();

                $secStartDate = $sec->getStartDate()->toDateString();
                $secEndDate = $sec->getEndDate()->toDateString();

                if ($sessionDate >= $secStartDate && $sessionDate <= $secEndDate) {
                    $exists = ClassSchedule::where('section_id', $sec->id)
                        ->whereDate('schedule_date', $sessionDate)
                        ->exists();

                    if (!$exists) {
                        ClassSchedule::create([
                            'section_id' => $sec->id,
                            'schedule_date' => $sessionDate,
                            'course_id' => $sec->course_id,
                            'lecturer_id' => $sec->lecturer_id,
                            'class_group_name' => $sec->specialization_name ?: 'Lớp HP',
                            'section_code' => $sec->section_code,
                            'subject_name' => $sec->course?->course_name ?? 'Học phần',
                            'start_period' => $sec->start_period ?? 1,
                            'end_period' => $sec->end_period ?? 3,
                            'room_info' => $sec->room_name ?? 'A110 - Phòng máy tính - 140 Lê Trọng Tấn',
                            'lecturer_name' => $lecturer->full_name,
                            'shift' => ($sec->start_period ?? 1) <= 6 ? 'MORNING' : (($sec->start_period ?? 1) <= 12 ? 'AFTERNOON' : 'EVENING'),
                            'schedule_type' => 'THEORY',
                        ]);
                    }
                }
            }

            $query->where(function ($q) use ($lecturer, $teachingSectionIds, $user) {
                if ($teachingSectionIds->isNotEmpty()) {
                    $q->whereIn('section_id', $teachingSectionIds);
                }
                if ($lecturer) {
                    $q->orWhere('lecturer_id', $lecturer->id)
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
