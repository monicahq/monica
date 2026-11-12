<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Models\PossessionField;
use App\Models\PossessionFieldGroup;
use App\Models\PossessionType;
use App\ViewModels\Vaults\PossessionTypeFieldsBlock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionTypeFieldsBlockTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gives_the_groups_in_order_then_the_standalone_fields_with_what_was_typed(): void
    {
        $vehicle = PossessionType::factory()->create(['name' => 'Vehicle']);
        $insurance = PossessionFieldGroup::factory()->create(['possession_type_id' => $vehicle->id, 'name' => 'Insurance', 'position' => 2]);
        $purchase = PossessionFieldGroup::factory()->create(['possession_type_id' => $vehicle->id, 'name' => 'Purchase', 'position' => 1]);
        PossessionFieldGroup::factory()->create(['possession_type_id' => $vehicle->id, 'name' => 'Empty', 'position' => 3]);
        PossessionField::factory()->create(['possession_type_id' => $vehicle->id, 'possession_field_group_id' => $insurance->id, 'name' => 'Insurer']);
        PossessionField::factory()->create(['possession_type_id' => $vehicle->id, 'possession_field_group_id' => $purchase->id, 'name' => 'Shop']);
        $mileage = PossessionField::factory()->create(['possession_type_id' => $vehicle->id, 'name' => 'Mileage', 'position' => 2]);
        PossessionField::factory()->create(['possession_type_id' => $vehicle->id, 'name' => 'Registration', 'position' => 1]);

        $block = new PossessionTypeFieldsBlock(possessionType: $vehicle, oldValues: [$mileage->uuid => '42000']);

        $this->assertSame(['Purchase', 'Insurance'], array_column($block->groups(), 'name'));
        $this->assertSame(['Registration', 'Mileage'], array_column($block->standaloneFields(), 'name'));
        $this->assertSame('42000', $block->standaloneFields()[1]['old']);
        $this->assertSame("values[{$mileage->uuid}]", $block->standaloneFields()[1]['inputName']);
        $this->assertFalse($block->isEmpty());
    }

    #[Test]
    public function a_type_without_fields_is_empty(): void
    {
        $this->assertTrue(new PossessionTypeFieldsBlock(possessionType: PossessionType::factory()->create())->isEmpty());
    }
}
