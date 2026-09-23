<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\StructureAppliesToEnum;
use App\Models\EntityType;
use App\Models\VaultDefaultTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VaultDefaultTemplateTest extends TestCase
{
    use RefreshDatabase;

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
