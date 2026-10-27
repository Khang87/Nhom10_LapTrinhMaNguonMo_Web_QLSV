<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Support\TimetableService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Lịch dạy trong tuần của giảng viên: mỗi ô mở màn hình điểm danh của lớp tương ứng.
 */
class TimetableController extends Controller
{
    public function index(Request $request): View
    {
        $semester = TimetableService::semesterFor($request);
        $slots = TimetableService::forTeacher($request->user(), $semester);

        return view('teacher.timetable', [
            'semesters' => TimetableService::semesters(),
            'semester' => $semester,
            'grid' => TimetableService::grid($slots),
            'conflicts' => TimetableService::conflicts($slots),
        ]);
    }
}
