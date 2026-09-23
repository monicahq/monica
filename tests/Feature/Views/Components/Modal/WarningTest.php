<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components\Modal;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WarningTest extends TestCase
{
    #[Test]
    public function it_is_a_dialog_that_traps_the_focus(): void
    {
        $rendered = $this->blade(
            '<x-modal.warning title="Delete “test”?" action="/settings/account/life-event-categories/9"><p>Nothing you have written changes.</p><x-slot:trigger><button type="button" @click="open = true">Delete this category</button></x-slot:trigger></x-modal.warning>',
        );

        $rendered->assertSee('role="dialog"', escape: false);
        $rendered->assertSee('aria-modal="true"', escape: false);
        $rendered->assertSee('x-trap.noscroll.inert="open"', escape: false);
        $rendered->assertSee('aria-label="Delete “test”?"', escape: false);
    }

    #[Test]
    public function it_draws_the_card_on_the_body_so_it_shows_from_inside_a_menu_that_closes(): void
    {
        $rendered = $this->blade(
            '<x-modal.warning title="Delete “test”?" action="/settings/account/life-event-categories/9"><p>Nothing you have written changes.</p><x-slot:trigger><button type="button" @click="open = true">Delete this category</button></x-slot:trigger></x-modal.warning>',
        );

        $rendered->assertSee('<template x-teleport="body">', escape: false);
    }

    #[Test]
    public function it_shows_the_trigger_and_what_the_card_asks(): void
    {
        $rendered = $this->blade(
            '<x-modal.warning title="Delete “test”?" confirm="Delete the category" action="/settings/account/life-event-categories/9"><p>Nothing you have written changes.</p><x-slot:trigger><button type="button" @click="open = true">Delete this category</button></x-slot:trigger></x-modal.warning>',
        );

        $rendered->assertSee('Delete this category', escape: false);
        $rendered->assertSee('Nothing you have written changes.', escape: false);
        $rendered->assertSee('Delete the category', escape: false);
        $rendered->assertSee('Keep it', escape: false);
    }

    #[Test]
    public function it_destroys_what_the_trigger_pointed_at(): void
    {
        $rendered = $this->blade(
            '<x-modal.warning action="/settings/account/life-event-categories/9"><p>Nothing you have written changes.</p><x-slot:trigger><button type="button">Delete</button></x-slot:trigger></x-modal.warning>',
        );

        $rendered->assertSee('action="/settings/account/life-event-categories/9"', escape: false);
        $rendered->assertSee('value="delete"', escape: false);
    }

    #[Test]
    public function it_sends_the_fields_it_is_given_with_the_form(): void
    {
        $rendered = $this->blade(
            '<x-modal.warning method="put" action="/vaults/1/contacts/2/template-version"><p>This contact changes version.</p><x-slot:trigger><button type="button">Revert</button></x-slot:trigger><x-slot:fields><input type="hidden" name="structure_version_id" value="7" /></x-slot:fields></x-modal.warning>',
        );

        $this->assertMatchesRegularExpression(
            '/<form[^>]*action="\/vaults\/1\/contacts\/2\/template-version".*name="structure_version_id" value="7".*<\/form>/s',
            (string) $rendered,
        );
    }
}
