<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\LifeEvent;
use App\Models\LifeEventType;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LifeEventTypePolicyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_allows_deleting_a_type_the_user_made(): void
    {
        $user = User::factory()->create();
        $type = LifeEventType::factory()->create(['account_id' => $user->account_id]);

        $this->assertTrue(Gate::forUser($user)->allows('delete', $type));
    }

    #[Test]
    public function it_refuses_deleting_a_type_the_product_supplied(): void
    {
        $user = User::factory()->create();
        $type = LifeEventType::factory()->supplied()->create(['account_id' => $user->account_id]);

        $response = Gate::forUser($user)->inspect('delete', $type);

        $this->assertTrue($response->denied());
        $this->assertStringContainsString('came with your account and stays', (string) $response->message());
    }

    #[Test]
    public function it_refuses_deleting_a_type_a_life_event_was_recorded_with(): void
    {
        $user = User::factory()->create();
        $lifeEvent = LifeEvent::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);

        $response = Gate::forUser($user)->inspect('delete', $lifeEvent->lifeEventType);

        $this->assertTrue($response->denied());
        $this->assertStringContainsString('Life events were recorded with this type', (string) $response->message());
    }

    #[Test]
    public function it_refuses_when_nobody_is_signed_in(): void
    {
        $user = User::factory()->create();
        $type = LifeEventType::factory()->create(['account_id' => $user->account_id]);

        $this->assertFalse(Gate::forUser(null)->allows('delete', $type));
    }
}
