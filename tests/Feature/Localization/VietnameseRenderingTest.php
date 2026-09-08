<?php

declare(strict_types=1);

namespace Tests\Feature\Localization;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VietnameseRenderingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_sign_in_screen_reads_in_vietnamese(): void
    {
        $response = $this->withSession(['locale' => 'vi_VN'])->get(route('auth.login.new'));

        $response->assertOk();
        $response->assertSee('Chào bạn trở lại', escape: false);
        $response->assertSee('Mật khẩu', escape: false);
        $response->assertSee('Quên mật khẩu?', escape: false);
        $response->assertSee('Tiếng Việt', escape: false);
        $response->assertDontSee('Sign in to', escape: false);
    }

    #[Test]
    public function a_rejected_form_says_why_in_vietnamese(): void
    {
        $response = $this->withSession(['locale' => 'vi_VN'])
            ->from(route('auth.login.new'))
            ->post(route('auth.login.create'), ['email' => '', 'password' => '']);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('là bắt buộc', (string) session('errors')->first('email'));
    }

    #[Test]
    public function a_counted_phrase_reads_the_same_at_every_number_in_vietnamese(): void
    {
        $this->app->setLocale('vi_VN');

        $phrase = ':count person|:count people';

        $this->assertSame('0 người', trans_choice($phrase, 0));
        $this->assertSame('1 người', trans_choice($phrase, 1));
        $this->assertSame('2 người', trans_choice($phrase, 2));
        $this->assertSame('5 người', trans_choice($phrase, 5));
    }
}
