<?php

namespace App\Http\Controllers;

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
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('student.dashboard', [
            'student' => $student->load('studentClass'),
            'enrolledCount' => $enrolledCount,
            'presentCount' => (int) ($attendanceSummary['present'] ?? 0),
            'absentCount' => (int) ($attendanceSummary['absent'] ?? 0),
            // Dashboard chỉ tóm tắt số đã công bố; điểm giảng viên chưa publish thuộc trang bảng điểm.
            'gradesCount' => $current->count(),
            'recentGrades' => $current->sortByDesc(fn ($grade) => $grade->updated_at?->timestamp)->take(5),
            'components' => GradingScale::components(),
        ]);
    }
}
