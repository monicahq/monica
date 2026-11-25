<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Enums\DateFormatEnum;
use App\Enums\FieldTypeEnum;
use App\Enums\NumberFormatEnum;
use App\Enums\PossessionFieldKindEnum;
use App\Helpers\FieldValueFormatter;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\Possession;
use App\Models\PossessionField;
use App\Models\PossessionFieldValue;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FieldValueFormatterTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_reads_a_line_of_text_back(): void
    {
        $user = User::factory()->create();

        $this->assertEquals('Scranton', new FieldValueFormatter($user)->format(
            $this->value($user, FieldTypeEnum::Text, ['value_string' => 'Scranton']),
        ));
    }

    #[Test]
    public function it_reads_a_relation_back_as_the_name_of_the_record_it_names(): void
    {
        $user = User::factory()->create();
        $jan = Contact::factory()->create(['account_id' => $user->account_id, 'first_name' => 'Jan', 'last_name' => 'Levinson']);
        $sprinkles = Entity::factory()->create(['name' => 'Sprinkles']);

        $this->assertEquals('Jan Levinson', new FieldValueFormatter($user)->format(
            $this->value($user, FieldTypeEnum::Relation, ['related_contact_id' => $jan->id]),
        ));
        $this->assertEquals('Sprinkles', new FieldValueFormatter($user)->format(
            $this->value($user, FieldTypeEnum::Relation, ['related_entity_id' => $sprinkles->id]),
        ));
    }

    #[Test]
    public function it_reads_nothing_out_of_a_field_holding_nothing(): void
    {
        $user = User::factory()->create();

        $this->assertNull(new FieldValueFormatter($user)->format(
            $this->value($user, FieldTypeEnum::Text, ['value_string' => null]),
        ));
        $this->assertNull(new FieldValueFormatter($user)->format(
            $this->value($user, FieldTypeEnum::Text, ['value_string' => '   ']),
        ));
    }

    #[Test]
    public function it_reads_an_email_address_alone_when_nobody_said_what_it_is_for(): void
    {
        $user = User::factory()->create();

        $this->assertEquals('pam@dundermifflin.example', new FieldValueFormatter($user)->format(
            $this->value($user, FieldTypeEnum::Email, ['value_string' => 'pam@dundermifflin.example']),
        ));
    }

    #[Test]
    public function it_reads_a_number_with_the_separators_the_person_uses(): void
    {
        $user = User::factory()->create(['number_format' => NumberFormatEnum::DotAndComma]);

        $this->assertEquals('1.234,56', new FieldValueFormatter($user)->format(
            $this->value($user, FieldTypeEnum::Number, ['value_number' => '1234.560000']),
        ));
    }

    #[Test]
    public function it_reads_a_number_with_as_many_decimals_as_were_written(): void
    {
        $user = User::factory()->create(['number_format' => NumberFormatEnum::CommaAndDot]);

        $this->assertEquals('42', new FieldValueFormatter($user)->format(
            $this->value($user, FieldTypeEnum::Number, ['value_number' => '42.000000']),
        ));
        $this->assertEquals('42.5', new FieldValueFormatter($user)->format(
            $this->value($user, FieldTypeEnum::Number, ['value_number' => '42.500000']),
        ));
    }

    #[Test]
    public function it_reads_a_duration_in_the_largest_units_that_fit(): void
    {
        $user = User::factory()->create();
        $formatter = new FieldValueFormatter($user);

        $this->assertEquals('1 minute', $formatter->format($this->value($user, FieldTypeEnum::Duration, ['value_number' => '1.000000'])));
        $this->assertEquals('1 hour 30 minutes', $formatter->format($this->value($user, FieldTypeEnum::Duration, ['value_number' => '90.000000'])));
        $this->assertNull($formatter->format($this->value($user, FieldTypeEnum::Duration, ['value_number' => null])));
    }

    #[Test]
    public function it_reads_a_date_in_the_order_the_person_reads_dates(): void
    {
        $user = User::factory()->create(['date_format' => DateFormatEnum::YearFirst]);

        $this->assertEquals('2005-03-24', new FieldValueFormatter($user)->format(
            $this->value($user, FieldTypeEnum::Date, ['value_date' => '2005-03-24']),
        ));
    }

    #[Test]
    public function it_never_writes_the_year_of_a_date_whose_year_nobody_knows(): void
    {
        $user = User::factory()->create(['date_format' => DateFormatEnum::DayFirst]);

        $read = new FieldValueFormatter($user)->format($this->value($user, FieldTypeEnum::RecurringDate, [
            'value_date' => FieldValue::PLACEHOLDER_YEAR.'-03-15',
            'year_is_unknown' => true,
        ]));

        $this->assertEquals('15/03', $read);
        $this->assertStringNotContainsString((string) FieldValue::PLACEHOLDER_YEAR, (string) $read);
    }

    #[Test]
    public function it_leaves_no_separator_behind_where_the_year_was(): void
    {
        $user = User::factory()->create(['date_format' => DateFormatEnum::MonthSpelled]);

        $read = new FieldValueFormatter($user)->format($this->value($user, FieldTypeEnum::RecurringDate, [
            'value_date' => FieldValue::PLACEHOLDER_YEAR.'-03-15',
            'year_is_unknown' => true,
        ]));

        $this->assertEquals('15 March', $read);
    }

    #[Test]
    public function it_reduces_a_date_nobody_knows_the_month_of_to_its_year(): void
    {
        $user = User::factory()->create(['date_format' => DateFormatEnum::DayFirst]);

        $this->assertEquals('1998', new FieldValueFormatter($user)->format(
            $this->value($user, FieldTypeEnum::RecurringDate, [
                'value_date' => '1998-01-01',
                'month_is_unknown' => true,
            ]),
        ));
    }

    #[Test]
    public function it_reads_nothing_out_of_a_date_with_neither_a_year_nor_a_month(): void
    {
        $user = User::factory()->create();

        $this->assertNull(new FieldValueFormatter($user)->format(
            $this->value($user, FieldTypeEnum::RecurringDate, [
                'value_date' => FieldValue::PLACEHOLDER_YEAR.'-01-01',
                'year_is_unknown' => true,
                'month_is_unknown' => true,
            ]),
        ));
    }

    #[Test]
    public function it_reads_a_choice_as_the_label_of_the_item_it_points_at(): void
    {
        $user = User::factory()->create();
        $item = ValueListItem::factory()->create(['account_id' => $user->account_id, 'label' => 'Vegetarian']);

        $this->assertEquals('Vegetarian', new FieldValueFormatter($user)->format(
            $this->value($user, FieldTypeEnum::Select, ['value_list_item_id' => $item->id]),
        ));
    }

    #[Test]
    public function it_reads_several_choices_in_the_order_the_list_offers_them(): void
    {
        $user = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        $second = ValueListItem::factory()->create([
            'account_id' => $user->account_id,
            'value_list_id' => $list->id,
            'label' => 'Sailing',
            'position' => 2,
        ]);
        $first = ValueListItem::factory()->create([
            'account_id' => $user->account_id,
            'value_list_id' => $list->id,
            'label' => 'Beets',
            'position' => 1,
        ]);

        $this->assertEquals('Beets, Sailing', new FieldValueFormatter($user)->format(
            $this->value($user, FieldTypeEnum::Multiselect, [
                'value_list_item_ids' => [$second->id, $first->id],
            ]),
        ));
    }

    #[Test]
    public function it_reads_nothing_out_of_a_multiple_choice_nobody_ticked(): void
    {
        $user = User::factory()->create();

        $this->assertNull(new FieldValueFormatter($user)->format(
            $this->value($user, FieldTypeEnum::Multiselect, ['value_list_item_ids' => []]),
        ));
    }

    #[Test]
    public function it_reads_nothing_out_of_a_type_it_does_not_render_yet(): void
    {
        $user = User::factory()->create();

        $this->assertNull(new FieldValueFormatter($user)->format(
            $this->value($user, FieldTypeEnum::Relation, ['value_string' => null]),
        ));
    }

    /** @param  array<string, mixed>  $attributes */
    private function value(User $user, FieldTypeEnum $type, array $attributes): FieldValue
    {
        $field = Field::factory()->create(['account_id' => $user->account_id, 'type' => $type]);

        return FieldValue::factory()->create(array_merge([
            'account_id' => $user->account_id,
            'field_id' => $field->id,
            'value_string' => null,
        ], $attributes));
    }

    #[Test]
    public function it_reads_an_empty_postal_address_back_as_nothing(): void
    {
        $user = User::factory()->create();

        $this->assertNull(new FieldValueFormatter($user)->format(
            $this->value($user, FieldTypeEnum::Address, ['value_string' => null, 'value_json' => null]),
        ));
    }

    #[Test]
    public function it_reads_the_wording_typed_beside_the_open_label_of_an_address(): void
    {
        $user = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        $custom = ValueListItem::factory()->create([
            'account_id' => $user->account_id,
            'value_list_id' => $list->id,
            'key' => ValueListItem::OPEN_KEY,
            'label' => 'Custom...',
        ]);

        $this->assertEquals('Schrute Farms, Honesdale (Beet farm)', new FieldValueFormatter($user)->format(
            $this->value($user, FieldTypeEnum::Address, [
                'value_json' => ['street' => 'Schrute Farms', 'city' => 'Honesdale'],
                'value_list_item_id' => $custom->id,
                'label_wording' => 'Beet farm',
            ]),
        ));
    }

    #[Test]
    public function it_joins_nothing_out_of_an_address_whose_parts_are_all_empty(): void
    {
        $this->assertNull(FieldValueFormatter::addressLine(parts: ['street' => '', 'city' => ' '], accountId: 1));
        $this->assertNull(FieldValueFormatter::addressLine(parts: [], accountId: 1));
        $this->assertNull(FieldValueFormatter::addressLine(parts: null, accountId: 1));
    }

    #[Test]
    public function it_lays_an_address_out_the_way_its_country_writes_it(): void
    {
        $user = User::factory()->create();
        $parts = ['street' => '1725 Slough Avenue', 'city' => 'Scranton', 'region' => 'PA', 'postal_code' => '18505'];

        $this->assertEquals(
            ['1725 Slough Avenue', 'Scranton, PA 18505', 'United States'],
            FieldValueFormatter::addressLines(parts: [...$parts, 'country' => 'United States'], accountId: $user->account_id),
        );
        $this->assertEquals(
            ['1725 Slough Avenue', '18505 Scranton', 'PA', 'France'],
            FieldValueFormatter::addressLines(parts: [...$parts, 'country' => 'France'], accountId: $user->account_id),
        );
    }

    #[Test]
    public function it_names_a_country_recorded_by_its_code_in_the_language_of_the_reader(): void
    {
        $user = User::factory()->create();
        $parts = ['street' => '1-1 Marunouchi', 'city' => 'Tokyo', 'postal_code' => '100-0005'];
        $laidOutInEnglish = FieldValueFormatter::addressLines(parts: [...$parts, 'country' => 'Japan'], accountId: $user->account_id);

        app()->setLocale('fr_FR');

        $recorded = FieldValueFormatter::addressLines(parts: [...$parts, 'country' => 'Japan', 'country_code' => 'JP'], accountId: $user->account_id);
        $written = FieldValueFormatter::addressLines(parts: [...$parts, 'country' => 'Japan'], accountId: $user->account_id);

        $this->assertEquals([...array_slice($laidOutInEnglish, 0, -1), 'Japon'], $recorded);
        $this->assertEquals($laidOutInEnglish, $written);
    }

    #[Test]
    public function it_drops_the_separator_a_missing_part_leaves_in_a_layout(): void
    {
        $user = User::factory()->create();

        $this->assertEquals(
            ['Scranton, 18505', 'US'],
            FieldValueFormatter::addressLines(parts: ['city' => 'Scranton', 'postal_code' => '18505', 'country' => 'US'], accountId: $user->account_id),
        );
        $this->assertEquals(
            ['Paris', 'France'],
            FieldValueFormatter::addressLines(parts: ['city' => 'Paris', 'country' => 'France'], accountId: $user->account_id),
        );
    }

    #[Test]
    public function it_keeps_the_stored_order_of_an_address_whose_country_is_unknown(): void
    {
        $user = User::factory()->create();
        $parts = ['street' => 'Cair Paravel', 'city' => 'Narnia City', 'postal_code' => '18505'];

        $this->assertEquals(
            ['Cair Paravel', 'Narnia City', '18505', 'Narnia'],
            FieldValueFormatter::addressLines(parts: [...$parts, 'country' => 'Narnia'], accountId: $user->account_id),
        );
        $this->assertEquals(
            ['Cair Paravel', 'Narnia City', '18505'],
            FieldValueFormatter::addressLines(parts: $parts, accountId: $user->account_id),
        );
    }

    #[Test]
    public function it_reads_an_address_on_one_line_in_the_order_of_its_lines(): void
    {
        $user = User::factory()->create();

        $this->assertEquals('1725 Slough Avenue, 18505 Scranton, France', new FieldValueFormatter($user)->format(
            $this->value($user, FieldTypeEnum::Address, [
                'value_json' => ['street' => '1725 Slough Avenue', 'city' => 'Scranton', 'postal_code' => '18505', 'country' => 'France'],
            ]),
        ));
    }

    #[Test]
    public function it_groups_a_phone_number_written_with_its_international_prefix(): void
    {
        $this->assertEquals('+33 6 12 34 56 78', FieldValueFormatter::phone('+33612345678'));
        $this->assertEquals('+1 570-555-0123', FieldValueFormatter::phone('+15705550123'));
    }

    #[Test]
    public function it_reads_any_other_phone_number_as_it_was_typed(): void
    {
        $this->assertEquals('06 12 34 56 78', FieldValueFormatter::phone('06 12 34 56 78'));
        $this->assertEquals('ask Pam', FieldValueFormatter::phone('ask Pam'));
        $this->assertEquals('+33612345678 (ask Pam)', FieldValueFormatter::phone('+33612345678 (ask Pam)'));
        $this->assertEquals('+999123', FieldValueFormatter::phone('+999123'));
    }

    #[Test]
    public function it_reads_a_phone_number_grouped_beside_its_label(): void
    {
        $user = User::factory()->create();

        $this->assertEquals('+33 6 12 34 56 78', new FieldValueFormatter($user)->format(
            $this->value($user, FieldTypeEnum::Phone, ['value_string' => '+33612345678']),
        ));
    }

    #[Test]
    public function it_writes_a_possession_number_in_the_separators_of_the_user(): void
    {
        $user = User::factory()->create(['number_format' => NumberFormatEnum::CommaAndDot]);

        $this->assertEquals('1,250.5', new FieldValueFormatter($user)->formatPossessionValue(
            $this->possessionValue($user, PossessionFieldKindEnum::Number, '1250.5'),
        ));
    }

    #[Test]
    public function it_writes_a_possession_date_in_the_format_of_the_user(): void
    {
        $user = User::factory()->create(['date_format' => DateFormatEnum::DayFirst]);

        $this->assertEquals('24/03/2005', new FieldValueFormatter($user)->formatPossessionValue(
            $this->possessionValue($user, PossessionFieldKindEnum::Date, '2005-03-24'),
        ));
    }

    #[Test]
    public function it_writes_a_possession_text_and_option_as_stored(): void
    {
        $user = User::factory()->create();
        $formatter = new FieldValueFormatter($user);

        $this->assertEquals('Dundie', $formatter->formatPossessionValue($this->possessionValue($user, PossessionFieldKindEnum::Select, 'Dundie')));
        $this->assertNull($formatter->formatPossessionValue($this->possessionValue($user, PossessionFieldKindEnum::Text, ' ')));
    }

    private function possessionValue(User $user, PossessionFieldKindEnum $kind, string $value): PossessionFieldValue
    {
        $possession = Possession::factory()->create(['account_id' => $user->account_id]);
        $field = PossessionField::factory()->create([
            'account_id' => $user->account_id,
            'possession_type_id' => $possession->possession_type_id,
            'kind' => $kind,
        ]);

        return PossessionFieldValue::factory()->create([
            'possession_id' => $possession->id,
            'possession_field_id' => $field->id,
            'value' => $value,
        ]);
    }
}
