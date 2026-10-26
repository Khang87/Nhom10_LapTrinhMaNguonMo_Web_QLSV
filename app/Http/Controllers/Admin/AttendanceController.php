<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\Subject;
use App\Models\User;
use App\Support\Csv;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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

        $students = User::where('role', 'student')->with('offerings.subject')->orderBy('name')->get();

        return view('admin.attendances.index', [
            'date' => $date,
            'students' => $students,
            'subjects' => Subject::orderBy('code')->get(),
            'records' => $records,
            'presentCount' => $records->where('status', 'present')->count(),
            'absentCount' => $records->where('status', 'absent')->count(),
        ]);
    }

    public function export(Request $request): Response
    {
        $date = $request->query('date', today()->toDateString());
        validator(['date' => $date], ['date' => ['required', 'date']])->validate();

        $rows = Attendance::with(['user', 'subject'])
            ->whereDate('date', $date)
            ->orderBy('subject_id')
            ->orderBy('user_id')
            ->get()
            ->map(fn (Attendance $record) => [
                $record->user->name,
                $record->user->email,
                $record->subject->code,
                $record->subject->name,
                Carbon::parse($record->date)->format('d/m/Y'),
                $record->status === 'present' ? 'Có mặt' : 'Vắng',
            ]);

        return Csv::download(
            'diem-danh-'.Carbon::parse($date)->toDateString().'.csv',
            ['Sinh viên', 'Email', 'Mã môn', 'Môn học', 'Ngày', 'Trạng thái'],
            $rows
        );
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

                    $enrolled = Enrollment::where('user_id', $student->id)
                        ->where('status', 'ACTIVE')
                        ->whereHas('offering', fn ($query) => $query->where('subject_id', $subject->id))
                        ->exists();

                    if (! $enrolled) {
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
