<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use App\Enums\FieldTypeEnum;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\File;
use App\Models\ValueList;
use App\Models\ValueListItem;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestView;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FieldValueTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_reads_a_line_of_text_as_the_line_of_text(): void
    {
        $this->render(new Field(['type' => FieldTypeEnum::Text, 'label' => 'Known as']), null, 'Prison Mike')
            ->assertSee('Prison Mike', escape: false);
    }

    #[Test]
    public function a_field_nobody_has_written_in_says_so_rather_than_disappearing(): void
    {
        $this->render(new Field(['type' => FieldTypeEnum::Text, 'label' => 'Known as']), null, null)
            ->assertSee('Not recorded');
    }

    #[Test]
    public function a_yes_or_no_takes_a_mark_you_can_see_at_a_glance(): void
    {
        $field = new Field(['type' => FieldTypeEnum::Boolean, 'label' => 'Vegetarian']);

        $yes = new FieldValue;
        $yes->value_boolean = true;

        $no = new FieldValue;
        $no->value_boolean = false;

        $this->render($field, $yes, 'Yes')
            ->assertSee('Yes');

        $this->render($field, $no, 'No')
            ->assertSee('No');
    }

    #[Test]
    public function a_paragraph_keeps_the_line_breaks_it_was_written_with(): void
    {
        $this->render(new Field(['type' => FieldTypeEnum::Textarea, 'label' => 'How we met']), null, "At the gym.\nTwice.")
            ->assertSee("At the gym.\nTwice.", escape: false);
    }

    #[Test]
    public function a_many_of_a_list_reads_as_the_set_it_is(): void
    {
        $list = ValueList::factory()->create();
        $climbing = ValueListItem::factory()->create(['value_list_id' => $list->id, 'label' => 'Climbing', 'position' => 1]);
        $cooking = ValueListItem::factory()->create(['value_list_id' => $list->id, 'label' => 'Cooking', 'position' => 2]);
        ValueListItem::factory()->create(['value_list_id' => $list->id, 'label' => 'Cycling', 'position' => 3]);

        $field = new Field(['type' => FieldTypeEnum::Multiselect, 'label' => 'Interests', 'value_list_id' => $list->id]);

        $value = new FieldValue;
        $value->value_list_item_ids = [$climbing->id, $cooking->id];

        $this->render($field, $value, 'Climbing, Cooking')
            ->assertSee('Climbing')
            ->assertSee('Cooking')
            ->assertDontSee('Cycling');
    }

    #[Test]
    public function a_document_reads_as_a_link_to_itself(): void
    {
        $file = $this->file('lease.pdf', 'application/pdf');

        $value = new FieldValue;
        $value->setRelation('file', $file);

        $this->render(new Field(['type' => FieldTypeEnum::File, 'label' => 'Document']), $value, 'lease.pdf')
            ->assertSee('lease.pdf')
            ->assertSee(route('files.show', ['vault' => $file->vault, 'file' => $file]), escape: false);
    }

    #[Test]
    public function a_photograph_reads_as_the_photograph(): void
    {
        $file = $this->file('amy.jpg', 'image/jpeg');

        $value = new FieldValue;
        $value->setRelation('file', $file);

        $this->render(new Field(['type' => FieldTypeEnum::Image, 'label' => 'Photo']), $value, 'amy.jpg')
            ->assertSee('<img', escape: false)
            ->assertSee(route('files.show', ['vault' => $file->vault, 'file' => $file]), escape: false);
    }

    #[Test]
    public function a_repeating_field_reads_as_each_of_its_repetitions(): void
    {
        $description = new Field(['type' => FieldTypeEnum::Text, 'label' => 'Description']);

        $rows = [
            ['id' => 1, 'fields' => [
                ['id' => 9, 'label' => 'Description', 'field' => $description, 'rawValue' => null, 'value' => 'The lease'],
            ]],
            ['id' => 2, 'fields' => [
                ['id' => 9, 'label' => 'Description', 'field' => $description, 'rawValue' => null, 'value' => 'The insurance'],
            ]],
        ];

        $this->blade(
            '<x-field-value :field="$field" :rows="$rows" />',
            ['field' => new Field(['type' => FieldTypeEnum::Repeater, 'label' => 'Document']), 'rows' => $rows],
        )
            ->assertSee('The lease')
            ->assertSee('The insurance');
    }

    #[Test]
    public function a_type_nothing_can_fill_in_yet_says_that_rather_than_reading_as_empty(): void
    {
        $this->render(new Field(['type' => FieldTypeEnum::Avatar, 'label' => 'Portrait']), null, null)
            ->assertSee('This kind of field cannot be filled in here yet.');
    }

    #[Test]
    public function an_address_reads_as_its_line_with_what_it_is_for_beside_it(): void
    {
        $list = ValueList::factory()->create();
        $work = ValueListItem::factory()->create(['value_list_id' => $list->id, 'label' => 'Work']);
        $field = new Field(['type' => FieldTypeEnum::Address, 'label' => 'Address', 'value_list_id' => $list->id]);

        $value = new FieldValue;
        $value->value_json = ['street' => '1725 Slough Avenue', 'city' => 'Scranton', 'country' => 'USA'];
        $value->value_list_item_id = $work->id;

        $this->render($field, $value, '1725 Slough Avenue, Scranton, USA (Work)')
            ->assertSeeInOrder(['1725 Slough Avenue, Scranton, USA', 'Work'])
            ->assertDontSee('(Work)');
    }

    #[Test]
    public function an_address_nobody_said_the_use_of_reads_as_its_line_alone(): void
    {
        $field = new Field(['type' => FieldTypeEnum::Address, 'label' => 'Address']);

        $value = new FieldValue;
        $value->value_json = ['street' => '1725 Slough Avenue', 'city' => 'Scranton'];

        $this->render($field, $value, '1725 Slough Avenue, Scranton')
            ->assertSee('1725 Slough Avenue, Scranton')
            ->assertDontSee('Not recorded');
    }

    #[Test]
    public function an_address_nobody_filled_in_says_so(): void
    {
        $field = new Field(['type' => FieldTypeEnum::Address, 'label' => 'Address']);

        $this->render($field, null, null)
            ->assertSee('Not recorded')
            ->assertDontSee('This kind of field cannot be filled in here yet.');
    }

    #[Test]
    public function a_date_somebody_asked_to_be_told_about_carries_a_mark_saying_what_it_does(): void
    {
        $this->markedDate('You will be reminded 7 days before, every year, at 09:00 Europe/Paris.')
            ->assertSee('3 February 1984')
            ->assertSee('You will be reminded 7 days before, every year, at 09:00 Europe/Paris.')
            ->assertSee('role="img"', escape: false);
    }

    #[Test]
    public function a_date_nobody_asked_to_be_told_about_carries_no_mark(): void
    {
        $this->markedDate(null)
            ->assertSee('3 February 1984')
            ->assertDontSee('You will be reminded');
    }

    private function markedDate(?string $reminder): TestView
    {
        return $this->blade(
            '<x-field-value :field="$field" :formatted="$formatted" :reminder="$reminder" />',
            [
                'field' => new Field(['type' => FieldTypeEnum::RecurringDate, 'label' => 'Birthday']),
                'formatted' => '3 February 1984',
                'reminder' => $reminder,
            ],
        );
    }

    private function file(string $name, string $mime): File
    {
        $vault = Vault::factory()->create();

        return File::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'original_name' => $name,
            'mime_type' => $mime,
        ]);
    }

    private function render(Field $field, ?FieldValue $value, ?string $formatted): TestView
    {
        return $this->blade(
            '<x-field-value :field="$field" :value="$value" :formatted="$formatted" />',
            ['field' => $field, 'value' => $value, 'formatted' => $formatted],
        );
    }
}
