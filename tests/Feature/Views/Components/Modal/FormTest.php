<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components\Modal;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FormTest extends TestCase
{
    #[Test]
    public function it_is_a_dialog_that_traps_the_focus(): void
    {
        $rendered = $this->blade(
            '<x-modal.form title="Switch off “Personal”?" method="put" action="/settings/account/activity-categories/9/activation"><input type="hidden" name="is_active" value="0" /><x-slot:trigger><button type="button" @click="open = true">Switch off</button></x-slot:trigger></x-modal.form>',
        );

        $rendered->assertSee('role="dialog"', escape: false);
        $rendered->assertSee('aria-modal="true"', escape: false);
        $rendered->assertSee('x-trap.noscroll.inert="open"', escape: false);
        $rendered->assertSee('aria-label="Switch off “Personal”?"', escape: false);
    }

    #[Test]
    public function it_draws_the_card_on_the_body_so_it_shows_from_inside_a_menu_that_closes(): void
    {
        $rendered = $this->blade(
            '<x-modal.form title="Switch off “Personal”?" method="put" action="/settings/account/activity-categories/9/activation"><input type="hidden" name="is_active" value="0" /><x-slot:trigger><button type="button" @click="open = true">Switch off</button></x-slot:trigger></x-modal.form>',
        );

        $rendered->assertSee('<template x-teleport="body">', escape: false);
    }

    #[Test]
    public function it_carries_what_the_card_asks_for_to_the_action_it_names(): void
    {
        $rendered = $this->blade(
            '<x-modal.form title="Switch off “Personal”?" confirm="Switch the category off" method="put" action="/settings/account/activity-categories/9/activation"><input type="hidden" name="is_active" value="0" /><x-slot:trigger><button type="button" @click="open = true">Switch off</button></x-slot:trigger></x-modal.form>',
        );

        $rendered->assertSee('action="/settings/account/activity-categories/9/activation"', escape: false);
        $rendered->assertSee('value="put"', escape: false);
        $rendered->assertSee('name="is_active"', escape: false);
        $rendered->assertSee('Switch the category off', escape: false);
        $rendered->assertSee('Cancel', escape: false);
    }

    #[Test]
    public function it_stands_open_when_the_screen_says_it_should(): void
    {
        $shut = $this->blade(
            '<x-modal.form action="/x"><p>Body</p><x-slot:trigger><button type="button">Open</button></x-slot:trigger></x-modal.form>',
        );

        $open = $this->blade(
            '<x-modal.form action="/x" :open="true"><p>Body</p><x-slot:trigger><button type="button">Open</button></x-slot:trigger></x-modal.form>',
        );

        $shut->assertSee('x-data="{ open: false }"', escape: false);
        $open->assertSee('x-data="{ open: true }"', escape: false);
    }
}
