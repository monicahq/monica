<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\RelationshipTypes;

use App\Models\RelationshipCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RelationshipCategoryPositionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_category(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $first = RelationshipCategory::factory()->create(['account_id' => $user->account_id, 'position' => 1]);
        $second = RelationshipCategory::factory()->create(['account_id' => $user->account_id, 'position' => 2]);

        $this->actingAs($user)->put(route('settings.relationshipCategoryPosition.update', $second), ['position' => 1])
            ->assertRedirect(route('settings.relationshipTypes.index'))
            ->assertSessionHas('status');

        $this->assertEquals(1, $second->refresh()->position);
        $this->assertEquals(2, $first->refresh()->position);
    }

    #[Test]
    public function it_refuses_a_position_that_is_not_a_number(): void
    {
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->put(route('settings.relationshipCategoryPosition.update', $category), ['position' => 'first'])
            ->assertSessionHasErrors(['position']);
    }

    #[Test]
    public function a_category_of_another_account_is_not_found(): void
    {
        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $dwight->account_id]);

        $this->actingAs($michael)->put(route('settings.relationshipCategoryPosition.update', $category), ['position' => 1])
            ->assertStatus(404);
    }
}
