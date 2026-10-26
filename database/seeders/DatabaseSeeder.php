<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\CourseOffering;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Major;
use App\Models\Semester;
use App\Models\StudentClass;
use App\Models\StudyPlan;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $classRows = [
            ['class_code' => 'CTK44A', 'class_name' => 'Công nghệ thông tin 44A',
                'intake_year' => 2024, 'department' => 'CNTT', 'major' => 'KTPM',
                'adviser' => 'giangvien1@gmail.com'],
            ['class_code' => 'CTK44B', 'class_name' => 'Công nghệ thông tin 44B',
                'intake_year' => 2024, 'department' => 'CNTT', 'major' => 'HTTT',
                'adviser' => 'giangvien2@gmail.com'],
        ];
        $departments = collect([
            ['code' => 'CNTT', 'name' => 'Khoa Công nghệ thông tin'],
            ['code' => 'KHTN', 'name' => 'Khoa Khoa học cơ bản'],
        ])->mapWithKeys(fn (array $data) => [
            $data['code'] => Department::updateOrCreate(['code' => $data['code']], ['name' => $data['name']]),
        ]);

        $majors = collect([
            ['code' => 'KTPM', 'name' => 'Kỹ thuật phần mềm', 'department' => 'CNTT'],
            ['code' => 'HTTT', 'name' => 'Hệ thống thông tin', 'department' => 'CNTT'],
        ])->mapWithKeys(fn (array $data) => [
            $data['code'] => Major::updateOrCreate(
                ['code' => $data['code']],
                ['name' => $data['name'], 'department_id' => $departments[$data['department']]->id],
            ),
        ]);

        $teachers = collect([
            ['name' => 'Nguyễn Văn Đạt', 'email' => 'giangvien1@gmail.com', 'teacher_code' => 'GV01',
                'dob' => '1985-04-12', 'gender' => 'MALE', 'hometown' => 'Hà Nội', 'phone' => '0912345678'],
            ['name' => 'Trần Thị Kim Oanh', 'email' => 'giangvien2@gmail.com', 'teacher_code' => 'GV02',
                'dob' => '1990-09-23', 'gender' => 'FEMALE', 'hometown' => 'Nghệ An', 'phone' => '0987654321'],
        ])->mapWithKeys(fn (array $data) => [
            $data['email'] => User::updateOrCreate(
                ['email' => $data['email']],
                $data + ['password' => Hash::make('12345678'), 'role' => 'teacher', 'class_id' => null],
            ),
        ]);

        $classes = collect($classRows)->mapWithKeys(fn (array $data) => [
            $data['class_code'] => StudentClass::updateOrCreate(
                ['class_code' => $data['class_code']],
                [
                    'class_name' => $data['class_name'],
                    'intake_year' => $data['intake_year'],
                    'department_id' => $departments[$data['department']]->id,
                    'major_id' => $majors[$data['major']]->id,
                    'adviser_id' => $teachers[$data['adviser']]->id,
                ],
            ),
        ]);

        $subjects = collect([
            ['code' => 'WEB101', 'name' => 'Lập trình Web', 'credits' => 3, 'major_id' => $majors['KTPM']->id,
                'total_sessions' => 60, 'description' => 'Xây dựng ứng dụng web với Laravel và Bootstrap.'],
            ['code' => 'DB101', 'name' => 'Cơ sở dữ liệu', 'credits' => 3, 'major_id' => $majors['KTPM']->id,
                'total_sessions' => 60, 'description' => 'Thiết kế, chuẩn hóa và truy vấn cơ sở dữ liệu quan hệ.'],
            ['code' => 'NET101', 'name' => 'Mạng máy tính', 'credits' => 2, 'major_id' => $majors['HTTT']->id,
                'total_sessions' => 45],
            ['code' => 'ADT101', 'name' => 'Cấu trúc dữ liệu và giải thuật', 'credits' => 4,
                'major_id' => $majors['KTPM']->id, 'total_sessions' => 75],
            ['code' => 'SEC201', 'name' => 'An toàn thông tin', 'credits' => 3, 'major_id' => $majors['HTTT']->id,
                'total_sessions' => 60],
            ['code' => 'GTA101', 'name' => 'Giải tích A', 'credits' => 3, 'major_id' => null,
                'total_sessions' => 45, 'description' => 'Môn chung của khoa cơ bản, dùng cho nhiều chuyên ngành.'],
        ])->mapWithKeys(fn (array $data) => [
            $data['code'] => Subject::updateOrCreate(['code' => $data['code']], $data),
        ]);

        $subjects['DB101']->prerequisites()->sync([$subjects['WEB101']->id]);
        $subjects['ADT101']->prerequisites()->sync([$subjects['DB101']->id]);
        $subjects['SEC201']->concurrents()->sync([$subjects['NET101']->id]);

        foreach ([
            'KTPM' => [
                ['WEB101', 1, 'REQUIRED'], ['DB101', 2, 'REQUIRED'],
                ['ADT101', 3, 'REQUIRED'], ['SEC201', 4, 'ELECTIVE'], ['GTA101', 1, 'REQUIRED'],
            ],
            'HTTT' => [
                ['NET101', 2, 'REQUIRED'], ['SEC201', 3, 'REQUIRED'], ['GTA101', 1, 'REQUIRED'],
            ],
        ] as $majorCode => $rows) {
            foreach ($rows as [$subjectCode, $semester, $kind]) {
                StudyPlan::updateOrCreate(
                    [
                        'major_id' => $majors[$majorCode]->id,
                        'subject_id' => $subjects[$subjectCode]->id,
                    ],
                    ['plan_semester' => $semester, 'kind' => $kind],
                );
            }
        }

        // Năm học theo quy tắc cùng kỳ với dữ liệu cũ: tháng >= 8 thì năm bắt đầu là năm hiện tại.
        $startYear = now()->month >= 8 ? (int) now()->format('Y') : (int) now()->format('Y') - 1;

        $semester = Semester::updateOrCreate(
            ['academic_year' => $startYear.'-'.($startYear + 1), 'term' => 1],
            [
                'starts_at' => $startYear.'-09-01',
                'ends_at' => ($startYear + 1).'-01-31',
                'registration_starts_at' => now()->subDays(14),
                'registration_ends_at' => now()->addDays(14),
            ],
        );

        $offerings = collect([
            ['subject' => 'WEB101', 'group' => '01', 'teacher' => 'giangvien1@gmail.com',
                'room' => 'A101', 'max_students' => 30],
            ['subject' => 'DB101', 'group' => '01', 'teacher' => 'giangvien1@gmail.com',
                'room' => 'A102', 'max_students' => 30],
            ['subject' => 'NET101', 'group' => '01', 'teacher' => 'giangvien2@gmail.com',
                'room' => 'B201', 'max_students' => 0],
            ['subject' => 'WEB101', 'group' => '02', 'teacher' => 'giangvien2@gmail.com',
                'room' => 'A103', 'max_students' => 25],
            ['subject' => 'SEC201', 'group' => '01', 'teacher' => 'giangvien2@gmail.com',
                'room' => 'B202', 'max_students' => 20, 'is_open' => false],
        ])->mapWithKeys(fn (array $data) => [
            $data['subject'].'-'.$data['group'] => CourseOffering::updateOrCreate(
                [
                    'subject_id' => $subjects[$data['subject']]->id,
                    'semester_id' => $semester->id,
                    'group_code' => $data['group'],
                ],
                [
                    'teacher_id' => $teachers[$data['teacher']]->id,
                    'room' => $data['room'],
                    'max_students' => $data['max_students'],
                    'is_open' => $data['is_open'] ?? true,
                ],
            ),
        ]);

        $enrolledOfferings = $offerings->only(['WEB101-01', 'DB101-01', 'NET101-01']);

        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Quản trị viên',
                'password' => Hash::make('12345678'),
                'role' => 'admin',
                'class_id' => null,
            ],
        );

        $studentNames = [
            ['name' => 'Nguyễn Minh Anh', 'email' => 'sinhvien1@gmail.com', 'student_code' => '20240001',
                'dob' => '2006-01-15', 'gender' => 'MALE', 'hometown' => 'Hà Nội', 'phone' => '0901234567'],
            ['name' => 'Trần Hoàng Nam', 'email' => 'sinhvien2@gmail.com', 'student_code' => '20240002',
                'dob' => '2006-02-08', 'gender' => 'MALE', 'hometown' => 'Đà Nẵng', 'phone' => '0912345679'],
            ['name' => 'Lê Thu Hà', 'email' => 'sinhvien3@gmail.com', 'student_code' => '20240003',
                'dob' => '2006-03-21', 'gender' => 'FEMALE', 'hometown' => 'Hải Phòng', 'phone' => '0923456780'],
            ['name' => 'Phạm Quốc Bảo', 'email' => 'sinhvien4@gmail.com', 'student_code' => '20240004',
                'dob' => '2006-07-02', 'gender' => 'MALE', 'hometown' => 'Cần Thơ', 'phone' => '0934567891'],
            ['name' => 'Võ Ngọc Linh', 'email' => 'sinhvien5@gmail.com', 'student_code' => '20240005',
                'dob' => '2006-11-30', 'gender' => 'FEMALE', 'hometown' => 'Huế', 'phone' => '0945678902',
                'academic_status' => 'AT_RISK'],
            // Chưa xếp lớp: để bộ lọc "Chưa xếp lớp" và hồ sơ trống lớp hiển thị đúng.
            ['name' => 'Đặng Tiến Dũng', 'email' => 'sinhvien6@gmail.com', 'student_code' => '20240006',
                'dob' => '2006-05-19', 'gender' => 'MALE', 'hometown' => 'Nha Trang', 'phone' => '0956789013'],
        ];
        $attendanceDate = today();

        foreach ($studentNames as $index => $data) {
            $unassigned = $index === count($studentNames) - 1;
            $student = User::updateOrCreate(
                ['email' => $data['email']],
                $data + [
                    'password' => Hash::make('12345678'),
                    'role' => 'student',
                    'class_id' => $unassigned ? null : $classes->values()[$index % 2]->id,
                    'academic_status' => $data['academic_status'] ?? 'STUDYING',
                ],
            );
            if ($unassigned) {
                continue;
            }
            $studentOfferings = $enrolledOfferings->values();

            foreach ($studentOfferings as $offeringIndex => $offering) {
                Enrollment::updateOrCreate(
                    ['course_offering_id' => $offering->id, 'user_id' => $student->id],
                    ['status' => 'ACTIVE', 'dropped_at' => null],
                );

                Grade::updateOrCreate(
                    ['user_id' => $student->id, 'course_offering_id' => $offering->id],
                    ['final_score' => 7 + (($index + $offeringIndex) % 4) * 0.5],
                );

                Attendance::updateOrCreate(
                    [
                        'user_id' => $student->id,
                        'subject_id' => $offering->subject_id,
                        'date' => $attendanceDate,
                    ],
                    ['status' => ($index + $offeringIndex) % 4 === 0 ? 'absent' : 'present'],
                );
            }
        }
    }
}
