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
    public function a_rejected_form_says_why_in_the_language_of_the_session(): void
    {
        $response = $this->withSession(['locale' => 'pl_PL'])
            ->from(route('auth.login.new'))
            ->post(route('auth.login.create'), ['email' => '', 'password' => '']);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('wymagane', (string) session('errors')->first('email'));
    }
}
