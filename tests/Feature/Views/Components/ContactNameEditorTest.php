<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use App\Models\Contact;
use App\Models\ContactNameRepresentation;
use App\ViewModels\Vaults\ContactNameEditorBlock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactNameEditorTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_binds_the_editor_and_posts_the_name_shown(): void
    {
        $this->blade('<x-contact-name-editor :block="$block" short-name="Michael" />', ['block' => new ContactNameEditorBlock])
            ->assertSee('x-data="contactNameEditor(', escape: false)
            ->assertSee('data-name-editor', escape: false)
            ->assertSee('name="display"', escape: false)
            ->assertSee(__('Add another way to write this name'))
            ->assertSee(__('For a name in another writing system, a transliteration or how it is pronounced.'));
    }

    #[Test]
    public function it_offers_the_choice_of_the_name_shown_as_a_group_of_radio_buttons(): void
    {
        $contact = Contact::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);
        ContactNameRepresentation::factory()->transliteration()->create(['contact_id' => $contact->id, 'formatted_name' => 'Maikeru Sukotto']);

        $this->blade('<x-contact-name-editor :block="$block" short-name="Michael" />', ['block' => new ContactNameEditorBlock(contact: $contact->refresh())])
            ->assertSee('<fieldset', escape: false)
            ->assertSee('<legend', escape: false)
            ->assertSee('type="radio"', escape: false)
            ->assertSee('aria-describedby="name-list-help"', escape: false)
            ->assertSee('Maikeru Sukotto')
            ->assertSee(__('Searching for any of these names finds :name.', ['name' => 'Michael']));
    }

    #[Test]
    public function it_says_why_a_pronunciation_cannot_be_chosen_as_the_name_shown(): void
    {
        $contact = Contact::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);
        ContactNameRepresentation::factory()->phonetic('x-romaji')->create(['contact_id' => $contact->id, 'given_name' => 'Maikeru', 'family_name' => null]);

        $this->blade('<x-contact-name-editor :block="$block" short-name="Michael" />', ['block' => new ContactNameEditorBlock(contact: $contact->refresh())])
            ->assertSee(':aria-disabled="isPhonetic(row) ? \'true\' : null"', escape: false)
            ->assertSee('`name-row-${index}-meta name-row-${index}-refusal`', escape: false)
            ->assertSee(':id="`name-row-${index}-refusal`"', escape: false)
            ->assertSee(__('A pronunciation cannot be the name the contact is shown under.'));
    }

    #[Test]
    public function it_names_the_row_each_edit_and_remove_button_acts_on(): void
    {
        $this->blade('<x-contact-name-editor :block="$block" short-name="Michael" />', ['block' => new ContactNameEditorBlock])
            ->assertSee(':aria-label="rowLabel(labels.editRow, row)"', escape: false)
            ->assertSee(':aria-label="rowLabel(labels.removeRow, row)"', escape: false);
    }

    #[Test]
    public function it_says_the_names_are_sent_only_when_the_editor_runs(): void
    {
        $this->blade('<x-contact-name-editor :block="$block" short-name="Michael" />', ['block' => new ContactNameEditorBlock])
            ->assertSee(':name="\'names_included\'"', escape: false)
            ->assertDontSee('name="names_included"', escape: false)
            ->assertSee('form="name-display-choice"', escape: false);
    }

    #[Test]
    public function it_asks_which_name_to_show_before_the_shown_one_is_removed(): void
    {
        $this->blade('<x-contact-name-editor :block="$block" short-name="Michael" />', ['block' => new ContactNameEditorBlock])
            ->assertSee(__('Remove and switch'))
            ->assertSee(__('Keep it'))
            ->assertSee(__('Name to show instead'));
    }

    #[Test]
    public function it_labels_every_control_of_the_editor_for_one_way_of_writing_the_name(): void
    {
        $this->blade('<x-contact-name-editor.draft :block="$block" :is-new="true" />', ['block' => new ContactNameEditorBlock])
            ->assertSee('for="name-draft-kind"', escape: false)
            ->assertSee('for="name-draft-language"', escape: false)
            ->assertSee('for="name-draft-script"', escape: false)
            ->assertSee('for="name-draft-system"', escape: false)
            ->assertSee('for="name-draft-formatted"', escape: false)
            ->assertSee('aria-controls="name-draft-parts"', escape: false)
            ->assertSee(':aria-expanded="showParts"', escape: false)
            ->assertSee(__('Another way to write this name'))
            ->assertSee(__('Add'));
    }

    #[Test]
    public function it_warns_before_a_name_is_saved_empty(): void
    {
        $this->blade('<x-contact-name-editor :block="$block" short-name="Michael" />', ['block' => new ContactNameEditorBlock])
            ->assertSee('role="alert"', escape: false)
            ->assertSee(__('Save without a name'));
    }
}
