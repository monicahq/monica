<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Actions\CreateEntity;
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
                'fields',
                'created_at',
                'updated_at',
            ],
            'links' => ['self', 'web'],
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
    public function it_creates_an_entity_on_another_template_of_the_type(): void
    {
        Queue::fake();

        [$user, $vault, $type] = $this->farm();
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);
        $picked = $this->entityDefaultCarrying($elsewhere, $type, ['Character' => ['character' => FieldTypeEnum::Textarea]]);

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->uuid}/entity-types/{$type->uuid}/entities", [
            'name' => 'Mose’s goat',
            'structure_version_id' => $picked->id,
            'fields' => [$picked->inputFields()->firstOrFail()->id => 'Stubborn'],
        ])->assertCreated();

        $this->assertDatabaseHas('entities', ['name' => 'Mose’s goat', 'structure_version_id' => $picked->id]);
    }

    #[Test]
    public function it_refuses_a_template_of_another_type(): void
    {
        [$user, $vault, $type] = $this->farm();
        $beets = EntityType::factory()->create(['account_id' => $user->account_id]);
        $other = $this->entityDefaultCarrying($vault, $beets, ['Beet' => ['variety' => FieldTypeEnum::Text]]);

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->uuid}/entity-types/{$type->uuid}/entities", [
            'name' => 'Mose’s goat',
            'structure_version_id' => $other->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('structure_version_id');

        $this->assertSame(0, Entity::query()->count());
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
            ->assertJsonValidationErrors(["fields.{$portrait->id}"]);
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

    #[Test]
    public function it_shows_an_entity_with_its_values(): void
    {
        [$user, $vault, , $version, $entity] = $this->goat();
        $species = $version->inputFields()->firstOrFail();

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/entities/{$entity->uuid}");

        $response->assertOk();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.attributes.fields.0.field_id', (string) $species->id);
        $response->assertJsonPath('data.attributes.fields.0.key', 'species');
        $response->assertJsonPath('data.attributes.fields.0.value', 'Goat');
    }

    #[Test]
    public function it_answers_not_found_for_an_entity_of_another_account(): void
    {
        [, $vault, , , $entity] = $this->goat();
        [$stranger] = $this->farm();

        Sanctum::actingAs($stranger);

        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/entities/{$entity->uuid}")->assertNotFound();
        $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/entities/{$entity->uuid}", ['name' => 'Bandit'])->assertNotFound();
    }

    #[Test]
    public function it_renames_an_entity_and_keeps_every_value_left_out(): void
    {
        [$user, $vault, , $version, $entity] = $this->goat();
        $species = $version->inputFields()->firstOrFail();

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/entities/{$entity->uuid}", ['name' => 'Mose’s best goat']);

        $response->assertOk();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.attributes.name', 'Mose’s best goat');
        $this->assertDatabaseHas('field_values', ['valuable_id' => $entity->id, 'valuable_type' => Entity::class, 'field_id' => $species->id, 'value_string' => 'Goat']);
    }

    #[Test]
    public function it_keeps_a_required_value_left_out(): void
    {
        [$user, $vault, , $version, $entity] = $this->goat();
        $species = $version->inputFields()->firstOrFail();
        $species->update(['is_required' => true]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/entities/{$entity->uuid}", ['name' => 'Mose’s best goat'])
            ->assertOk()
            ->assertJsonPath('data.attributes.name', 'Mose’s best goat');

        $this->assertDatabaseHas('field_values', ['valuable_id' => $entity->id, 'valuable_type' => Entity::class, 'field_id' => $species->id, 'value_string' => 'Goat']);
    }

    #[Test]
    public function it_clears_a_value_sent_as_null(): void
    {
        [$user, $vault, , $version, $entity] = $this->goat();
        $species = $version->inputFields()->firstOrFail();

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/entities/{$entity->uuid}", ['fields' => [$species->id => null]])
            ->assertOk()
            ->assertJsonPath('data.attributes.name', 'Mose’s goat')
            ->assertJsonCount(0, 'data.attributes.fields');
    }

    #[Test]
    public function it_refuses_to_clear_the_name_of_an_entity(): void
    {
        [$user, $vault, , , $entity] = $this->goat();

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/entities/{$entity->uuid}", ['name' => null])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    /** @return array{0: User, 1: Vault, 2: EntityType, 3: StructureVersion, 4: Entity} */
    private function goat(): array
    {
        Queue::fake();

        [$user, $vault, $type, $version] = $this->farm();

        $entity = new CreateEntity(
            user: $user,
            vault: $vault,
            entityType: $type,
            name: 'Mose’s goat',
            values: [$version->inputFields()->firstOrFail()->id => 'Goat'],
        )->execute();

        return [$user, $vault, $type, $version, $entity];
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
