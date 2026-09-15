<?php

declare(strict_types=1);

namespace Tests\Feature\Localization;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TamilRenderingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_sign_in_screen_reads_in_tamil(): void
    {
        $response = $this->withSession(['locale' => 'ta_IN'])->get(route('auth.login.new'));

        $response->assertOk();
        $response->assertSee('மீண்டும் வருக', escape: false);
        $response->assertSee('உள்நுழை', escape: false);
        $response->assertSee('கடவுச்சொல்லை மறந்துவிட்டீர்களா?', escape: false);
        $response->assertSee('தமிழ்', escape: false);
        $response->assertDontSee('Sign in to', escape: false);
    }

    #[Test]
    public function a_rejected_form_says_why_in_tamil(): void
    {
        $response = $this->withSession(['locale' => 'ta_IN'])
            ->from(route('auth.login.new'))
            ->post(route('auth.login.create'), ['email' => '', 'password' => '']);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('புலம் அவசியம்', (string) session('errors')->first('email'));
    }

    #[Test]
    public function a_counted_phrase_takes_the_two_forms_tamil_needs(): void
    {
        $this->app->setLocale('ta_IN');

        $phrase = ':count person|:count people';

        $this->assertSame('1 நபர்', trans_choice($phrase, 1));
        $this->assertSame('2 நபர்கள்', trans_choice($phrase, 2));
        $this->assertSame('0 நபர்கள்', trans_choice($phrase, 0));
    }
}
