<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\SecurityKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SecurityKeyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_states_whether_it_has_ever_answered_a_challenge(): void
    {
        $fresh = SecurityKey::factory()->create();
        $used = SecurityKey::factory()->used()->create();

        $this->assertFalse($fresh->hasBeenUsed());
        $this->assertTrue($used->hasBeenUsed());
    }

    #[Test]
    public function it_names_the_authenticator_it_recognises_and_nothing_otherwise(): void
    {
        $recognised = SecurityKey::factory()->make();
        $recognised->credential = [...$recognised->credential, 'aaguid' => 'ea9b8d66-4d01-1d21-3ce4-b6b48cb575d4'];
        $anonymous = SecurityKey::factory()->make();
        $anonymous->credential = [...$anonymous->credential, 'aaguid' => '00000000-0000-0000-0000-000000000000'];

        $this->assertEquals('Google Password Manager', $recognised->authenticator);
        $this->assertNull($anonymous->authenticator);
    }

    #[Test]
    public function it_resolves_a_key_of_the_signed_in_user(): void
    {
        $user = User::factory()->create();
        $key = SecurityKey::factory()->for($user)->create();
        Auth::login($user);

        $this->assertEquals($key->id, new SecurityKey()->resolveRouteBinding((string) $key->id)?->id);
    }

    #[Test]
    public function it_resolves_nothing_for_a_key_belonging_to_somebody_else(): void
    {
        $user = User::factory()->create();
        $somebodyElse = SecurityKey::factory()->create();
        Auth::login($user);

        $this->assertNull(new SecurityKey()->resolveRouteBinding((string) $somebodyElse->id));
    }

    #[Test]
    public function it_resolves_nothing_for_a_guest(): void
    {
        $key = SecurityKey::factory()->create();

        $this->assertNull(new SecurityKey()->resolveRouteBinding((string) $key->id));
    }
}
