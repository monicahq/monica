<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\EntityTypes;

use App\Models\EntityType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EntityTypePositionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_type_and_renumbers_the_rest(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $animals = EntityType::factory()->create(['account_id' => $user->account_id, 'slug' => 'animals', 'position' => 1]);
        $companies = EntityType::factory()->create(['account_id' => $user->account_id, 'slug' => 'companies', 'position' => 2]);
        $cars = EntityType::factory()->create(['account_id' => $user->account_id, 'slug' => 'cars', 'position' => 3]);

        $response = $this->actingAs($user)->put(route('settings.entityTypePosition.update', $cars), [
            'position' => '1',
        ]);

        $response->assertRedirect(route('settings.entityTypes.index'));
        $response->assertSessionHas('status');

        $this->assertSame(1, $cars->refresh()->position);
        $this->assertSame(2, $animals->refresh()->position);
        $this->assertSame(3, $companies->refresh()->position);
    }

    #[Test]
    public function it_refuses_a_position_below_one(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $entityType = EntityType::factory()->create(['account_id' => $user->account_id, 'slug' => 'animals']);

        $this->actingAs($user)->put(route('settings.entityTypePosition.update', $entityType), ['position' => '0'])
            ->assertSessionHasErrors('position');
    }
}
