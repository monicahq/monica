<?php

declare(strict_types=1);

namespace Tests\Feature\Localization;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HindiRenderingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_sign_in_screen_reads_in_hindi(): void
    {
        $response = $this->withSession(['locale' => 'hi_IN'])->get(route('auth.login.new'));

        $response->assertOk();
        $response->assertSee('फिर स्वागत है', escape: false);
        $response->assertSee('साइन-इन करें', escape: false);
        $response->assertSee('पासवर्ड भूल गए?', escape: false);
        $response->assertSee('हिन्दी', escape: false);
        $response->assertDontSee('Sign in to', escape: false);
    }

    #[Test]
    public function a_rejected_form_says_why_in_hindi(): void
    {
        $response = $this->withSession(['locale' => 'hi_IN'])
            ->from(route('auth.login.new'))
            ->post(route('auth.login.create'), ['email' => '', 'password' => '']);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('फील्ड आवश्यक होता है', (string) session('errors')->first('email'));
    }

    #[Test]
    public function a_counted_phrase_groups_none_with_the_singular_in_hindi(): void
    {
        $this->app->setLocale('hi_IN');

        $phrase = ':count person|:count people';

        $this->assertSame('0 व्यक्ति', trans_choice($phrase, 0));
        $this->assertSame('1 व्यक्ति', trans_choice($phrase, 1));
        $this->assertSame('2 लोग', trans_choice($phrase, 2));
        $this->assertSame('5 लोग', trans_choice($phrase, 5));
    }
}
