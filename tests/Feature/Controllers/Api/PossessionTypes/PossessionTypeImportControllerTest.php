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

class PossessionTypeImportControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_imports_what_an_export_wrote_as_a_second_type(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Office equipment']);
        $purchase = PossessionFieldGroup::factory()->create(['possession_type_id' => $type->id, 'name' => 'Purchase']);
        PossessionField::factory()->inGroup($purchase)->create(['name' => 'Shop']);

        Sanctum::actingAs($user);

        $file = $this->json('GET', "/api/v1/possession-types/{$type->uuid}/export")->getContent();

        $response = $this->json('POST', '/api/v1/possession-types/import', ['file' => $file]);

        $response->assertCreated();
        $response->assertJsonPath('data.type', 'possession_type');
        $response->assertJsonPath('data.attributes.name', 'Office equipment');
        $response->assertJsonPath('data.attributes.groups.0.name', 'Purchase');
        $response->assertJsonPath('data.attributes.groups.0.fields.0.name', 'Shop');
        $this->assertNotSame($type->uuid, $response->json('data.id'));
        $this->assertDatabaseCount('possession_types', 2);
    }

    #[Test]
    public function it_refuses_a_file_it_cannot_read(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->json('POST', '/api/v1/possession-types/import', ['file' => 'Dunder Mifflin']);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['file']);
        $this->assertDatabaseCount('possession_types', 0);
    }

    #[Test]
    public function it_refuses_a_request_without_a_file(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->json('POST', '/api/v1/possession-types/import', []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['file']);
    }
}
