<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SortableRowTest extends TestCase
{
    private const CALL = '<x-sortable-row :row-id="7" action="/types/7/position" target="entity-types">The row</x-sortable-row>';

    #[Test]
    public function it_can_be_picked_up_and_dropped(): void
    {
        $this->blade(self::CALL)
            ->assertSee('draggable="true"', escape: false)
            ->assertSee('start($event, 7)', escape: false)
            ->assertSee('over($event, 7)', escape: false)
            ->assertSee('drop($event, 7)', escape: false)
            ->assertSee('stop()', escape: false)
            ->assertSee('data-row-id="7"', escape: false);
    }

    #[Test]
    public function it_shows_where_the_row_would_land(): void
    {
        $this->blade(self::CALL)
            ->assertSee('hoveringId === 7 && draggingId !== 7', escape: false);
    }

    #[Test]
    public function every_row_offers_the_same_handle(): void
    {
        $this->blade(self::CALL)
            ->assertSee(__('Drag to reorder'), escape: false);
    }

    #[Test]
    public function it_carries_the_form_that_records_where_it_landed(): void
    {
        $this->blade(self::CALL)
            ->assertSee('data-position-form="7"', escape: false)
            ->assertSee('action="/types/7/position"', escape: false)
            ->assertSee('name="position"', escape: false);
    }

    #[Test]
    public function a_row_being_edited_may_refuse_to_be_dragged(): void
    {
        $this->blade('<x-sortable-row :row-id="3" action="/x" target="t" dragstart="editing ? $event.preventDefault() : start($event, 3)">Row</x-sortable-row>')
            ->assertSee('editing ? $event.preventDefault() : start($event, 3)', escape: false);
    }

    #[Test]
    public function it_holds_what_it_is_given(): void
    {
        $this->blade(self::CALL)->assertSee('The row', escape: false);
    }
}
