<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use App\Enums\FieldTypeEnum;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\ValueList;
use App\Models\ValueListItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestView;
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
