<?php

declare(strict_types=1);

namespace Tests\Feature\Localization;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IrishRenderingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_sign_in_screen_reads_in_irish(): void
    {
        $response = $this->withSession(['locale' => 'ga_IE'])->get(route('auth.login.new'));

        $response->assertOk();
        $response->assertSee('Fáilte ar ais', escape: false);
        $response->assertSee('Pasfhocal', escape: false);
        $response->assertSee('Ar dhearmad tú do phasfhocal?', escape: false);
        $response->assertSee('Gaeilge', escape: false);
        $response->assertDontSee('Sign in to', escape: false);
    }

    #[Test]
    public function a_rejected_form_says_why_in_irish(): void
    {
        $response = $this->withSession(['locale' => 'ga_IE'])
            ->from(route('auth.login.new'))
            ->post(route('auth.login.create'), ['email' => '', 'password' => '']);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('riachtanach', (string) session('errors')->first('email'));
    }

    #[Test]
    public function a_counted_phrase_takes_the_three_forms_irish_needs(): void
    {
        $this->app->setLocale('ga_IE');

        $phrase = ':count person|:count people';

        $this->assertSame('1 duine', trans_choice($phrase, 1));
        $this->assertSame('2 dhuine', trans_choice($phrase, 2));
        $this->assertSame('3 duine', trans_choice($phrase, 3));
        $this->assertSame('0 duine', trans_choice($phrase, 0));
    }
}
