<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\FieldTypeEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FieldTypeEnumTest extends TestCase
{
    #[Test]
    public function it_says_which_column_each_type_writes_to(): void
    {
        $this->assertEquals('value_string', FieldTypeEnum::Text->valueColumn());
        $this->assertEquals('value_string', FieldTypeEnum::Url->valueColumn());
        $this->assertEquals('value_string', FieldTypeEnum::Email->valueColumn());
        $this->assertEquals('value_string', FieldTypeEnum::Phone->valueColumn());
        $this->assertEquals('value_text', FieldTypeEnum::Textarea->valueColumn());
        $this->assertEquals('value_number', FieldTypeEnum::Number->valueColumn());
        $this->assertEquals('value_boolean', FieldTypeEnum::Boolean->valueColumn());
        $this->assertEquals('value_date', FieldTypeEnum::Date->valueColumn());
        $this->assertEquals('value_date', FieldTypeEnum::RecurringDate->valueColumn());
        $this->assertEquals('value_list_item_id', FieldTypeEnum::Select->valueColumn());
        $this->assertEquals('value_list_item_id', FieldTypeEnum::Radio->valueColumn());
        $this->assertEquals('value_list_item_ids', FieldTypeEnum::Multiselect->valueColumn());
        $this->assertEquals('value_file_id', FieldTypeEnum::File->valueColumn());
        $this->assertEquals('value_file_id', FieldTypeEnum::Image->valueColumn());
        $this->assertEquals('value_file_id', FieldTypeEnum::Avatar->valueColumn());
        $this->assertEquals('value_json', FieldTypeEnum::Address->valueColumn());
    }

    #[Test]
    public function it_names_no_column_for_a_type_whose_storage_is_decided_elsewhere(): void
    {
        $this->assertNull(FieldTypeEnum::Relation->valueColumn());
        $this->assertNull(FieldTypeEnum::Repeater->valueColumn());
        $this->assertNull(FieldTypeEnum::Divider->valueColumn());
        $this->assertNull(FieldTypeEnum::Heading->valueColumn());
        $this->assertNull(FieldTypeEnum::Help->valueColumn());
    }

    #[Test]
    public function it_never_names_a_column_for_a_type_that_stores_nothing(): void
    {
        foreach (FieldTypeEnum::cases() as $type) {
            if ($type->storesValue()) {
                continue;
            }

            $this->assertNull($type->valueColumn());
        }
    }

    #[Test]
    public function it_says_which_types_store_nothing_at_all(): void
    {
        $this->assertFalse(FieldTypeEnum::Divider->storesValue());
        $this->assertFalse(FieldTypeEnum::Heading->storesValue());
        $this->assertFalse(FieldTypeEnum::Help->storesValue());
        $this->assertTrue(FieldTypeEnum::Text->storesValue());
    }

    #[Test]
    public function it_says_which_types_a_form_can_offer_a_control_for(): void
    {
        $this->assertTrue(FieldTypeEnum::Text->hasAnInput());
        $this->assertTrue(FieldTypeEnum::RecurringDate->hasAnInput());
        $this->assertTrue(FieldTypeEnum::Multiselect->hasAnInput());
    }

    #[Test]
    public function it_says_which_types_still_need_machinery_of_their_own(): void
    {
        $this->assertFalse(FieldTypeEnum::Relation->hasAnInput());
        $this->assertFalse(FieldTypeEnum::Address->hasAnInput());
        $this->assertFalse(FieldTypeEnum::Avatar->hasAnInput());
    }

    #[Test]
    public function it_offers_a_control_for_a_repetition_and_for_an_upload(): void
    {
        $this->assertTrue(FieldTypeEnum::Repeater->hasAnInput());
        $this->assertTrue(FieldTypeEnum::File->hasAnInput());
        $this->assertTrue(FieldTypeEnum::Image->hasAnInput());
    }

    /**
     * An avatar is the one file type nothing asks for: a contact carries its own
     * and is not asked for it twice.
     */
    #[Test]
    public function it_says_which_types_hold_a_file(): void
    {
        $this->assertTrue(FieldTypeEnum::File->holdsAFile());
        $this->assertTrue(FieldTypeEnum::Image->holdsAFile());
        $this->assertTrue(FieldTypeEnum::Avatar->holdsAFile());

        $this->assertFalse(FieldTypeEnum::Text->holdsAFile());
        $this->assertFalse(FieldTypeEnum::Repeater->holdsAFile());
    }

    #[Test]
    public function it_never_offers_a_control_for_a_type_that_stores_nothing(): void
    {
        foreach (FieldTypeEnum::cases() as $type) {
            if ($type->storesValue()) {
                continue;
            }

            $this->assertFalse($type->hasAnInput());
        }
    }

    #[Test]
    public function it_says_which_types_read_from_a_value_list(): void
    {
        $this->assertTrue(FieldTypeEnum::Select->usesValueList());
        $this->assertTrue(FieldTypeEnum::Multiselect->usesValueList());
        $this->assertTrue(FieldTypeEnum::Radio->usesValueList());
        $this->assertFalse(FieldTypeEnum::Text->usesValueList());
    }

    #[Test]
    public function it_says_which_type_points_at_another_record(): void
    {
        $this->assertTrue(FieldTypeEnum::Relation->isRelation());
        $this->assertFalse(FieldTypeEnum::Text->isRelation());
    }

    #[Test]
    public function it_says_which_type_a_reminder_may_read_from(): void
    {
        $this->assertTrue(FieldTypeEnum::RecurringDate->canDriveAReminder());
        $this->assertTrue(FieldTypeEnum::Date->canDriveAReminder());
        $this->assertFalse(FieldTypeEnum::Text->canDriveAReminder());
        $this->assertFalse(FieldTypeEnum::Heading->canDriveAReminder());
    }

    #[Test]
    public function it_says_which_type_may_be_missing_part_of_its_date(): void
    {
        $this->assertTrue(FieldTypeEnum::RecurringDate->mayHaveAnUnknownPart());
        $this->assertFalse(FieldTypeEnum::Date->mayHaveAnUnknownPart());
        $this->assertFalse(FieldTypeEnum::Text->mayHaveAnUnknownPart());
    }

    #[Test]
    public function it_says_which_type_can_be_filled_several_times_over(): void
    {
        $this->assertTrue(FieldTypeEnum::Repeater->isRepeater());
        $this->assertFalse(FieldTypeEnum::Text->isRepeater());
    }
}
