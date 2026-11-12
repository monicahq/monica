<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\Possession;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionFeaturedControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_features_a_possession(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dundie = Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Dundie award']);

        $this->actingAs($user)->put(route('possessions.featured.update', [$vault, $dundie]), ['is_featured' => '1'])
            ->assertRedirect(route('possessions.show', [$vault, $dundie]))
            ->assertSessionHas('status');

        $this->assertTrue($dundie->refresh()->is_featured);
    }

    #[Test]
    public function it_stops_featuring_a_possession(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dundie = Possession::factory()->featured(1)->create(['vault_id' => $vault->id, 'name' => 'Dundie award']);

        $this->actingAs($user)->put(route('possessions.featured.update', [$vault, $dundie]), ['is_featured' => '0'])
            ->assertSessionHas('status');

        $this->assertFalse($dundie->refresh()->is_featured);
    }

    #[Test]
    public function it_refuses_a_request_that_does_not_say_which_way(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dundie = Possession::factory()->create(['vault_id' => $vault->id]);

        $this->actingAs($user)->put(route('possessions.featured.update', [$vault, $dundie]))
            ->assertSessionHasErrors('is_featured');
    }
}
