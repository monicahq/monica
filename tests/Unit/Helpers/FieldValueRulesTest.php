<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Enums\FieldTypeEnum;
use App\Helpers\FieldValueRules;
use App\Models\Field;
use App\Models\ValueList;
use App\Models\ValueListItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FieldValueRulesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_asks_for_what_the_type_of_each_field_needs(): void
    {
        $text = Field::factory()->create(['type' => FieldTypeEnum::Text]);
        $url = Field::factory()->create(['type' => FieldTypeEnum::Url]);
        $number = Field::factory()->create(['type' => FieldTypeEnum::Number]);

        $rules = FieldValueRules::for(collect([$text, $url, $number]));

        $this->assertContains('string', $rules["fields.{$text->id}"]);
        $this->assertContains('url', $rules["fields.{$url->id}"]);
        $this->assertContains('numeric', $rules["fields.{$number->id}"]);
    }

    #[Test]
    public function it_asks_for_an_answer_only_where_the_field_says_it_is_required(): void
    {
        $required = Field::factory()->create(['is_required' => true]);
        $optional = Field::factory()->create(['is_required' => false]);

        $rules = FieldValueRules::for(collect([$required, $optional]));

        $this->assertContains('required', $rules["fields.{$required->id}"]);
        $this->assertContains('nullable', $rules["fields.{$optional->id}"]);
    }

    #[Test]
    public function it_asks_nothing_of_a_field_that_stores_nothing(): void
    {
        $divider = Field::factory()->layout()->create();

        $this->assertEquals([], FieldValueRules::for(collect([$divider])));
    }

    #[Test]
    public function it_refuses_a_choice_that_is_not_on_the_list_the_field_offers(): void
    {
        $list = ValueList::factory()->create();
        $another = ValueList::factory()->create();
        $field = Field::factory()->choice($list)->create();
        $elsewhere = ValueListItem::factory()->create(['value_list_id' => $another->id]);

        $validator = Validator::make(
            ['fields' => [$field->id => $elsewhere->id]],
            FieldValueRules::for(collect([$field])),
        );

        $this->assertTrue($validator->fails());
    }

    #[Test]
    public function it_accepts_a_choice_the_list_still_offers(): void
    {
        $list = ValueList::factory()->create();
        $field = Field::factory()->choice($list)->create();
        $item = ValueListItem::factory()->create(['value_list_id' => $list->id]);

        $validator = Validator::make(
            ['fields' => [$field->id => $item->id]],
            FieldValueRules::for(collect([$field])),
        );

        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_refuses_an_item_nobody_offers_any_more(): void
    {
        $list = ValueList::factory()->create();
        $field = Field::factory()->choice($list)->create();
        $retired = ValueListItem::factory()->disabled()->create(['value_list_id' => $list->id]);

        $validator = Validator::make(
            ['fields' => [$field->id => $retired->id]],
            FieldValueRules::for(collect([$field])),
        );

        $this->assertTrue($validator->fails());
    }

    #[Test]
    public function it_asks_for_the_label_of_an_email_or_a_phone_naming_a_list(): void
    {
        $list = ValueList::factory()->create();
        $phone = Field::factory()->create(['type' => FieldTypeEnum::Phone, 'value_list_id' => $list->id]);
        $item = ValueListItem::factory()->create(['value_list_id' => $list->id]);

        $rules = FieldValueRules::for(collect([$phone]));

        $this->assertArrayHasKey("labels.{$phone->id}", $rules);
        $this->assertContains('nullable', $rules["labels.{$phone->id}"]);

        $validator = Validator::make(
            ['fields' => [$phone->id => '+44 7700 900461'], 'labels' => [$phone->id => $item->id]],
            $rules,
        );

        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_refuses_a_label_that_is_not_on_the_list_the_field_names(): void
    {
        $list = ValueList::factory()->create();
        $another = ValueList::factory()->create();
        $phone = Field::factory()->create(['type' => FieldTypeEnum::Phone, 'value_list_id' => $list->id]);
        $elsewhere = ValueListItem::factory()->create(['value_list_id' => $another->id]);

        $validator = Validator::make(
            ['fields' => [$phone->id => '+44 7700 900461'], 'labels' => [$phone->id => $elsewhere->id]],
            FieldValueRules::for(collect([$phone])),
        );

        $this->assertTrue($validator->fails());
    }

    #[Test]
    public function it_refuses_a_label_nobody_offers_any_more(): void
    {
        $list = ValueList::factory()->create();
        $email = Field::factory()->create(['type' => FieldTypeEnum::Email, 'value_list_id' => $list->id]);
        $retired = ValueListItem::factory()->disabled()->create(['value_list_id' => $list->id]);

        $validator = Validator::make(
            ['fields' => [$email->id => 'pam@dundermifflin.example'], 'labels' => [$email->id => $retired->id]],
            FieldValueRules::for(collect([$email])),
        );

        $this->assertTrue($validator->fails());
    }

    #[Test]
    public function it_asks_for_no_label_where_the_field_names_no_list(): void
    {
        $phone = Field::factory()->create(['type' => FieldTypeEnum::Phone, 'value_list_id' => null]);
        $text = Field::factory()->create(['type' => FieldTypeEnum::Text]);

        $rules = FieldValueRules::for(collect([$phone, $text]));

        $this->assertArrayNotHasKey("labels.{$phone->id}", $rules);
        $this->assertArrayNotHasKey("labels.{$text->id}", $rules);
    }

    #[Test]
    public function it_checks_every_item_of_a_multiple_choice(): void
    {
        $list = ValueList::factory()->create();
        $field = Field::factory()->choice($list)->create(['type' => FieldTypeEnum::Multiselect]);
        $item = ValueListItem::factory()->create(['value_list_id' => $list->id]);

        $rules = FieldValueRules::for(collect([$field]));

        $this->assertContains('array', $rules["fields.{$field->id}"]);
        $this->assertArrayHasKey("fields.{$field->id}.*", $rules);

        $validator = Validator::make(
            ['fields' => [$field->id => [$item->id, 9999]]],
            $rules,
        );

        $this->assertTrue($validator->fails());
    }
}
