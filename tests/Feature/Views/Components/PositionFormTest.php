<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PositionFormTest extends TestCase
{
    private const CALL = '<x-position-form action="/types/7/position" target="entity-types" :row-id="7" />';

    #[Test]
    public function it_carries_the_place_a_dropped_row_landed_in(): void
    {
        $this->blade(self::CALL)
            ->assertSee('name="position"', escape: false)
            ->assertSee('data-position-form="7"', escape: false)
            ->assertSee('action="/types/7/position"', escape: false);
    }
}
