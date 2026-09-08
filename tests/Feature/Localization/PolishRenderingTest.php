<?php

declare(strict_types=1);

namespace Tests\Feature\Localization;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PolishRenderingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_sign_in_screen_reads_in_polish(): void
    {
        $response = $this->withSession(['locale' => 'pl_PL'])->get(route('auth.login.new'));

        $response->assertOk();
        $response->assertSee('Witaj ponownie', escape: false);
        $response->assertSee('Hasło', escape: false);
        $response->assertSee('Nie pamiętasz hasła?', escape: false);
        $response->assertSee('Polski', escape: false);
        $response->assertDontSee('Sign in to', escape: false);
    }

    #[Test]
    public function a_rejected_form_says_why_in_polish(): void
    {
        $response = $this->withSession(['locale' => 'pl_PL'])
            ->from(route('auth.login.new'))
            ->post(route('auth.login.create'), ['email' => '', 'password' => '']);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('wymagane', (string) session('errors')->first('email'));
    }

    #[Test]
    public function a_counted_phrase_takes_the_three_forms_polish_needs(): void
    {
        $this->app->setLocale('pl_PL');

        $phrase = ':count person|:count people';

        $this->assertSame('1 osoba', trans_choice($phrase, 1));
        $this->assertSame('2 osoby', trans_choice($phrase, 2));
        $this->assertSame('22 osoby', trans_choice($phrase, 22));
        $this->assertSame('5 osób', trans_choice($phrase, 5));
        $this->assertSame('0 osób', trans_choice($phrase, 0));
    }
}
