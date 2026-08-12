<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Enums\DateFormatEnum;
use App\Enums\FieldTypeEnum;
use App\Enums\NumberFormatEnum;
use App\Helpers\FieldValueFormatter;
use App\Models\Field;
use App\Models\FieldValue;
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
    public function it_reads_a_long_text_back(): void
    {
        $user = User::factory()->create();

        $this->assertEquals('Sells paper.', new FieldValueFormatter($user)->format(
            $this->value($user, FieldTypeEnum::Textarea, ['value_text' => 'Sells paper.']),
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
    public function it_reads_a_yes_or_no_in_the_language_of_whoever_is_looking(): void
    {
        $user = User::factory()->create();
        $formatter = new FieldValueFormatter($user);

        $this->assertEquals(__('Yes'), $formatter->format(
            $this->value($user, FieldTypeEnum::Boolean, ['value_boolean' => true]),
        ));
        $this->assertEquals(__('No'), $formatter->format(
            $this->value($user, FieldTypeEnum::Boolean, ['value_boolean' => false]),
        ));
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

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function value(User $user, FieldTypeEnum $type, array $attributes): FieldValue
    {
        $field = Field::factory()->create(['account_id' => $user->account_id, 'type' => $type]);

        return FieldValue::factory()->create(array_merge([
            'account_id' => $user->account_id,
            'field_id' => $field->id,
            'value_string' => null,
        ], $attributes));
    }
}
