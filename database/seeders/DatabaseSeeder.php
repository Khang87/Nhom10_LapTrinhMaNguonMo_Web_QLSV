<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Grade;
use App\Models\StudentClass;
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

        $subjects = collect([
            ['code' => 'WEB101', 'name' => 'Lập trình Web', 'credits' => 3],
            ['code' => 'DB101', 'name' => 'Cơ sở dữ liệu', 'credits' => 3],
            ['code' => 'NET101', 'name' => 'Mạng máy tính', 'credits' => 2],
        ])->mapWithKeys(fn (array $data) => [
            $data['code'] => Subject::updateOrCreate(['code' => $data['code']], $data),
        ]);

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
            $student->subjects()->syncWithoutDetaching($subjects->pluck('id'));

            foreach ($subjects->values() as $subjectIndex => $subject) {
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
