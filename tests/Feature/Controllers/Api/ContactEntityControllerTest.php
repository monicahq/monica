<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Actions\CreateEntity;
use App\Enums\FieldTypeEnum;
use App\Enums\RelationTargetEnum;
use App\Models\Contact;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactEntityControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'data' => [
            '*' => [
                'type',
                'id',
                'attributes' => ['name', 'entity_type_id', 'structure_version_id', 'template_name', 'fields'],
                'links' => ['self', 'web'],
            ],
        ],
        'meta' => ['page', 'per_page', 'total'],
    ];

    #[Test]
    public function it_lists_the_records_naming_a_contact(): void
    {
        [$user, $vault, $type, $owner] = $this->farmWithAnOwner();
        $dwight = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        $jim = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        $this->goat($user, $vault, $type, $owner, 'Mose’s goat', $dwight);
        $this->goat($user, $vault, $type, $owner, 'Jim’s goat', $jim);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contacts/{$dwight->uuid}/entities")
            ->assertOk()
            ->assertJsonStructure($this->jsonStructure)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.attributes.name', 'Mose’s goat');
    }

    #[Test]
    public function it_answers_not_found_for_a_contact_of_another_account(): void
    {
        [, $vault] = $this->farmWithAnOwner();
        $dwight = Contact::factory()->create(['vault_id' => $vault->id]);

        Sanctum::actingAs(User::factory()->create());

        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contacts/{$dwight->uuid}/entities")->assertNotFound();
    }

    #[Test]
    public function it_does_not_find_a_contact_through_the_wrong_vault(): void
    {
        [$user, $vault] = $this->farmWithAnOwner();
        $dwight = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        $stamford = Vault::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$stamford->uuid}/contacts/{$dwight->uuid}/entities")->assertNotFound();
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

    private function goat(User $user, Vault $vault, EntityType $type, Field $owner, string $name, Contact $contact): void
    {
        Queue::fake();

        new CreateEntity(
            user: $user,
            vault: $vault,
            entityType: $type,
            name: $name,
            values: [$owner->id => $contact->uuid],
        )->execute();
    }
}
