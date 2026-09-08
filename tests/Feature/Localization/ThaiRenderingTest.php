<?php

declare(strict_types=1);

namespace Tests\Feature\Localization;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ThaiRenderingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_sign_in_screen_reads_in_thai(): void
    {
        $response = $this->withSession(['locale' => 'th_TH'])->get(route('auth.login.new'));

        $response->assertOk();
        $response->assertSee('ยินดีที่กลับมา', escape: false);
        $response->assertSee('รหัสผ่าน', escape: false);
        $response->assertSee('ลืมรหัสผ่าน?', escape: false);
        $response->assertSee('ไทย', escape: false);
        $response->assertDontSee('Sign in to', escape: false);
    }

    #[Test]
    public function a_rejected_form_says_why_in_thai(): void
    {
        $response = $this->withSession(['locale' => 'th_TH'])
            ->from(route('auth.login.new'))
            ->post(route('auth.login.create'), ['email' => '', 'password' => '']);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('ต้องกรอก', (string) session('errors')->first('email'));
    }

    #[Test]
    public function a_counted_phrase_reads_the_same_at_every_number_in_thai(): void
    {
        $this->app->setLocale('th_TH');

        $phrase = ':count person|:count people';

        $this->assertSame('0 คน', trans_choice($phrase, 0));
        $this->assertSame('1 คน', trans_choice($phrase, 1));
        $this->assertSame('2 คน', trans_choice($phrase, 2));
        $this->assertSame('5 คน', trans_choice($phrase, 5));
    }
}
