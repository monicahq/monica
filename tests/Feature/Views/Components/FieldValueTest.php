<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use App\Enums\FieldTypeEnum;
use App\Models\Contact;
use App\Models\Entity;
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
    public function an_address_reads_as_its_line_with_what_it_is_for_beside_it(): void
    {
        $list = ValueList::factory()->create();
        $work = ValueListItem::factory()->create(['value_list_id' => $list->id, 'label' => 'Work']);
        $field = new Field(['type' => FieldTypeEnum::Address, 'label' => 'Address', 'value_list_id' => $list->id]);

        $value = new FieldValue;
        $value->value_json = ['street' => '1725 Slough Avenue', 'city' => 'Scranton', 'country' => 'USA'];
        $value->value_list_item_id = $work->id;

        $this->render($field, $value, '1725 Slough Avenue, Scranton, USA (Work)')
            ->assertSeeInOrder(['1725 Slough Avenue', 'Scranton', 'USA', 'Work'])
            ->assertDontSee('(Work)');
    }

    #[Test]
    public function an_address_nobody_said_the_use_of_reads_as_its_line_alone(): void
    {
        $field = new Field(['type' => FieldTypeEnum::Address, 'label' => 'Address']);

        $value = new FieldValue;
        $value->value_json = ['street' => '1725 Slough Avenue', 'city' => 'Scranton'];

        $this->render($field, $value, '1725 Slough Avenue, Scranton')
            ->assertSeeInOrder(['1725 Slough Avenue', 'Scranton']);
    }

    #[Test]
    public function a_date_somebody_asked_to_be_told_about_carries_a_mark_saying_what_it_does(): void
    {
        $this->markedDate('You will be reminded 7 days before, every year, at 09:00 Europe/Paris.')
            ->assertSee('3 February 1984')
            ->assertSee('You will be reminded 7 days before, every year, at 09:00 Europe/Paris.')
            ->assertSee('role="img"', escape: false);
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

    #[Test]
    public function a_contact_named_in_a_relation_reads_as_a_link_to_their_page(): void
    {
        $angela = Contact::factory()->create(['first_name' => 'Angela', 'is_partial' => false]);

        $value = new FieldValue;
        $value->setRelation('relatedContact', $angela);

        $this->render(new Field(['type' => FieldTypeEnum::Relation, 'label' => 'Person']), $value, 'Angela')
            ->assertSee(route('contacts.show', ['vault' => $angela->vault, 'contact' => $angela]), escape: false);
    }

    #[Test]
    public function a_partial_contact_named_in_a_relation_reads_as_a_name_alone(): void
    {
        $angela = Contact::factory()->create(['first_name' => 'Angela', 'is_partial' => true]);

        $value = new FieldValue;
        $value->setRelation('relatedContact', $angela);

        $this->render(new Field(['type' => FieldTypeEnum::Relation, 'label' => 'Person']), $value, 'Angela')
            ->assertSee('Angela')
            ->assertDontSee(route('contacts.show', ['vault' => $angela->vault, 'contact' => $angela]), escape: false);
    }

    #[Test]
    public function a_record_named_in_a_relation_reads_as_a_link_to_its_page(): void
    {
        $sprinkles = Entity::factory()->create(['name' => 'Sprinkles']);

        $value = new FieldValue;
        $value->setRelation('relatedContact', null);
        $value->setRelation('relatedEntity', $sprinkles);

        $this->render(new Field(['type' => FieldTypeEnum::Relation, 'label' => 'Pet']), $value, 'Sprinkles')
            ->assertSee(route('entities.show', ['vault' => $sprinkles->vault, 'entityType' => $sprinkles->entityType, 'entity' => $sprinkles]), escape: false);
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
