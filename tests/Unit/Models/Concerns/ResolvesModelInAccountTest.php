<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns;

use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ResolvesModelInAccountTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_finds_a_record_of_the_account_by_its_route_key(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin Scranton',
        ]);

        $this->actingAs($user);

        $this->assertTrue($vault->is(new Vault()->resolveRouteBinding($vault->uuid)));
    }

    #[Test]
    public function it_does_not_find_a_record_of_another_account(): void
    {
        $user = User::factory()->create();
        $somebodyElse = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $somebodyElse->account_id,
            'name' => 'Dunder Mifflin Utica',
        ]);

        $this->actingAs($user);

        $this->assertNull(new Vault()->resolveRouteBinding($vault->uuid));
    }

    #[Test]
    public function it_accepts_the_primary_key_as_a_second_spelling_of_the_address(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user);

        $this->assertTrue($vault->is(new Vault()->resolveRouteBinding((string) $vault->id)));
    }

    #[Test]
    public function it_does_not_find_a_record_of_another_account_by_its_primary_key(): void
    {
        $user = User::factory()->create();
        $somebodyElse = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $somebodyElse->account_id]);

        $this->actingAs($user);

        $this->assertNull(new Vault()->resolveRouteBinding((string) $vault->id));
    }

    #[Test]
    public function it_finds_nothing_when_nobody_is_signed_in(): void
    {
        $vault = Vault::factory()->create();

        $this->assertNull(new Vault()->resolveRouteBinding($vault->uuid));
    }
}
