<?php

namespace App\Http\Controllers;

use App\Models\CourseOffering;
use App\Models\Enrollment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StudentOfferingController extends Controller
{
    public function index(): View
    {
        $student = Auth::user();

        $registered = $student->offerings()
            ->wherePivot('status', 'ACTIVE')
            ->with(['subject', 'semester', 'teacher'])
            ->orderBy('semester_id')
            ->orderBy('subject_id')
            ->orderBy('group_code')
            ->get();

        $available = CourseOffering::with(['subject', 'semester', 'teacher'])
            ->withCount(['enrollments as active_count' => fn ($query) => $query->where('status', 'ACTIVE')])
            ->where('is_open', true)
            ->whereDoesntHave('enrollments', fn ($query) => $query
                ->where('user_id', $student->id)->where('status', 'ACTIVE'))
            ->orderBy('subject_id')
            ->orderBy('group_code')
            ->get();

        return view('student.offerings.index', [
            'registered' => $registered,
            'available' => $available,
            'totalCredits' => $registered->sum(fn (CourseOffering $offering) => $offering->subject?->credits ?? 0),
        ]);
    }

    public function register(CourseOffering $offering): RedirectResponse
    {
        $student = Auth::user();
        $enrollment = Enrollment::where('user_id', $student->id)
            ->where('course_offering_id', $offering->id)
            ->first();

        if ($enrollment?->status === 'ACTIVE') {
            return redirect()->route('student.offerings.index')
                ->with('error', 'Bạn đã đăng ký học phần này.');
        }

        if ($offering->seatsLeft() === 0) {
            return redirect()->route('student.offerings.index')
                ->with('error', "Lớp {$offering->label()} đã hết chỗ.");
        }

        if ($enrollment === null) {
            Enrollment::create([
                'course_offering_id' => $offering->id,
                'user_id' => $student->id,
                'status' => 'ACTIVE',
            ]);
        } else {
            $enrollment->update(['status' => 'ACTIVE', 'dropped_at' => null]);
        }

        return redirect()->route('student.offerings.index')
            ->with('success', 'Đăng ký học phần thành công.');
    }

    public function unregister(CourseOffering $offering): RedirectResponse
    {
        $student = Auth::user();
        $enrollment = $this->activeEnrollment($student->id, $offering->id)->first();

        if ($enrollment === null) {
            return redirect()->route('student.offerings.index')
                ->with('error', 'Học phần này chưa được đăng ký.');
        }

        $enrollment->update(['status' => 'DROPPED', 'dropped_at' => now()]);

        return redirect()->route('student.offerings.index')
            ->with('success', 'Đã hủy đăng ký học phần.');
    }

    private function activeEnrollment(int $studentId, int $offeringId): Builder
    {
        return Enrollment::where('user_id', $studentId)
            ->where('course_offering_id', $offeringId)
            ->where('status', 'ACTIVE');
    }
}
