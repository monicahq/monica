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

    #[Test]
    public function it_names_a_control_trigger_by_its_label_and_keeps_the_menu_contract(): void
    {
        $rendered = $this->blade('<span id="template-label">Template used for Pam</span><x-actions-menu label="Template" variant="control" width="full" labelledby="template-label"><x-slot:text>Basic contact</x-slot:text></x-actions-menu>');

        $rendered->assertSee('aria-labelledby="template-label"', escape: false);
        $rendered->assertSee('aria-haspopup="menu"', escape: false);
        $rendered->assertSee(':aria-expanded="menuOpen ? \'true\' : \'false\'"', escape: false);
        $rendered->assertSee('@keydown.escape.window="if (menuOpen) { menuOpen = false; $refs.menuTrigger.focus() }"', escape: false);
        $rendered->assertSee('Basic contact');
    }

    #[Test]
    public function it_reports_which_line_is_chosen_when_its_lines_are_choices(): void
    {
        $chosen = $this->blade('<x-actions-menu.item href="/one" :checked="true">Basic contact</x-actions-menu.item>');
        $other = $this->blade('<x-actions-menu.item href="/two" :checked="false">Coworker</x-actions-menu.item>');
        $action = $this->blade('<x-actions-menu.item href="/three">Export</x-actions-menu.item>');

        $chosen->assertSee('role="menuitemradio"', escape: false);
        $chosen->assertSee('aria-checked="true"', escape: false);
        $other->assertSee('aria-checked="false"', escape: false);
        $action->assertSee('role="menuitem"', escape: false);
        $action->assertDontSee('aria-checked', escape: false);
    }
}
