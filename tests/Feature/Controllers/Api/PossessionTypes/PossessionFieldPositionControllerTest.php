<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api\PossessionTypes;

use App\Models\PossessionField;
use App\Models\PossessionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionFieldPositionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_field_to_a_position(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id]);
        PossessionField::factory()->create(['possession_type_id' => $type->id, 'name' => 'Serial number', 'position' => 1]);
        $power = PossessionField::factory()->create(['possession_type_id' => $type->id, 'name' => 'Power', 'position' => 2]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/possession-types/{$type->uuid}/fields/{$power->uuid}/position", ['position' => 1]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.fields.0.name', 'Power');
        $response->assertJsonPath('data.attributes.fields.1.name', 'Serial number');
        $this->assertSame(1, $power->refresh()->position);
    }

    #[Test]
    public function it_refuses_a_position_below_one(): void
    {
        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $field = PossessionField::factory()->create(['possession_type_id' => $type->id]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/possession-types/{$type->uuid}/fields/{$field->uuid}/position", ['position' => 0]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['position']);
    }

    #[Test]
    public function it_returns_not_found_for_a_field_of_another_type_of_the_same_account(): void
    {
        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $otherType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $field = PossessionField::factory()->create(['possession_type_id' => $otherType->id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/possession-types/{$type->uuid}/fields/{$field->uuid}/position", ['position' => 1])->assertNotFound();
    }

    #[Test]
    public function it_returns_not_found_for_a_type_of_another_account(): void
    {
        $type = PossessionType::factory()->create();
        $field = PossessionField::factory()->create(['possession_type_id' => $type->id]);

        Sanctum::actingAs(User::factory()->create());

        $this->json('PUT', "/api/v1/possession-types/{$type->uuid}/fields/{$field->uuid}/position", ['position' => 1])->assertNotFound();
    }
}
