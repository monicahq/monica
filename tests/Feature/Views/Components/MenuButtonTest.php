<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MenuButtonTest extends TestCase
{
    #[Test]
    public function it_names_itself_and_the_drawer_it_opens(): void
    {
        $view = $this->blade('<x-menu-button />');

        $view->assertSee('aria-label="Open the menu"', escape: false);
        $view->assertSee('aria-controls="main-nav"', escape: false);
        $view->assertDontSee('aria-describedby', escape: false);
    }

    #[Test]
    public function it_describes_what_waits_behind_it(): void
    {
        $view = $this->blade('<x-menu-button description="Dunder Mifflin has a request" />');

        $view->assertSee('aria-describedby="menu-button-description"', escape: false);
        $view->assertSee('id="menu-button-description"', escape: false);
        $view->assertSee('Dunder Mifflin has a request');
    }
}
