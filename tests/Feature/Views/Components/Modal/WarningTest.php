<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components\Modal;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WarningTest extends TestCase
{
    #[Test]
    public function it_shows_the_trigger_and_what_the_card_asks(): void
    {
        $rendered = $this->blade(
            '<x-modal.warning title="Delete “test”?" confirm="Delete the category" action="/settings/account/life-event-categories/9"><p>Nothing you have written changes.</p><x-slot:trigger><button type="button" @click="open = true">Delete this category</button></x-slot:trigger></x-modal.warning>',
        );

        $rendered->assertSee('Nothing you have written changes.', escape: false);
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

    #[Test]
    public function it_hands_its_form_to_the_credential_card_when_asked_to(): void
    {
        $rendered = $this->blade(
            '<x-modal.warning title="Fire Ryan?" action="/admin/configuration/1" confirms-password><p>Dunder Mifflin loses its temp.</p><x-slot:trigger><button type="button" @click="open = true">Fire Ryan</button></x-slot:trigger></x-modal.warning>',
        );

        $rendered->assertSee("@click.prevent=\"open = false; \$dispatch('confirm-password', { form: \$el.form })\"", escape: false);
        $rendered->assertDontSee('confirms-password', escape: false);
    }

    #[Test]
    public function it_submits_its_own_form_unless_asked_otherwise(): void
    {
        $template = '<x-modal.warning title="Fire Ryan?" action="/admin/configuration/1" %s><p>Dunder Mifflin loses its temp.</p><x-slot:trigger><button type="button" @click="open = true">Fire Ryan</button></x-slot:trigger></x-modal.warning>';

        $default = (string) $this->blade(sprintf($template, ''));
        $declined = (string) $this->blade(sprintf($template, ':confirms-password="false"'));

        $this->assertSame($default, $declined);
        $this->assertStringNotContainsString('confirm-password', $default);
    }
}
