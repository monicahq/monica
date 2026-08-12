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
            ->assertSee('text-forest', escape: false);

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

    private function render(Field $field, ?FieldValue $value, ?string $formatted): TestView
    {
        return $this->blade(
            '<x-field-value :field="$field" :value="$value" :formatted="$formatted" />',
            ['field' => $field, 'value' => $value, 'formatted' => $formatted],
        );
    }
}
