<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api\PossessionTypes;

use App\Enums\PossessionFieldKindEnum;
use App\Models\PossessionField;
use App\Models\PossessionFieldGroup;
use App\Models\PossessionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionFieldControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_standalone_field(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('POST', "/api/v1/possession-types/{$type->uuid}/fields", [
            'name' => 'Serial number',
            'kind' => 'text',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.attributes.fields.0.name', 'Serial number');
        $response->assertJsonPath('data.attributes.fields.0.kind', 'text');
        $response->assertJsonPath('data.attributes.fields.0.group_id', null);
        $this->assertDatabaseHas('possession_fields', [
            'possession_type_id' => $type->id,
            'possession_field_group_id' => null,
            'name' => 'Serial number',
        ]);
    }

    #[Test]
    public function it_creates_a_select_field_in_a_group_with_its_options(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $group = PossessionFieldGroup::factory()->create(['possession_type_id' => $type->id, 'name' => 'Purchase']);

        Sanctum::actingAs($user);

        $response = $this->json('POST', "/api/v1/possession-types/{$type->uuid}/fields", [
            'name' => 'Condition',
            'kind' => 'select',
            'options' => ['New', 'Used'],
            'group_id' => $group->uuid,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.attributes.groups.0.fields.0.name', 'Condition');
        $response->assertJsonPath('data.attributes.groups.0.fields.0.options', ['New', 'Used']);
        $response->assertJsonPath('data.attributes.groups.0.fields.0.group_id', $group->uuid);
    }

    #[Test]
    public function it_refuses_a_field_with_an_empty_name_or_an_unknown_kind(): void
    {
        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('POST', "/api/v1/possession-types/{$type->uuid}/fields", [
            'name' => '',
            'kind' => 'hologram',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name', 'kind']);
        $this->assertDatabaseCount('possession_fields', 0);
    }

    #[Test]
    public function it_refuses_a_field_created_in_a_group_of_another_type(): void
    {
        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $otherType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $group = PossessionFieldGroup::factory()->create(['possession_type_id' => $otherType->id]);

        Sanctum::actingAs($user);

        $response = $this->json('POST', "/api/v1/possession-types/{$type->uuid}/fields", [
            'name' => 'Shop',
            'kind' => 'text',
            'group_id' => $group->uuid,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['group_id']);
        $this->assertDatabaseCount('possession_fields', 0);
    }

    #[Test]
    public function it_keeps_what_is_left_out_of_an_update(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $field = PossessionField::factory()->create([
            'possession_type_id' => $type->id,
            'name' => 'Condition',
            'kind' => PossessionFieldKindEnum::Select,
            'options' => ['New', 'Used'],
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/possession-types/{$type->uuid}/fields/{$field->uuid}", ['name' => 'State']);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.fields.0.name', 'State');

        $field->refresh();
        $this->assertSame('State', $field->name);
        $this->assertSame(PossessionFieldKindEnum::Select, $field->kind);
        $this->assertSame(['New', 'Used'], $field->options);
    }

    #[Test]
    public function it_clears_a_field_sent_as_null(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $field = PossessionField::factory()->create([
            'possession_type_id' => $type->id,
            'name' => 'Condition',
            'kind' => PossessionFieldKindEnum::Select,
            'options' => ['New', 'Used'],
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/possession-types/{$type->uuid}/fields/{$field->uuid}", ['options' => null]);

        $response->assertOk();
        $this->assertEmpty($field->refresh()->options);
    }

    #[Test]
    public function it_refuses_a_name_or_a_kind_sent_as_null(): void
    {
        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $field = PossessionField::factory()->create(['possession_type_id' => $type->id, 'name' => 'Condition']);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/possession-types/{$type->uuid}/fields/{$field->uuid}", ['name' => null, 'kind' => null]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name', 'kind']);
        $this->assertSame('Condition', $field->refresh()->name);
    }

    #[Test]
    public function it_refuses_to_update_a_field_with_an_unknown_kind(): void
    {
        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $field = PossessionField::factory()->create(['possession_type_id' => $type->id, 'name' => 'Shop']);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/possession-types/{$type->uuid}/fields/{$field->uuid}", [
            'name' => 'Shop',
            'kind' => 'hologram',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['kind']);
    }

    #[Test]
    public function it_deletes_a_field(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $field = PossessionField::factory()->create(['possession_type_id' => $type->id]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/possession-types/{$type->uuid}/fields/{$field->uuid}")->assertNoContent();

        $this->assertModelMissing($field);
    }

    #[Test]
    public function it_returns_not_found_for_a_field_of_another_type_of_the_same_account(): void
    {
        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $otherType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $field = PossessionField::factory()->create(['possession_type_id' => $otherType->id, 'name' => 'Shop']);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/possession-types/{$type->uuid}/fields/{$field->uuid}", ['name' => 'Store', 'kind' => 'text'])->assertNotFound();
        $this->json('DELETE', "/api/v1/possession-types/{$type->uuid}/fields/{$field->uuid}")->assertNotFound();
        $this->assertSame('Shop', $field->refresh()->name);
    }

    #[Test]
    public function it_returns_not_found_for_a_type_of_another_account(): void
    {
        $type = PossessionType::factory()->create();
        $field = PossessionField::factory()->create(['possession_type_id' => $type->id]);

        Sanctum::actingAs(User::factory()->create());

        $this->json('POST', "/api/v1/possession-types/{$type->uuid}/fields", ['name' => 'Shop', 'kind' => 'text'])->assertNotFound();
        $this->json('PUT', "/api/v1/possession-types/{$type->uuid}/fields/{$field->uuid}", ['name' => 'Store', 'kind' => 'text'])->assertNotFound();
        $this->json('DELETE', "/api/v1/possession-types/{$type->uuid}/fields/{$field->uuid}")->assertNotFound();
        $this->assertModelExists($field);
    }
}
