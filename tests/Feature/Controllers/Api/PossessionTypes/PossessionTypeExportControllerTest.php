<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api\PossessionTypes;

use App\Enums\PossessionFieldKindEnum;
use App\Enums\VaultColourEnum;
use App\Models\PossessionField;
use App\Models\PossessionFieldGroup;
use App\Models\PossessionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionTypeExportControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_exports_a_possession_type_as_a_file(): void
    {
        $user = User::factory()->create();
        $type = PossessionType::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Office equipment',
            'colour' => VaultColourEnum::Teal->value,
        ]);
        $purchase = PossessionFieldGroup::factory()->create(['possession_type_id' => $type->id, 'name' => 'Purchase']);
        PossessionField::factory()->inGroup($purchase)->create(['name' => 'Shop']);
        PossessionField::factory()->create([
            'possession_type_id' => $type->id,
            'name' => 'Condition',
            'kind' => PossessionFieldKindEnum::Select,
            'options' => ['New', 'Used'],
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/possession-types/{$type->uuid}/export");

        $response->assertOk();
        $response->assertJsonPath('format', 'possession_type');
        $response->assertJsonPath('format_version', 1);
        $response->assertJsonPath('possession_type.name', 'Office equipment');
        $response->assertJsonPath('possession_type.colour', VaultColourEnum::Teal->value);
        $response->assertJsonPath('possession_type.groups.0.name', 'Purchase');
        $response->assertJsonPath('possession_type.groups.0.fields.0.name', 'Shop');
        $response->assertJsonPath('possession_type.fields.0.name', 'Condition');
        $response->assertJsonPath('possession_type.fields.0.kind', 'select');
        $response->assertJsonPath('possession_type.fields.0.options', ['New', 'Used']);
    }

    #[Test]
    public function it_returns_not_found_for_a_possession_type_of_another_account(): void
    {
        $type = PossessionType::factory()->create();

        Sanctum::actingAs(User::factory()->create());

        $this->json('GET', "/api/v1/possession-types/{$type->uuid}/export")->assertNotFound();
    }
}
