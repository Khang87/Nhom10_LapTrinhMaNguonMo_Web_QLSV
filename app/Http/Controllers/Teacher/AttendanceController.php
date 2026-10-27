<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\CourseOffering;
use App\Models\Enrollment;
use App\Models\LectureSlot;
use App\Support\AttendanceRate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Điểm danh trực tuyến (SPEC-attendance-schedule 7.4): giảng viên mở buổi học của lớp mình,
 * bỏ check người vắng, lưu một lần. Toàn lớp mặc định "Có mặt".
 */
class AttendanceController extends Controller
{
    /**
     * Lưới lịch lên lớp của một tuần: mỗi ô là một slot, kèm buổi đã điểm danh của ô đó.
     */
    public function index(Request $request, CourseOffering $offering): View
    {
        $this->authorizeTeacher($request, $offering);

        $weekStart = $this->weekStart($request);
        $roster = $this->roster($offering);
        $held = $offering->attendanceSessions()->whereHas('records')->count();

        return view('teacher.attendance.index', [
            'offering' => $offering->load(['subject', 'semester']),
            'weekStart' => $weekStart,
            'weekEnd' => $weekStart->copy()->addDays(count(LectureSlot::WEEK_DAYS) - 1),
            'days' => $this->daysOf($weekStart, $offering),
            'sessionCount' => $held,
            'size' => $roster->count(),
            'classRate' => $this->classRate(AttendanceRate::forEnrollments($roster)),
        ]);
    }

    /**
     * Form điểm danh nhanh của một buổi. Buổi đã chấm rồi thì mở lại để sửa, không tạo buổi mới.
     */
    public function create(Request $request, CourseOffering $offering): View
    {
        $this->authorizeTeacher($request, $offering);

        $date = $this->sessionDate($request);
        $session = AttendanceSession::with('records')
            ->where('course_offering_id', $offering->id)
            ->whereDate('session_date', $date)
            ->first();
        $roster = $this->roster($offering);
        $rates = AttendanceRate::forEnrollments($roster);

        $rows = $roster->map(function (Enrollment $enrollment) use ($session, $rates) {
            return [
                'enrollment' => $enrollment,
                'student' => $enrollment->user,
                'record' => $session?->records->first(fn (Attendance $record) => $record->enrollment_id === $enrollment->id),
                'rate' => $rates[$enrollment->id],
            ];
        });

        return view('teacher.attendance.session', [
            'offering' => $offering->load(['subject', 'semester']),
            'date' => $date,
            'slot' => $this->slotOf($request, $offering, $date),
            'session' => $session,
            'rows' => $rows,
            'threshold' => AttendanceRate::warningThreshold(),
        ]);
    }

    public function store(Request $request, CourseOffering $offering): RedirectResponse
    {
        $this->authorizeTeacher($request, $offering);

        $data = $request->validate([
            'session_date' => ['required', 'date'],
            'lecture_slot_id' => ['nullable', 'integer'],
            'note' => ['nullable', 'string', 'max:2000'],
            'statuses' => ['nullable', 'array'],
            'statuses.*' => [Rule::in(Attendance::STATUSES)],
        ]);

        $date = Carbon::parse($data['session_date'])->startOfDay();
        $slotId = $this->slotIdOf($offering, $data['lecture_slot_id'] ?? null);
        $enrollments = $offering->enrollments()->active()->orderBy('user_id')->pluck('id');

        DB::transaction(function () use ($data, $offering, $date, $slotId, $enrollments, $request) {
            $session = AttendanceSession::updateOrCreate(
                ['course_offering_id' => $offering->id, 'session_date' => $date->toDateString()],
                [
                    'lecture_slot_id' => $slotId,
                    'note' => filled($data['note'] ?? null) ? $data['note'] : null,
                    'recorded_by' => $request->user()->id,
                ],
            );

            foreach ($enrollments as $enrollmentId) {
                Attendance::updateOrCreate(
                    ['attendance_session_id' => $session->id, 'enrollment_id' => $enrollmentId],
                    [
                        'course_offering_id' => $offering->id,
                        'status' => $data['statuses'][$enrollmentId] ?? Attendance::PRESENT,
                    ],
                );
            }
        });

        return redirect()
            ->route('teacher.course-offerings.attendance.session.create', [
                'offering' => $offering,
                'date' => $date->toDateString(),
            ])
            ->with('success', 'Đã điểm danh '.$enrollments->count().' sinh viên của buổi '.$date->format('d/m/Y').'.');
    }

    /**
     * Mỗi ngày của tuần: ngày dương lịch, các slot áp dụng cho tuần đó và buổi đã điểm danh
     * của ngày (nếu có). Slot ngoài khoảng starts_on/ends_on bị loại.
     *
     * @return array<int, array{date: Carbon, slots: Collection<int, LectureSlot>, session: AttendanceSession|null}>
     */
    private function daysOf(Carbon $weekStart, CourseOffering $offering): array
    {
        $weekEnd = $weekStart->copy()->addDays(count(LectureSlot::WEEK_DAYS) - 1);
        $slots = $offering->lectureSlots()
            ->with(['sessions' => fn ($query) => $query
                ->whereBetween('session_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
                ->with('records')])
            ->orderBy('start_time')->get();
        $days = [];

        foreach (array_keys(LectureSlot::WEEK_DAYS) as $weekDay) {
            $day = $weekStart->copy()->addDays($weekDay - 2);

            $daySlots = $slots->filter(function (LectureSlot $slot) use ($weekDay, $day) {
                if ($slot->week_day !== $weekDay) {
                    return false;
                }

                return ($slot->starts_on === null || $slot->starts_on->lte($day))
                    && ($slot->ends_on === null || $slot->ends_on->gte($day));
            })->values();

            $days[$weekDay] = [
                'date' => $day,
                'slots' => $daySlots,
                'session' => $daySlots->flatMap(fn (LectureSlot $slot) => $slot->sessions)
                    ->first(fn (AttendanceSession $session) => $session->session_date->toDateString() === $day->toDateString()),
            ];
        }

        return $days;
    }

    /**
     * Danh sách lớp hiện hành, sắp theo tên sinh viên để giảng viên dễ dò trên lớp.
     *
     * @return Collection<int, Enrollment>
     */
    private function roster(CourseOffering $offering): Collection
    {
        return $offering->enrollments()->active()->with('user')->get()
            ->sortBy(fn (Enrollment $enrollment) => $enrollment->user?->name ?? '')
            ->values();
    }

    /**
     * Slot của buổi đang mở: giảng viên bấm từ ô lịch thì có `slot`, còn truy vấn tay theo
     * ngày thì suy ra slot có thứ trùng ngày hôm đó.
     */
    private function slotOf(Request $request, CourseOffering $offering, string $date): ?LectureSlot
    {
        $slotId = $request->query('slot');

        if ($slotId !== null) {
            $slot = $offering->lectureSlots()->find((int) $slotId);

            if ($slot !== null) {
                return $slot;
            }
        }

        $weekDay = $this->weekDayOf(Carbon::parse($date));

        if ($weekDay === null) {
            return null;
        }

        return $offering->lectureSlots()
            ->where('week_day', $weekDay)
            ->orderBy('start_time')
            ->first();
    }

    /**
     * Ngày dương lịch -> mã tuần của slot (ISO 1=Thứ 2 ... 6=Thứ 7 => 2..7); Chủ nhật không có lịch.
     */
    private function weekDayOf(Carbon $date): ?int
    {
        $iso = (int) $date->dayOfWeekIso;

        return $iso === 7 ? null : $iso + 1;
    }

    private function slotIdOf(CourseOffering $offering, ?int $slotId): ?int
    {
        if ($slotId === null) {
            return null;
        }

        return $offering->lectureSlots()->whereKey($slotId)->exists() ? $slotId : null;
    }

    /**
     * Tỷ lệ chuyên cần trung bình của lớp, chỉ tính những sinh viên đã có dữ liệu.
     *
     * @param  array<int, array{rate: float|null}>  $rates
     */
    private function classRate(array $rates): ?float
    {
        $rates = collect(array_column($rates, 'rate'))->filter();

        return $rates->isEmpty() ? null : round((float) $rates->avg(), 1);
    }

    private function weekStart(Request $request): Carbon
    {
        $week = $request->query('week');
        $base = filled($week)
            ? Carbon::parse(validator(['week' => $week], ['week' => ['required', 'date']])->validated()['week'])
            : Carbon::now();

        return $base->startOfWeek(Carbon::MONDAY);
    }

    private function sessionDate(Request $request): string
    {
        $date = $request->query('date', Carbon::now()->toDateString());
        validator(['date' => $date], ['date' => ['required', 'date']])->validate();

        return Carbon::parse($date)->toDateString();
    }

    private function authorizeTeacher(Request $request, CourseOffering $offering): void
    {
        abort_unless((int) $offering->teacher_id === (int) $request->user()->id, 403);
    }
}
