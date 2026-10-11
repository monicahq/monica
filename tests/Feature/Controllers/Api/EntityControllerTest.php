<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Enums\FieldTypeEnum;
use App\Enums\RelationTargetEnum;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\StructureVersion;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Laravel\Pennant\Feature;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EntityControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'data' => [
            'type',
            'id',
            'attributes' => [
                'name',
                'entity_type_id',
                'initials',
                'avatar_colour',
                'avatar',
                'is_archived',
                'structure_version_id',
                'template_name',
                'created_at',
                'updated_at',
            ],
        ],
    ];

    #[Test]
    public function it_lists_the_entities_of_a_type_in_a_vault(): void
    {
        [$user, $vault, $type] = $this->farm();
        Entity::factory()->create(['vault_id' => $vault->id, 'entity_type_id' => $type->id, 'name' => 'Mose’s goat']);
        Entity::factory()->create(['vault_id' => $vault->id, 'entity_type_id' => $type->id, 'name' => 'Garbage', 'is_archived' => true]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/entity-types/{$type->uuid}/entities");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [$this->jsonStructure['data']],
            'meta' => ['page', 'per_page', 'total'],
        ]);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('meta.total', 2);
        $response->assertJsonPath('data.0.attributes.name', 'Garbage');
        $response->assertJsonPath('data.0.attributes.is_archived', true);
    }

    #[Test]
    public function it_leaves_out_the_entities_of_another_vault(): void
    {
        [$user, $vault, $type] = $this->farm();
        $stamford = Vault::factory()->create(['account_id' => $user->account_id]);
        Entity::factory()->create(['vault_id' => $stamford->id, 'entity_type_id' => $type->id, 'name' => 'Bandit']);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/entity-types/{$type->uuid}/entities")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    #[Test]
    public function it_answers_not_found_for_a_hidden_type(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $type = EntityType::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/entity-types/{$type->uuid}/entities")
            ->assertNotFound();
    }

    #[Test]
    public function it_creates_an_entity(): void
    {
        Queue::fake();

        [$user, $vault, $type, $version] = $this->farm();
        $species = $version->inputFields()->firstOrFail();

        Sanctum::actingAs($user);

        $response = $this->json('POST', "/api/v1/vaults/{$vault->uuid}/entity-types/{$type->uuid}/entities", [
            'name' => 'Mose’s goat',
            'fields' => [$species->id => 'Goat'],
        ]);

        $response->assertCreated();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.attributes.name', 'Mose’s goat');
        $response->assertJsonPath('data.attributes.entity_type_id', $type->uuid);
        $this->assertDatabaseHas('field_values', ['field_id' => $species->id, 'value_string' => 'Goat']);
    }

    #[Test]
    public function it_refuses_an_entity_without_a_name(): void
    {
        [$user, $vault, $type] = $this->farm();

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->uuid}/entity-types/{$type->uuid}/entities", ['name' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    #[Test]
    public function it_refuses_a_file_while_file_upload_is_off_for_the_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Schrute Farms']);
        $type = EntityType::factory()->create(['account_id' => $user->account_id]);
        $version = $this->entityDefaultCarrying($vault, $type, ['Animal details' => ['portrait' => FieldTypeEnum::Image]]);
        $portrait = $version->inputFields()->firstOrFail();
        $user->account->features()->deactivate('file-upload');
        Feature::flushCache();

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->uuid}/entity-types/{$type->uuid}/entities", [
            'name' => 'Mose’s goat',
            'fields' => [$portrait->id => UploadedFile::fake()->image('goat.jpg')],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(["fields.{$portrait->id}" => 'Uploads are unavailable on this account for now.']);
    }

    #[Test]
    public function it_refuses_an_entity_of_a_turned_off_type(): void
    {
        [$user, $vault, $type] = $this->farm();
        Entity::factory()->create(['vault_id' => $vault->id, 'entity_type_id' => $type->id]);
        $type->update(['is_active' => false]);

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->uuid}/entity-types/{$type->uuid}/entities", ['name' => 'Mose’s goat'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    #[Test]
    public function it_creates_an_entity_naming_somebody_in_the_vault(): void
    {
        Queue::fake();

        [$user, $vault, $type, $owner] = $this->farmWithAnOwner();
        $dwight = Contact::factory()->create(['vault_id' => $vault->id]);

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->uuid}/entity-types/{$type->uuid}/entities", [
            'name' => 'Mose’s goat',
            'fields' => [$owner->id => $dwight->uuid],
        ])->assertCreated();

        $this->assertDatabaseHas('field_values', ['field_id' => $owner->id, 'related_contact_id' => $dwight->id]);
    }

    #[Test]
    public function it_refuses_to_name_somebody_of_another_vault(): void
    {
        Queue::fake();

        [$user, $vault, $type, $owner] = $this->farmWithAnOwner();
        $stamford = Vault::factory()->create(['account_id' => $user->account_id]);
        $andy = Contact::factory()->create(['vault_id' => $stamford->id]);

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->uuid}/entity-types/{$type->uuid}/entities", [
            'name' => 'Mose’s goat',
            'fields' => [$owner->id => $andy->uuid],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors("fields.{$owner->id}");
    }

    /** @return array{0: User, 1: Vault, 2: EntityType, 3: Field} */
    private function farmWithAnOwner(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Schrute Farms']);
        $type = EntityType::factory()->create(['account_id' => $user->account_id]);
        $version = $this->entityDefaultCarrying($vault, $type, ['People' => ['owner' => FieldTypeEnum::Relation]]);

        $owner = $version->inputFields()->firstOrFail();
        $owner->update(['relation_target' => RelationTargetEnum::Contact]);

        return [$user, $vault, $type, $owner];
    }

    /** @return array{0: User, 1: Vault, 2: EntityType, 3: StructureVersion} */
    private function farm(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Schrute Farms']);
        $type = EntityType::factory()->create(['account_id' => $user->account_id]);
        $version = $this->entityDefaultCarrying($vault, $type, ['Animal details' => ['species' => FieldTypeEnum::Text]]);

        return [$user, $vault, $type, $version];
    }
}
