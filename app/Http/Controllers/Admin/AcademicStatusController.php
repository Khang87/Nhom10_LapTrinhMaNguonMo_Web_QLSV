<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PersonLabels;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Ghi tình trạng học tập sau khi đã xem gợi ý xét duyệt. Phần mềm chỉ đề xuất; quyết định
 * lên lớp hay tốt nghiệp thuộc hội đồng đào tạo nên admin phải chọn tường minh trong từ điển
 * PersonLabels::ACADEMIC_STATUSES.
 */
class AcademicStatusController extends Controller
{
    public function update(Request $request, User $student): RedirectResponse
    {
        abort_unless($student->role === 'student', 404);

        $data = $request->validate([
            'academic_status' => ['required', Rule::in(PersonLabels::statusCodes())],
        ]);

        $student->update([
            'academic_status' => $data['academic_status'],
            'academic_status_updated_at' => now(),
        ]);

        return redirect()
            ->route('admin.students.show', $student)
            ->with('success', 'Đã ghi nhận tình trạng '.PersonLabels::status($data['academic_status']).' cho '.$student->name.'.');
    }
}
