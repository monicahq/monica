<?php

declare(strict_types=1);

namespace Tests\Feature\Localization;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UzbekRenderingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_sign_in_screen_reads_in_uzbek(): void
    {
        $response = $this->withSession(['locale' => 'uz_UZ'])->get(route('auth.login.new'));

        $response->assertOk();
        $response->assertSee('Qaytganingiz bilan', escape: false);
        $response->assertSee('Parolni esdan chiqardingizmi?', escape: false);
        $response->assertSee('Oʻzbekcha', escape: false);
        $response->assertDontSee('Sign in to', escape: false);
    }

    #[Test]
    public function a_rejected_form_says_why_in_uzbek(): void
    {
        $response = $this->withSession(['locale' => 'uz_UZ'])
            ->from(route('auth.login.new'))
            ->post(route('auth.login.create'), ['email' => '', 'password' => '']);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('majburiy', (string) session('errors')->first('email'));
    }

    #[Test]
    public function a_counted_phrase_reads_the_same_at_every_number_in_uzbek(): void
    {
        $this->app->setLocale('uz_UZ');

        $phrase = ':count person|:count people';

        $this->assertSame('0 kishi', trans_choice($phrase, 0));
        $this->assertSame('1 kishi', trans_choice($phrase, 1));
        $this->assertSame('2 kishi', trans_choice($phrase, 2));
        $this->assertSame('5 kishi', trans_choice($phrase, 5));
    }
}
