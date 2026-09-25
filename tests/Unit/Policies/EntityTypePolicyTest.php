<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Enums\FieldTypeEnum;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\StructureVersion;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EntityTypePolicyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_allows_deleting_a_type_holding_nothing(): void
    {
        $user = User::factory()->create();
        $type = EntityType::factory()->create(['account_id' => $user->account_id, 'entity_count' => 0]);

        $this->assertTrue(Gate::forUser($user)->allows('delete', $type));
    }

    #[Test]
    public function it_refuses_deleting_a_type_holding_records(): void
    {
        $user = User::factory()->create();
        $type = EntityType::factory()->create(['account_id' => $user->account_id, 'entity_count' => 7]);

        $response = Gate::forUser($user)->inspect('delete', $type);

        $this->assertTrue($response->denied());
        $this->assertStringContainsString('still holds records and stays', (string) $response->message());
    }

    #[Test]
    public function it_refuses_when_nobody_is_signed_in(): void
    {
        $user = User::factory()->create();
        $type = EntityType::factory()->create(['account_id' => $user->account_id]);

        $this->assertFalse(Gate::forUser(null)->allows('delete', $type));
    }

    #[Test]
    public function it_lets_somebody_add_an_entity_to_an_active_type_with_a_published_default(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();
        $this->entityDefaultCarrying($vault, $type, ['Animal details' => ['species' => FieldTypeEnum::Text]]);

        $this->assertTrue(Gate::forUser($user)->allows('addEntity', [$type, $vault]));
    }

    #[Test]
    public function it_refuses_to_add_an_entity_to_a_turned_off_type(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();
        $this->entityDefaultCarrying($vault, $type, ['Animal details' => ['species' => FieldTypeEnum::Text]]);
        $type->update(['is_active' => false]);

        $response = Gate::forUser($user)->inspect('addEntity', [$type, $vault]);

        $this->assertTrue($response->denied());
        $this->assertStringContainsString('This type is turned off.', (string) $response->message());
    }

    #[Test]
    public function it_refuses_to_add_an_entity_without_a_usable_default(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();

        $this->assertTrue(Gate::forUser($user)->denies('addEntity', [$type, $vault]));

        VaultDefaultTemplate::factory()->forEntity($type)->create([
            'vault_id' => $vault->id,
            'structure_version_id' => StructureVersion::factory()->create(),
        ]);

        $this->assertTrue(Gate::forUser($user)->denies('addEntity', [$type, $vault]));

        $stamford = Vault::factory()->create(['account_id' => $user->account_id]);
        $version = $this->entityDefaultCarrying($stamford, $type, ['Animal details' => ['species' => FieldTypeEnum::Text]]);
        $version->structure->owner->update(['is_active' => false]);

        $response = Gate::forUser($user)->inspect('addEntity', [$type, $stamford]);

        $this->assertTrue($response->denied());
        $this->assertStringContainsString('Nothing can be added until this vault has a published template for Animals.', (string) $response->message());
    }

    #[Test]
    public function it_refuses_to_add_an_entity_when_the_template_requires_what_cannot_be_filled_in(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();
        $version = $this->entityDefaultCarrying($vault, $type, ['People' => ['caregivers' => FieldTypeEnum::Repeater]]);
        $caregivers = $version->inputFields()->firstOrFail();
        Field::query()->whereKey($caregivers->id)->update(['is_required' => true]);
        Field::factory()->create([
            'section_version_id' => $caregivers->section_version_id,
            'parent_field_id' => $caregivers->id,
            'type' => FieldTypeEnum::Avatar,
            'is_required' => true,
        ]);

        $response = Gate::forUser($user)->inspect('addEntity', [$type, $vault]);

        $this->assertTrue($response->denied());
        $this->assertStringContainsString('requires something that cannot be filled in here yet', (string) $response->message());
    }

    #[Test]
    public function it_lets_somebody_add_an_entity_whose_template_requires_a_relation_in_a_repeater(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();
        $version = $this->entityDefaultCarrying($vault, $type, ['People' => ['caregivers' => FieldTypeEnum::Repeater]]);
        $caregivers = $version->inputFields()->firstOrFail();
        Field::query()->whereKey($caregivers->id)->update(['is_required' => true]);
        Field::factory()->relation()->create([
            'section_version_id' => $caregivers->section_version_id,
            'parent_field_id' => $caregivers->id,
            'is_required' => true,
        ]);

        $this->assertTrue(Gate::forUser($user)->inspect('addEntity', [$type, $vault])->allowed());
    }

    #[Test]
    public function it_refuses_to_add_an_entity_when_nobody_is_signed_in(): void
    {
        [, $vault, $type] = $this->vaultWithType();
        $this->entityDefaultCarrying($vault, $type, ['Animal details' => ['species' => FieldTypeEnum::Text]]);

        $this->assertFalse(Gate::forUser(null)->allows('addEntity', [$type, $vault]));
    }

    /** @return array{0: User, 1: Vault, 2: EntityType} */
    private function vaultWithType(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $type = EntityType::factory()->create(['account_id' => $user->account_id, 'name_plural' => 'Animals']);

        return [$user, $vault, $type];
    }
}
