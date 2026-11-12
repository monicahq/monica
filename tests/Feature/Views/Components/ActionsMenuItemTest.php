<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ActionsMenuItemTest extends TestCase
{
    #[Test]
    public function it_announces_itself_as_a_line_of_the_menu(): void
    {
        $this->blade('<x-actions-menu.item href="/somewhere">Merge</x-actions-menu.item>')
            ->assertSee('role="menuitem"', escape: false)
            ->assertSee('href="/somewhere"', escape: false)
            ->assertSee('Merge');
    }

    #[Test]
    public function it_goes_through_turbo_only_when_it_leads_somewhere(): void
    {
        $this->blade('<x-actions-menu.item href="/somewhere">Rename</x-actions-menu.item>')
            ->assertSee('data-turbo="true"', escape: false);

        $this->blade('<x-actions-menu.item @click="open = true">Rename</x-actions-menu.item>')
            ->assertDontSee('data-turbo', escape: false);

        $this->blade('<x-actions-menu.item href="/somewhere" data-turbo="false">Rename</x-actions-menu.item>')
            ->assertDontSee('data-turbo="true"', escape: false);
    }
}
