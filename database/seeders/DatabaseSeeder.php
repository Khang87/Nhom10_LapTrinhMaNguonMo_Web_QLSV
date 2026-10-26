<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\Grade;
use App\Models\Major;
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
            ['class_code' => 'CTK44A', 'class_name' => 'Công nghệ thông tin 44A', 'room' => 'A101',
                'intake_year' => 2024, 'department' => 'CNTT', 'major' => 'KTPM'],
            ['class_code' => 'CTK44B', 'class_name' => 'Công nghệ thông tin 44B', 'room' => 'A102',
                'intake_year' => 2024, 'department' => 'CNTT', 'major' => 'HTTT'],
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

        $classes = collect($classRows)->mapWithKeys(fn (array $data) => [
            $data['class_code'] => StudentClass::updateOrCreate(
                ['class_code' => $data['class_code']],
                [
                    'class_name' => $data['class_name'],
                    'room' => $data['room'],
                    'intake_year' => $data['intake_year'],
                    'department_id' => $departments[$data['department']]->id,
                    'major_id' => $majors[$data['major']]->id,
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

        $enrolledSubjects = $subjects->only(['WEB101', 'DB101', 'NET101']);

        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Quản trị viên',
                'password' => Hash::make('12345678'),
                'role' => 'admin',
                'class_id' => null,
            ],
        );

        foreach ([
            ['name' => 'Nguyễn Văn Đạt', 'email' => 'giangvien1@gmail.com', 'teacher_code' => 'GV01',
                'dob' => '1985-04-12', 'gender' => 'MALE', 'hometown' => 'Hà Nội', 'phone' => '0912345678'],
            ['name' => 'Trần Thị Kim Oanh', 'email' => 'giangvien2@gmail.com', 'teacher_code' => 'GV02',
                'dob' => '1990-09-23', 'gender' => 'FEMALE', 'hometown' => 'Nghệ An', 'phone' => '0987654321'],
        ] as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                $data + [
                    'password' => Hash::make('12345678'),
                    'role' => 'teacher',
                    'class_id' => null,
                ],
            );
        }

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
            $student->subjects()->syncWithoutDetaching($enrolledSubjects->pluck('id'));

            foreach ($enrolledSubjects->values() as $subjectIndex => $subject) {
                Grade::updateOrCreate(
                    ['user_id' => $student->id, 'subject_id' => $subject->id],
                    ['score' => 7 + (($index + $subjectIndex) % 4) * 0.5],
                );
                Attendance::updateOrCreate(
                    [
                        'user_id' => $student->id,
                        'subject_id' => $subject->id,
                        'date' => $attendanceDate,
                    ],
                    ['status' => ($index + $subjectIndex) % 4 === 0 ? 'absent' : 'present'],
                );
            }
        }
    }
}
