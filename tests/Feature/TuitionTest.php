<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\TuitionInvoice;
use App\Models\User;
use App\Support\Tuition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\OfferingFixtures;
use Tests\TestCase;

/**
 * Ý tưởng màn hình "Học phí & VietQR": nhà trường thu theo tín chỉ, phát hành một thông báo
 * mỗi học kỳ và sinh viên chuyển khoản theo mã QR kèm nội dung đối chiếu.
 */
class TuitionTest extends TestCase
{
    use OfferingFixtures, RefreshDatabase;

    private const PER_CREDIT = 350_000;

    protected function setUp(): void
    {
        parent::setUp();
        config(['academic.tuition_per_credit' => self::PER_CREDIT]);
    }

    public function test_the_amount_is_the_active_credits_of_one_semester_times_the_rate(): void
    {
        $term = $this->semester('2025-2026', 1);
        $nextTerm = $this->semester('2025-2026', 2);
        $student = $this->student();
        $this->enroll($student, $this->offering($this->subjectWith('WEB101', 3), $term));
        $this->enroll($student, $this->offering($this->subjectWith('DB101', 4), $term));
        $this->enroll($student, $this->offering($this->subjectWith('ADT101', 3), $nextTerm));

        $dropped = $this->enroll($student, $this->offering($this->subjectWith('SEC201', 2), $term));
        $dropped->update(['status' => 'DROPPED', 'dropped_at' => now()]);

        $this->assertSame(7, Tuition::creditsFor($student, $term), 'Chỉ tính môn đang học của đúng học kỳ.');
        $this->assertSame(2_450_000, Tuition::amountFor(Tuition::creditsFor($student, $term)));
    }

    public function test_the_transfer_content_stays_within_the_banks_25_character_limit(): void
    {
        $term = $this->semester();
        $coded = $this->student('Nguyễn Văn An');
        $coded->update(['student_code' => '20240123456789012345']);
        $plain = $this->student('Trần Bình');

        $content = Tuition::contentFor($coded, $term);
        $this->assertSame('QLSV20240123456789012345', $content, 'Quá 25 ký tự là ngân hàng cắt âm ảnh QR.');
        $this->assertLessThanOrEqual(25, strlen($content));

        // Mã số chưa cập nhật vẫn phải truy được về đúng hồ sơ.
        $this->assertSame('QLSVSV'.$plain->id.'-HK1', Tuition::contentFor($plain, $term));
    }

    public function test_declaring_creates_one_invoice_per_student_and_can_be_run_again(): void
    {
        $term = $this->semester('2025-2026', 1);
        $web = $this->subjectWith('WEB101', 3);
        $db = $this->subjectWith('DB101', 4);
        $first = $this->student('Nguyễn Văn An');
        $second = $this->student('Trần Bình');
        $this->enroll($first, $this->offering($web, $term));
        $this->enroll($second, $this->offering($db, $term));
        $this->student('Chưa đăng ký môn nào');

        $this->assertSame(['created' => 2, 'refreshed' => 0], Tuition::declare($term));
        $this->assertSame(['created' => 0, 'refreshed' => 0], Tuition::declare($term), 'Chạy lại không sinh thêm thông báo.');
        $this->assertSame(2, TuitionInvoice::count());

        $invoice = TuitionInvoice::where('user_id', $first->id)->sole();
        $this->assertSame(3, $invoice->credits);
        $this->assertSame(1_050_000, $invoice->amount);
        $this->assertSame(Tuition::contentFor($first, $term), $invoice->content);
    }

    public function test_an_uncollected_invoice_follows_the_registration_but_a_paid_one_is_frozen(): void
    {
        $term = $this->semester('2025-2026', 1);
        $web = $this->subjectWith('WEB101', 3);
        $db = $this->subjectWith('DB101', 4);
        $student = $this->student();
        $this->enroll($student, $firstClass = $this->offering($web, $term));
        $this->assertSame(['created' => 1, 'refreshed' => 0], Tuition::declare($term));

        $this->enroll($student, $this->offering($db, $term));
        $this->assertSame(['created' => 0, 'refreshed' => 1], Tuition::declare($term));
        $this->assertSame(7, TuitionInvoice::sole()->credits);

        $invoice = TuitionInvoice::sole();
        $invoice->update(['paid_at' => now()]);
        Enrollment::where('course_offering_id', $firstClass->id)->update(['status' => 'DROPPED', 'dropped_at' => now()]);

        $this->assertSame(['created' => 0, 'refreshed' => 0], Tuition::declare($term));
        $this->assertSame(7, $invoice->fresh()->credits, 'Thông báo đã thu không bị đổi số tiền.');
    }

    public function test_the_qr_appears_only_when_the_receiving_account_is_configured(): void
    {
        $term = $this->semester('2025-2026', 1, ['is_active' => true]);
        $student = $this->student('Nguyễn Văn An');
        $student->update(['student_code' => '20240001']);
        $this->enroll($student, $this->offering($this->subjectWith('WEB101', 3), $term));
        Tuition::declare($term);
        $invoice = TuitionInvoice::sole();

        $this->assertNull($invoice->qr_url, 'Chưa cấu hình tài khoản nhận thì không bịa ra mã QR.');
        $this->actingAs($student)->get(route('student.tuition'))
            ->assertOk()
            ->assertSee('Nhà trường chưa cấu hình tài khoản nhận');

        config(['academic.tuition_bank' => ['code' => 'vietcombank', 'account' => '1234567890']]);

        $this->assertSame(
            'https://img.vietqr.io/image/vietcombank-1234567890-compact2.png?amount=1050000&addInfo=QLSV20240001-HK1',
            $invoice->qr_url
        );

        $page = $this->actingAs($student)->get(route('student.tuition'))->assertOk();
        $page->assertSee('Mã QR do VietQR tạo từ số tiền và nội dung bên phải')
            ->assertSee('QLSV20240001-HK1')
            ->assertSee('tài khoản 1234567890');
        $this->assertStringContainsString('img.vietqr.io/image/vietcombank-1234567890-compact2.png', $page->getContent());
    }

    public function test_the_admin_screen_declares_the_term_and_records_collection(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $term = $this->semester('2025-2026', 1);
        $student = $this->student('Nguyễn Văn An');
        $student->update(['student_code' => '20240001']);
        $this->enroll($student, $this->offering($this->subjectWith('WEB101', 3), $term));

        $this->actingAs($admin)->get(route('admin.tuition.index', ['semester_id' => $term->id]))
            ->assertOk()
            ->assertSee('Học kỳ này chưa có thông báo học phí nào')
            ->assertSee('350.000 đ')
            ->assertSee('Phát hành học phí kỳ này');

        $this->actingAs($admin)->post(route('admin.tuition.declare'), ['semester_id' => $term->id])
            ->assertRedirect(route('admin.tuition.index', ['semester_id' => $term->id]))
            ->assertSessionHas('success', 'Đã phát hành 1 thông báo học phí mới, cập nhật 0 thông báo chưa thu cho '
                .$term->label.'.');

        $invoice = TuitionInvoice::sole();
        $this->actingAs($admin)->get(route('admin.tuition.index', ['semester_id' => $term->id]))
            ->assertOk()
            ->assertSee('Nguyễn Văn An')
            ->assertSee('20240001')
            ->assertSee('1.050.000 đ')
            ->assertSee('QLSV20240001-HK1')
            ->assertSee('Chưa thu')
            ->assertSee('Đánh dấu đã thu');

        $this->actingAs($admin)->post(route('admin.tuition.paid', $invoice), ['note' => 'Ủy nhiệm thu 02/01'])
            ->assertSessionHas('success', 'Đã ghi nhận thu 1.050.000 đ của Nguyễn Văn An.');

        $invoice->refresh();
        $this->assertTrue($invoice->isPaid());
        $this->assertSame($admin->id, $invoice->collected_by);
        $this->assertSame('Ủy nhiệm thu 02/01', $invoice->note);

        $this->actingAs($admin)->get(route('admin.tuition.index', ['semester_id' => $term->id]))
            ->assertOk()
            ->assertSee('1.050.000 đ')
            ->assertSee('Ủy nhiệm thu 02/01')
            ->assertSee('Hủy ghi nhận thu')
            ->assertDontSee('Đánh dấu đã thu');

        $this->actingAs($admin)->post(route('admin.tuition.unpaid', $invoice))
            ->assertSessionHas('success', 'Đã hủy ghi nhận thu của Nguyễn Văn An.');

        $invoice->refresh();
        $this->assertFalse($invoice->isPaid());
        $this->assertNull($invoice->collected_by);
        $this->assertNull($invoice->note);
    }

    public function test_the_student_page_shows_pending_tuition_before_the_school_declares(): void
    {
        $term = $this->semester('2025-2026', 1, ['is_active' => true]);
        $student = $this->student('Lê Thị Bình');
        $this->enroll($student, $this->offering($this->subjectWith('WEB101', 3), $term));
        $this->enroll($student, $this->offering($this->subjectWith('DB101', 4), $term));

        $this->actingAs($student)->get(route('student.tuition'))
            ->assertOk()
            ->assertSee('chưa có thông báo học phí')
            ->assertSee('Bạn đang học 7 TC, tạm tính')
            ->assertSee('2.450.000 đ')
            ->assertSee('chỉ chính thức sau khi nhà trường phát hành')
            ->assertSee('Bạn chưa có thông báo học phí nào');
    }

    public function test_the_money_cards_cover_the_whole_semester_not_just_the_current_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $term = $this->semester('2025-2026', 1);
        $offering = $this->offering($this->subjectWith('WEB101', 3), $term);
        $names = [];
        for ($i = 1; $i <= 12; $i++) {
            $student = $this->student('Sinh viên '.str_pad((string) $i, 2, '0', STR_PAD_LEFT));
            $names[$i] = $student->name;
            $this->enroll($student, $offering);
        }
        Tuition::declare($term);

        $first = $this->actingAs($admin)->get(route('admin.tuition.index', ['semester_id' => $term->id]))
            ->assertOk()
            ->assertSee('Sinh viên 01')
            ->assertSee('Sinh viên 10')
            ->assertDontSee('Sinh viên 11')
            ->getContent();

        // 12 thông báo x 1.050.000 đ: thẻ tiền phải tính trên cả học kỳ dù trang chỉ hiện 10 dòng.
        $this->assertStringContainsString('12.600.000 đ', $first);
        $this->assertStringContainsString('12 sinh viên', $first);

        $this->actingAs($admin)->get(route('admin.tuition.index', ['semester_id' => $term->id, 'page' => 2]))
            ->assertOk()
            ->assertSee('Sinh viên 11')
            ->assertSee('Sinh viên 12')
            ->assertDontSee('Sinh viên 01');
    }

    public function test_a_student_never_sees_another_students_invoice(): void
    {
        $term = $this->semester('2025-2026', 1, ['is_active' => true]);
        $mine = $this->student('Lê Thị Bình');
        $theirs = $this->student('Đỗ Minh Châu');
        $theirs->update(['student_code' => '20240099']);
        $this->enroll($mine, $this->offering($this->subjectWith('WEB101', 3), $term));
        $this->enroll($theirs, $this->offering($this->subjectWith('DB101', 4), $term));
        Tuition::declare($term);

        $this->actingAs($mine)->get(route('student.tuition'))
            ->assertOk()
            ->assertSee('QLSVSV'.$mine->id.'-HK1')
            ->assertSee('1.050.000 đ')
            ->assertDontSee('20240099')
            ->assertDontSee('1.400.000 đ')
            ->assertDontSee('Đỗ Minh Châu');
    }

    public function test_declaration_needs_a_configured_rate_and_only_an_admin_can_collect(): void
    {
        $term = $this->semester('2025-2026', 1);
        $student = $this->student();
        $this->enroll($student, $this->offering($this->subjectWith('WEB101', 3), $term));

        config(['academic.tuition_per_credit' => 0]);
        $this->assertSame(0, Tuition::perCredit());

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('admin.tuition.declare'), ['semester_id' => $term->id])
            ->assertSessionHas('error', 'Chưa cấu hình mức thu một tín chỉ nên không phát hành được thông báo học phí.');
        $this->assertSame(0, TuitionInvoice::count());

        config(['academic.tuition_per_credit' => self::PER_CREDIT]);
        Tuition::declare($term);
        $invoice = TuitionInvoice::sole();

        $this->actingAs($this->teacher())->get(route('admin.tuition.index'))->assertForbidden();
        $this->actingAs($student)->get(route('admin.tuition.index'))->assertForbidden();
        $this->actingAs($student)->post(route('admin.tuition.declare'), ['semester_id' => $term->id])->assertForbidden();
        $this->actingAs($student)->post(route('admin.tuition.paid', $invoice))->assertForbidden();
        $this->actingAs($student)->post(route('admin.tuition.unpaid', $invoice))->assertForbidden();
        $this->assertFalse($invoice->isPaid());
    }

    public function test_an_invoice_disappears_with_the_student_it_belongs_to(): void
    {
        $term = $this->semester('2025-2026', 1);
        $student = $this->student();
        $this->enroll($student, $this->offering($this->subjectWith('WEB101', 3), $term));
        Tuition::declare($term);
        $this->assertSame(1, TuitionInvoice::count());

        $student->delete();

        $this->assertSame(0, TuitionInvoice::count());
        $this->assertSame(0, Enrollment::count());
    }
}
