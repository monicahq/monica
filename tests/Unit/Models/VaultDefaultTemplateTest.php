<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\StructureAppliesToEnum;
use App\Models\Account;
use App\Models\EntityType;
use App\Models\StructureVersion;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VaultDefaultTemplateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_an_account(): void
    {
        $account = Account::factory()->create();
        $default = VaultDefaultTemplate::factory()->create(['account_id' => $account->id]);

        $this->assertTrue($default->account()->exists());
        $this->assertTrue($account->is($default->account));
    }

    #[Test]
    public function it_belongs_to_a_vault(): void
    {
        $vault = Vault::factory()->create();
        $default = VaultDefaultTemplate::factory()->create(['vault_id' => $vault->id]);

        $this->assertTrue($default->vault()->exists());
        $this->assertTrue($vault->is($default->vault));
    }

    #[Test]
    public function it_belongs_to_the_version_a_new_record_starts_on(): void
    {
        $version = StructureVersion::factory()->published()->create();
        $default = VaultDefaultTemplate::factory()->create(['structure_version_id' => $version->id]);

        $this->assertTrue($default->structureVersion()->exists());
        $this->assertTrue($version->is($default->structureVersion));
    }

    #[Test]
    public function it_belongs_to_the_kind_of_record_it_is_for_when_it_is_not_for_contacts(): void
    {
        $entityType = EntityType::factory()->create();
        $default = VaultDefaultTemplate::factory()->forEntity($entityType)->create();

        $this->assertTrue($default->entityType()->exists());
        $this->assertTrue($entityType->is($default->entityType));
        $this->assertEquals(StructureAppliesToEnum::Entity, $default->applies_to);
    }

    #[Test]
    public function it_points_at_no_kind_of_record_when_it_is_for_contacts(): void
    {
        $default = VaultDefaultTemplate::factory()->create();

        $this->assertEquals(StructureAppliesToEnum::Contact, $default->applies_to);
        $this->assertNull($default->entity_type_id);
        $this->assertNull($default->entityType);
    }
}
