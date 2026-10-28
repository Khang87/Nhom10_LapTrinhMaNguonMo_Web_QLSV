<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseSection;
use App\Models\CurriculumFramework;
use App\Models\Enrollment;
use App\Models\Lecturer;
use App\Models\Semester;
use App\Models\Specialization;
use App\Models\Student;
use App\Models\StudentClass;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $specId = $request->query('specialization_id');

        $specializations = Specialization::orderBy('name')->get();

        $students = Student::query()
            ->with(['user', 'specialization'])
            ->when($specId, fn ($q) => $q->where('specialization_id', $specId))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('student_code', 'like', "%{$search}%")
                        ->orWhere('full_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        return view('admin.students.index', compact('students', 'search', 'specializations', 'specId'));
    }

    public function create(): View
    {
        // Tự động sinh mã sinh viên kế tiếp
        $latestStudent = Student::orderByDesc('id')->first();
        $nextNum = ($latestStudent ? $latestStudent->id + 1 : 1);
        $cohortYear = date('Y');
        $autoStudentCode = $cohortYear . str_pad($nextNum, 6, '0', STR_PAD_LEFT);

        return view('admin.students.create', [
            'student' => new Student([
                'student_code' => $autoStudentCode,
                'cohort_year' => $cohortYear,
                'study_status' => 'Đang học',
                'training_type' => 'Chính quy',
                'education_level' => 'Đại học',
                'faculty_name' => 'Khoa Công nghệ Thông tin',
            ]),
            'specializations' => Specialization::orderBy('name')->get(),
            'classes' => StudentClass::orderByDesc('cohort_year')->orderBy('class_code')->get(),
            'autoStudentCode' => $autoStudentCode,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_code' => ['nullable', 'string', 'max:30', 'unique:students,student_code', 'unique:users,username'],
            'full_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:6'],
            'specialization_id' => ['required', 'exists:specializations,id'],
            'class_code' => ['nullable', 'string', 'max:50'],
            'class_id' => ['nullable'],
            'gender' => ['required', 'in:Nam,Nữ,Khác'],
            'dob' => ['nullable', 'date'],
            'phone' => ['nullable', 'string', 'max:20'],
            'cccd_number' => ['nullable', 'string', 'max:20', 'unique:students,cccd_number'],
            'contact_address' => ['nullable', 'string', 'max:255'],
        ]);

        $specialization = Specialization::findOrFail($validated['specialization_id']);

        return DB::transaction(function () use ($validated, $specialization, $request) {
            $cohortYear = date('Y');
            $classCode = null;

            if ($request->filled('class_id') && ($matchedClass = StudentClass::find($request->input('class_id')))) {
                $classCode = $matchedClass->class_code;
                $cohortYear = $matchedClass->cohort_year ?: $cohortYear;
            } elseif (!empty($validated['class_code'])) {
                if (is_numeric($validated['class_code']) && ($matchedClass = StudentClass::find($validated['class_code']))) {
                    $classCode = $matchedClass->class_code;
                    $cohortYear = $matchedClass->cohort_year ?: $cohortYear;
                } else {
                    $classCode = $validated['class_code'];
                    $matchedClass = StudentClass::where('class_code', $classCode)->first();
                    if ($matchedClass && $matchedClass->cohort_year) {
                        $cohortYear = $matchedClass->cohort_year;
                    }
                }
            } else {
                $classCode = '14DHTH04';
            }

            // Nếu người dùng nhập MSSV tùy chỉnh thì dùng, không thì tự động sinh
            if (!empty($validated['student_code'])) {
                $studentCode = trim($validated['student_code']);
            } else {
                $latestStudent = Student::lockForUpdate()->orderByDesc('id')->first();
                $nextNum = ($latestStudent ? $latestStudent->id + 1 : 1);
                $studentCode = $cohortYear . str_pad($nextNum, 6, '0', STR_PAD_LEFT);
            }

            // 1. Tạo tài khoản User (Tài khoản đăng nhập = MSSV, Email = Email liên hệ của SV)
            $password = !empty($validated['password']) ? $validated['password'] : '12345678';
            $user = User::create([
                'name' => $validated['full_name'],
                'username' => $studentCode, // Tài khoản đăng nhập là MSSV
                'email' => $validated['email'], // Email cá nhân của sinh viên để liên hệ
                'password' => Hash::make($password),
                'role' => 'student',
                'is_active' => true,
            ]);

            // 2. Tạo hồ sơ Student
            $student = Student::create([
                'user_id' => $user->id,
                'student_code' => $studentCode,
                'full_name' => $validated['full_name'],
                'gender' => $validated['gender'],
                'specialization_id' => $specialization->id,
                'specialization_name' => $specialization->name,
                'faculty_name' => $specialization->faculty_name,
                'major_name' => 'Công nghệ thông tin',
                'class_code' => $classCode,
                'cohort_year' => (int) $cohortYear,
                'enrollment_date' => now()->toDateString(),
                'dob' => $validated['dob'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'],
                'cccd_number' => $validated['cccd_number'] ?? null,
                'contact_address' => $validated['contact_address'] ?? null,
                'study_status' => 'Đang học',
            ]);

            // 3. TỰ ĐỘNG XẾP HỌC PHẦN KỲ 1 (Do admin xếp mặc định cho sinh viên mới)
            $this->autoEnrollSemester1($student, $specialization);

            return redirect()->route('admin.students.show', $student)
                ->with('success', "Đã thêm sinh viên [{$student->full_name}] thành công. Tài khoản đăng nhập (MSSV): {$student->student_code}, Email liên hệ: {$student->email}. Đã tự động xếp học phần Kỳ 1!");
        });
    }

    /**
     * Xem toàn bộ thông tin chi tiết của sinh viên
     */
    public function show(Student $student): View
    {
        $student->load([
            'user',
            'specialization',
            'enrollments.section.course',
            'enrollments.section.lecturer',
        ]);

        return view('admin.students.show', compact('student'));
    }

    public function edit(Student $student): View
    {
        $student->load(['user', 'specialization']);
        $specializations = Specialization::orderBy('name')->get();
        $classes = StudentClass::orderByDesc('cohort_year')->orderBy('class_code')->get();

        return view('admin.students.edit', compact('student', 'specializations', 'classes'));
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'student_code' => ['nullable', 'string', 'max:30', Rule::unique('students', 'student_code')->ignore($student->id), Rule::unique('users', 'username')->ignore($student->user_id)],
            'full_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($student->user_id)],
            'specialization_id' => ['required', 'exists:specializations,id'],
            'class_code' => ['nullable', 'string', 'max:50'],
            'class_id' => ['nullable'],
            'gender' => ['required', 'in:Nam,Nữ,Khác'],
            'dob' => ['nullable', 'date'],
            'phone' => ['nullable', 'string', 'max:20'],
            'cccd_number' => ['nullable', 'string', 'max:20', Rule::unique('students', 'cccd_number')->ignore($student->id)],
            'contact_address' => ['nullable', 'string', 'max:255'],
            'study_status' => ['required', 'string', 'max:50'],
        ]);

        $specialization = Specialization::findOrFail($validated['specialization_id']);

        return DB::transaction(function () use ($validated, $student, $specialization, $request) {
            $newCode = !empty($validated['student_code']) ? trim($validated['student_code']) : $student->student_code;

            $classCode = $student->class_code;
            $cohortYear = $student->cohort_year;

            if ($request->filled('class_id') && ($matchedClass = StudentClass::find($request->input('class_id')))) {
                $classCode = $matchedClass->class_code;
                $cohortYear = $matchedClass->cohort_year ?: $cohortYear;
            } elseif ($request->filled('class_code')) {
                $raw = $request->input('class_code');
                if (is_numeric($raw) && ($matchedClass = StudentClass::find($raw))) {
                    $classCode = $matchedClass->class_code;
                    $cohortYear = $matchedClass->cohort_year ?: $cohortYear;
                } else {
                    $classCode = $raw;
                    $matchedClass = StudentClass::where('class_code', $classCode)->first();
                    if ($matchedClass && $matchedClass->cohort_year) {
                        $cohortYear = $matchedClass->cohort_year;
                    }
                }
            }

            $student->user->update([
                'name' => $validated['full_name'],
                'username' => $newCode,
                'email' => $validated['email'],
            ]);

            $student->update([
                'student_code' => $newCode,
                'full_name' => $validated['full_name'],
                'email' => $validated['email'],
                'specialization_id' => $specialization->id,
                'specialization_name' => $specialization->name,
                'faculty_name' => $specialization->faculty_name,
                'class_code' => $classCode,
                'cohort_year' => $cohortYear,
                'gender' => $validated['gender'],
                'dob' => $validated['dob'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'cccd_number' => $validated['cccd_number'] ?? null,
                'contact_address' => $validated['contact_address'] ?? null,
                'study_status' => $validated['study_status'],
            ]);
        });

        return redirect()->route('admin.students.show', $student)
            ->with('success', 'Đã cập nhật thông tin sinh viên thành công.');
    }

    /**
     * Reset mật khẩu cho sinh viên
     */
    public function resetPassword(Request $request, Student $student): RedirectResponse
    {
        $newPassword = $request->input('new_password');
        if (empty($newPassword)) {
            $newPassword = '12345678';
        }

        $student->user->update([
            'password' => Hash::make($newPassword),
        ]);

        return back()->with('success', "Mật khẩu của sinh viên {$student->full_name} ({$student->student_code}) đã được đặt lại thành: [{$newPassword}].");
    }

    public function destroy(Student $student): RedirectResponse
    {
        $name = $student->full_name;
        $user = $student->user;

        DB::transaction(function () use ($student, $user) {
            $student->delete();
            if ($user) {
                $user->delete();
            }
        });

        return redirect()->route('admin.students.index')
            ->with('success', "Đã xóa sinh viên [{$name}] thành công.");
    }

    /**
     * Tự động xếp học phần Kỳ 1 theo chương trình khung của chuyên ngành
     */
    private function autoEnrollSemester1(Student $student, Specialization $specialization): void
    {
        // 1. Lấy danh sách môn học Kỳ 1 trong chương trình khung
        $sem1Framework = CurriculumFramework::with('course')
            ->where('specialization_id', $specialization->id)
            ->where('semester_order', 1)
            ->get();

        if ($sem1Framework->isEmpty()) {
            return;
        }

        // 2. Lấy học kỳ hiện tại đang mở hoặc học kỳ mới nhất
        $currentSemester = Semester::where('is_registration_open', true)->orderBy('order_index')->first()
            ?? Semester::orderByDesc('order_index')->first();
        $semCode = $currentSemester ? $currentSemester->semester_code : 'HK1_2026_2027';

        // Bố trí khung giờ cố định phân tán các thứ trong tuần để không bao giờ bị trùng lịch
        $timeSlots = [
            ['day' => 2, 'start' => 1, 'end' => 3, 'room' => 'B201 - Cơ sở 140 Lê Trọng Tấn'],
            ['day' => 3, 'start' => 1, 'end' => 3, 'room' => 'B202 - Cơ sở 140 Lê Trọng Tấn'],
            ['day' => 4, 'start' => 1, 'end' => 3, 'room' => 'B203 - Cơ sở 140 Lê Trọng Tấn'],
            ['day' => 5, 'start' => 1, 'end' => 3, 'room' => 'C101 - Cơ sở 140 Lê Trọng Tấn'],
            ['day' => 6, 'start' => 1, 'end' => 3, 'room' => 'C102 - Cơ sở 140 Lê Trọng Tấn'],
            ['day' => 2, 'start' => 4, 'end' => 6, 'room' => 'A301 - Cơ sở 140 Lê Trọng Tấn'],
            ['day' => 3, 'start' => 4, 'end' => 6, 'room' => 'A302 - Cơ sở 140 Lê Trọng Tấn'],
            ['day' => 4, 'start' => 4, 'end' => 6, 'room' => 'A303 - Cơ sở 140 Lê Trọng Tấn'],
        ];

        // 3. Với từng môn, tìm lớp học phần mở sẵn hoặc tự động mở 1 lớp
        foreach ($sem1Framework as $index => $item) {
            $course = $item->course;
            if (!$course) continue;

            $section = CourseSection::where('course_id', $course->id)
                ->where('semester_code', $semCode)
                ->where(function ($q) use ($specialization) {
                    $q->where('specialization_name', $specialization->name)
                      ->orWhereNull('specialization_name');
                })
                ->first();

            if (!$section) {
                // Random 1 giảng viên thuộc chuyên ngành này
                $lecturer = Lecturer::where('specialization_id', $specialization->id)->inRandomOrder()->first()
                    ?? Lecturer::inRandomOrder()->first();

                $slot = $timeSlots[$index % count($timeSlots)];

                $section = CourseSection::create([
                    'section_code' => '0101' . rand(10000000, 99999999),
                    'course_id' => $course->id,
                    'lecturer_id' => $lecturer?->id,
                    'semester_code' => $semCode,
                    'specialization_name' => $specialization->name,
                    'max_capacity' => 60,
                    'day_of_week' => $slot['day'],
                    'start_period' => $slot['start'],
                    'end_period' => $slot['end'],
                    'room_name' => $slot['room'],
                ]);
            }

            // Ghi danh sinh viên vào lớp
            Enrollment::firstOrCreate([
                'student_id' => $student->id,
                'section_id' => $section->id,
            ], [
                'registration_type' => 'FIRST_TIME',
            ]);
        }
    }
}
