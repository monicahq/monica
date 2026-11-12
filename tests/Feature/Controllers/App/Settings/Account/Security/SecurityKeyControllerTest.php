<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Security;

use App\Models\SecurityKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Illuminate\Testing\TestResponse;
use ParagonIE\ConstantTime\Base64UrlSafe;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\WebAuthn\SoftwareAuthenticator;
use Tests\TestCase;

class SecurityKeyControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_adds_the_first_key_and_shows_the_recovery_codes_next(): void
    {
        Queue::fake();
        Notification::fake();
        URL::forceScheme('https');
        $user = User::factory()->create();

        $response = $this->addKey($user, new SoftwareAuthenticator);

        $response->assertCreated();
        $response->assertJsonPath('redirect', route('settings.recoveryCodes.show'));
        $response->assertSessionHas('recoveryCodes');
        $this->assertTrue($user->refresh()->usesTwoFactorAuthentication());
    }

    #[Test]
    public function it_adds_another_key_and_goes_back_to_the_security_screen(): void
    {
        Queue::fake();
        Notification::fake();
        URL::forceScheme('https');
        $user = User::factory()->twoFactor()->create();

        $response = $this->addKey($user, new SoftwareAuthenticator);

        $response->assertCreated();
        $response->assertJsonPath('redirect', route('settings.security.index'));
        $response->assertSessionHas('status', 'Your security key is added.');
    }

    #[Test]
    public function it_says_why_a_key_was_refused(): void
    {
        URL::forceScheme('https');
        $user = User::factory()->create();
        $authenticator = new SoftwareAuthenticator;

        $this->withConfirmedPassword()->actingAs($user)->getJson(route('settings.securityKeyOptions.show'))->assertOk();

        $response = $this->postJson(route('settings.securityKeys.create'), [
            'name' => '_',
            'credential' => $authenticator->registration(random_bytes(32)),
        ]);

        $response->assertUnprocessable();
        $response->assertJsonPath('message', 'The security key could not be added. Please try again.');
        $this->assertDatabaseCount('security_keys', 0);
    }

    #[Test]
    public function it_says_it_took_too_long_when_no_key_was_asked_for(): void
    {
        URL::forceScheme('https');
        $authenticator = new SoftwareAuthenticator;

        $response = $this->withConfirmedPassword()->actingAs(User::factory()->create())
            ->postJson(route('settings.securityKeys.create'), [
                'name' => '_',
                'credential' => $authenticator->registration(random_bytes(32)),
            ]);

        $response->assertUnprocessable();
        $response->assertJsonPath('message', 'That took too long. Please try again.');
    }

    #[Test]
    public function it_refuses_a_key_the_browser_did_not_describe(): void
    {
        URL::forceScheme('https');

        $response = $this->withConfirmedPassword()->actingAs(User::factory()->create())
            ->post(route('settings.securityKeys.create'), [
                'name' => '_',
                'credential' => ['id' => 'abc', 'rawId' => 'abc', 'type' => 'password', 'response' => ['x' => 'y']],
            ]);

        $response->assertSessionHasErrors('credential.type');
    }

    #[Test]
    public function it_removes_one_key_of_several_and_keeps_two_factor_on(): void
    {
        Queue::fake();
        Notification::fake();
        $user = User::factory()->twoFactor()->create();
        $key = SecurityKey::factory()->for($user)->create();

        $response = $this->withConfirmedPassword()->actingAs($user)
            ->delete(route('settings.securityKeys.destroy', $key));

        $response->assertSessionHas('status', 'Your security key is removed.');
    }

    #[Test]
    public function it_renames_a_key(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $key = SecurityKey::factory()->for($user)->create();

        $response = $this->withConfirmedPassword()->actingAs($user)
            ->put(route('settings.securityKeys.update', $key), ['name' => 'Dundie key']);

        $response->assertRedirect(route('settings.security.index'));
        $response->assertSessionHas('status', 'Your security key is renamed.');
        $this->assertEquals('Dundie key', $key->refresh()->name);
    }

    #[Test]
    public function it_removes_the_last_key_and_says_two_factor_authentication_is_off(): void
    {
        Queue::fake();
        Notification::fake();
        $user = User::factory()->create(['two_factor_recovery_codes' => ['scranton-1']]);
        $key = SecurityKey::factory()->for($user)->create();

        $response = $this->withConfirmedPassword()->actingAs($user)
            ->delete(route('settings.securityKeys.destroy', $key));

        $response->assertRedirect(route('settings.security.index'));
        $response->assertSessionHas('status', 'Two-factor authentication is off.');
        $this->assertModelMissing($key);
    }

    private function addKey(User $user, SoftwareAuthenticator $authenticator): TestResponse
    {
        $this->withConfirmedPassword()->actingAs($user);

        $challenge = $this->getJson(route('settings.securityKeyOptions.show'))->json('options.challenge');

        return $this->postJson(route('settings.securityKeys.create'), [
            'name' => '_',
            'credential' => $authenticator->registration(Base64UrlSafe::decodeNoPadding($challenge)),
        ]);
    }
}
