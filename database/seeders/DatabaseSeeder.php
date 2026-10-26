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
        $classes = collect([
            ['class_code' => 'CTK44A', 'class_name' => 'Công nghệ thông tin 44A', 'room' => 'A101'],
            ['class_code' => 'CTK44B', 'class_name' => 'Công nghệ thông tin 44B', 'room' => 'A102'],
        ])->mapWithKeys(fn (array $data) => [
            $data['class_code'] => StudentClass::updateOrCreate(
                ['class_code' => $data['class_code']],
                $data,
            ),
        ]);

        $departments = collect([
            ['code' => 'CNTT', 'name' => 'Khoa Công nghệ thông tin'],
            ['code' => 'KHTN', 'name' => 'Khoa Khoa học cơ bản'],
        ])->mapWithKeys(fn (array $data) => [
            $data['code'] => Department::updateOrCreate(['code' => $data['code']], $data),
        ]);

        $majors = collect([
            ['code' => 'KTPM', 'name' => 'Kỹ thuật phần mềm', 'department_id' => $departments['CNTT']->id],
            ['code' => 'HTTT', 'name' => 'Hệ thống thông tin', 'department_id' => $departments['CNTT']->id],
        ])->mapWithKeys(fn (array $data) => [
            $data['code'] => Major::updateOrCreate(['code' => $data['code']], $data),
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
            ['name' => 'Nguyễn Văn Đạt', 'email' => 'giangvien1@gmail.com'],
            ['name' => 'Trần Thị Kim Oanh', 'email' => 'giangvien2@gmail.com'],
        ] as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('12345678'),
                    'role' => 'teacher',
                    'class_id' => null,
                ],
            );
        }

        $studentNames = [
            ['name' => 'Nguyễn Minh Anh', 'email' => 'sinhvien1@gmail.com'],
            ['name' => 'Trần Hoàng Nam', 'email' => 'sinhvien2@gmail.com'],
            ['name' => 'Lê Thu Hà', 'email' => 'sinhvien3@gmail.com'],
            ['name' => 'Phạm Quốc Bảo', 'email' => 'sinhvien4@gmail.com'],
            ['name' => 'Võ Ngọc Linh', 'email' => 'sinhvien5@gmail.com'],
        ];
        $attendanceDate = today();

        foreach ($studentNames as $index => $data) {
            $student = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('12345678'),
                    'role' => 'student',
                    'class_id' => $classes->values()[$index % 2]->id,
                ],
            );
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
