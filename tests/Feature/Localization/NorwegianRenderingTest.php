<?php

declare(strict_types=1);

namespace Tests\Feature\Localization;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NorwegianRenderingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_sign_in_screen_reads_in_norwegian(): void
    {
        $response = $this->withSession(['locale' => 'nb_NO'])->get(route('auth.login.new'));

        $response->assertOk();
        $response->assertSee('Velkommen tilbake', escape: false);
        $response->assertSee('Passord', escape: false);
        $response->assertSee('Glemt passordet?', escape: false);
        $response->assertSee('Norsk bokmål', escape: false);
        $response->assertDontSee('Sign in to', escape: false);
    }

    #[Test]
    public function a_rejected_form_says_why_in_norwegian(): void
    {
        $response = $this->withSession(['locale' => 'nb_NO'])
            ->from(route('auth.login.new'))
            ->post(route('auth.login.create'), ['email' => '', 'password' => '']);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('må fylles ut', (string) session('errors')->first('email'));
    }

    #[Test]
    public function a_counted_phrase_takes_the_two_forms_norwegian_needs(): void
    {
        $this->app->setLocale('nb_NO');

        $phrase = ':count person|:count people';

        $this->assertSame('0 personer', trans_choice($phrase, 0));
        $this->assertSame('1 person', trans_choice($phrase, 1));
        $this->assertSame('2 personer', trans_choice($phrase, 2));
        $this->assertSame('5 personer', trans_choice($phrase, 5));
        $this->assertSame('21 personer', trans_choice($phrase, 21));
    }
}
