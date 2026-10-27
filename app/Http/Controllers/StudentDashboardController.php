<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Support\GradeBook;
use App\Support\GradingScale;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StudentDashboardController extends Controller
{
    public function index(): View
    {
        $student = Auth::user();
        $current = GradeBook::current($student)->where('is_published', true);
        $enrolledCount = $student->offerings()->wherePivot('status', 'ACTIVE')->count();
        $attendanceSummary = $student->attendances()
            ->selectRaw('attendances.status as status_key, COUNT(*) as total')
            ->groupBy('attendances.status')
            ->pluck('total', 'status_key');

        return view('student.dashboard', [
            'student' => $student->load('studentClass'),
            'enrolledCount' => $enrolledCount,
            'presentCount' => (int) ($attendanceSummary[Attendance::PRESENT] ?? 0),
            'absentCount' => (int) ($attendanceSummary[Attendance::ABSENT] ?? 0),
            // Dashboard chỉ tóm tắt số đã công bố; điểm giảng viên chưa publish thuộc trang bảng điểm.
            'gradesCount' => $current->count(),
            'recentGrades' => $current->sortByDesc(fn ($grade) => $grade->updated_at?->timestamp)->take(5),
            'components' => GradingScale::components(),
        ]);
    }
}
