<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Support\EnrollmentContext;
use App\Support\EnrollmentPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function drop(Request $request, Enrollment $enrollment): RedirectResponse
    {
        $student = $request->user();

        abort_unless($enrollment->user_id === $student->id, 403);

        if (! $enrollment->isActive()) {
            return redirect()->route('student.course-offerings.index')
                ->with('error', 'Học phần này không còn ở trạng thái đang học.');
        }

        $verdict = EnrollmentPolicy::checkDrop(EnrollmentContext::forDrop($enrollment));

        if (! $verdict['ok']) {
            return redirect()->route('student.course-offerings.index')
                ->with('error', EnrollmentPolicy::reasonText($verdict));
        }

        $enrollment->update(['status' => 'DROPPED', 'dropped_at' => now()]);

        return redirect()->route('student.course-offerings.index')
            ->with('success', 'Đã hủy đăng ký '.$enrollment->offering->label().'.');
    }
}
