<?php

declare(strict_types=1);

namespace Tests\Feature\Localization;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LithuanianRenderingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_sign_in_screen_reads_in_lithuanian(): void
    {
        $response = $this->withSession(['locale' => 'lt_LT'])->get(route('auth.login.new'));

        $response->assertOk();
        $response->assertSee('Sveiki sugrįžę', escape: false);
        $response->assertSee('Slaptažodis', escape: false);
        $response->assertSee('Užmiršote slaptažodį?', escape: false);
        $response->assertSee('Lietuvių', escape: false);
        $response->assertDontSee('Sign in to', escape: false);
    }

    #[Test]
    public function a_rejected_form_says_why_in_lithuanian(): void
    {
        $response = $this->withSession(['locale' => 'lt_LT'])
            ->from(route('auth.login.new'))
            ->post(route('auth.login.create'), ['email' => '', 'password' => '']);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('yra būtinas', (string) session('errors')->first('email'));
    }

    #[Test]
    public function a_counted_phrase_takes_the_three_forms_lithuanian_needs(): void
    {
        $this->app->setLocale('lt_LT');

        $phrase = ':count person|:count people';

        $this->assertSame('1 žmogus', trans_choice($phrase, 1));
        $this->assertSame('2 žmonės', trans_choice($phrase, 2));
        $this->assertSame('11 žmonių', trans_choice($phrase, 11));
        $this->assertSame('21 žmogus', trans_choice($phrase, 21));
    }
}
