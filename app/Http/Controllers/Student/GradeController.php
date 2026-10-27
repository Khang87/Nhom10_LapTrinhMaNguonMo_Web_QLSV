<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Support\GradeBook;
use App\Support\GradingScale;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Bảng điểm của chính sinh viên: hiện hành (/grades) và lịch sử mọi lớp đã học (/grades/history).
 * Điểm chưa công bố vẫn ra tên lớp để sinh viên biết mình đang học gì, nhưng cột điểm chỉ ghi
 * "Chưa công bố" thay vì số.
 */
class GradeController extends Controller
{
    public function index(Request $request): View
    {
        $student = $request->user();

        return view('student.grades.index', [
            'rows' => GradeBook::current($student),
            'summary' => GradeBook::summary(GradeBook::history($student)),
            'components' => GradingScale::components(),
        ]);
    }

    public function history(Request $request): View
    {
        return view('student.grades.history', [
            'rows' => GradeBook::history($request->user()),
            'components' => GradingScale::components(),
        ]);
    }
}
