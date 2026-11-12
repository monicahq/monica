<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api\PossessionTypes;

use App\Models\PossessionField;
use App\Models\PossessionFieldGroup;
use App\Models\PossessionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionFieldGroupControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_group(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('POST', "/api/v1/possession-types/{$type->uuid}/groups", ['name' => 'Purchase']);

        $response->assertCreated();
        $response->assertJsonPath('data.type', 'possession_type');
        $response->assertJsonPath('data.attributes.groups.0.name', 'Purchase');
        $this->assertDatabaseHas('possession_field_groups', [
            'possession_type_id' => $type->id,
            'name' => 'Purchase',
        ]);
    }

    #[Test]
    public function it_refuses_a_group_without_a_name(): void
    {
        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('POST', "/api/v1/possession-types/{$type->uuid}/groups", ['name' => '']);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name']);
        $this->assertDatabaseCount('possession_field_groups', 0);
    }

    #[Test]
    public function it_renames_a_group(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $group = PossessionFieldGroup::factory()->create(['possession_type_id' => $type->id, 'name' => 'Purchase']);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/possession-types/{$type->uuid}/groups/{$group->uuid}", ['name' => 'Warranty']);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.groups.0.name', 'Warranty');
        $this->assertSame('Warranty', $group->refresh()->name);
    }

    #[Test]
    public function it_refuses_to_rename_a_group_with_an_empty_name(): void
    {
        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $group = PossessionFieldGroup::factory()->create(['possession_type_id' => $type->id, 'name' => 'Purchase']);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/possession-types/{$type->uuid}/groups/{$group->uuid}", ['name' => '']);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name']);
        $this->assertSame('Purchase', $group->refresh()->name);
    }

    #[Test]
    public function it_deletes_a_group_and_leaves_its_fields_standing_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $group = PossessionFieldGroup::factory()->create(['possession_type_id' => $type->id]);
        $field = PossessionField::factory()->inGroup($group)->create(['name' => 'Shop']);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/possession-types/{$type->uuid}/groups/{$group->uuid}")->assertNoContent();

        $this->assertModelMissing($group);
        $this->assertNull($field->refresh()->possession_field_group_id);
    }

    #[Test]
    public function it_returns_not_found_for_a_group_of_another_type_of_the_same_account(): void
    {
        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $otherType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $group = PossessionFieldGroup::factory()->create(['possession_type_id' => $otherType->id, 'name' => 'Purchase']);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/possession-types/{$type->uuid}/groups/{$group->uuid}", ['name' => 'Warranty'])->assertNotFound();
        $this->json('DELETE', "/api/v1/possession-types/{$type->uuid}/groups/{$group->uuid}")->assertNotFound();
        $this->assertSame('Purchase', $group->refresh()->name);
    }

    #[Test]
    public function it_returns_not_found_for_a_type_of_another_account(): void
    {
        $type = PossessionType::factory()->create();
        $group = PossessionFieldGroup::factory()->create(['possession_type_id' => $type->id]);

        Sanctum::actingAs(User::factory()->create());

        $this->json('POST', "/api/v1/possession-types/{$type->uuid}/groups", ['name' => 'Purchase'])->assertNotFound();
        $this->json('PUT', "/api/v1/possession-types/{$type->uuid}/groups/{$group->uuid}", ['name' => 'Warranty'])->assertNotFound();
        $this->json('DELETE', "/api/v1/possession-types/{$type->uuid}/groups/{$group->uuid}")->assertNotFound();
        $this->assertModelExists($group);
    }
}
