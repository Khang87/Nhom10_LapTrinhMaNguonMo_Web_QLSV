<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Major;
use App\Models\StudentClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class PersonProfileTest extends TestCase
{
    use RefreshDatabase;

    private const HEADER = ['Mã SV', 'Họ tên', 'Email', 'Ngày sinh', 'Giới tính', 'Quê quán', 'SĐT', 'Lớp', 'Tình trạng'];

    public function test_admin_creates_student_with_full_profile_and_sees_it_on_detail_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $class = $this->class('CTK45A', 2025);

        $this->actingAs($admin)->post('/admin/students', [
            'name' => 'Lê Thị Bia',
            'email' => 'lethibia@example.com',
            'password' => '12345678',
            'class_id' => $class->id,
            'student_code' => '20250001',
            'dob' => '2007-03-14',
            'gender' => 'FEMALE',
            'hometown' => 'Cần Thơ',
            'phone' => '0912345678',
            'academic_status' => 'AT_RISK',
        ])->assertRedirect(route('admin.students.index'));

        $student = User::where('email', 'lethibia@example.com')->sole();
        $this->assertSame('20250001', $student->student_code);
        $this->assertSame('2007-03-14', $student->dob->toDateString());
        $this->assertSame('FEMALE', $student->gender);
        $this->assertSame('Cần Thơ', $student->hometown);
        $this->assertSame('0912345678', $student->phone);
        $this->assertSame('AT_RISK', $student->academic_status);

        $this->actingAs($admin)->get(route('admin.students.show', $student))
            ->assertOk()
            ->assertSee('Lê Thị Bia')
            ->assertSee('20250001')
            ->assertSee('14/03/2007')
            ->assertSee('Nữ')
            ->assertSee('0912345678')
            ->assertSee('Cần Thơ')
            ->assertSee('Cảnh báo học vụ')
            ->assertSee('Lớp học phần đã đăng ký')
            ->assertSee('Kết quả học tập')
            ->assertSee('Tỷ lệ chuyên cần');
    }

    public function test_profile_validation_rejects_young_dob_bad_phone_and_duplicate_code(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $other = $this->student('20250009', 'khac@example.com');

        $this->actingAs($admin)->post('/admin/students', [
            'name' => 'Sai hồ sơ',
            'email' => 'saihoso@example.com',
            'password' => '12345678',
            'student_code' => $other->student_code,
            'dob' => now()->subYears(14)->toDateString(),
            'phone' => '1234567890',
            'gender' => 'KEN',
            'academic_status' => 'KHOI',
        ])->assertSessionHasErrors([
            'student_code' => 'Mã sinh viên này đã được sử dụng.',
            'dob' => 'Ngày sinh phải cách đây ít nhất 15 năm.',
            'phone' => 'Số điện thoại phải bắt đầu bằng 0 và có 10–11 chữ số.',
            'gender' => 'Giới tính không hợp lệ.',
            'academic_status' => 'Tình trạng học tập không hợp lệ.',
        ]);

        $this->assertDatabaseMissing('users', ['email' => 'saihoso@example.com']);
    }

    public function test_admin_updates_profile_without_touching_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = $this->student('20250002', 'updatesv@example.com');
        $hash = $student->password;

        $this->actingAs($admin)->put(route('admin.students.update', $student), [
            'name' => $student->name,
            'email' => $student->email,
            'student_code' => '20250002',
            'dob' => '2006-12-01',
            'gender' => 'OTHER',
            'hometown' => 'Ninh Thuận',
            'phone' => '0987654321',
            'academic_status' => 'GRADUATED',
        ])->assertRedirect(route('admin.students.index'));

        $student->refresh();
        $this->assertSame('GRADUATED', $student->academic_status);
        $this->assertSame('Khác', $student->gender_label);
        $this->assertSame('Ninh Thuận', $student->hometown);
        $this->assertTrue(Hash::check('password', $student->password));
        $this->assertSame($hash, $student->password);
    }

    public function test_deleting_a_class_keeps_students_and_clears_their_class(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $class = $this->class('CTK46A', 2026);
        $student = $this->student('20260001', 'danclass@example.com', $class);

        $this->actingAs($admin)->delete(route('admin.classes.destroy', $class))->assertRedirect();

        $this->assertDatabaseMissing('classes', ['id' => $class->id]);
        $this->assertDatabaseHas('users', ['id' => $student->id, 'class_id' => null]);
    }

    public function test_deleting_a_class_row_in_the_database_keeps_student_rows(): void
    {
        // Ràng buộc khóa mới là nullOnDelete: xóa lớp không được xóa âm thầm hồ sơ sinh viên.
        $class = $this->class('CTK46B', 2026);
        $student = $this->student('20260002', 'trực tiếp@example.com', $class);

        $class->delete();

        $this->assertDatabaseHas('users', ['id' => $student->id, 'class_id' => null]);
    }

    public function test_filters_narrow_the_list_by_intake_department_major_and_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $khoaCntt = $this->department('CNTT');
        $khoaKhtn = $this->department('KHTN');
        $ktpm = $this->major($khoaCntt, 'KTPM');
        $httt = $this->major($khoaCntt, 'HTTT');

        $lop44 = $this->class('CTK44A', 2024, $khoaCntt, $ktpm);
        $lop45 = $this->class('CTK45B', 2025, $khoaCntt, $httt);

        $a = $this->student('20240001', 'a@example.com', $lop44);
        $b = $this->student('20240002', 'b@example.com', $lop44, 'AT_RISK');
        $c = $this->student('20250001', 'c@example.com', $lop45, 'GRADUATED');
        $d = $this->student('20250002', 'd@example.com', $lop45);
        $e = $this->student('20250003', 'e@example.com');
        $f = $this->student('20250004', 'f@example.com', null, 'SUSPENDED');

        $this->assertNames([$a, $b, $c, $d, $e, $f], '/admin/students');
        $this->assertNames([$b], '/admin/students?academic_status=AT_RISK');
        $this->assertNames([$a, $b], '/admin/students?intake_year=2024');
        $this->assertNames([$c, $d], '/admin/students?major_id='.$httt->id);
        $this->assertNames([$a, $b, $c, $d], '/admin/students?department_id='.$khoaCntt->id);
        $this->assertNames([], '/admin/students?department_id='.$khoaKhtn->id);
        $this->assertNames([$c, $d], '/admin/students?intake_year=2025&major_id='.$httt->id);
        $this->assertNames([$e, $f], '/admin/students?class_id=none');

        // Bộ lọc chỉ liệt kê giá trị có thật trong dữ liệu: chưa sinh viên nào thuộc KHTN.
        $page = $this->actingAs($admin)->get('/admin/students')->getContent();
        $this->assertStringNotContainsString('KHTN', $page);
        $this->assertStringContainsString('CNTT', $page);
        $this->assertStringContainsString('Tạm dừng học (SUSPENDED)', $page);
        $this->assertStringContainsString('2024', $page);
    }

    public function test_export_writes_xlsx_with_readable_vietnamese_and_kept_formats(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $class = $this->class('CTK44A', 2024);
        $first = $this->student('20240001', 'xuat@example.com', $class);
        $first->update(['dob' => '2006-05-09', 'phone' => '0912345678', 'hometown' => 'Huế', 'gender' => 'MALE']);
        $this->student('20240002', 'xuat2@example.com');

        $response = $this->actingAs($admin)->get('/admin/students/export');
        $response->assertOk();
        $this->assertStringContainsString('spreadsheetml', (string) $response->headers->get('content-type'));

        $rows = $this->readXlsx($response->streamedContent());
        $this->assertSame(self::HEADER, $rows[0]);
        $this->assertCount(3, $rows, 'Một hàng tiêu đề + hai sinh viên.');

        $byCode = collect(array_slice($rows, 1))->keyBy(fn ($row) => (string) $row[0]);
        $this->assertSame($first->name, $byCode['20240001'][1]);
        $this->assertSame('CTK44A', $byCode['20240001'][7]);
        $this->assertSame('', (string) $byCode['20240002'][7], 'Chưa xếp lớp thì để trống, không ghi số 0.');
        $this->assertSame('0912345678', (string) $byCode['20240001'][6], 'SĐT phải giữ số 0 đầu tiên.');
        $this->assertSame('09/05/2006', (string) $byCode['20240001'][3]);
        $this->assertSame('Huế', $byCode['20240001'][5]);
        $this->assertSame('Nam', $byCode['20240001'][4]);
        $this->assertSame('Đang học', $byCode['20240001'][8]);
    }

    public function test_export_follows_the_active_filters(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->student('20240010', 'loc1@example.com', null, 'STUDYING');
        $risk = $this->student('20240011', 'loc2@example.com', null, 'AT_RISK');

        $rows = $this->readXlsx(
            $this->actingAs($admin)->get('/admin/students/export?academic_status=AT_RISK')->streamedContent()
        );

        $this->assertCount(2, $rows);
        $this->assertSame((string) $risk->student_code, (string) $rows[1][0]);
    }

    public function test_import_creates_every_valid_row_then_updates_them_on_reupload(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $class = $this->class('CTK44A', 2024);

        $this->actingAs($admin)->post('/admin/students/import', ['file' => $this->xlsx($this->hundredRows($class->class_code))])
            ->assertSessionHas('success', 'Nhập 100 dòng: thêm mới 100, cập nhật 0, bỏ qua 0.');
        $this->assertDatabaseCount('users', 101);

        $nhu = User::where('student_code', 'SV0042')->sole();
        $this->assertSame('Cần Thơ', $nhu->hometown);
        $this->assertSame($class->id, $nhu->class_id);
        $this->assertSame('2006-07-15', $nhu->dob->toDateString());
        $this->assertSame('Nữ', $nhu->gender_label);
        $this->assertSame('0900000042', $nhu->phone);
        $this->assertTrue(Hash::check('12345678', $nhu->password));

        $this->actingAs($admin)->post('/admin/students/import', ['file' => $this->xlsx($this->hundredRows($class->class_code))])
            ->assertSessionHas('success', 'Nhập 100 dòng: thêm mới 0, cập nhật 100, bỏ qua 0.');
        $this->assertDatabaseCount('users', 101); // Trùng mã sinh viên phải cập nhật, không tạo bản sao.
    }

    public function test_import_updates_profile_but_keeps_password_of_existing_student(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = $this->student('SV0001', 'update@example.com');
        $hash = $student->password;

        $rows = [['SV0001', 'Đổi họ tên', 'update@example.com', '2005-08-02', 'Nữ', 'Bạc Liêu', '0977000111', '', 'AT_RISK']];
        $this->actingAs($admin)->post('/admin/students/import', ['file' => $this->xlsx($rows)])
            ->assertSessionHas('success', 'Nhập 1 dòng: thêm mới 0, cập nhật 1, bỏ qua 0.');

        $student->refresh();
        $this->assertSame('Đổi họ tên', $student->name);
        $this->assertSame('AT_RISK', $student->academic_status);
        $this->assertSame('2005-08-02', $student->dob->toDateString());
        $this->assertSame($hash, $student->password, 'Import không được đụng mật khẩu đang dùng.');
    }

    public function test_import_skips_bad_rows_with_reasons_and_never_fails_hard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $class = $this->class('CTK44A', 2024);
        $existing = $this->student('20240001', 'daton@example.com', $class);

        $rows = [
            ['SV1001', 'Đủ dữ liệu', 'du@example.com', '2006-01-01', 'MALE', 'Hà Nội', '0900000001', 'CTK44A', 'STUDYING'],
            ['SV1002', 'Thiếu email', '', '2006-01-01', 'MALE', 'Hà Nội', '0900000002', 'CTK44A', 'STUDYING'],
            ['SV1003', 'Trùng email', $existing->email, '2006-01-01', 'MALE', 'Hà Nội', '0900000003', 'CTK44A', 'STUDYING'],
            ['SV1004', 'Lớp lạ', 'lopLa@example.com', '2006-01-01', 'MALE', 'Hà Nội', '0900000004', 'CTK99X', 'STUDYING'],
            ['SV1005', 'Ngày sinh sai', 'ngaysinh@example.com', '31/02/2006', 'MALE', 'Hà Nội', '0900000005', 'CTK44A', 'STUDYING'],
            ['SV1006', 'Giới tính lạ', 'gioitinh@example.com', '2006-01-01', 'KEN', 'Hà Nội', '0900000006', 'CTK44A', 'STUDYING'],
            ['', 'Không có mã', 'khongma@example.com', '2006-01-01', 'MALE', 'Hà Nội', '0900000007', 'CTK44A', 'STUDYING'],
        ];

        $this->actingAs($admin)->post('/admin/students/import', ['file' => $this->xlsx($rows)])
            ->assertSessionHas('success', 'Nhập 7 dòng: thêm mới 1, cập nhật 0, bỏ qua 6.');

        $reasons = collect(session('import_result')['skipped'])->pluck('reason', 'row');
        $this->assertSame('Thiếu họ tên hoặc email.', $reasons[3]);
        $this->assertSame("Email {$existing->email} đã thuộc mã sinh viên khác (20240001).", $reasons[4]);
        $this->assertSame('Lớp CTK99X không tồn tại.', $reasons[5]);
        $this->assertSame('Ngày sinh "31/02/2006" không hợp lệ.', $reasons[6]);
        $this->assertSame('Giới tính "KEN" không hợp lệ.', $reasons[7]);
        $this->assertSame('Thiếu mã sinh viên.', $reasons[8]);

        $this->assertDatabaseHas('users', ['student_code' => 'SV1001', 'class_id' => $class->id]);
        $this->assertDatabaseMissing('classes', ['class_code' => 'CTK99X']); // Import không được âm thầm sinh ra lớp.
        $this->assertDatabaseMissing('users', ['email' => 'khongma@example.com']);
    }

    public function test_import_rejects_wrong_extension_and_more_than_five_hundred_rows(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/students/import', [
            'file' => UploadedFile::fake()->create('danh-sach.csv', 12),
        ])->assertSessionHasErrors('file');

        $rows = array_map(
            fn (int $i) => ['SV'.str_pad((string) $i, 4, '0', STR_PAD_LEFT), 'Sinh viên '.$i, "nhieu{$i}@example.com", '2006-01-01', 'MALE', 'Hà Nội', '09'.str_pad((string) $i, 8, '0', STR_PAD_LEFT), '', 'STUDYING'],
            range(1, 501),
        );

        $this->actingAs($admin)->post('/admin/students/import', ['file' => $this->xlsx($rows)])
            ->assertSessionHasErrors('file');
        $this->assertDatabaseCount('users', 1); // File bị từ chối thì không được ghi nửa vời.
    }

    public function test_import_page_and_excel_routes_are_admin_only(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = $this->student('20240099', 'svkhongquyen@example.com');

        $paths = [
            '/admin/students/import',
            '/admin/students/export',
            '/admin/students/import/template',
            route('admin.students.show', $student),
        ];

        foreach ($paths as $path) {
            foreach ([$teacher, $student] as $user) {
                $this->actingAs($user)->get($path)->assertForbidden();
            }
        }

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/admin/students/import')
            ->assertOk()
            ->assertSee('Tải mẫu Excel')
            ->assertSee('Chọn file .xlsx');
    }

    public function test_template_download_uses_the_same_headers_as_import(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $rows = $this->readXlsx($this->actingAs($admin)->get('/admin/students/import/template')->streamedContent());

        $this->assertSame(self::HEADER, $rows[0]);
        $this->assertCount(3, $rows, 'Mẫu phải kèm hai dòng ví dụ.');
        $this->assertSame('20240001', (string) $rows[1][0]);
        $this->assertSame('MALE', $rows[2][4]);
    }

    public function test_extended_profile_fields_save_and_appear_on_detail_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/students', [
            'name' => 'Phạm Minh Châu',
            'email' => 'chau@example.com',
            'password' => '12345678',
            'student_code' => '20250101',
            'enrolled_on' => '2025-09-05',
            'education_level' => 'Đại học',
            'training_type' => 'Chính quy',
            'admission_region' => 'KV1',
            'priority_category' => '06',
            'ethnicity' => 'Kinh',
            'religion' => 'Phật giáo',
            'nationality' => 'Việt Nam',
            'birth_place' => 'Bệnh viện Đa khoa tỉnh Hải Dương',
            'permanent_residence' => 'Thôn 4, xã Nam Sách, Hải Dương',
            'contact_address' => 'Khu phố 3, phường Linh Xuân, TP.HCM',
            'id_card_number' => '001200011222',
            'id_card_issued_at' => '2020-01-15',
            'id_card_issued_place' => 'Cục Cảnh sát quản lý hành chính',
        ])->assertRedirect(route('admin.students.index'));

        $student = User::where('email', 'chau@example.com')->sole();
        $this->assertSame('2025-09-05', $student->enrolled_on->toDateString());
        $this->assertSame('Kinh', $student->ethnicity);
        $this->assertSame('001200011222', $student->id_card_number);
        $this->assertSame('2020-01-15', $student->id_card_issued_at->toDateString());

        $this->actingAs($admin)->get(route('admin.students.show', $student))
            ->assertOk()
            ->assertSee('Hồ sơ đào tạo')
            ->assertSee('Cá nhân & liên hệ')
            ->assertSee('Giấy tờ & diện ưu tiên')
            ->assertSee('Chính quy')
            ->assertSee('05/09/2025')
            ->assertSee('Phật giáo')
            ->assertSee('Việt Nam')
            ->assertSee('Thôn 4, xã Nam Sách, Hải Dương')
            ->assertSee('001200011222')
            ->assertSee('15/01/2020')
            ->assertSee('KV1');
    }

    public function test_id_card_number_must_have_twelve_digits(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/students', [
            'name' => 'Sai giấy tờ',
            'email' => 'saigiayto@example.com',
            'password' => '12345678',
            'id_card_number' => '00120001122',
        ])->assertSessionHasErrors(['id_card_number' => 'Số CCCD / định danh phải đủ 12 chữ số.']);

        $this->assertDatabaseMissing('users', ['email' => 'saigiayto@example.com']);
    }

    public function test_student_list_shows_extended_columns_and_finds_by_phone(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $khoaCntt = $this->department('CNTT');
        $ktpm = $this->major($khoaCntt, 'KTPM');
        $class = $this->class('CTK45A', 2025, $khoaCntt, $ktpm);

        $hasPhone = $this->student('20250201', 'cosodt@example.com', $class);
        $hasPhone->update(['phone' => '0905113355', 'dob' => '2007-04-19', 'gender' => 'FEMALE']);
        $noPhone = $this->student('20250202', 'khongcodt@example.com', $class);

        $page = $this->actingAs($admin)->get('/admin/students')->getContent();
        foreach (['#', 'Mã SV', 'Ngày sinh', 'Giới tính', 'Chuyên ngành', 'Tình trạng'] as $header) {
            $this->assertStringContainsString("<th scope=\"col\">{$header}</th>", $page);
        }
        $this->assertStringContainsString('Ngành KTPM', $page);
        $this->assertStringContainsString('19/04/2007', $page);
        $this->assertStringContainsString('Nữ', $page);
        $this->assertStringContainsString('Chưa có số điện thoại', $page);

        $this->assertNames([$hasPhone], '/admin/students?search=0905113355');
        $this->assertNames([$noPhone], '/admin/students?search=khongcodt');
    }

    public function test_student_and_account_lists_carry_summary_cards(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->student('20250301', 'card1@example.com');
        $this->student('20250302', 'card2@example.com', null, 'AT_RISK');

        $page = $this->actingAs($admin)->get('/admin/students')->assertOk()->getContent();
        foreach (['Tổng sinh viên', 'Đang học bình thường', 'Cần theo dõi học vụ', 'Đã ra trường'] as $card) {
            $this->assertStringContainsString($card, $page, "Thiếu thẻ thống kê {$card}.");
        }

        $this->actingAs($admin)->get('/admin/accounts')->assertOk()
            ->assertSee('Tổng tài khoản')
            ->assertSee('Quản trị viên');
    }

    public function test_account_form_stores_role_profile_and_clears_it_when_role_changes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $department = $this->department('CNTT');
        $class = $this->class('CTK45A', 2025, $department);

        $this->actingAs($admin)->post('/admin/accounts', [
            'name' => 'Trần Quốc Bảo',
            'email' => 'bao@example.com',
            'password' => '12345678',
            'role' => 'teacher',
            'teacher_code' => 'GV99',
            'degree' => 'Thạc sĩ',
            'department_id' => $department->id,
            'phone' => '0913456789',
            'dob' => '1988-06-21',
            'gender' => 'MALE',
            'class_id' => $class->id,
            'student_code' => '20250999',
        ])->assertRedirect(route('admin.accounts.index'));

        $teacher = User::where('email', 'bao@example.com')->sole();
        $this->assertSame('GV99', $teacher->teacher_code);
        $this->assertSame('Thạc sĩ', $teacher->degree);
        $this->assertSame($department->id, $teacher->department_id);
        $this->assertNull($teacher->class_id, 'Giảng viên không mang hồ sơ sinh viên.');
        $this->assertNull($teacher->student_code);

        $page = $this->actingAs($admin)->get('/admin/accounts')->getContent();
        $this->assertStringContainsString('GV99', $page);
        $this->assertStringContainsString('Thạc sĩ', $page);
        $this->assertStringContainsString('Khoa CNTT', $page);
        $this->assertStringContainsString('0913456789', $page);

        $this->actingAs($admin)->put(route('admin.accounts.update', $teacher), [
            'name' => $teacher->name,
            'email' => $teacher->email,
            'role' => 'student',
            'student_code' => '20250999',
            'class_id' => $class->id,
        ])->assertRedirect(route('admin.accounts.index'));

        $teacher->refresh();
        $this->assertSame('student', $teacher->role);
        $this->assertNull($teacher->teacher_code);
        $this->assertNull($teacher->degree);
        $this->assertNull($teacher->department_id);
        $this->assertSame('20250999', $teacher->student_code);
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function readXlsx(string $content): array
    {
        $path = tempnam(sys_get_temp_dir(), 'sv').'.xlsx';
        file_put_contents($path, $content);

        try {
            return IOFactory::load($path)->getActiveSheet()->toArray(null, true, false, false);
        } finally {
            unlink($path);
        }
    }

    /**
     * @return list<array<int, string>>
     */
    private function hundredRows(string $classCode): array
    {
        return array_map(
            fn (int $i) => [
                'SV'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'Sinh viên số '.$i,
                'nhap'.str_pad((string) $i, 3, '0', STR_PAD_LEFT).'@example.com',
                sprintf('2006-%02d-%02d', ($i % 12) + 1, ($i % 28) + 1),
                $i % 3 === 0 ? 'Nữ' : 'Nam',
                'Cần Thơ',
                '09'.str_pad((string) $i, 8, '0', STR_PAD_LEFT),
                $classCode,
                'STUDYING',
            ],
            range(1, 100),
        );
    }

    /**
     * @param  list<array<int, string>>  $rows
     */
    private function xlsx(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray(array_merge([self::HEADER], $rows), null, 'A1');

        $path = tempnam(sys_get_temp_dir(), 'fixture').'.xlsx';
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($path);
        $content = (string) file_get_contents($path);
        unlink($path);
        $spreadsheet->disconnectWorksheets();

        return UploadedFile::fake()->createWithContent('danh-sach.xlsx', $content);
    }

    /**
     * @param  list<User>  $expected
     */
    private function assertNames(array $expected, string $path): void
    {
        $content = $this->actingAs(User::where('role', 'admin')->sole())->get($path)->getContent();
        $rows = $this->rowsOnly($content);
        $keep = array_map(fn (User $student) => $student->email, $expected);

        foreach (User::where('role', 'student')->pluck('email') as $email) {
            if (in_array($email, $keep, true)) {
                $this->assertStringContainsString($email, $rows, "Lọc {$path} làm mất {$email}.");
            } else {
                $this->assertStringNotContainsString($email, $rows, "Lọc {$path} vẫn trả về {$email}.");
            }
        }
    }

    /**
     * Chỉ soi bảng kết quả: email của admin hiện trên thanh bên và có thể chứa chuỗi con giống email kiểm thử.
     */
    private function rowsOnly(string $content): string
    {
        $start = strpos($content, '<tbody');
        $end = strpos($content, '</tbody>');

        return $start === false || $end === false ? $content : substr($content, $start, $end - $start);
    }

    private function department(string $code): Department
    {
        return Department::create(['code' => $code, 'name' => 'Khoa '.$code]);
    }

    private function major(Department $department, string $code): Major
    {
        return Major::create(['department_id' => $department->id, 'code' => $code, 'name' => 'Ngành '.$code]);
    }

    private function class(string $code, int $intakeYear, ?Department $department = null, ?Major $major = null): StudentClass
    {
        return StudentClass::create([
            'class_code' => $code,
            'class_name' => 'Lớp '.$code,
            'intake_year' => $intakeYear,
            'department_id' => $department?->id,
            'major_id' => $major?->id,
        ]);
    }

    private function student(string $code, string $email, ?StudentClass $class = null, string $status = 'STUDYING'): User
    {
        return User::factory()->create([
            'name' => 'Nguyễn Văn A '.$code,
            'email' => $email,
            'role' => 'student',
            'class_id' => $class?->id,
            'student_code' => $code,
            'academic_status' => $status,
        ]);
    }
}
