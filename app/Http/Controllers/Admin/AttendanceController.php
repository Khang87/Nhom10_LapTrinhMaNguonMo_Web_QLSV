<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $date = $request->query('date', today()->toDateString());
        validator(['date' => $date], ['date' => ['required', 'date']])->validate();

        $records = Attendance::with(['user', 'subject'])
            ->whereDate('date', $date)
            ->orderBy('subject_id')
            ->orderBy('user_id')
            ->get();

        return view('admin.attendances.index', [
            'date' => $date,
            'students' => User::where('role', 'student')->with('subjects')->orderBy('name')->get(),
            'subjects' => Subject::orderBy('code')->get(),
            'records' => $records,
            'presentCount' => $records->where('status', 'present')->count(),
            'absentCount' => $records->where('status', 'absent')->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'attendances' => ['nullable', 'array'],
            'attendances.*' => ['array'],
            'attendances.*.*' => ['nullable', Rule::in(['present', 'absent'])],
        ]);
        $attendanceDate = Carbon::parse($data['date'])->startOfDay();

        DB::transaction(function () use ($data, $attendanceDate) {
            foreach ($data['attendances'] ?? [] as $studentId => $subjects) {
                $student = User::where('role', 'student')->findOrFail($studentId);

                foreach ($subjects as $subjectId => $status) {
                    if ($status === null || $status === '') {
                        continue;
                    }

                    $subject = Subject::findOrFail($subjectId);

                    if (! $student->subjects()->whereKey($subject->id)->exists()) {
                        throw ValidationException::withMessages([
                            'attendances' => "Sinh viên {$student->name} chưa đăng ký {$subject->name}.",
                        ]);
                    }

                    Attendance::updateOrCreate(
                        [
                            'user_id' => $student->id,
                            'subject_id' => $subject->id,
                            'date' => $attendanceDate,
                        ],
                        ['status' => $status],
                    );
                }
            }
        });

        return redirect()->route('admin.attendances.index', ['date' => $attendanceDate->toDateString()])
            ->with('success', 'Đã lưu điểm danh.');
    }
}
