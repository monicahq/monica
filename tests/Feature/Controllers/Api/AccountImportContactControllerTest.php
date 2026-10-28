<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Actions\ReadMonicaFourFile;
use App\Models\AccountImport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountImportContactControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int|string, mixed> */
    private array $jsonStructure = [
        'type',
        'id',
        'attributes' => [
            'name',
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Queue::fake();
    }

    #[Test]
    public function it_lists_the_contacts_of_the_file_by_name(): void
    {
        $michael = User::factory()->create();
        $import = $this->readImport($michael);
        Sanctum::actingAs($michael);

        $response = $this->json('GET', "/api/v1/imports/{$import->uuid}/contacts");

        $response->assertOk();
        $response->assertJsonStructure(['data' => ['*' => $this->jsonStructure], 'meta' => ['page', 'per_page', 'total']]);
        $response->assertJsonPath('meta.total', 5);
        $response->assertJsonPath('data.0.attributes.name', 'Cece');
        $response->assertJsonPath('data.1.id', '10000000-0000-4000-8000-000000000002');
        $response->assertJsonPath('data.1.attributes.name', 'Dwight Schrute');
    }

    #[Test]
    public function it_narrows_the_contacts_to_a_name(): void
    {
        $michael = User::factory()->create();
        $import = $this->readImport($michael);
        Sanctum::actingAs($michael);

        $response = $this->json('GET', "/api/v1/imports/{$import->uuid}/contacts", ['search' => 'halpert']);

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.attributes.name', 'Jim Halpert');
        $response->assertJsonPath('meta.total', 1);
    }

    #[Test]
    public function it_answers_not_found_for_an_import_of_another_account(): void
    {
        $import = $this->readImport(User::factory()->create());
        Sanctum::actingAs(User::factory()->create());

        $this->json('GET', "/api/v1/imports/{$import->uuid}/contacts")->assertNotFound();
    }

    private function readImport(User $user): AccountImport
    {
        $import = AccountImport::factory()->create(['user_id' => $user->id]);
        Storage::disk('local')->put($import->path, (string) file_get_contents(base_path('tests/Fixtures/Imports/monica-4-small.json')));

        new ReadMonicaFourFile(import: $import)->execute();

        return $import;
    }
}
