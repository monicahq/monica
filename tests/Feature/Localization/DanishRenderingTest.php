<?php

declare(strict_types=1);

namespace Tests\Feature\Localization;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DanishRenderingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_sign_in_screen_reads_in_danish(): void
    {
        $response = $this->withSession(['locale' => 'da_DK'])->get(route('auth.login.new'));

        $response->assertOk();
        $response->assertSee('Velkommen tilbage', escape: false);
        $response->assertSee('Adgangskode', escape: false);
        $response->assertSee('Glemt adgangskoden?', escape: false);
        $response->assertSee('Dansk', escape: false);
        $response->assertDontSee('Sign in to', escape: false);
    }

    #[Test]
    public function a_rejected_form_says_why_in_danish(): void
    {
        $response = $this->withSession(['locale' => 'da_DK'])
            ->from(route('auth.login.new'))
            ->post(route('auth.login.create'), ['email' => '', 'password' => '']);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('skal udfyldes', (string) session('errors')->first('email'));
    }

    #[Test]
    public function a_counted_phrase_takes_the_two_forms_danish_needs(): void
    {
        $this->app->setLocale('da_DK');

        $phrase = ':count person|:count people';

        $this->assertSame('0 personer', trans_choice($phrase, 0));
        $this->assertSame('1 person', trans_choice($phrase, 1));
        $this->assertSame('2 personer', trans_choice($phrase, 2));
        $this->assertSame('5 personer', trans_choice($phrase, 5));
        $this->assertSame('21 personer', trans_choice($phrase, 21));
    }
}
