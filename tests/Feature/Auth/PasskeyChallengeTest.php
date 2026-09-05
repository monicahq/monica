<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Passkey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The challenge a passkey sign-in answers. Nothing here signs anybody in: it
 * proves the options the server offers are the ones the specification needs,
 * because everything downstream depends on their shape.
 */
class PasskeyChallengeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_issues_a_challenge_without_being_told_who_is_signing_in(): void
    {
        $response = $this->getJson(route('passkey.login-options'));

        $response->assertOk();
        $response->assertJsonStructure(['options' => ['challenge', 'allowCredentials', 'userVerification']]);
    }

    #[Test]
    public function it_names_no_credential_so_nothing_reveals_who_has_an_account(): void
    {
        $response = $this->getJson(route('passkey.login-options'));

        $this->assertSame([], $response->json('options.allowCredentials'));
    }

    #[Test]
    public function it_requires_the_device_to_verify_its_owner(): void
    {
        $response = $this->getJson(route('passkey.login-options'));

        $this->assertSame('required', $response->json('options.userVerification'));
    }

    #[Test]
    public function it_bounds_how_long_the_challenge_stands(): void
    {
        $response = $this->getJson(route('passkey.login-options'));

        $this->assertSame(60000, $response->json('options.timeout'));
    }

    #[Test]
    public function it_encodes_the_challenge_the_way_the_browser_library_reads_it(): void
    {
        $challenge = $this->getJson(route('passkey.login-options'))->json('options.challenge');

        $this->assertIsString($challenge);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $challenge);
    }

    #[Test]
    public function it_offers_creation_options_in_the_shape_the_browser_library_reads(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('passkey.registration-options'));

        $response->assertOk();
        $response->assertJsonStructure([
            'options' => ['challenge', 'rp', 'user' => ['id', 'name'], 'pubKeyCredParams', 'authenticatorSelection'],
        ]);

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $response->json('options.challenge'));
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $response->json('options.user.id'));
    }

    #[Test]
    public function it_asks_for_a_discoverable_credential_that_verifies_its_owner(): void
    {
        $user = User::factory()->create();

        $selection = $this->actingAs($user)
            ->getJson(route('passkey.registration-options'))
            ->json('options.authenticatorSelection');

        $this->assertSame('required', $selection['residentKey']);
        $this->assertSame('required', $selection['userVerification']);
    }

    #[Test]
    public function it_tells_the_device_which_credentials_this_account_already_holds(): void
    {
        $user = User::factory()->create();
        Passkey::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->getJson(route('passkey.registration-options'));

        $this->assertCount(1, $response->json('options.excludeCredentials'));
    }

    #[Test]
    public function the_challenge_is_kept_in_the_session_for_the_request_that_answers_it(): void
    {
        $this->getJson(route('passkey.login-options'))->assertOk();

        $this->assertNotNull(session('passkey.verification_options'));
    }

    #[Test]
    public function adding_a_passkey_needs_no_password_to_be_confirmed_first(): void
    {
        $user = User::factory()->create(['password' => null]);

        $this->actingAs($user)->getJson(route('passkey.registration-options'))->assertOk();
    }
}
