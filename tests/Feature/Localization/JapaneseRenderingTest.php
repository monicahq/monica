<?php

declare(strict_types=1);

namespace Tests\Feature\Localization;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class JapaneseRenderingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_sign_in_screen_reads_in_japanese(): void
    {
        $response = $this->withSession(['locale' => 'ja_JP'])->get(route('auth.login.new'));

        $response->assertOk();
        $response->assertSee('サインイン', escape: false);
        $response->assertSee('パスワード', escape: false);
        $response->assertSee('日本語', escape: false);
        $response->assertDontSee('Sign in to', escape: false);
    }

    #[Test]
    public function a_rejected_form_says_why_in_japanese(): void
    {
        $response = $this->withSession(['locale' => 'ja_JP'])
            ->from(route('auth.login.new'))
            ->post(route('auth.login.create'), ['email' => '', 'password' => '']);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('必須', (string) session('errors')->first('email'));
    }
}
