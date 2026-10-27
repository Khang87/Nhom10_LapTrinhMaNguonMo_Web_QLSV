<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TuitionInvoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StudentSubjectController extends Controller
{
    /**
     * Display course registration page with:
     * - Semester progression filter (from Year 1 up to current semester)
     * - Registration window verification (open 2 months before semester start)
     * - Top 3 Tabs: First-time (Học mới), Retake (Học lại), Improvement (Học cải thiện)
     * - All open sections consolidated in one place
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $student = $user->student;

        // 1. Lấy danh sách các học kỳ
        $availableSemesters = Semester::orderBy('order_index')->get();

        // Mặc định chọn kỳ học mở đăng ký gần nhất, hoặc kỳ cuối cùng trong danh sách
        $defaultSemester = $availableSemesters->first(fn($s) => $s->is_registration_open)
            ?? $availableSemesters->last();
        $selectedSemesterCode = $request->input('semester', $defaultSemester?->semester_code);
        $selectedSemester = Semester::where('semester_code', $selectedSemesterCode)->first();
        if (!$selectedSemester && $availableSemesters->isNotEmpty()) {
            $selectedSemester = $availableSemesters->last();
            $selectedSemesterCode = $selectedSemester->semester_code;
        }

        $isRegistrationActive = $selectedSemester ? $selectedSemester->isRegistrationActive() : false;

        // 2. Danh sách lớp học phần sinh viên ĐÃ ĐĂNG KÝ trong kỳ được chọn
        $enrollments = $student
            ? Enrollment::with(['section.course', 'section.lecturer'])
                ->where('student_id', $student->id)
                ->whereHas('section', fn($q) => $q->where('semester_code', $selectedSemesterCode))
                ->get()
            : collect();

        $enrolledSectionIds = $enrollments->pluck('section_id')->toArray();
        $enrolledCourseIds = $enrollments->pluck('section.course_id')->filter()->unique()->toArray();

        // 3. Lấy danh sách lớp học phần trong kỳ và phân loại ra 3 Tab ở trên (Học mới, Học lại, Học cải thiện)
        $firstTimeSections = collect();
        $retakeSections = collect();
        $improvementSections = collect();
        $firstTimeCourses = collect();
        $retakeCourses = collect();
        $improvementCourses = collect();
        $allOpenSections = collect();

        if ($student) {
            // Lấy toàn bộ lớp học phần mở trong kỳ theo chuyên ngành của SV (gộp chung cả môn theo khung & môn add thêm)
            // Loại trừ các môn sinh viên ĐÃ ĐĂNG KÝ trong kỳ này
            $allOpenSections = CourseSection::with(['course', 'lecturer'])
                ->withCount('enrollments')
                ->where('semester_code', $selectedSemesterCode)
                ->where(function ($q) use ($student) {
                    $q->where('specialization_name', $student->specialization_name)
                      ->orWhereNull('specialization_name')
                      ->orWhere('specialization_name', '');
                })
                ->whereNotIn('course_id', $enrolledCourseIds)
                ->orderBy('day_of_week')
                ->orderBy('start_period')
                ->get();

            // Nếu không có lớp theo chuyên ngành riêng, nạp tất cả lớp trong kỳ chưa đăng ký
            if ($allOpenSections->isEmpty()) {
                $allOpenSections = CourseSection::with(['course', 'lecturer'])
                    ->withCount('enrollments')
                    ->where('semester_code', $selectedSemesterCode)
                    ->whereNotIn('course_id', $enrolledCourseIds)
                    ->orderBy('day_of_week')
                    ->orderBy('start_period')
                    ->get();
            }

            // Lấy lịch sử học tập của SV ở các kỳ trước (các môn đã có điểm)
            $pastEnrollments = Enrollment::with('section.course')
                ->where('student_id', $student->id)
                ->whereHas('section', fn($q) => $q->where('semester_code', '!=', $selectedSemesterCode))
                ->get();

            // Môn đã từng RỚT ở các kỳ trước (Điểm 10 < 5.0 hoặc Điểm F)
            $failedCourseIds = $pastEnrollments->filter(function ($e) {
                return (float)$e->total_score_10 < 5.0 || $e->letter_grade === 'F';
            })->pluck('section.course_id')->filter()->unique()->toArray();

            // Môn đã từng HỌC XONG VÀ ĐẠT ở các kỳ trước (Điểm 10 >= 5.0)
            $passedCourseIds = $pastEnrollments->filter(function ($e) {
                return (float)$e->total_score_10 >= 5.0 && $e->letter_grade !== 'F';
            })->pluck('section.course_id')->filter()->unique()->toArray();

            // Phân loại:
            // Tab 1: Học lại -> Lọc các lớp mở của các môn sinh viên đã rớt
            $retakeSections = $allOpenSections->filter(function ($sec) use ($failedCourseIds) {
                return in_array($sec->course_id, $failedCourseIds);
            })->values();

            // Tab 2: Học cải thiện -> Lọc các lớp mở của các môn sinh viên đã học xong
            $improvementSections = $allOpenSections->filter(function ($sec) use ($passedCourseIds) {
                return in_array($sec->course_id, $passedCourseIds);
            })->values();

            // Tab 3: Học mới (Lần đầu) -> Các môn chưa từng có điểm rớt hoặc đạt
            $firstTimeSections = $allOpenSections->reject(function ($sec) use ($failedCourseIds, $passedCourseIds) {
                return in_array($sec->course_id, $failedCourseIds) || in_array($sec->course_id, $passedCourseIds);
            })->values();

            // Gộp theo từng Môn học (Course), mỗi môn cung cấp tối đa 3 option thời gian/ca học
            $groupIntoCourses = function ($sections) {
                return $sections->groupBy('course_id')->map(function ($secGroup) {
                    return (object)[
                        'course' => $secGroup->first()->course,
                        'sections' => $secGroup->take(3), // Tối đa 3 option thời gian
                    ];
                })->values();
            };

            $firstTimeCourses = $groupIntoCourses($firstTimeSections);
            $retakeCourses = $groupIntoCourses($retakeSections);
            $improvementCourses = $groupIntoCourses($improvementSections);
        }

        // Hỗ trợ legacy subjects
        $registeredSubjects = $user->subjects()->orderBy('code')->get();
        $openSubjects = Subject::query()
            ->whereDoesntHave('students', fn ($query) => $query->whereKey($user->id))
            ->orderBy('code')
            ->get();

        return view('student.subjects.index', compact(
            'student',
            'availableSemesters',
            'selectedSemester',
            'selectedSemesterCode',
            'isRegistrationActive',
            'enrollments',
            'allOpenSections',
            'firstTimeSections',
            'retakeSections',
            'improvementSections',
            'firstTimeCourses',
            'retakeCourses',
            'improvementCourses',
            'registeredSubjects',
            'openSubjects'
        ));
    }

    /**
     * Register a course section with collision check and registration window enforcement.
     */
    public function register(Request $request, $id = null): RedirectResponse
    {
        $user = Auth::user();
        $student = $user->student;

        if (!$student) {
            return redirect()->route('student.subjects.index')
                ->with('error', 'Không tìm thấy hồ sơ sinh viên. Vui lòng liên hệ phòng đào tạo.');
        }

        $sectionId = $request->input('section_id', $id);
        if (!$sectionId) {
            return redirect()->route('student.subjects.index')
                ->with('error', 'Vui lòng chọn 1 ca học / thời gian học phần cụ thể trước khi ấn Đăng ký.');
        }

        $section = CourseSection::with('course')->find($sectionId);

        // Fallback legacy: tìm section từ Subject cũ nếu không có section_id
        if (!$section) {
            $subject = Subject::find($id);
            if ($subject) {
                $course = Course::where('course_code', $subject->code)->first();
                if ($course) {
                    $section = CourseSection::where('course_id', $course->id)->first();
                }
            }
        }

        if (!$section) {
            return redirect()->route('student.subjects.index')->with('error', 'Không tìm thấy lớp học phần để đăng ký.');
        }

        $semesterCode = $section->semester_code;
        $redirectParams = $request->filled('semester') ? ['semester' => $request->input('semester')] : [];

        // 1. KIỂM TRA THỜI GIAN MỞ ĐĂNG KÝ HỌC PHẦN (Chỉ cho phép khi đang mở)
        $semester = Semester::where('semester_code', $semesterCode)->first();
        if ($semester && !$semester->isRegistrationActive()) {
            return redirect()->route('student.subjects.index', $redirectParams)
                ->with('error', "❌ KHÔNG THỂ ĐĂNG KÝ: Đợt đăng ký học phần cho [{$semester->semester_name}] hiện đang đóng hoặc đã hết thời hạn.");
        }

        // 2. Lấy loại đăng ký: FIRST_TIME (Học mới), RETAKE (Học lại), IMPROVEMENT (Học cải thiện)
        $regType = $request->input('registration_type', 'FIRST_TIME');
        if (!in_array($regType, ['FIRST_TIME', 'RETAKE', 'IMPROVEMENT'])) {
            $regType = 'FIRST_TIME';
        }

        // 3. Kiểm tra đã đăng ký môn này (hoặc lớp này) trong kỳ chưa
        $existingEnrollment = Enrollment::where('student_id', $student->id)
            ->where('section_id', $section->id)
            ->first();

        if ($existingEnrollment) {
            return redirect()->route('student.subjects.index', $redirectParams)
                ->with('error', "Bạn đã đăng ký lớp học phần [{$section->section_code}] rồi.");
        }

        $alreadyEnrolledCourse = Enrollment::where('student_id', $student->id)
            ->whereHas('section', function ($q) use ($section) {
                $q->where('semester_code', $section->semester_code)
                  ->where('course_id', $section->course_id);
            })->exists();

        if ($alreadyEnrolledCourse) {
            return redirect()->route('student.subjects.index', $redirectParams)
                ->with('error', "Bạn đã đăng ký một lớp học phần của môn [{$section->course->course_name}] trong học kỳ này rồi.");
        }

        // 4. KIỂM TRA TRÙNG LỊCH HỌC (Schedule Collision Detection)
        $currentEnrollments = Enrollment::with('section.course')
            ->where('student_id', $student->id)
            ->whereHas('section', fn($q) => $q->where('semester_code', $semesterCode))
            ->get();

        $targetDay = (int)$section->day_of_week;
        $targetStart = (int)$section->start_period;
        $targetEnd = (int)$section->end_period;

        foreach ($currentEnrollments as $enr) {
            $registeredSec = $enr->section;
            if (!$registeredSec) continue;

            $regDay = (int)$registeredSec->day_of_week;
            $regStart = (int)$registeredSec->start_period;
            $regEnd = (int)$registeredSec->end_period;

            if ($regDay === $targetDay) {
                $overlapStart = max($targetStart, $regStart);
                $overlapEnd = min($targetEnd, $regEnd);

                if ($overlapStart <= $overlapEnd) {
                    $dayName = match ($targetDay) {
                        2 => 'Thứ 2',
                        3 => 'Thứ 3',
                        4 => 'Thứ 4',
                        5 => 'Thứ 5',
                        6 => 'Thứ 6',
                        7 => 'Thứ 7',
                        default => 'Chủ nhật',
                    };

                    return redirect()->route('student.subjects.index', $redirectParams)
                        ->with('error', "❌ KHÔNG THỂ ĐĂNG KÝ: Ca học bạn chọn ({$dayName}, Tiết {$targetStart} - {$targetEnd}) bị TRÙNG LỊCH HỌC với môn [{$registeredSec->course->course_name}] (Tiết {$regStart} - {$regEnd})! Vui lòng chọn option thời gian khác.");
                }
            }
        }

        // 5. Kiểm tra sĩ số lớp học phần
        $enrolledCount = Enrollment::where('section_id', $section->id)->count();
        if ($enrolledCount >= $section->max_capacity) {
            return redirect()->route('student.subjects.index', $redirectParams)
                ->with('error', "Lớp học phần [{$section->section_code}] đã đầy sĩ số tối đa ({$section->max_capacity} sinh viên). Vui lòng chọn option thời gian khác.");
        }

        // 6. Lưu đăng ký vào CSDL
        Enrollment::create([
            'student_id' => $student->id,
            'section_id' => $section->id,
            'registration_type' => $regType,
        ]);

        // Đồng bộ legacy Subject
        $subj = Subject::where('code', $section->course->course_code)->first();
        if ($subj) {
            $user->subjects()->syncWithoutDetaching([$subj->id]);
        }

        // 7. Tự động tính lại công nợ học phí
        $this->recalculateTuition($student, $semesterCode);

        $typeLabel = match ($regType) {
            'RETAKE' => 'Học lại',
            'IMPROVEMENT' => 'Học cải thiện',
            default => 'Học mới',
        };

        return redirect()->route('student.subjects.index', $redirectParams)
            ->with('success', "Đăng ký thành công môn [{$section->course->course_name}] theo diện [{$typeLabel}]! Học phí đã được tự động cập nhật.");
    }

    /**
     * Unregister a course section (only allowed when registration window is open).
     */
    public function unregister(Request $request, $id): RedirectResponse
    {
        $user = Auth::user();
        $student = $user->student;

        if (!$student) {
            return redirect()->route('student.subjects.index')
                ->with('error', 'Không tìm thấy hồ sơ sinh viên. Vui lòng liên hệ phòng đào tạo.');
        }

        $enrollment = Enrollment::with('section.course')->find($id);

        if (!$enrollment) {
            $subject = Subject::find($id);
            if ($subject) {
                $course = Course::where('course_code', $subject->code)->first();
                if ($student && $course) {
                    $enrollment = Enrollment::where('student_id', $student->id)
                        ->whereHas('section', fn($q) => $q->where('course_id', $course->id))
                        ->first();
                }
                $user->subjects()->detach($subject->id);
            }
        }

        $redirectParams = $request->filled('semester') ? ['semester' => $request->input('semester')] : [];

        if (!$enrollment) {
            return redirect()->route('student.subjects.index', $redirectParams)->with('success', 'Đã hủy đăng ký môn học.');
        }

        $semesterCode = $enrollment->section->semester_code;
        $courseName = $enrollment->section->course->course_name;
        $courseCode = $enrollment->section->course->course_code;

        // KIỂM TRA THỜI GIAN MỞ ĐĂNG KÝ (Không cho hủy nếu hết hạn)
        $semester = Semester::where('semester_code', $semesterCode)->first();
        if ($semester && !$semester->isRegistrationActive()) {
            return redirect()->route('student.subjects.index', $redirectParams)
                ->with('error', "❌ KHÔNG THỂ HỦY MÔN: Đợt đăng ký học phần cho [{$semester->semester_name}] đã kết thúc. Bạn chỉ có thể hủy môn khi đợt đăng ký đang mở.");
        }

        $enrollment->delete();

        // Đồng bộ legacy
        $subj = Subject::where('code', $courseCode)->first();
        if ($subj) {
            $user->subjects()->detach($subj->id);
        }

        // Tự động tính lại công nợ học phí
        if ($student) {
            $this->recalculateTuition($student, $semesterCode);
        }

        return redirect()->route('student.subjects.index', $redirectParams)
            ->with('success', "Đã hủy đăng ký môn [{$courseName}]. Công nợ học phí đã được điều chỉnh giảm.");
    }

    /**
     * Helper to recalculate tuition invoice based on registered credits.
     */
    private function recalculateTuition($student, string $semesterCode): void
    {
        if (!$student) return;

        $allEnrollments = Enrollment::with('section.course')
            ->where('student_id', $student->id)
            ->whereHas('section', fn($q) => $q->where('semester_code', $semesterCode))
            ->get();

        $totalCredits = 0;
        $totalAmount = 0;

        foreach ($allEnrollments as $enr) {
            $credits = $enr->section->course->credits ?? 3;
            $fee = $enr->section->course->tuition_fee_per_credit ?? 550000;
            $totalCredits += $credits;
            $totalAmount += $credits * $fee;
        }

        // Tạo mã thanh toán từ MSSV và mã học kỳ
        $semesterShort = strtoupper(str_replace(['_', '-'], '', $semesterCode));
        $paymentCode = 'HUIT ' . $student->student_code . ' ' . substr($semesterShort, 0, 10);

        $invoice = TuitionInvoice::firstOrCreate(
            ['student_id' => $student->id, 'semester_code' => $semesterCode],
            [
                'payment_code' => $paymentCode,
                'status' => 'UNPAID',
            ]
        );

        $invoice->update([
            'total_credits' => $totalCredits,
            'total_amount' => $totalAmount,
            'status' => $invoice->paid_amount >= $totalAmount && $totalAmount > 0 ? 'PAID' : 'UNPAID',
        ]);
    }
}
