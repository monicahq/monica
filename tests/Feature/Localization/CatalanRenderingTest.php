<?php

declare(strict_types=1);

namespace Tests\Feature\Localization;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CatalanRenderingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_sign_in_screen_reads_in_catalan(): void
    {
        $response = $this->withSession(['locale' => 'ca_ES'])->get(route('auth.login.new'));

        $response->assertOk();
        $response->assertSee('Benvingut de nou', escape: false);
        $response->assertSee('Contrasenya', escape: false);
        $response->assertSee('Has oblidat la contrasenya?', escape: false);
        $response->assertSee('Català', escape: false);
        $response->assertDontSee('Sign in to', escape: false);
    }

    #[Test]
    public function a_rejected_form_says_why_in_catalan(): void
    {
        $response = $this->withSession(['locale' => 'ca_ES'])
            ->from(route('auth.login.new'))
            ->post(route('auth.login.create'), ['email' => '', 'password' => '']);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('és obligatori', (string) session('errors')->first('email'));
    }

    #[Test]
    public function a_counted_phrase_takes_the_two_forms_catalan_needs(): void
    {
        $this->app->setLocale('ca_ES');

        $phrase = ':count person|:count people';

        $this->assertSame('0 persones', trans_choice($phrase, 0));
        $this->assertSame('1 persona', trans_choice($phrase, 1));
        $this->assertSame('2 persones', trans_choice($phrase, 2));
        $this->assertSame('5 persones', trans_choice($phrase, 5));
    }
}
