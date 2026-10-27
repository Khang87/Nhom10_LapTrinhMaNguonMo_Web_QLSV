<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Support\TimetableService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Thời khóa biểu tuần của sinh viên (SPEC-attendance-schedule 7.5), chỉ xem, không tự đổi lịch.
 */
class TimetableController extends Controller
{
    public function index(Request $request): View
    {
        $semester = TimetableService::semesterFor($request);
        $slots = TimetableService::forStudent($request->user(), $semester);

        return view('student.timetable', [
            'semesters' => TimetableService::semesters(),
            'semester' => $semester,
            'grid' => TimetableService::grid($slots),
            'conflicts' => TimetableService::conflicts($slots),
        ]);
    }
}
