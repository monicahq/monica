<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegistrationScreenTest extends TestCase
{
    use RefreshDatabase;

    private function secure(): self
    {
        URL::forceScheme('https');

        return $this;
    }

    private function insecure(): self
    {
        URL::forceScheme('http');

        return $this;
    }

    #[Test]
    public function it_leads_with_a_passkey_over_a_secure_connection(): void
    {
        $response = $this->secure()->get(route('auth.register.new'));

        $response->assertStatus(200);
        $response->assertSee('Create a passkey');
        $response->assertSee('unlocked by your fingerprint, your face or your device PIN');
    }

    #[Test]
    public function it_offers_the_password_as_an_alternative_rather_than_an_equal(): void
    {
        $response = $this->secure()->get(route('auth.register.new'));

        $response->assertSee('Other options');
        $response->assertSee("mode === 'password'", escape: false);
    }

    #[Test]
    public function it_can_post_to_either_route(): void
    {
        $html = str_replace('\\', '', $this->secure()->get(route('auth.register.new'))->getContent());

        $this->assertStringContainsString(route('auth.registerPasskey.create'), $html);
        $this->assertStringContainsString(route('auth.register.create'), $html);
    }

    #[Test]
    public function it_embeds_no_uncompiled_blade_directive_in_an_attribute(): void
    {
        $html = $this->secure()->get(route('auth.register.new'))->getContent();

        $this->assertStringNotContainsString('@js(', $html);
    }

    #[Test]
    public function it_says_why_and_offers_only_the_password_over_an_insecure_connection(): void
    {
        $response = $this->insecure()->get(route('auth.register.new'));

        $response->assertStatus(200);
        $response->assertSee('Passkeys need a secure connection, so this account will be created with a password.');
        $response->assertDontSee('Other options');
    }

    #[Test]
    public function an_account_with_neither_credential_is_told_what_reaches_it(): void
    {
        Queue::fake();

        $user = User::factory()->create(['password' => null]);

        $this->secure()->actingAs($user)->get(route('auth.registerPasskey.new'))
            ->assertSee('Create a passkey');

        $this->actingAs($user)->get(route('settings.security.index'))
            ->assertSee('This account has no password.');
    }
}
