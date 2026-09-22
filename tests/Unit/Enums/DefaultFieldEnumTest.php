<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\DefaultFieldEnum;
use App\Enums\FieldTypeEnum;
use App\Enums\ValueListEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DefaultFieldEnumTest extends TestCase
{
    #[Test]
    public function it_names_the_list_an_email_and_a_phone_take_their_label_from(): void
    {
        $this->assertEquals(ValueListEnum::EmailLabels, DefaultFieldEnum::Email->valueList());
        $this->assertEquals(ValueListEnum::PhoneLabels, DefaultFieldEnum::Phone->valueList());
    }

    #[Test]
    public function it_names_the_list_a_choice_field_offers(): void
    {
        $this->assertEquals(ValueListEnum::ContactCategories, DefaultFieldEnum::Category->valueList());
        $this->assertEquals(ValueListEnum::Genders, DefaultFieldEnum::Gender->valueList());
    }

    #[Test]
    public function it_names_no_list_for_a_field_that_reads_from_none(): void
    {
        $this->assertNull(DefaultFieldEnum::Website->valueList());
        $this->assertNull(DefaultFieldEnum::Birthday->valueList());
        $this->assertNull(DefaultFieldEnum::Description->valueList());
    }

    #[Test]
    public function it_names_a_list_for_every_field_whose_type_asks_for_one(): void
    {
        foreach (DefaultFieldEnum::cases() as $field) {
            if (! $field->type()->usesValueList() && ! $field->type()->usesALabelList()) {
                continue;
            }

            $this->assertNotNull($field->valueList());
        }
    }

    #[Test]
    public function it_says_which_fields_identify_a_record(): void
    {
        $this->assertTrue(DefaultFieldEnum::Email->isIdentifying());
        $this->assertTrue(DefaultFieldEnum::Phone->isIdentifying());
        $this->assertFalse(DefaultFieldEnum::Gender->isIdentifying());
    }

    #[Test]
    public function it_gives_an_email_and_a_phone_their_own_type(): void
    {
        $this->assertEquals(FieldTypeEnum::Email, DefaultFieldEnum::Email->type());
        $this->assertEquals(FieldTypeEnum::Phone, DefaultFieldEnum::Phone->type());
    }
}
