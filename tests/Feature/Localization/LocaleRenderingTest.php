<?php

declare(strict_types=1);

namespace Tests\Feature\Localization;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LocaleRenderingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_sign_in_screen_reads_in_the_language_of_the_session(): void
    {
        $response = $this->withSession(['locale' => 'pl_PL'])->get(route('auth.login.new'));

        $response->assertOk();
        $response->assertSee('Witaj ponownie', escape: false);
        $response->assertSee('Nie pamiętasz hasła?', escape: false);
        $response->assertDontSee('Sign in to', escape: false);
    }

    #[Test]
    public function a_rejected_form_says_why_in_the_language_of_the_session(): void
    {
        $response = $this->withSession(['locale' => 'pl_PL'])
            ->from(route('auth.login.new'))
            ->post(route('auth.login.create'), ['email' => '', 'password' => '']);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('wymagane', (string) session('errors')->first('email'));
    }

    #[Test]
    public function every_shipped_language_says_why_a_form_was_rejected_in_its_own_words(): void
    {
        $english = trans('validation.required', ['attribute' => 'email'], 'en');

        foreach (array_keys(config('localization.locales')) as $locale) {
            if ($locale === 'en') {
                continue;
            }

            $this->assertNotSame(
                $english,
                trans('validation.required', ['attribute' => 'email'], $locale),
                "{$locale} shows the English validation messages.",
            );
        }
    }
}
