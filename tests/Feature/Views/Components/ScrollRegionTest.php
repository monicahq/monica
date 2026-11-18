<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ScrollRegionTest extends TestCase
{
    #[Test]
    public function it_is_a_region_reachable_by_keyboard_and_named_by_its_heading(): void
    {
        $this->blade('<x-scroll-region labelledby="dunder-sales-title"><table></table></x-scroll-region>')
            ->assertSee('role="region"', escape: false)
            ->assertSee('tabindex="0"', escape: false)
            ->assertSee('aria-labelledby="dunder-sales-title"', escape: false);
    }
}
