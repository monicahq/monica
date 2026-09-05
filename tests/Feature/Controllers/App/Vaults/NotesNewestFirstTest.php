<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Enums\DefaultFieldEnum;
use App\Enums\DefaultSectionEnum;
use App\Jobs\CreateDefaultTemplate;
use App\Models\Contact;
use App\Models\Field;
use App\Models\Template;
use App\Models\TemplateVersionSection;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotesNewestFirstTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_note_somebody_writes_lands_above_the_ones_already_there(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        new CreateDefaultTemplate(user: $user)->handle();

        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
            'template_version_id' => Template::query()->where('account_id', $user->account_id)->sole()->published_version_id,
        ]);

        $notes = Field::query()->where('account_id', $user->account_id)->where('key', DefaultFieldEnum::Notes->value)->sole();
        $note = $notes->children->sole();
        $section = TemplateVersionSection::query()
            ->where('account_id', $user->account_id)
            ->where('section_version_id', $notes->section_version_id)
            ->sole();

        $this->actingAs($user)->put(route('contacts.sections.update', [$vault, $contact, $section]),
            ['fields' => [$notes->id => [['values' => [$note->id => 'Where we met']]]]])->assertRedirect();

        $held = $contact->fieldValueRows()->where('field_id', $notes->id)->sole();

        $this->actingAs($user)->put(route('contacts.sections.update', [$vault, $contact, $section]), ['fields' => [$notes->id => [
            0 => ['values' => [$note->id => 'What they said on Tuesday']],
            1 => ['id' => $held->id, 'values' => [$note->id => 'Where we met']],
        ]]])->assertRedirect();

        $page = $section->templateVersionPage->key;
        $html = $this->actingAs($user)->get(route('contacts.show', [$vault, $contact, 'page' => $page]))->getContent();

        $this->assertLessThan(
            mb_strpos($html, 'Where we met'),
            mb_strpos($html, 'What they said on Tuesday'),
            'The note written last is read first.',
        );
    }

    #[Test]
    public function the_notes_are_on_the_tab_a_contact_opens_on(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        new CreateDefaultTemplate(user: $user)->handle();

        $notes = Field::query()->where('account_id', $user->account_id)->where('key', DefaultFieldEnum::Notes->value)->sole();
        $section = TemplateVersionSection::query()
            ->where('account_id', $user->account_id)
            ->where('section_version_id', $notes->section_version_id)
            ->sole();

        $this->assertSame(DefaultSectionEnum::Notes->value, $section->sectionVersion->section->slug);
        $this->assertSame('general', $section->templateVersionPage->key);
    }

    #[Test]
    public function the_blank_is_drawn_above_the_notes_already_there(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        new CreateDefaultTemplate(user: $user)->handle();

        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
            'template_version_id' => Template::query()->where('account_id', $user->account_id)->sole()->published_version_id,
        ]);

        $notes = Field::query()->where('account_id', $user->account_id)->where('key', DefaultFieldEnum::Notes->value)->sole();
        $note = $notes->children->sole();
        $section = TemplateVersionSection::query()
            ->where('account_id', $user->account_id)
            ->where('section_version_id', $notes->section_version_id)
            ->sole();

        $this->actingAs($user)->put(route('contacts.sections.update', [$vault, $contact, $section]),
            ['fields' => [$notes->id => [['values' => [$note->id => 'Where we met']]]]])->assertRedirect();

        $html = $this->actingAs($user)->get(route('contacts.sections.edit', [$vault, $contact, $section]))->getContent();

        preg_match_all('/name="fields\['.$notes->id.'\]\[(\d+)\]\[id\]"/', $html, $existing);
        preg_match_all('/name="fields\['.$notes->id.'\]\[(\d+)\]\[values\]/', $html, $all);

        $existingIndices = array_map('intval', array_unique($existing[1]));
        $blankIndices = array_diff(array_map('intval', array_unique($all[1])), $existingIndices);

        $this->assertNotEmpty($blankIndices);
        $this->assertLessThan(min($existingIndices), max($blankIndices), 'The blank is drawn above what is already there.');
    }
}
