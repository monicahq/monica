<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use App\Models\ValueList;
use App\Models\ValueListItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Testing\TestView;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MultiSelectTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_unfolds_a_named_list_from_a_button_that_says_whether_it_is_open(): void
    {
        $rendered = $this->render($this->offering(['Sales', 'Accounting']));

        $rendered->assertSee('aria-controls="branch-list"', escape: false);
        $rendered->assertSee(':aria-expanded="open ? \'true\' : \'false\'"', escape: false);
        $rendered->assertSee('id="branch-list"', escape: false);
        $rendered->assertSee('role="group"', escape: false);
        $rendered->assertSee('aria-label="Branch"', escape: false);
    }

    #[Test]
    public function it_ticks_a_box_for_every_value_chosen(): void
    {
        $items = $this->offering(['Sales', 'Accounting']);

        $chosen = $this->render($items, ['chosen' => [$items[0]->id]]);
        $nothing = $this->render($items);

        $chosen->assertSee('name="branches[]"', escape: false);
        $chosen->assertSee('value="'.$items[0]->id.'"', escape: false);
        $this->assertSame(1, substr_count((string) $chosen, ' checked'));
        $this->assertSame(0, substr_count((string) $nothing, ' checked'));
    }

    #[Test]
    public function it_offers_to_add_a_value_only_when_it_is_given_the_words_for_it(): void
    {
        $items = $this->offering(['Sales']);

        $offering = $this->render($items, ['addLabel' => 'Add a branch']);
        $refusing = $this->render($items);

        $offering->assertSee('Add a branch');
        $offering->assertSee('name="new_branches[]"', escape: false);
        $offering->assertSee('aria-label="Add"', escape: false);
        $refusing->assertDontSee('new_branches', escape: false);
        $refusing->assertDontSee('aria-label="Add"', escape: false);
    }

    /**
     * @param  array<int, string>  $labels
     * @return Collection<int, ValueListItem>
     */
    private function offering(array $labels): Collection
    {
        $list = ValueList::factory()->create();

        return collect($labels)->map(fn (string $label): ValueListItem => ValueListItem::factory()->create([
            'value_list_id' => $list->id,
            'label' => $label,
        ]));
    }

    /**
     * @param  Collection<int, ValueListItem>  $items
     * @param  array{chosen?: array<int, int>, addLabel?: string}  $extra
     */
    private function render(Collection $items, array $extra = []): TestView
    {
        return $this->blade(
            '<x-multi-select :items="$items" id="branch" name="branches" label="Branch"'
            .' :chosen="$chosen" :add-label="$addLabel" new-name="new_branches[]" />',
            [
                'items' => $items,
                'chosen' => $extra['chosen'] ?? [],
                'addLabel' => $extra['addLabel'] ?? null,
            ],
        );
    }
}
