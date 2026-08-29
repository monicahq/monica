<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\WebauthnKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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
        $response = $this->postJson(route('webauthn.auth.options'));

        $response->assertOk();
        $response->assertJsonStructure(['publicKey' => ['challenge', 'allowCredentials', 'userVerification']]);
    }

    #[Test]
    public function it_names_no_credential_so_nothing_reveals_who_has_an_account(): void
    {
        $response = $this->postJson(route('webauthn.auth.options'));

        $this->assertSame([], $response->json('publicKey.allowCredentials'));
    }

    #[Test]
    public function it_requires_the_device_to_verify_its_owner(): void
    {
        $response = $this->postJson(route('webauthn.auth.options'));

        $this->assertSame('required', $response->json('publicKey.userVerification'));
    }

    #[Test]
    public function it_bounds_how_long_the_challenge_stands(): void
    {
        $response = $this->postJson(route('webauthn.auth.options'));

        $this->assertSame(60000, $response->json('publicKey.timeout'));
    }

    #[Test]
    public function it_encodes_the_challenge_the_way_the_browser_library_reads_it(): void
    {
        $challenge = $this->postJson(route('webauthn.auth.options'))->json('publicKey.challenge');

        $this->assertIsString($challenge);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $challenge);
    }

    #[Test]
    public function it_offers_creation_options_in_the_shape_the_browser_library_reads(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('webauthn.store.options'));

        $response->assertOk();
        $response->assertJsonStructure([
            'publicKey' => ['challenge', 'rp', 'user' => ['id', 'name'], 'pubKeyCredParams', 'authenticatorSelection'],
        ]);

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $response->json('publicKey.challenge'));
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $response->json('publicKey.user.id'));
    }

    #[Test]
    public function it_asks_for_a_discoverable_credential_that_verifies_its_owner(): void
    {
        $user = User::factory()->create();

        $selection = $this->actingAs($user)
            ->postJson(route('webauthn.store.options'))
            ->json('publicKey.authenticatorSelection');

        $this->assertSame('required', $selection['residentKey']);
        $this->assertSame('required', $selection['userVerification']);
    }

    #[Test]
    public function it_tells_the_device_which_credentials_this_account_already_holds(): void
    {
        $user = User::factory()->create();
        WebauthnKey::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->postJson(route('webauthn.store.options'));

        $this->assertCount(1, $response->json('publicKey.excludeCredentials'));
    }

    #[Test]
    public function the_configured_cache_store_carries_a_challenge_between_two_requests(): void
    {
        $this->postJson(route('webauthn.auth.options'))->assertOk();
        Cache::put('webauthn.publicKeyRequest|probe', 'a-challenge', 60);

        $this->postJson(route('webauthn.auth.options'))->assertOk();

        $this->assertEquals('a-challenge', Cache::pull('webauthn.publicKeyRequest|probe'));
    }
}
