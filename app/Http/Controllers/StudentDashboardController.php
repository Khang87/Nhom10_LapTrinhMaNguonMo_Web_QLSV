<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StudentDashboardController extends Controller
{
    public function index(): View
    {
        $student = Auth::user()->load('studentClass')->loadCount('grades');
        $enrolledCount = $student->offerings()->wherePivot('status', 'ACTIVE')->count();
        $attendanceSummary = $student->attendances()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $recentGrades = $student->grades()
            ->with('offering.subject')
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        return view('student.dashboard', [
            'student' => $student,
            'enrolledCount' => $enrolledCount,
            'presentCount' => (int) ($attendanceSummary['present'] ?? 0),
            'absentCount' => (int) ($attendanceSummary['absent'] ?? 0),
            'recentGrades' => $recentGrades,
        ]);
    }
}
