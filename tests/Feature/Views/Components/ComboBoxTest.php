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

class ComboBoxTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_offers_every_label_the_list_holds(): void
    {
        $rendered = $this->render($this->offering(['Mobile', 'Work']));

        $rendered->assertSee('name="kind"', escape: false);
        $rendered->assertSee('Mobile');
        $rendered->assertSee('Work');
    }

    #[Test]
    public function it_takes_a_name_of_its_own_when_it_is_given_one(): void
    {
        $this->render($this->offering(['Mobile']), ['label' => 'Relationship'])
            ->assertSee('aria-label="Relationship"', escape: false);
    }

    #[Test]
    public function a_list_holding_nothing_draws_nothing_at_all(): void
    {
        $this->assertSame('', mb_trim((string) $this->render(collect())));
    }

    #[Test]
    public function the_open_label_offers_a_box_for_a_wording_and_a_way_back(): void
    {
        $rendered = $this->render($this->offering(['Mobile'], open: true));

        $rendered->assertSee('name="kind_wording"', escape: false);
    }

    #[Test]
    public function a_list_with_no_open_label_asks_for_no_wording(): void
    {
        $this->render($this->offering(['Mobile', 'Work']))
            ->assertDontSee('name="kind_wording"', escape: false);
    }

    #[Test]
    public function it_shows_back_what_was_already_chosen_and_typed(): void
    {
        $items = $this->offering(['Mobile'], open: true);
        $open = $items->last();

        $rendered = $this->render($items, ['chosen' => $open->id, 'wording' => 'Boat']);

        $rendered->assertSee('value="'.$open->id.'" selected', escape: false);
        $rendered->assertSee('value="Boat"', escape: false);
    }

    #[Test]
    public function the_whole_picker_stands_down_for_a_wording_rather_than_the_control_alone(): void
    {
        $html = (string) $this->render($this->offering(['Mobile'], open: true));
        $tag = mb_substr($html, (int) mb_strpos($html, '<select'), 400);

        $this->assertStringContainsString('x-show="! typing"', $html);
        $this->assertStringNotContainsString('x-show', mb_substr($tag, 0, (int) mb_strpos($tag, '>')));
    }

    #[Test]
    public function it_takes_a_plain_array_as_readily_as_a_collection(): void
    {
        $this->render($this->offering(['Mobile'])->all())->assertSee('Mobile');
    }

    /**
     * @param  list<string>  $labels
     * @return Collection<int, ValueListItem>
     */
    private function offering(array $labels, bool $open = false): Collection
    {
        $list = ValueList::factory()->create();

        $items = collect($labels)->map(fn (string $label): ValueListItem => ValueListItem::factory()->create([
            'value_list_id' => $list->id,
            'label' => $label,
        ]));

        if ($open) {
            $items->push(ValueListItem::factory()->create([
                'value_list_id' => $list->id,
                'key' => ValueListItem::OPEN_KEY,
                'label' => 'Custom...',
            ]));
        }

        return $items->values();
    }

    /**
     * @param  Collection<int, ValueListItem>|array<int, ValueListItem>  $items
     * @param  array<string, mixed>  $extra
     */
    private function render(Collection|array $items, array $extra = []): TestView
    {
        return $this->blade(
            '<x-combo-box :items="$items" id="kind" name="kind" wording-name="kind_wording"'
            .' :chosen="$chosen" :wording="$wording" :label="$label" />',
            [
                'items' => $items,
                'chosen' => $extra['chosen'] ?? null,
                'wording' => $extra['wording'] ?? null,
                'label' => $extra['label'] ?? null,
            ],
        );
    }
}
