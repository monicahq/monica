<?php

declare(strict_types=1);

namespace Tests\Feature\Localization;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FinnishRenderingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_sign_in_screen_reads_in_finnish(): void
    {
        $response = $this->withSession(['locale' => 'fi_FI'])->get(route('auth.login.new'));

        $response->assertOk();
        $response->assertSee('Tervetuloa takaisin', escape: false);
        $response->assertSee('Salasana', escape: false);
        $response->assertSee('Unohditko salasanasi?', escape: false);
        $response->assertSee('Suomi', escape: false);
        $response->assertDontSee('Sign in to', escape: false);
    }

    #[Test]
    public function a_rejected_form_says_why_in_finnish(): void
    {
        $response = $this->withSession(['locale' => 'fi_FI'])
            ->from(route('auth.login.new'))
            ->post(route('auth.login.create'), ['email' => '', 'password' => '']);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('on pakollinen', (string) session('errors')->first('email'));
    }

    #[Test]
    public function a_counted_phrase_takes_the_two_forms_finnish_needs(): void
    {
        $this->app->setLocale('fi_FI');

        $phrase = ':count person|:count people';

        $this->assertSame('0 henkilöä', trans_choice($phrase, 0));
        $this->assertSame('1 henkilö', trans_choice($phrase, 1));
        $this->assertSame('2 henkilöä', trans_choice($phrase, 2));
        $this->assertSame('5 henkilöä', trans_choice($phrase, 5));
        $this->assertSame('21 henkilöä', trans_choice($phrase, 21));
    }
}
