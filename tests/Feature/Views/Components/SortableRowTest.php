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
    public function a_row_being_edited_may_refuse_to_be_dragged(): void
    {
        $this->blade('<x-sortable-row :row-id="3" action="/x" target="t" dragstart="editing ? $event.preventDefault() : start($event, 3)">Row</x-sortable-row>')
            ->assertSee('editing ? $event.preventDefault() : start($event, 3)', escape: false);
    }
}
