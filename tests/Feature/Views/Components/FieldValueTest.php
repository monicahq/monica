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
            ->assertSee('Prison Mike', escape: false)
            ->assertSee('text-ink', escape: false);
    }

    #[Test]
    public function a_field_nobody_has_written_in_says_so_rather_than_disappearing(): void
    {
        $this->render(new Field(['type' => FieldTypeEnum::Text, 'label' => 'Known as']), null, null)
            ->assertSee('Not recorded')
            ->assertSee('text-placeholder', escape: false);
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
            ->assertSee('Yes')
            ->assertSee('text-success', escape: false);

        $this->render($field, $no, 'No')
            ->assertSee('No')
            ->assertSee('text-muted', escape: false);
    }

    #[Test]
    public function a_paragraph_keeps_the_line_breaks_it_was_written_with(): void
    {
        $this->render(new Field(['type' => FieldTypeEnum::Textarea, 'label' => 'How we met']), null, "At the gym.\nTwice.")
            ->assertSee('whitespace-pre-line', escape: false)
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
            ->assertDontSee('Cycling')
            ->assertSee('bg-sand', escape: false);
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

    /**
     * A photograph reads as the photograph. A file name is what a document has
     * to be read as, and what an image never should be.
     */
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

    /**
     * A gap the product left is not a gap the reader left, and "not recorded"
     * would tell them it was.
     */
    #[Test]
    public function a_type_nothing_can_fill_in_yet_says_that_rather_than_reading_as_empty(): void
    {
        $this->render(new Field(['type' => FieldTypeEnum::Relation, 'label' => 'Introduced by']), null, null)
            ->assertSee('This kind of field cannot be filled in here yet.');
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
