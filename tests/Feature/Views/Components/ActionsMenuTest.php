<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ActionsMenuTest extends TestCase
{
    #[Test]
    public function it_says_what_it_opens_and_whether_it_is_open(): void
    {
        $rendered = $this->blade('<x-actions-menu label="More actions for Michael Scott" />');

        $rendered->assertSee('aria-haspopup="menu"', escape: false);
        $rendered->assertSee(':aria-expanded="menuOpen ? \'true\' : \'false\'"', escape: false);
        $rendered->assertSee('role="menu"', escape: false);
    }

    #[Test]
    public function it_binds_the_markup_to_the_state_that_opens_and_closes_it(): void
    {
        $rendered = $this->blade('<x-actions-menu label="More actions" />');

        $rendered->assertSee('x-data="{ menuOpen: false }"', escape: false);
        $rendered->assertSee('@click="menuOpen = ! menuOpen"', escape: false);
        $rendered->assertSee('@click.outside="menuOpen = false"', escape: false);
        $rendered->assertSee('@keydown.escape.window="if (menuOpen) { menuOpen = false; $refs.menuTrigger.focus() }"', escape: false);
    }
}
