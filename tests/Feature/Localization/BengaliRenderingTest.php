<?php

declare(strict_types=1);

namespace Tests\Feature\Localization;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BengaliRenderingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_sign_in_screen_reads_in_bengali(): void
    {
        $response = $this->withSession(['locale' => 'bn_BD'])->get(route('auth.login.new'));

        $response->assertOk();
        $response->assertSee('আবার স্বাগতম', escape: false);
        $response->assertSee('সাইন-ইন', escape: false);
        $response->assertSee('পাসওয়ার্ড ভুলে গেছেন?', escape: false);
        $response->assertSee('বাংলা', escape: false);
        $response->assertDontSee('Sign in to', escape: false);
    }

    #[Test]
    public function a_rejected_form_says_why_in_bengali(): void
    {
        $response = $this->withSession(['locale' => 'bn_BD'])
            ->from(route('auth.login.new'))
            ->post(route('auth.login.create'), ['email' => '', 'password' => '']);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('পূরণ করা বাধ্যতামূলক', (string) session('errors')->first('email'));
    }

    #[Test]
    public function a_counted_phrase_takes_the_two_forms_bengali_needs(): void
    {
        $this->app->setLocale('bn_BD');

        $phrase = ':count person|:count people';

        $this->assertSame('1 জন', trans_choice($phrase, 1));
        $this->assertSame('2 জন', trans_choice($phrase, 2));
        $this->assertSame('0 জন', trans_choice($phrase, 0));
    }
}
