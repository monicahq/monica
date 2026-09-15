<?php

declare(strict_types=1);

namespace Tests\Feature\Localization;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HungarianRenderingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_sign_in_screen_reads_in_hungarian(): void
    {
        $response = $this->withSession(['locale' => 'hu_HU'])->get(route('auth.login.new'));

        $response->assertOk();
        $response->assertSee('Üdv újra itt', escape: false);
        $response->assertSee('Jelszó', escape: false);
        $response->assertSee('Elfelejtetted a jelszavad?', escape: false);
        $response->assertSee('Magyar', escape: false);
        $response->assertDontSee('Sign in to', escape: false);
    }

    #[Test]
    public function a_rejected_form_says_why_in_hungarian(): void
    {
        $response = $this->withSession(['locale' => 'hu_HU'])
            ->from(route('auth.login.new'))
            ->post(route('auth.login.create'), ['email' => '', 'password' => '']);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('megadása kötelező', (string) session('errors')->first('email'));
    }

    #[Test]
    public function a_counted_phrase_reads_the_same_at_every_number_in_hungarian(): void
    {
        $this->app->setLocale('hu_HU');

        $phrase = ':count person|:count people';

        $this->assertSame('0 személy', trans_choice($phrase, 0));
        $this->assertSame('1 személy', trans_choice($phrase, 1));
        $this->assertSame('2 személy', trans_choice($phrase, 2));
        $this->assertSame('5 személy', trans_choice($phrase, 5));
    }
}
