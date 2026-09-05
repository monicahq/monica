<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Passkey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasskeyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_a_user(): void
    {
        $passkey = Passkey::factory()->create();

        $this->assertTrue($passkey->user()->exists());
        $this->assertInstanceOf(User::class, $passkey->user);
    }

    #[Test]
    public function it_states_that_it_has_never_been_used(): void
    {
        $passkey = Passkey::factory()->create();

        $this->assertFalse($passkey->hasBeenUsed());
        $this->assertNull($passkey->last_used_at);
    }

    #[Test]
    public function it_states_that_it_has_been_used(): void
    {
        $passkey = Passkey::factory()->used()->create();

        $this->assertTrue($passkey->hasBeenUsed());
        $this->assertNotNull($passkey->last_used_at);
    }

    #[Test]
    public function it_keeps_the_credential_as_a_structure_rather_than_a_string(): void
    {
        $passkey = Passkey::factory()->create();

        $this->assertIsArray($passkey->credential);
        $this->assertArrayHasKey('publicKeyCredentialId', $passkey->credential);
    }

    #[Test]
    public function it_resolves_a_passkey_of_the_signed_in_user(): void
    {
        $user = User::factory()->create();
        $passkey = Passkey::factory()->create(['user_id' => $user->id]);
        Auth::login($user);

        $this->assertEquals($passkey->id, new Passkey()->resolveRouteBinding((string) $passkey->id)?->id);
    }

    #[Test]
    public function it_resolves_nothing_for_a_passkey_belonging_to_somebody_else(): void
    {
        $user = User::factory()->create();
        $somebodyElse = Passkey::factory()->create();
        Auth::login($user);

        $this->assertNull(new Passkey()->resolveRouteBinding((string) $somebodyElse->id));
    }

    #[Test]
    public function it_resolves_nothing_for_a_guest(): void
    {
        $passkey = Passkey::factory()->create();

        $this->assertNull(new Passkey()->resolveRouteBinding((string) $passkey->id));
    }
}
