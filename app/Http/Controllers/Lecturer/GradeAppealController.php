<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\GradeAppeal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class GradeAppealController extends Controller
{
    /**
     * Danh sách các yêu cầu thắc mắc / phúc khảo điểm của sinh viên các lớp giảng viên dạy
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $lecturer = $user->lecturer;

        $sectionIds = $lecturer ? $lecturer->sections()->pluck('id') : collect();

        $status = $request->input('status', 'all');

        $query = GradeAppeal::with(['student', 'section.course', 'enrollment'])
            ->whereIn('section_id', $sectionIds);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $appeals = $query->orderBy('created_at', 'desc')->get();

        $pendingCount = GradeAppeal::whereIn('section_id', $sectionIds)->where('status', 'PENDING')->count();
        $reviewedCount = GradeAppeal::whereIn('section_id', $sectionIds)->where('status', 'REVIEWED')->count();
        $resolvedCount = GradeAppeal::whereIn('section_id', $sectionIds)->where('status', 'RESOLVED')->count();

        return view('lecturer.appeals.index', compact(
            'appeals',
            'status',
            'pendingCount',
            'reviewedCount',
            'resolvedCount',
            'lecturer'
        ));
    }

    /**
     * Giảng viên phản hồi thắc mắc điểm và đề xuất điểm điều chỉnh
     */
    public function reply(GradeAppeal $appeal, Request $request): RedirectResponse
    {
        $user = Auth::user();
        $lecturer = $user->lecturer;

        if ($lecturer && $appeal->section?->lecturer_id !== $lecturer->id && !$user->isAdmin()) {
            abort(403, 'Bạn không phụ trách lớp học phần này.');
        }

        $validated = $request->validate([
            'lecturer_feedback' => ['required', 'string', 'max:1000'],
            'new_score' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'decision' => ['required', 'in:ACCEPT,REJECT'],
        ]);

        if ($validated['decision'] === 'REJECT') {
            $appeal->update([
                'status' => 'REJECTED',
                'lecturer_feedback' => $validated['lecturer_feedback'],
                'new_score' => null,
            ]);
            $msg = "Đã phản hồi từ chối phúc khảo điểm cho sinh viên [{$appeal->student?->student_code}].";
        } else {
            $appeal->update([
                'status' => 'REVIEWED',
                'lecturer_feedback' => $validated['lecturer_feedback'],
                'new_score' => $validated['new_score'] !== null ? (float) $validated['new_score'] : $appeal->current_score,
            ]);
            $msg = "Đã gửi phản hồi và đề xuất điều chỉnh điểm lên Phòng Đào tạo để phê duyệt chính thức.";
        }

        return back()->with('success', $msg);
    }
}
