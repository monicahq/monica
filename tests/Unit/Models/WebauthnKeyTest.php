<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\WebauthnKey;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebauthnKeyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_a_user(): void
    {
        $passkey = WebauthnKey::factory()->create();

        $this->assertTrue($passkey->user()->exists());
        $this->assertInstanceOf(User::class, $passkey->user);
    }

    #[Test]
    public function it_has_no_last_use_when_it_is_created(): void
    {
        $passkey = WebauthnKey::factory()->create();

        $this->assertNull($passkey->used_at);
        $this->assertFalse($passkey->hasBeenUsed());
    }

    #[Test]
    public function it_casts_the_last_use_to_a_date(): void
    {
        $passkey = WebauthnKey::factory()->used()->create();

        $this->assertInstanceOf(CarbonImmutable::class, $passkey->used_at);
        $this->assertTrue($passkey->hasBeenUsed());
    }

    #[Test]
    public function it_keeps_the_fields_the_package_serialises_visible(): void
    {
        $passkey = WebauthnKey::factory()->create();

        $visible = $passkey->getVisible();

        $this->assertContains('used_at', $visible);
        $this->assertContains('name', $visible);
        $this->assertContains('type', $visible);
        $this->assertContains('transports', $visible);
    }

    #[Test]
    public function it_refuses_to_register_the_same_credential_twice(): void
    {
        $user = User::factory()->create();
        WebauthnKey::factory()->create(['user_id' => $user->id, 'credentialId' => 'the-same-credential']);

        $this->expectException(QueryException::class);

        WebauthnKey::factory()->create(['user_id' => $user->id, 'credentialId' => 'the-same-credential']);
    }

    #[Test]
    public function it_refuses_the_same_credential_even_for_a_different_account(): void
    {
        WebauthnKey::factory()->create(['credentialId' => 'the-same-credential']);

        $this->expectException(QueryException::class);

        WebauthnKey::factory()->create(['credentialId' => 'the-same-credential']);
    }

    #[Test]
    public function it_never_exposes_the_credential_material(): void
    {
        $passkey = WebauthnKey::factory()->create();

        $serialised = $passkey->toArray();

        $this->assertArrayNotHasKey('credentialPublicKey', $serialised);
        $this->assertArrayNotHasKey('credentialId', $serialised);
        $this->assertArrayNotHasKey('trustPath', $serialised);
        $this->assertArrayNotHasKey('counter', $serialised);
    }
}
