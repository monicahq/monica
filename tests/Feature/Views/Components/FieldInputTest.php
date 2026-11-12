<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use App\Enums\CountryEnum;
use App\Enums\FieldTypeEnum;
use App\Enums\ValueListEnum;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestView;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FieldInputTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_phone_offers_the_number_and_what_it_is_for_together(): void
    {
        $field = $this->phoneNaming($this->listOffering(['Mobile', 'Work']));

        $rendered = $this->render($field);

        $rendered->assertSee('name="fields['.$field->id.']"', escape: false);
        $rendered->assertSee('name="labels['.$field->id.']"', escape: false);
        $rendered->assertSee('Mobile');
        $rendered->assertSee('Work');
    }

    #[Test]
    public function it_names_the_value_after_the_field_it_belongs_to(): void
    {
        $field = $this->phoneNaming($this->listOffering(['Mobile']));

        $this->render($field)->assertSee('aria-label="Mobile number"', escape: false);
    }

    #[Test]
    public function a_field_naming_no_list_offers_no_label_at_all(): void
    {
        $field = Field::factory()->create(['type' => FieldTypeEnum::Phone, 'value_list_id' => null]);

        $rendered = $this->render($field);

        $rendered->assertDontSee('name="labels['.$field->id.']"', escape: false);
        $rendered->assertSee('name="fields['.$field->id.']"', escape: false);
    }

    #[Test]
    public function it_never_offers_a_label_nobody_offers_any_more(): void
    {
        $list = ValueList::factory()->create();
        ValueListItem::factory()->create(['value_list_id' => $list->id, 'label' => 'Mobile']);
        ValueListItem::factory()->disabled()->create(['value_list_id' => $list->id, 'label' => 'Fax']);

        $rendered = $this->render($this->phoneNaming($list));

        $rendered->assertSee('Mobile');
        $rendered->assertDontSee('Fax');
    }

    #[Test]
    public function an_email_asks_for_an_address_rather_than_a_number(): void
    {
        $list = $this->listOffering(['Personal']);
        $field = Field::factory()->create([
            'type' => FieldTypeEnum::Email,
            'label' => 'Email',
            'value_list_id' => $list->id,
        ]);

        $rendered = $this->render($field);

        $rendered->assertSee('type="email"', escape: false);
        $rendered->assertSee('aria-label="Email"', escape: false);
    }

    #[Test]
    public function an_address_offers_its_seven_parts_in_the_order_an_address_is_written(): void
    {
        $field = $this->addressField();

        $rendered = $this->render($field);

        $rendered->assertSeeInOrder([
            'name="fields['.$field->id.'][street]"',
            'name="fields['.$field->id.'][extended]"',
            'name="fields['.$field->id.'][po_box]"',
            'name="fields['.$field->id.'][city]"',
            'name="fields['.$field->id.'][region]"',
            'name="fields['.$field->id.'][postal_code]"',
            'name="fields['.$field->id.'][country]"',
        ], escape: false);
    }

    #[Test]
    public function it_names_every_part_of_an_address_on_a_label_of_its_own(): void
    {
        $field = $this->addressField();

        $rendered = $this->render($field);

        foreach (['street', 'extended', 'po_box', 'city', 'region', 'postal_code', 'country'] as $part) {
            $rendered->assertSee('for="field-'.$field->id.'-'.$part.'"', escape: false);
            $rendered->assertSee('id="field-'.$field->id.'-'.$part.'"', escape: false);
        }
    }

    #[Test]
    public function an_address_offers_what_it_is_for_when_the_field_names_a_list(): void
    {
        $labelled = $this->addressField($this->listOffering(['Home', 'Holiday home']));
        $unlabelled = $this->addressField();

        $this->render($labelled)
            ->assertSee('name="labels['.$labelled->id.']"', escape: false)
            ->assertSee('Holiday home');
        $this->render($unlabelled)->assertDontSee('name="labels['.$unlabelled->id.']"', escape: false);
    }

    #[Test]
    public function the_country_is_picked_from_the_countries_the_account_offers(): void
    {
        $field = $this->addressField();
        $canada = $this->countryOf($field, CountryEnum::Canada);
        $france = $this->countryOf($field, CountryEnum::France, active: false);
        $spain = $this->countryOf(Field::factory()->create(), CountryEnum::Spain);

        $rendered = $this->render($field);

        $rendered->assertSee('<option value="'.$canada->id.'"', escape: false);
        $rendered->assertDontSee('<option value="'.$france->id.'"', escape: false);
        $rendered->assertDontSee('<option value="'.$spain->id.'"', escape: false);
        $rendered->assertDontSee('value="keep"', escape: false);
    }

    #[Test]
    public function it_picks_the_stored_country_whatever_its_case(): void
    {
        $field = $this->addressField();
        $canada = $this->countryOf($field, CountryEnum::Canada);
        $value = $this->addressValue($field, ['city' => 'Toronto', 'country' => 'CANADA']);

        $rendered = $this->render($field, $value);

        $rendered->assertSee('value="'.$canada->id.'" selected', escape: false);
        $rendered->assertDontSee('value="keep"', escape: false);
    }

    #[Test]
    public function it_offers_back_a_country_a_device_wrote_that_the_list_does_not_hold(): void
    {
        $field = $this->addressField();
        $this->countryOf($field, CountryEnum::Canada);
        $value = $this->addressValue($field, ['city' => 'Scranton', 'country' => 'USA']);

        $this->render($field, $value)->assertSee('<option value="keep" selected>USA</option>', escape: false);
    }

    #[Test]
    public function it_shows_back_every_part_of_a_stored_address(): void
    {
        $field = $this->addressField();
        $value = $this->addressValue($field, ['po_box' => 'PO Box 12', 'extended' => 'Suite 3', 'street' => '1725 Slough Avenue']);

        $rendered = $this->render($field, $value);

        $rendered->assertSee('value="PO Box 12"', escape: false);
        $rendered->assertSee('value="Suite 3"', escape: false);
    }

    #[Test]
    public function a_sensitive_address_hides_every_part_while_it_is_typed(): void
    {
        $field = $this->addressField(attributes: ['is_sensitive' => true]);
        $plain = $this->addressField();

        $this->assertSame(6, substr_count((string) $this->render($field), 'type="password"'));
        $this->render($plain)->assertDontSee('type="password"', escape: false);
    }

    #[Test]
    public function an_address_inside_a_repetition_carries_the_names_the_repetition_gives_it(): void
    {
        $field = $this->addressField($this->listOffering(['Home']));

        $rendered = $this->blade(
            '<x-field-input :field="$field" name="fields[9][0][values]['.$field->id.']" label-name="fields[9][0][labels]['.$field->id.']" />',
            ['field' => $field->fresh()],
        );

        $rendered->assertSee('name="fields[9][0][values]['.$field->id.'][street]"', escape: false);
        $rendered->assertSee('name="fields[9][0][values]['.$field->id.'][country]"', escape: false);
        $rendered->assertSee('name="fields[9][0][labels]['.$field->id.']"', escape: false);
    }

    /** @param  array<string, mixed>  $attributes */
    #[Test]
    public function a_multiple_choice_of_categories_offers_to_add_a_category(): void
    {
        $this->actingAs(User::factory()->create());
        $list = $this->listOffering(['Family', 'Colleague']);
        $list->update(['key' => ValueListEnum::ContactCategories->value]);
        $field = $this->multipleChoiceNaming($list);

        $rendered = $this->render($field);

        $rendered->assertSee('name="new_choices['.$field->id.'][]"', escape: false);
        $rendered->assertSee('Add a category');
    }

    #[Test]
    public function a_multiple_choice_of_another_list_offers_to_add_a_value(): void
    {
        $this->actingAs(User::factory()->create());
        $field = $this->multipleChoiceNaming($this->listOffering(['Sales', 'Accounting']));

        $rendered = $this->render($field);

        $rendered->assertSee('Add a value');
        $rendered->assertDontSee('Add a category');
    }

    #[Test]
    public function a_multiple_choice_keeps_a_held_value_that_is_no_longer_offered(): void
    {
        $this->actingAs(User::factory()->create());
        $list = $this->listOffering(['Sales', 'Accounting', 'Warehouse']);
        $field = $this->multipleChoiceNaming($list);
        $list->items()->whereIn('label', ['Sales', 'Warehouse'])->update(['is_active' => false]);
        $sales = $list->items()->where('label', 'Sales')->first();
        $value = FieldValue::factory()->make(['field_id' => $field->id, 'value_list_item_ids' => [$sales->id]]);

        $rendered = $this->render($field, $value);

        $rendered->assertSee('name="fields['.$field->id.'][]"', escape: false);
        $rendered->assertSee('value="'.$sales->id.'"', escape: false);
        $rendered->assertSee('Accounting');
        $rendered->assertDontSee('Warehouse');
    }

    #[Test]
    public function a_multiple_choice_offers_no_value_of_ones_own_when_its_list_takes_none(): void
    {
        $this->actingAs(User::factory()->create());
        $list = $this->listOffering(['Sales']);
        $list->update(['allows_user_values' => false]);
        $field = $this->multipleChoiceNaming($list);

        $rendered = $this->render($field);

        $rendered->assertSee('Sales');
        $rendered->assertDontSee('new_choices', escape: false);
        $rendered->assertDontSee('Add a value');
    }

    #[Test]
    public function a_file_field_offers_no_upload_while_file_upload_is_off(): void
    {
        $field = Field::factory()->create(['type' => FieldTypeEnum::Image, 'label' => 'Portrait', 'is_required' => false]);

        $this->render($field)->assertSee('type="file"', escape: false);

        Feature::for(null)->deactivate('file-upload');
        Feature::flushCache();

        $rendered = $this->render($field);

        $rendered->assertDontSee('type="file"', escape: false);
        $rendered->assertSee('Portrait');
    }

    private function addressField(?ValueList $labels = null, array $attributes = []): Field
    {
        return Field::factory()->create(array_merge([
            'type' => FieldTypeEnum::Address,
            'label' => 'Home address',
            'value_list_id' => $labels?->id,
        ], $attributes));
    }

    /** @param  array<string, string>  $parts */
    private function addressValue(Field $field, array $parts): FieldValue
    {
        return FieldValue::factory()->create([
            'account_id' => $field->account_id,
            'field_id' => $field->id,
            'value_string' => null,
            'value_json' => array_merge(array_fill_keys(FieldValue::ADDRESS_PARTS, ''), $parts),
        ]);
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

    /** @param  list<string>  $labels */
    private function listOffering(array $labels): ValueList
    {
        $list = ValueList::factory()->create();

        foreach ($labels as $label) {
            ValueListItem::factory()->create(['value_list_id' => $list->id, 'label' => $label]);
        }

        return $list;
    }

    private function multipleChoiceNaming(ValueList $list): Field
    {
        return Field::factory()->create([
            'type' => FieldTypeEnum::Multiselect,
            'label' => 'Department',
            'value_list_id' => $list->id,
        ]);
    }

    private function phoneNaming(ValueList $list): Field
    {
        return Field::factory()->create([
            'type' => FieldTypeEnum::Phone,
            'label' => 'Mobile number',
            'value_list_id' => $list->id,
        ]);
    }

    private function render(Field $field, ?FieldValue $value = null): TestView
    {
        return $this->blade(
            '<x-field-input :field="$field" :value="$value" />',
            ['field' => $field->fresh(), 'value' => $value],
        );
    }
}
