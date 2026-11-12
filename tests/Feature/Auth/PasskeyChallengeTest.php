<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Passkey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

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
    public function it_offers_creation_options_in_the_shape_the_browser_library_reads(): void
    {
        $user = User::factory()->create();

        $response = $this->withConfirmedPassword()->actingAs($user)->getJson(route('passkey.registration-options'));

        $response->assertOk();
        $response->assertJsonStructure([
            'options' => ['challenge', 'rp', 'user' => ['id', 'name'], 'pubKeyCredParams', 'authenticatorSelection'],
        ]);

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $response->json('options.challenge'));
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $response->json('options.user.id'));
    }

    #[Test]
    public function it_tells_the_device_which_credentials_this_account_already_holds(): void
    {
        $user = User::factory()->create();
        Passkey::factory()->create(['user_id' => $user->id]);

        $response = $this->withConfirmedPassword()->actingAs($user)->getJson(route('passkey.registration-options'));

        $this->assertCount(1, $response->json('options.excludeCredentials'));
    }

    #[Test]
    public function adding_a_passkey_needs_the_password_confirmed_when_there_is_one(): void
    {
        $user = User::factory()->create(['password' => Hash::make('dundermifflin')]);

        $this->actingAs($user)->getJson(route('passkey.registration-options'))->assertStatus(423);
    }

    #[Test]
    public function adding_a_passkey_needs_a_confirmation_from_an_account_with_no_password(): void
    {
        $user = User::factory()->create(['password' => null]);

        $this->actingAs($user)->getJson(route('passkey.registration-options'))->assertStatus(423);
    }
}
