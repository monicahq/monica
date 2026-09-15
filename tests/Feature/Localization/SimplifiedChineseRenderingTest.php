<?php

declare(strict_types=1);

namespace Tests\Feature\Localization;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SimplifiedChineseRenderingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_sign_in_screen_reads_in_simplified_chinese(): void
    {
        $response = $this->withSession(['locale' => 'zh_CN'])->get(route('auth.login.new'));

        $response->assertOk();
        $response->assertSee('登录', escape: false);
        $response->assertSee('密码', escape: false);
        $response->assertSee('简体中文', escape: false);
        $response->assertDontSee('Sign in to', escape: false);
    }

    #[Test]
    public function a_rejected_form_says_why_in_simplified_chinese(): void
    {
        $response = $this->withSession(['locale' => 'zh_CN'])
            ->from(route('auth.login.new'))
            ->post(route('auth.login.create'), ['email' => '', 'password' => '']);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('不能为空', (string) session('errors')->first('email'));
    }
}
