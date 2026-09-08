<?php

declare(strict_types=1);

namespace Tests\Feature\Localization;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GreekRenderingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_sign_in_screen_reads_in_greek(): void
    {
        $response = $this->withSession(['locale' => 'el_GR'])->get(route('auth.login.new'));

        $response->assertOk();
        $response->assertSee('Καλώς ήρθατε ξανά', escape: false);
        $response->assertSee('Κωδικός', escape: false);
        $response->assertSee('Ξεχάσατε τον κωδικό σας;', escape: false);
        $response->assertSee('Ελληνικά', escape: false);
        $response->assertDontSee('Sign in to', escape: false);
    }

    #[Test]
    public function a_rejected_form_says_why_in_greek(): void
    {
        $response = $this->withSession(['locale' => 'el_GR'])
            ->from(route('auth.login.new'))
            ->post(route('auth.login.create'), ['email' => '', 'password' => '']);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('είναι υποχρεωτικό', (string) session('errors')->first('email'));
    }

    #[Test]
    public function a_counted_phrase_takes_the_two_forms_greek_needs(): void
    {
        $this->app->setLocale('el_GR');

        $phrase = ':count person|:count people';

        $this->assertSame('0 άτομα', trans_choice($phrase, 0));
        $this->assertSame('1 άτομο', trans_choice($phrase, 1));
        $this->assertSame('2 άτομα', trans_choice($phrase, 2));
        $this->assertSame('5 άτομα', trans_choice($phrase, 5));
    }
}
