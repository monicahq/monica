<?php

declare(strict_types=1);

namespace Tests\Feature\Localization;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IcelandicRenderingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_sign_in_screen_reads_in_icelandic(): void
    {
        $response = $this->withSession(['locale' => 'is_IS'])->get(route('auth.login.new'));

        $response->assertOk();
        $response->assertSee('Velkomin aftur', escape: false);
        $response->assertSee('Lykilorð', escape: false);
        $response->assertSee('Gleymdirðu lykilorðinu?', escape: false);
        $response->assertSee('Íslenska', escape: false);
        $response->assertDontSee('Sign in to', escape: false);
    }

    #[Test]
    public function a_rejected_form_says_why_in_icelandic(): void
    {
        $response = $this->withSession(['locale' => 'is_IS'])
            ->from(route('auth.login.new'))
            ->post(route('auth.login.create'), ['email' => '', 'password' => '']);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('er nauðsynlegt', (string) session('errors')->first('email'));
    }

    #[Test]
    public function a_counted_phrase_takes_the_two_forms_icelandic_needs(): void
    {
        $this->app->setLocale('is_IS');

        $phrase = ':count person|:count people';

        $this->assertSame('0 manneskjur', trans_choice($phrase, 0));
        $this->assertSame('1 manneskja', trans_choice($phrase, 1));
        $this->assertSame('2 manneskjur', trans_choice($phrase, 2));
        $this->assertSame('5 manneskjur', trans_choice($phrase, 5));
    }
}
