<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Enums\CountryEnum;
use App\Enums\FieldTypeEnum;
use App\Enums\RelationTargetEnum;
use App\Enums\ValueListEnum;
use App\Helpers\FieldValueRules;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\ValueList;
use App\Models\ValueListItem;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Laravel\Pennant\Feature;
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
    public function it_takes_a_duration_only_as_a_whole_number_of_minutes_within_its_bounds(): void
    {
        $duration = Field::factory()->create(['type' => FieldTypeEnum::Duration]);
        $rules = FieldValueRules::for(collect([$duration]));

        $this->assertTrue(Validator::make(['fields' => [$duration->id => 90]], $rules)->passes());

        foreach ([0, 2.5, 'abc', 100001] as $refused) {
            $this->assertTrue(Validator::make(['fields' => [$duration->id => $refused]], $rules)->fails());
        }
    }

    #[Test]
    public function it_takes_a_number_only_within_what_the_column_holds(): void
    {
        $number = Field::factory()->create(['type' => FieldTypeEnum::Number]);
        $rules = FieldValueRules::for(collect([$number]));

        foreach ([FieldValue::LARGEST_NUMBER, -FieldValue::LARGEST_NUMBER, 2.5] as $accepted) {
            $this->assertTrue(Validator::make(['fields' => [$number->id => $accepted]], $rules)->passes());
        }

        foreach ([1000000000000000, -1000000000000000, FieldValue::LARGEST_NUMBER + 1, '1e400'] as $refused) {
            $this->assertTrue(Validator::make(['fields' => [$number->id => $refused]], $rules)->fails());
        }
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

    #[Test]
    public function it_asks_for_the_seven_parts_of_an_address(): void
    {
        $address = Field::factory()->create(['type' => FieldTypeEnum::Address]);

        $rules = FieldValueRules::for(collect([$address]));

        $this->assertEquals(['nullable', 'array'], $rules["fields.{$address->id}"]);

        foreach (['po_box', 'extended', 'street', 'city', 'region', 'postal_code'] as $part) {
            $this->assertEquals(['nullable', 'string', 'max:255'], $rules["fields.{$address->id}.{$part}"]);
        }

        $this->assertContains('nullable', $rules["fields.{$address->id}.country"]);
    }

    #[Test]
    public function it_refuses_a_part_of_an_address_longer_than_a_box_holds(): void
    {
        $address = Field::factory()->create(['type' => FieldTypeEnum::Address]);

        $validator = Validator::make(
            ['fields' => [$address->id => ['street' => str_repeat('a', 256)]]],
            FieldValueRules::for(collect([$address])),
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has("fields.{$address->id}.street"));
    }

    #[Test]
    public function it_accepts_a_country_the_account_offers_and_the_word_that_keeps_the_stored_one(): void
    {
        $address = Field::factory()->create(['type' => FieldTypeEnum::Address]);
        $canada = $this->countryOf($address, CountryEnum::Canada);
        $rules = FieldValueRules::for(collect([$address]));

        $picked = Validator::make(['fields' => [$address->id => ['country' => (string) $canada->id]]], $rules);
        $kept = Validator::make(['fields' => [$address->id => ['country' => 'keep']]], $rules);
        $empty = Validator::make(['fields' => [$address->id => ['country' => '']]], $rules);

        $this->assertFalse($picked->fails());
        $this->assertFalse($kept->fails());
        $this->assertFalse($empty->fails());
    }

    #[Test]
    public function it_refuses_a_country_that_is_retired_on_another_list_or_typed_in(): void
    {
        $address = Field::factory()->create(['type' => FieldTypeEnum::Address]);
        $retired = $this->countryOf($address, CountryEnum::Canada, active: false);
        $phoneLabels = ValueList::factory()->supplied(ValueListEnum::PhoneLabels)->create(['account_id' => $address->account_id]);
        $mobile = ValueListItem::factory()->create(['account_id' => $address->account_id, 'value_list_id' => $phoneLabels->id]);
        $rules = FieldValueRules::for(collect([$address]));

        foreach ([(string) $retired->id, (string) $mobile->id, 'Canada'] as $country) {
            $validator = Validator::make(['fields' => [$address->id => ['country' => $country]]], $rules);

            $this->assertTrue($validator->fails(), "The country {$country} was accepted.");
            $this->assertTrue($validator->errors()->has("fields.{$address->id}.country"));
        }
    }

    #[Test]
    public function it_refuses_a_country_of_another_account(): void
    {
        $address = Field::factory()->create(['type' => FieldTypeEnum::Address]);
        $this->countryOf($address, CountryEnum::Canada);
        $theirs = $this->countryOf(Field::factory()->create(['type' => FieldTypeEnum::Address]), CountryEnum::France);

        $validator = Validator::make(
            ['fields' => [$address->id => ['country' => (string) $theirs->id]]],
            FieldValueRules::for(collect([$address])),
        );

        $this->assertTrue($validator->fails());
    }

    #[Test]
    public function it_asks_for_the_parts_of_an_address_inside_every_repetition(): void
    {
        $list = ValueList::factory()->create();
        $addresses = Field::factory()->create(['type' => FieldTypeEnum::Repeater, 'is_repeatable' => true]);
        $address = Field::factory()->create([
            'type' => FieldTypeEnum::Address,
            'section_version_id' => $addresses->section_version_id,
            'parent_field_id' => $addresses->id,
            'value_list_id' => $list->id,
        ]);
        $addresses->setRelation('children', collect([$address]));

        $rules = FieldValueRules::for(collect([$addresses]));
        $prefix = "fields.{$addresses->id}.*.values.{$address->id}";

        $this->assertEquals(['nullable', 'array'], $rules[$prefix]);
        $this->assertEquals(['nullable', 'string', 'max:255'], $rules["{$prefix}.street"]);
        $this->assertArrayHasKey("{$prefix}.country", $rules);
        $this->assertArrayHasKey("fields.{$addresses->id}.*.labels.{$address->id}", $rules);
    }

    #[Test]
    public function it_accepts_somebody_of_the_vault_the_values_are_written_in(): void
    {
        $scranton = Vault::factory()->create();
        $field = Field::factory()->relation()->create(['account_id' => $scranton->account_id]);
        $jim = Contact::factory()->create(['vault_id' => $scranton->id]);

        $validator = Validator::make(['fields' => [$field->id => $jim->uuid]], FieldValueRules::for(collect([$field]), $scranton));

        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_refuses_somebody_of_another_vault_or_nobody_at_all(): void
    {
        $scranton = Vault::factory()->create();
        $stamford = Vault::factory()->create(['account_id' => $scranton->account_id]);
        $field = Field::factory()->relation()->create(['account_id' => $scranton->account_id]);
        $andy = Contact::factory()->create(['vault_id' => $stamford->id]);
        $rules = FieldValueRules::for(collect([$field]), $scranton);

        $this->assertTrue(Validator::make(['fields' => [$field->id => $andy->uuid]], $rules)->fails());
        $this->assertTrue(Validator::make(['fields' => [$field->id => 'not-a-record']], $rules)->fails());
    }

    #[Test]
    public function it_refuses_a_record_of_another_kind_than_the_relation_asks_for(): void
    {
        $scranton = Vault::factory()->create();
        $animals = EntityType::factory()->create(['account_id' => $scranton->account_id]);
        $plants = EntityType::factory()->create(['account_id' => $scranton->account_id]);
        $field = Field::factory()->relation()->create([
            'account_id' => $scranton->account_id,
            'relation_target' => RelationTargetEnum::Entity,
            'relation_entity_type_id' => $animals->id,
        ]);
        $sprinkles = Entity::factory()->create(['vault_id' => $scranton->id, 'entity_type_id' => $animals->id]);
        $fern = Entity::factory()->create(['vault_id' => $scranton->id, 'entity_type_id' => $plants->id]);
        $rules = FieldValueRules::for(collect([$field]), $scranton);

        $this->assertFalse(Validator::make(['fields' => [$field->id => $sprinkles->uuid]], $rules)->fails());
        $this->assertTrue(Validator::make(['fields' => [$field->id => $fern->uuid]], $rules)->fails());
    }

    #[Test]
    public function it_refuses_a_file_and_says_why_while_file_upload_is_off_for_the_account(): void
    {
        $vault = Vault::factory()->create();
        $field = Field::factory()->create(['type' => FieldTypeEnum::Image, 'is_required' => false]);
        $vault->account->features()->deactivate('file-upload');
        Feature::flushCache();

        $withAFile = Validator::make(
            ['fields' => [$field->id => UploadedFile::fake()->image('dwight.jpg')]],
            FieldValueRules::for(collect([$field]), $vault),
        );
        $withoutAFile = Validator::make(['fields' => []], FieldValueRules::for(collect([$field]), $vault));

        $this->assertTrue($withAFile->errors()->has("fields.{$field->id}"));
        $this->assertFalse($withoutAFile->fails());
    }

    private function countryOf(Field $field, CountryEnum $country, bool $active = true): ValueListItem
    {
        $countries = ValueList::query()
            ->where('account_id', $field->account_id)
            ->where('key', ValueListEnum::Countries->value)
            ->first()
            ?? ValueList::factory()->supplied(ValueListEnum::Countries)->create(['account_id' => $field->account_id]);

        return ValueListItem::factory()->supplied($country)->create([
            'account_id' => $field->account_id,
            'value_list_id' => $countries->id,
            'is_active' => $active,
        ]);
    }
}
