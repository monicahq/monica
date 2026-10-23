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
    public function it_reads_as_the_name_it_was_given(): void
    {
        $passkey = Passkey::factory()->make(['name' => 'Dwight’s YubiKey']);

        $this->assertEquals('Dwight’s YubiKey', $passkey->displayName());
    }

    #[Test]
    public function it_reads_an_unnamed_passkey_as_its_authenticator(): void
    {
        $passkey = Passkey::factory()->make(['name' => '_']);
        $passkey->credential = ['aaguid' => 'ea9b8d66-4d01-1d21-3ce4-b6b48cb575d4'] + $passkey->credential;

        $this->assertEquals('Google Password Manager', $passkey->displayName());
    }

    #[Test]
    public function it_reads_an_unnamed_passkey_from_an_unknown_authenticator_as_this_device(): void
    {
        $passkey = Passkey::factory()->make(['name' => '_']);
        $passkey->credential = ['aaguid' => '00000000-0000-0000-0000-000000000000'] + $passkey->credential;

        $this->assertEquals('This device', $passkey->displayName());
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
