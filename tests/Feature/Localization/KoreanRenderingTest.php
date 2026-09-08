<?php

declare(strict_types=1);

namespace Tests\Feature\Localization;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KoreanRenderingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_sign_in_screen_reads_in_korean(): void
    {
        $response = $this->withSession(['locale' => 'ko_KR'])->get(route('auth.login.new'));

        $response->assertOk();
        $response->assertSee('다시 오신 것을 환영합니다', escape: false);
        $response->assertSee('비밀번호', escape: false);
        $response->assertSee('비밀번호를 잊으셨나요?', escape: false);
        $response->assertSee('한국어', escape: false);
        $response->assertDontSee('Sign in to', escape: false);
    }

    #[Test]
    public function a_rejected_form_says_why_in_korean(): void
    {
        $response = $this->withSession(['locale' => 'ko_KR'])
            ->from(route('auth.login.new'))
            ->post(route('auth.login.create'), ['email' => '', 'password' => '']);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('필수', (string) session('errors')->first('email'));
    }

    #[Test]
    public function a_counted_phrase_takes_the_one_form_korean_needs(): void
    {
        $this->app->setLocale('ko_KR');

        $phrase = ':count person|:count people';

        $this->assertSame('0명', trans_choice($phrase, 0));
        $this->assertSame('1명', trans_choice($phrase, 1));
        $this->assertSame('2명', trans_choice($phrase, 2));
        $this->assertSame('5명', trans_choice($phrase, 5));
        $this->assertSame('21명', trans_choice($phrase, 21));
    }
}
