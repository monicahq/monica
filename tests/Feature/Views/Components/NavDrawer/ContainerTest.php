<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components\NavDrawer;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContainerTest extends TestCase
{
    #[Test]
    public function it_is_the_drawer_the_menu_button_opens_and_holds_focus_while_open(): void
    {
        $rendered = $this->blade('<x-nav-drawer.container>Dunder Mifflin</x-nav-drawer.container>');

        $rendered->assertSee('id="main-nav"', escape: false);
        $rendered->assertSee('x-trap.noscroll.inert="navOpen"', escape: false);
        $rendered->assertSee('aria-label="Close the menu"', escape: false);
        $rendered->assertSee('Dunder Mifflin');
    }

    #[Test]
    public function it_resolves_to_the_element_it_is_asked_for(): void
    {
        $this->blade('<x-nav-drawer.container>Scranton</x-nav-drawer.container>')
            ->assertDontSee('<aside', escape: false);

        $this->blade('<x-nav-drawer.container tag="aside">Scranton</x-nav-drawer.container>')
            ->assertSee('<aside', escape: false);
    }
}
