<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\EntityType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EntityTypePolicyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_allows_deleting_a_type_holding_nothing(): void
    {
        $user = User::factory()->create();
        $type = EntityType::factory()->create(['account_id' => $user->account_id, 'entity_count' => 0]);

        $this->assertTrue(Gate::forUser($user)->allows('delete', $type));
    }

    #[Test]
    public function it_refuses_deleting_a_type_holding_records(): void
    {
        $user = User::factory()->create();
        $type = EntityType::factory()->create(['account_id' => $user->account_id, 'entity_count' => 7]);

        $response = Gate::forUser($user)->inspect('delete', $type);

        $this->assertTrue($response->denied());
        $this->assertStringContainsString('still holds records and stays', (string) $response->message());
    }

    #[Test]
    public function it_refuses_when_nobody_is_signed_in(): void
    {
        $user = User::factory()->create();
        $type = EntityType::factory()->create(['account_id' => $user->account_id]);

        $this->assertFalse(Gate::forUser(null)->allows('delete', $type));
    }
}
