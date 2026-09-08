<?php

declare(strict_types=1);

namespace Tests\Feature\Localization;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SinhalaRenderingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_sign_in_screen_reads_in_sinhala(): void
    {
        $response = $this->withSession(['locale' => 'si_LK'])->get(route('auth.login.new'));

        $response->assertOk();
        $response->assertSee('නැවත සාදරයෙන් පිළිගනිමු', escape: false);
        $response->assertSee('පිවිසෙන්න', escape: false);
        $response->assertSee('මුරපදය අමතක වුණාද?', escape: false);
        $response->assertSee('සිංහල', escape: false);
        $response->assertDontSee('Sign in to', escape: false);
    }

    #[Test]
    public function a_rejected_form_says_why_in_sinhala(): void
    {
        $response = $this->withSession(['locale' => 'si_LK'])
            ->from(route('auth.login.new'))
            ->post(route('auth.login.create'), ['email' => '', 'password' => '']);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('අවශ්‍යය', (string) session('errors')->first('email'));
    }

    #[Test]
    public function a_counted_phrase_reads_the_same_at_every_number_in_sinhala(): void
    {
        $this->app->setLocale('si_LK');

        $phrase = ':count person|:count people';

        $this->assertSame('පුද්ගලයන් 0', trans_choice($phrase, 0));
        $this->assertSame('පුද්ගලයන් 1', trans_choice($phrase, 1));
        $this->assertSame('පුද්ගලයන් 2', trans_choice($phrase, 2));
        $this->assertSame('පුද්ගලයන් 5', trans_choice($phrase, 5));
    }
}
