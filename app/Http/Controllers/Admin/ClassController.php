<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cohort;
use App\Models\Faculty;
use App\Models\Lecturer;
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

class ClassController extends Controller
{
    /**
     * Quản lý Lớp học Danh nghĩa theo phân cấp:
     * Khoa -> Chuyên ngành -> Khóa tuyển sinh -> Lớp danh nghĩa -> Sinh viên
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $selectedFacultyId = $request->query('faculty_id');
        $selectedSpecId = $request->query('specialization_id');
        $selectedCohort = $request->query('cohort');
        $selectedClassId = $request->query('class_id');

        // Tự động phân giải các cấp cha nếu được cung cấp cấp con
        $selectedClass = null;
        if ($selectedClassId) {
            $selectedClass = StudentClass::with(['lecturer', 'specialization.faculty'])->find($selectedClassId);
            if ($selectedClass) {
                if (!$selectedCohort) {
                    $selectedCohort = $selectedClass->cohort_year;
                }
                if (!$selectedSpecId) {
                    $selectedSpecId = $selectedClass->specialization_id;
                }
                if (!$selectedFacultyId) {
                    $selectedFacultyId = $selectedClass->specialization?->faculty_id;
                }
            }
        }

        if ($selectedSpecId && !$selectedFacultyId) {
            $specObj = Specialization::find($selectedSpecId);
            if ($specObj) {
                $selectedFacultyId = $specObj->faculty_id;
            }
        }

        $selectedFaculty = $selectedFacultyId ? Faculty::find($selectedFacultyId) : null;
        $selectedSpecialization = $selectedSpecId ? Specialization::with('faculty')->find($selectedSpecId) : null;

        // 1. DỮ LIỆU LEVEL 1: DANH SÁCH KHOA (FACULTIES)
        $allFaculties = Faculty::orderBy('name')->get();
        $facultiesSummary = [];
        foreach ($allFaculties as $f) {
            $specCount = Specialization::where('faculty_id', $f->id)->count();
            $classesCount = StudentClass::whereHas('specialization', fn ($q) => $q->where('faculty_id', $f->id))->count();
            $studentsCount = Student::whereHas('specialization', fn ($q) => $q->where('faculty_id', $f->id))->count();

            $facultiesSummary[] = [
                'id' => $f->id,
                'code' => $f->code,
                'name' => $f->name,
                'specializations_count' => $specCount,
                'classes_count' => $classesCount,
                'students_count' => $studentsCount,
            ];
        }

        // 2. DỮ LIỆU LEVEL 2: DANH SÁCH CHUYÊN NGÀNH THUỘC KHOA ĐÃ CHỌN
        $facultySpecs = collect();
        if ($selectedFacultyId) {
            $specs = Specialization::where('faculty_id', $selectedFacultyId)->orderBy('name')->get();
            foreach ($specs as $s) {
                $clsCount = StudentClass::where('specialization_id', $s->id)->count();
                $stuCount = Student::where('specialization_id', $s->id)->count();
                $facultySpecs->push((object) [
                    'id' => $s->id,
                    'code' => $s->code,
                    'name' => $s->name,
                    'classes_count' => $clsCount,
                    'students_count' => $stuCount,
                ]);
            }
        }

        // 3. DỮ LIỆU LEVEL 3: DANH SÁCH KHÓA TUYỂN SINH CỦA CHUYÊN NGÀNH ĐÃ CHỌN
        $allCohorts = array_values(array_unique(array_merge(
            Cohort::pluck('cohort_year')->toArray(),
            StudentClass::whereNotNull('cohort_year')->pluck('cohort_year')->toArray(),
            Student::whereNotNull('cohort_year')->pluck('cohort_year')->toArray(),
            ['2026', '2023']
        )));
        rsort($allCohorts);

        $specCohortsSummary = [];
        if ($selectedSpecId) {
            foreach ($allCohorts as $ch) {
                $year = (int) $ch;
                $kNum = $year >= 2010 ? ($year - 2009) : null;
                $clsCount = StudentClass::where('specialization_id', $selectedSpecId)->where('cohort_year', $ch)->count();
                $stuCount = Student::where('specialization_id', $selectedSpecId)->where('cohort_year', $ch)->count();

                $specCohortsSummary[] = [
                    'cohort_year' => $ch,
                    'k_label' => $kNum ? "Niên khóa K{$kNum}" : "Khóa {$ch}",
                    'classes_count' => $clsCount,
                    'students_count' => $stuCount,
                ];
            }
        }

        // 4. DỮ LIỆU LEVEL 4: DANH SÁCH LỚP DANH NGHĨA
        $classesQuery = StudentClass::with(['lecturer', 'specialization.faculty'])
            ->when($selectedCohort, fn ($q) => $q->where('cohort_year', $selectedCohort))
            ->when($selectedSpecId, fn ($q) => $q->where('specialization_id', $selectedSpecId))
            ->when($search !== '' && !$selectedClass, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('class_code', 'like', "%{$search}%")
                        ->orWhere('class_name', 'like', "%{$search}%")
                        ->orWhereHas('lecturer', fn ($lq) => $lq->where('full_name', 'like', "%{$search}%"));
                });
            })
            ->orderBy('class_code');

        $classes = $classesQuery->get();

        // 5. DỮ LIỆU LEVEL 5: SINH VIÊN TRONG LỚP
        $classStudents = null;
        $unassignedStudents = collect();
        if ($selectedClass) {
            $query = Student::with(['user', 'specialization'])
                ->where('class_code', $selectedClass->class_code)
                ->when($selectedClass->cohort_year, fn ($q) => $q->where('cohort_year', $selectedClass->cohort_year));

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('student_code', 'like', "%{$search}%")
                        ->orWhere('full_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            $classStudents = $query->orderBy('student_code')->paginate(20)->withQueryString();

            $unassignedStudents = Student::where(function ($q) use ($selectedClass) {
                $q->whereNull('class_code')
                    ->orWhere('class_code', '')
                    ->orWhere('class_code', '!=', $selectedClass->class_code);
            })
            ->when($selectedClass->cohort_year, fn ($q) => $q->where('cohort_year', $selectedClass->cohort_year))
            ->orderBy('full_name')
            ->take(50)
            ->get();
        }

        $lecturers = Lecturer::orderBy('full_name')->get();
        $specializations = Specialization::orderBy('name')->get();

        return view('admin.classes.index', compact(
            'selectedFacultyId',
            'selectedFaculty',
            'selectedSpecId',
            'selectedSpecialization',
            'selectedCohort',
            'selectedClass',
            'facultiesSummary',
            'facultySpecs',
            'specCohortsSummary',
            'allCohorts',
            'allFaculties',
            'classes',
            'classStudents',
            'unassignedStudents',
            'lecturers',
            'specializations',
            'search'
        ));
    }

    public function create(): View
    {
        return view('admin.classes.create', [
            'class' => new StudentClass,
            'lecturers' => Lecturer::orderBy('full_name')->get(),
            'specializations' => Specialization::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'class_code' => ['required', 'string', 'max:30'],
            'class_name' => ['required', 'string', 'max:100'],
            'cohort_year' => ['required', 'string', 'max:10'],
            'specialization_id' => ['required', 'exists:specializations,id'],
            'lecturer_id' => ['nullable', 'exists:lecturers,id'],
            'room' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
        ], [
            'class_code.required' => 'Vui lòng nhập mã lớp danh nghĩa.',
            'class_name.required' => 'Vui lòng nhập tên lớp danh nghĩa.',
            'cohort_year.required' => 'Vui lòng chọn khóa tuyển sinh.',
            'specialization_id.required' => 'Vui lòng chọn chuyên ngành đào tạo để gán cho lớp danh nghĩa.',
            'specialization_id.exists' => 'Chuyên ngành đào tạo đã chọn không hợp lệ.',
            'lecturer_id.exists' => 'Giảng viên phụ trách không hợp lệ.',
        ]);

        $exists = StudentClass::where('class_code', $validated['class_code'])
            ->where('cohort_year', $validated['cohort_year'])
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error', "Lớp danh nghĩa [{$validated['class_code']}] đã tồn tại trong Khóa {$validated['cohort_year']}.");
        }

        $class = StudentClass::create($validated);

        return redirect()->route('admin.classes.index', array_filter([
            'faculty_id' => $class->specialization?->faculty_id,
            'specialization_id' => $class->specialization_id,
            'cohort' => $class->cohort_year,
        ]))->with('success', "Đã thêm thành công lớp danh nghĩa [{$class->class_code} - {$class->class_name}] thuộc chuyên ngành " . ($class->specialization?->name ?? '') . " cho Khóa {$class->cohort_year}.");
    }

    public function edit(StudentClass $class): View
    {
        $lecturers = Lecturer::orderBy('full_name')->get();
        $specializations = Specialization::orderBy('name')->get();

        return view('admin.classes.edit', compact('class', 'lecturers', 'specializations'));
    }

    public function update(Request $request, StudentClass $class): RedirectResponse
    {
        $validated = $request->validate([
            'class_code' => ['required', 'string', 'max:30'],
            'class_name' => ['required', 'string', 'max:100'],
            'cohort_year' => ['required', 'string', 'max:10'],
            'specialization_id' => ['required', 'exists:specializations,id'],
            'lecturer_id' => ['nullable', 'exists:lecturers,id'],
            'room' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
        ], [
            'class_code.required' => 'Vui lòng nhập mã lớp danh nghĩa.',
            'class_name.required' => 'Vui lòng nhập tên lớp danh nghĩa.',
            'cohort_year.required' => 'Vui lòng chọn khóa tuyển sinh.',
            'specialization_id.required' => 'Vui lòng chọn chuyên ngành đào tạo.',
            'specialization_id.exists' => 'Chuyên ngành đào tạo không hợp lệ.',
        ]);

        $oldCode = $class->class_code;
        $oldCohort = $class->cohort_year;

        $class->update($validated);

        // Đồng bộ mã lớp cho sinh viên nếu mã lớp hoặc khóa thay đổi
        if ($oldCode !== $class->class_code || $oldCohort !== $class->cohort_year) {
            Student::where('class_code', $oldCode)
                ->when($oldCohort, fn ($q) => $q->where('cohort_year', $oldCohort))
                ->update([
                    'class_code' => $class->class_code,
                    'cohort_year' => $class->cohort_year,
                ]);
        }

        return redirect()->route('admin.classes.index', array_filter([
            'faculty_id' => $class->specialization?->faculty_id,
            'specialization_id' => $class->specialization_id,
            'cohort' => $class->cohort_year,
            'class_id' => $class->id,
        ]))->with('success', "Đã cập nhật thông tin lớp danh nghĩa [{$class->class_code}].");
    }

    public function destroy(StudentClass $class): RedirectResponse
    {
        $cohort = $class->cohort_year;
        $specId = $class->specialization_id;
        $facultyId = $class->specialization?->faculty_id;
        $code = $class->class_code;

        DB::transaction(function () use ($class) {
            // Rút toàn bộ sinh viên khỏi lớp danh nghĩa bị xóa
            Student::where('class_code', $class->class_code)
                ->when($class->cohort_year, fn ($q) => $q->where('cohort_year', $class->cohort_year))
                ->update(['class_code' => null]);

            $class->delete();
        });

        return redirect()->route('admin.classes.index', array_filter([
            'faculty_id' => $facultyId,
            'specialization_id' => $specId,
            'cohort' => $cohort,
        ]))->with('success', "Đã xóa lớp danh nghĩa [{$code}]. Các sinh viên trong lớp đã được chuyển sang trạng thái chưa có lớp.");
    }

    /**
     * Thêm Khóa tuyển sinh mới (Chỉ tạo khóa, không tự động thêm lớp danh nghĩa)
     */
    public function storeCohort(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cohort_year' => ['required', 'integer', 'min:2000', 'max:2099'],
        ], [
            'cohort_year.required' => 'Vui lòng nhập năm khóa tuyển sinh (ví dụ: 2027).',
            'cohort_year.integer' => 'Khóa tuyển sinh phải là năm 4 chữ số hợp lệ.',
        ]);

        $cohortYear = (string) $validated['cohort_year'];
        $yearInt = (int) $cohortYear;
        $kNum = $yearInt >= 2010 ? ($yearInt - 2009) : null;
        $name = $kNum ? "Khóa {$cohortYear} (Niên khóa K{$kNum})" : "Khóa {$cohortYear}";

        Cohort::firstOrCreate(
            ['cohort_year' => $cohortYear],
            ['name' => $name]
        );

        $redirectParams = ['cohort' => $cohortYear];
        if ($request->filled('faculty_id')) {
            $redirectParams['faculty_id'] = $request->input('faculty_id');
        }
        if ($request->filled('specialization_id')) {
            $redirectParams['specialization_id'] = $request->input('specialization_id');
        }

        return redirect()->route('admin.classes.index', $redirectParams)
            ->with('success', "Đã tạo thành công Khóa tuyển sinh {$cohortYear}. Bạn có thể bấm vào 'Thêm lớp danh nghĩa' bên dưới để gán cho từng chuyên ngành.");
    }

    /**
     * Phân công nhanh giảng viên phụ trách / cố vấn học tập
     */
    public function assignLecturer(Request $request, StudentClass $class): RedirectResponse
    {
        $validated = $request->validate([
            'lecturer_id' => ['nullable', 'exists:lecturers,id'],
        ]);

        $class->update(['lecturer_id' => $validated['lecturer_id']]);
        $lec = $class->lecturer;

        return back()->with('success', "Đã cập nhật Giảng viên phụ trách cho lớp [{$class->class_code}]: " . ($lec ? "{$lec->full_name} ({$lec->degree})" : "Chưa phân công"));
    }

    /**
     * Thêm sinh viên vào lớp danh nghĩa (Gán sinh viên có sẵn hoặc Tạo mới sinh viên vào lớp)
     */
    public function addStudent(Request $request, StudentClass $class): RedirectResponse
    {
        $actionType = $request->input('action_type', 'assign');

        if ($actionType === 'assign') {
            $validated = $request->validate([
                'student_id' => ['required', 'exists:students,id'],
            ], [
                'student_id.required' => 'Vui lòng chọn sinh viên cần thêm vào lớp.',
            ]);

            $student = Student::findOrFail($validated['student_id']);
            $student->update([
                'class_code' => $class->class_code,
                'cohort_year' => $class->cohort_year ?? $student->cohort_year,
            ]);

            return back()->with('success', "Đã xếp sinh viên [{$student->student_code} - {$student->full_name}] vào lớp danh nghĩa {$class->class_code}.");
        }

        // Tạo mới sinh viên trực tiếp vào lớp
        $validated = $request->validate([
            'student_code' => ['nullable', 'string', 'max:30', 'unique:students,student_code', 'unique:users,username'],
            'full_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'gender' => ['required', 'in:Nam,Nữ,Khác'],
            'dob' => ['nullable', 'date'],
            'phone' => ['nullable', 'string', 'max:20'],
            'specialization_id' => ['nullable', 'exists:specializations,id'],
        ]);

        $cohortYear = $class->cohort_year ?? date('Y');
        $studentCode = $validated['student_code'];
        if (!$studentCode) {
            $latest = Student::orderByDesc('id')->first();
            $nextNum = $latest ? $latest->id + 1 : 1;
            $studentCode = $cohortYear . str_pad($nextNum, 6, '0', STR_PAD_LEFT);
        }

        $spec = $validated['specialization_id'] ? Specialization::find($validated['specialization_id']) : $class->specialization;
        if (!$spec) {
            $spec = Specialization::first();
        }

        DB::transaction(function () use ($validated, $class, $cohortYear, $studentCode, $spec) {
            $user = User::create([
                'name' => $validated['full_name'],
                'username' => $studentCode,
                'email' => $validated['email'],
                'password' => Hash::make('12345678'),
                'role' => 'student',
                'class_id' => $class->id,
            ]);

            Student::create([
                'user_id' => $user->id,
                'student_code' => $studentCode,
                'full_name' => $validated['full_name'],
                'gender' => $validated['gender'],
                'dob' => $validated['dob'],
                'phone' => $validated['phone'],
                'email' => $validated['email'],
                'class_code' => $class->class_code,
                'cohort_year' => $cohortYear,
                'specialization_id' => $spec?->id,
                'specialization_name' => $spec?->name,
                'faculty_name' => $spec?->faculty?->name ?? 'Khoa Công nghệ Thông tin',
                'study_status' => 'Đang học',
                'training_type' => 'Chính quy',
                'education_level' => 'Đại học',
                'campus' => 'ĐHCT TP.HCM',
            ]);
        });

        return back()->with('success', "Đã thêm mới sinh viên [{$studentCode} - {$validated['full_name']}] vào lớp {$class->class_code}.");
    }

    /**
     * Rút sinh viên ra khỏi lớp danh nghĩa
     */
    public function removeStudent(Request $request, StudentClass $class, Student $student): RedirectResponse
    {
        $student->update(['class_code' => null]);

        return back()->with('success', "Đã rút sinh viên [{$student->student_code} - {$student->full_name}] ra khỏi lớp {$class->class_code}.");
    }

    /**
     * Cập nhật thông tin sinh viên từ modal trong lớp (bao gồm chuyển lớp)
     */
    public function updateStudent(Request $request, StudentClass $class, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($student->user_id)],
            'gender' => ['required', 'in:Nam,Nữ,Khác'],
            'dob' => ['nullable', 'date'],
            'phone' => ['nullable', 'string', 'max:20'],
            'study_status' => ['required', 'string', 'max:50'],
            'specialization_id' => ['nullable', 'exists:specializations,id'],
            'target_class_code' => ['nullable', 'string', 'max:30'],
        ]);

        $spec = $validated['specialization_id'] ? Specialization::find($validated['specialization_id']) : null;

        DB::transaction(function () use ($student, $validated, $spec) {
            if ($student->user) {
                $student->user->update([
                    'name' => $validated['full_name'],
                    'email' => $validated['email'],
                ]);
            }

            $updateData = [
                'full_name' => $validated['full_name'],
                'email' => $validated['email'],
                'gender' => $validated['gender'],
                'dob' => $validated['dob'],
                'phone' => $validated['phone'],
                'study_status' => $validated['study_status'],
            ];

            if ($spec) {
                $updateData['specialization_id'] = $spec->id;
                $updateData['specialization_name'] = $spec->name;
            }

            if (!empty($validated['target_class_code'])) {
                $updateData['class_code'] = $validated['target_class_code'];
            }

            $student->update($updateData);
        });

        return back()->with('success', "Đã cập nhật thông tin sinh viên [{$student->student_code} - {$student->full_name}].");
    }
}
