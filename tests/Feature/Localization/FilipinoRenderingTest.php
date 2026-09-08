<?php

declare(strict_types=1);

namespace Tests\Feature\Localization;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FilipinoRenderingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_sign_in_screen_reads_in_filipino(): void
    {
        $response = $this->withSession(['locale' => 'fil_PH'])->get(route('auth.login.new'));

        $response->assertOk();
        $response->assertSee('Maligayang pagbabalik', escape: false);
        $response->assertSee('Mag-sign in', escape: false);
        $response->assertSee('Nakalimutan ang password mo?', escape: false);
        $response->assertSee('Filipino', escape: false);
        $response->assertDontSee('Sign in to', escape: false);
    }

    #[Test]
    public function a_rejected_form_says_why_in_filipino(): void
    {
        $response = $this->withSession(['locale' => 'fil_PH'])
            ->from(route('auth.login.new'))
            ->post(route('auth.login.create'), ['email' => '', 'password' => '']);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('Kailangan ang', (string) session('errors')->first('email'));
    }

    #[Test]
    public function a_counted_phrase_reads_the_same_at_every_number_in_filipino(): void
    {
        $this->app->setLocale('fil_PH');

        $phrase = ':count person|:count people';

        $this->assertSame('0 tao', trans_choice($phrase, 0));
        $this->assertSame('1 tao', trans_choice($phrase, 1));
        $this->assertSame('2 tao', trans_choice($phrase, 2));
        $this->assertSame('5 tao', trans_choice($phrase, 5));
    }
}
