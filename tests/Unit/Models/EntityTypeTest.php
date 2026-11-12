<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Entity;
use App\Models\EntityType;
use App\Models\StructureVersion;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EntityTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_an_active_type_with_a_published_default_in_the_vault(): void
    {
        $vault = Vault::factory()->create();
        $type = $this->typeIn($vault);
        VaultDefaultTemplate::factory()->forEntity($type)->create(['vault_id' => $vault->id]);

        $this->assertTrue($this->isVisible($type, $vault));
    }

    #[Test]
    public function it_keeps_a_type_turned_off_while_the_vault_holds_one_of_its_records(): void
    {
        $vault = Vault::factory()->create();
        $type = $this->typeIn($vault, ['is_active' => false]);
        Entity::factory()->create(['vault_id' => $vault->id, 'entity_type_id' => $type->id]);

        $this->assertTrue($this->isVisible($type, $vault));
    }

    #[Test]
    public function it_keeps_a_type_whose_only_record_is_archived(): void
    {
        $vault = Vault::factory()->create();
        $type = $this->typeIn($vault, ['is_active' => false]);
        Entity::factory()->archived()->create(['vault_id' => $vault->id, 'entity_type_id' => $type->id]);

        $this->assertTrue($this->isVisible($type, $vault));
    }

    #[Test]
    public function it_hides_a_turned_off_type_once_its_last_record_is_deleted(): void
    {
        $vault = Vault::factory()->create();
        $type = $this->typeIn($vault, ['is_active' => false]);
        VaultDefaultTemplate::factory()->forEntity($type)->create(['vault_id' => $vault->id]);
        $entity = Entity::factory()->create(['vault_id' => $vault->id, 'entity_type_id' => $type->id]);

        $entity->delete();

        $this->assertFalse($this->isVisible($type, $vault));
    }

    #[Test]
    public function it_hides_a_type_whose_default_is_retired_or_whose_template_is_turned_off(): void
    {
        $vault = Vault::factory()->create();
        $retired = $this->typeIn($vault);
        VaultDefaultTemplate::factory()->forEntity($retired)->create([
            'vault_id' => $vault->id,
            'structure_version_id' => StructureVersion::factory()->retired(),
        ]);
        $turnedOff = $this->typeIn($vault);
        $default = VaultDefaultTemplate::factory()->forEntity($turnedOff)->create(['vault_id' => $vault->id]);
        $default->structureVersion->structure->owner->update(['is_active' => false]);

        $this->assertFalse($this->isVisible($retired, $vault));
        $this->assertFalse($this->isVisible($turnedOff, $vault));
    }

    #[Test]
    public function it_decides_each_vault_on_its_own(): void
    {
        $scranton = Vault::factory()->create();
        $stamford = Vault::factory()->create(['account_id' => $scranton->account_id]);
        $type = $this->typeIn($scranton);
        VaultDefaultTemplate::factory()->forEntity($type)->create(['vault_id' => $scranton->id]);

        $this->assertTrue($this->isVisible($type, $scranton));
        $this->assertFalse($this->isVisible($type, $stamford));
    }

    #[Test]
    public function it_counts_a_type_as_configured_only_when_the_vault_starts_its_records_from_it(): void
    {
        $vault = Vault::factory()->create();
        $animals = $this->typeIn($vault);
        VaultDefaultTemplate::factory()->forEntity($animals)->create(['vault_id' => $vault->id]);
        $companies = $this->typeIn($vault);
        Entity::factory()->create(['vault_id' => $vault->id, 'entity_type_id' => $companies->id]);

        $this->assertTrue($this->isConfigured($animals, $vault));
        $this->assertFalse($this->isConfigured($companies, $vault));
        $this->assertTrue($this->isVisible($companies, $vault));
    }

    #[Test]
    public function it_does_not_count_a_turned_off_type_as_configured(): void
    {
        $vault = Vault::factory()->create();
        $type = $this->typeIn($vault, ['is_active' => false]);
        VaultDefaultTemplate::factory()->forEntity($type)->create(['vault_id' => $vault->id]);

        $this->assertFalse($this->isConfigured($type, $vault));
    }

    /** @param  array<string, mixed>  $attributes */
    private function typeIn(Vault $vault, array $attributes = []): EntityType
    {
        return EntityType::factory()->create(array_merge(['account_id' => $vault->account_id], $attributes));
    }

    private function isVisible(EntityType $type, Vault $vault): bool
    {
        return EntityType::query()->whereKey($type->id)->visibleInVault($vault)->exists();
    }

    private function isConfigured(EntityType $type, Vault $vault): bool
    {
        return EntityType::query()->whereKey($type->id)->configuredInVault($vault)->exists();
    }
}
