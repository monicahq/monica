<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\FieldTypeEnum;
use App\Enums\VersionStatusEnum;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Models\Field;
use App\Models\SectionVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FieldTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_takes_what_it_holds_with_it_when_a_repeater_goes(): void
    {
        $version = SectionVersion::factory()->create();
        $repeater = Field::factory()->repeater()->create(['section_version_id' => $version->id]);
        $child = Field::factory()->create([
            'section_version_id' => $version->id,
            'parent_field_id' => $repeater->id,
        ]);

        $repeater->delete();

        $this->assertModelMissing($child);
    }

    #[Test]
    public function it_reads_by_its_translation_key_when_the_product_supplied_it(): void
    {
        $field = Field::factory()->create(['label' => null, 'label_translation_key' => 'Street']);

        $this->assertEquals('Street', $field->label);
    }

    #[Test]
    public function it_stores_nothing_for_a_layout_type(): void
    {
        $field = Field::factory()->layout()->create();

        $this->assertFalse($field->type->storesValue());
        $this->assertTrue(FieldTypeEnum::Text->storesValue());
    }

    #[Test]
    public function it_knows_any_date_can_drive_a_reminder(): void
    {
        $field = Field::factory()->recurringDate()->create();

        $this->assertTrue($field->type->canDriveAReminder());
        $this->assertTrue(FieldTypeEnum::Date->canDriveAReminder());
        $this->assertFalse(FieldTypeEnum::Text->canDriveAReminder());
    }

    #[Test]
    public function it_refuses_to_be_added_to_a_published_section(): void
    {
        $version = SectionVersion::factory()->published()->create();

        $this->expectException(PublishedVersionIsFrozen::class);

        Field::factory()->create(['section_version_id' => $version->id]);
    }

    #[Test]
    public function it_refuses_to_be_changed_on_a_published_section(): void
    {
        $field = Field::factory()->create();
        $field->sectionVersion->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);
        $field->refresh();

        $this->expectException(PublishedVersionIsFrozen::class);

        $field->label = 'Something else';
        $field->save();
    }

    #[Test]
    public function it_refuses_to_be_removed_from_a_published_section(): void
    {
        $field = Field::factory()->create();
        $field->sectionVersion->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);
        $field->refresh();

        $this->expectException(PublishedVersionIsFrozen::class);

        $field->delete();
    }

    #[Test]
    public function it_takes_its_account_from_the_section_it_sits_in(): void
    {
        $field = Field::factory()->create();

        $this->assertEquals($field->sectionVersion->account_id, $field->account_id);
    }

    #[Test]
    public function it_can_be_filled_in_when_it_has_a_control_of_its_own(): void
    {
        $this->assertTrue(Field::factory()->create()->canBeFilledIn());
        $this->assertTrue(Field::factory()->relation()->create()->canBeFilledIn());
        $this->assertFalse(Field::factory()->create(['type' => FieldTypeEnum::Avatar])->canBeFilledIn());
        $this->assertFalse(Field::factory()->layout()->create()->canBeFilledIn());
    }

    #[Test]
    public function it_leaves_out_a_repeater_whose_required_child_has_no_control(): void
    {
        $caregivers = Field::factory()->repeater()->create(['label' => 'Caregivers']);
        Field::factory()->create([
            'section_version_id' => $caregivers->section_version_id,
            'parent_field_id' => $caregivers->id,
            'label' => 'Role',
        ]);

        $this->assertTrue($caregivers->refresh()->canBeFilledIn());

        Field::factory()->create([
            'section_version_id' => $caregivers->section_version_id,
            'parent_field_id' => $caregivers->id,
            'type' => FieldTypeEnum::Avatar,
            'label' => 'Portrait',
            'is_required' => true,
        ]);

        $this->assertFalse($caregivers->refresh()->canBeFilledIn());
    }
}
