<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\PossessionTypes;

use App\Models\PossessionField;
use App\Models\PossessionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionTypeExportControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_json_of_the_type(): void
    {
        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Office equipment']);
        PossessionField::factory()->create(['possession_type_id' => $possessionType->id, 'name' => 'Serial number']);

        $this->actingAs($user)->get(route('settings.possessionTypeExport.index', $possessionType))
            ->assertStatus(200)
            ->assertSee('"format": "possession_type"')
            ->assertSee('Serial number');
    }

    #[Test]
    public function it_downloads_the_type_as_a_json_file(): void
    {
        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Office equipment']);
        PossessionField::factory()->create(['possession_type_id' => $possessionType->id, 'name' => 'Serial number']);

        $response = $this->actingAs($user)->get(route('settings.possessionTypeExport.show', $possessionType))
            ->assertDownload('office-equipment.json');

        $document = json_decode($response->streamedContent(), true);

        $this->assertSame('possession_type', $document['format']);
        $this->assertSame('Office equipment', $document['possession_type']['name']);
        $this->assertSame('Serial number', $document['possession_type']['fields'][0]['name']);
    }
}
