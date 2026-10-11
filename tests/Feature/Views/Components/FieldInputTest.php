<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use App\Enums\CountryEnum;
use App\Enums\FieldTypeEnum;
use App\Enums\ValueListEnum;
use App\Models\Field;
use App\Models\FieldValue;
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
    public function it_groups_the_number_and_its_label_under_the_name_of_the_field(): void
    {
        $field = $this->phoneNaming($this->listOffering(['Mobile']));

        $rendered = $this->render($field);

        $rendered->assertSee('<fieldset', escape: false);
        $rendered->assertSee('<legend', escape: false);
        $rendered->assertSee('Mobile number');
    }

    #[Test]
    public function the_label_names_itself_on_hover_rather_than_taking_a_line_of_its_own(): void
    {
        $field = $this->phoneNaming($this->listOffering(['Mobile']));

        $rendered = $this->render($field);

        $rendered->assertSee('title="Type"', escape: false);
        $rendered->assertSee('aria-label="Type"', escape: false);
        $rendered->assertDontSee('What it is for');
    }

    #[Test]
    public function it_names_the_value_after_the_field_it_belongs_to(): void
    {
        $field = $this->phoneNaming($this->listOffering(['Mobile']));

        $this->render($field)->assertSee('aria-label="Mobile number"', escape: false);
    }

    #[Test]
    public function the_open_label_offers_a_wording_of_its_own(): void
    {
        $list = $this->listOffering(['Mobile']);
        ValueListItem::factory()->create(['value_list_id' => $list->id, 'key' => ValueListItem::OPEN_KEY, 'label' => 'Custom...']);

        $field = $this->phoneNaming($list);
        $rendered = $this->render($field);

        $rendered->assertSee('name="label_wordings['.$field->id.']"', escape: false);
        $rendered->assertSee('Pick from the list');
    }

    #[Test]
    public function it_shows_back_the_wording_somebody_typed(): void
    {
        $list = $this->listOffering(['Mobile']);
        $other = ValueListItem::factory()->create(['value_list_id' => $list->id, 'key' => ValueListItem::OPEN_KEY, 'label' => 'Custom...']);
        $field = $this->phoneNaming($list);
        $value = FieldValue::factory()->create([
            'field_id' => $field->id,
            'value_string' => '+44 7700 900461',
            'value_list_item_id' => $other->id,
            'label_wording' => 'Boat',
        ]);

        $this->render($field, $value)->assertSee('value="Boat"', escape: false);
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
    public function it_shows_back_the_label_somebody_already_picked(): void
    {
        $list = $this->listOffering(['Mobile', 'Work']);
        $work = ValueListItem::query()->where('value_list_id', $list->id)->where('label', 'Work')->firstOrFail();
        $field = $this->phoneNaming($list);
        $value = FieldValue::factory()->create([
            'field_id' => $field->id,
            'value_string' => '+44 7700 900461',
            'value_list_item_id' => $work->id,
        ]);

        $this->render($field, $value)
            ->assertSee('value="'.$work->id.'" selected', escape: false);
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

        $rendered->assertSeeInOrder(['Street', 'Extended street line', 'Post office box', 'City', 'Region', 'Postcode', 'Country']);
    }

    #[Test]
    public function it_groups_the_parts_of_an_address_under_the_name_of_the_field(): void
    {
        $field = $this->addressField();

        $this->render($field)->assertSeeInOrder(['<fieldset', '<legend', 'Home address', 'Street'], escape: false);
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
    public function the_open_label_of_an_address_offers_a_wording_of_its_own(): void
    {
        $list = $this->listOffering(['Home']);
        ValueListItem::factory()->create(['value_list_id' => $list->id, 'key' => ValueListItem::OPEN_KEY, 'label' => 'Custom...']);
        $field = $this->addressField($list);

        $rendered = $this->render($field);

        $rendered->assertSee('name="label_wordings['.$field->id.']"', escape: false);
        $rendered->assertSee('Custom...');
    }

    #[Test]
    public function an_address_shows_back_the_wording_somebody_typed_for_its_label(): void
    {
        $list = $this->listOffering(['Home']);
        $custom = ValueListItem::factory()->create(['value_list_id' => $list->id, 'key' => ValueListItem::OPEN_KEY, 'label' => 'Custom...']);
        $field = $this->addressField($list);
        $value = FieldValue::factory()->create([
            'field_id' => $field->id,
            'value_string' => null,
            'value_json' => array_merge(array_fill_keys(FieldValue::ADDRESS_PARTS, ''), ['street' => 'Schrute Farms']),
            'value_list_item_id' => $custom->id,
            'label_wording' => 'Beet farm',
        ]);

        $this->render($field, $value)->assertSee('value="Beet farm"', escape: false);
    }

    #[Test]
    public function the_country_is_picked_from_the_countries_the_account_offers(): void
    {
        $field = $this->addressField();
        $canada = $this->countryOf($field, CountryEnum::Canada);
        $this->countryOf($field, CountryEnum::France, active: false);
        $this->countryOf(Field::factory()->create(), CountryEnum::Spain);

        $rendered = $this->render($field);

        $rendered->assertSee('<option value="'.$canada->id.'"', escape: false);
        $rendered->assertSee('Canada');
        $rendered->assertDontSee('France');
        $rendered->assertDontSee('Spain');
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
        $rendered->assertSee('value="1725 Slough Avenue"', escape: false);
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
    public function an_address_no_longer_says_it_cannot_be_filled_in_here(): void
    {
        $address = $this->addressField();
        $divider = Field::factory()->layout()->create();

        $this->render($address)->assertDontSee('This kind of field cannot be filled in here yet.');
        $this->render($divider)->assertSee('This kind of field cannot be filled in here yet.');
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
    public function a_file_field_offers_no_upload_while_file_upload_is_off(): void
    {
        $field = Field::factory()->create(['type' => FieldTypeEnum::Image, 'label' => 'Portrait', 'is_required' => false]);

        $this->render($field)->assertSee('type="file"', escape: false);

        Feature::for(null)->deactivate('file-upload');
        Feature::flushCache();

        $rendered = $this->render($field);

        $rendered->assertDontSee('type="file"', escape: false);
        $rendered->assertSee('Portrait');
        $rendered->assertDontSee('Uploads are unavailable on this account for now.');
    }

    #[Test]
    public function a_required_file_field_says_why_it_cannot_be_filled_in_while_file_upload_is_off(): void
    {
        $field = Field::factory()->create(['type' => FieldTypeEnum::File, 'label' => 'Lease', 'is_required' => true]);
        Feature::for(null)->deactivate('file-upload');
        Feature::flushCache();

        $this->render($field)->assertSee('Uploads are unavailable on this account for now.');
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
