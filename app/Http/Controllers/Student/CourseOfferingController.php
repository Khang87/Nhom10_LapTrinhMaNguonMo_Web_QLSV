<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\CourseOffering;
use App\Models\Semester;
use App\Support\EnrollmentContext;
use App\Support\EnrollmentPolicy;
use App\Support\EnrollmentRegistrar;
use App\Support\TimetableService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseOfferingController extends Controller
{
    public function index(Request $request): View
    {
        $student = $request->user();
        $semester = Semester::current();

        $registered = $student->offerings()
            ->wherePivot('status', 'ACTIVE')
            ->where('course_offerings.semester_id', $semester?->id)
            ->with(['subject', 'semester', 'teacher'])
            ->orderBy('subject_id')
            ->orderBy('group_code')
            ->get();

        $available = CourseOffering::with(['subject.prerequisites', 'subject.concurrents', 'subject.concurrentOf', 'semester', 'teacher'])
            ->withCount(['enrollments as active_count' => fn ($query) => $query->where('status', 'ACTIVE')])
            ->where('semester_id', $semester?->id)
            ->whereDoesntHave('enrollments', fn ($query) => $query
                ->where('user_id', $student->id)->where('status', 'ACTIVE'))
            ->orderBy('subject_id')
            ->orderBy('group_code')
            ->get();

        $contexts = EnrollmentContext::collect($student, $available);
        $slotsByOffering = TimetableService::forSemester($semester)->groupBy('course_offering_id');
        $mySlots = TimetableService::forStudent($student, $semester);
        $blocked = [];
        $clashing = [];

        foreach ($available as $offering) {
            $verdict = EnrollmentPolicy::check($student, $offering, $contexts[$offering->id]);

            $blocked[$offering->id] = $verdict['ok'] ? null : EnrollmentPolicy::reasonText($verdict);
            $clashing[$offering->id] = TimetableService::overlapping(
                $mySlots,
                $slotsByOffering->get($offering->id, collect()),
            )->count();
        }

        return view('student.course-offerings.index', [
            'registered' => $registered,
            'available' => $available,
            'blocked' => $blocked,
            'clashing' => $clashing,
            'currentCredits' => (int) $registered->sum(fn (CourseOffering $offering) => (int) ($offering->subject?->credits ?? 0)),
            'accumulatedCredits' => EnrollmentContext::accumulatedCredits($student),
            'maxCredits' => (int) config('academic.max_credits_per_term'),
            'semester' => $semester,
            'gateOpen' => $semester?->isRegistrationOpen() === true,
        ]);
    }

    public function enroll(Request $request, CourseOffering $offering): RedirectResponse
    {
        EnrollmentRegistrar::enroll($request->user(), $offering);

        return redirect()->route('student.course-offerings.index')
            ->with('success', 'Đã đăng ký '.$offering->label().'.');
    }
}
