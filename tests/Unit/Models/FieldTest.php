<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\FieldTypeEnum;
use App\Enums\RelationTargetEnum;
use App\Enums\RepetitionOrderEnum;
use App\Enums\VersionStatusEnum;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\SectionVersion;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FieldTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_an_account_and_to_a_section(): void
    {
        $field = Field::factory()->create();

        $this->assertTrue($field->account()->exists());
        $this->assertTrue($field->sectionVersion()->exists());
    }

    #[Test]
    public function it_belongs_to_the_list_its_choices_come_from(): void
    {
        $field = Field::factory()->choice()->create();

        $this->assertTrue($field->valueList()->exists());
        $this->assertTrue($field->type->usesValueList());
    }

    #[Test]
    public function it_belongs_to_the_kind_of_record_it_points_at(): void
    {
        $entityType = EntityType::factory()->create();
        $field = Field::factory()->relation()->create([
            'relation_target' => RelationTargetEnum::Entity,
            'relation_entity_type_id' => $entityType->id,
        ]);

        $this->assertTrue($field->relationEntityType()->exists());
    }

    #[Test]
    public function it_holds_the_fields_inside_a_repeater(): void
    {
        $version = SectionVersion::factory()->create();
        $repeater = Field::factory()->repeater()->create(['section_version_id' => $version->id]);
        $child = Field::factory()->create([
            'section_version_id' => $version->id,
            'parent_field_id' => $repeater->id,
        ]);

        $this->assertTrue($repeater->children()->exists());
        $this->assertTrue($child->parent->is($repeater));
    }

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
    public function it_knows_a_recurring_date_can_drive_a_reminder(): void
    {
        $field = Field::factory()->recurringDate()->create();

        $this->assertTrue($field->type->canDriveAReminder());
        $this->assertTrue($field->is_reminder_source);
        $this->assertFalse(FieldTypeEnum::Date->canDriveAReminder());
    }

    #[Test]
    public function it_reads_its_settings_back_as_arrays(): void
    {
        $field = Field::factory()->create([
            'default_value' => ['scranton'],
            'options' => ['max' => 10],
            'validation' => ['min_length' => 2],
        ]);

        $this->assertEquals(['scranton'], $field->fresh()->default_value);
        $this->assertEquals(['max' => 10], $field->fresh()->options);
        $this->assertEquals(['min_length' => 2], $field->fresh()->validation);
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

    /**
     * The key is what an import, an export or an API caller holds on to, so two
     * fields in one section may never answer to the same one.
     */
    #[Test]
    public function it_refuses_two_fields_with_the_same_key_in_one_section(): void
    {
        $version = SectionVersion::factory()->create();
        Field::factory()->create(['section_version_id' => $version->id, 'key' => 'street']);

        $this->expectException(QueryException::class);

        Field::factory()->create(['section_version_id' => $version->id, 'key' => 'street']);
    }

    #[Test]
    public function it_reads_which_end_a_new_repetition_is_added_at(): void
    {
        $field = Field::factory()->create(['repetition_order' => RepetitionOrderEnum::NewestFirst]);

        $this->assertSame(RepetitionOrderEnum::NewestFirst, $field->refresh()->repetition_order);
    }

    #[Test]
    public function it_says_nothing_about_the_end_until_somebody_does(): void
    {
        $field = Field::factory()->create();

        $this->assertNull($field->refresh()->repetition_order);
    }

    #[Test]
    public function it_takes_its_account_from_the_section_it_sits_in(): void
    {
        $field = Field::factory()->create();

        $this->assertEquals($field->sectionVersion->account_id, $field->account_id);
    }
}
