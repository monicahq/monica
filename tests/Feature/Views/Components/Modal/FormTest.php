<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components\Modal;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FormTest extends TestCase
{
    #[Test]
    public function it_carries_what_the_card_asks_for_to_the_action_it_names(): void
    {
        $rendered = $this->blade(
            '<x-modal.form title="Switch off “Personal”?" confirm="Switch the category off" method="put" action="/settings/account/life-event-categories/9/activation"><input type="hidden" name="is_active" value="0" /><x-slot:trigger><button type="button" @click="open = true">Switch off</button></x-slot:trigger></x-modal.form>',
        );

        $rendered->assertSee('action="/settings/account/life-event-categories/9/activation"', escape: false);
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
