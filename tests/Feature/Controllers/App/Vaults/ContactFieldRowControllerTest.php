<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Enums\FieldTypeEnum;
use App\Enums\VersionStatusEnum;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\FieldValueRow;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactFieldRowControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_answers_one_page_of_notes_with_the_way_to_the_next(): void
    {
        $user = User::factory()->create();
        [$notes, $contact] = $this->contactWithNotes($user, 25);

        $this->actingAs($user)
            ->get(route('contacts.fieldRows.index', ['vault' => $contact->vault, 'contact' => $contact, 'field' => $notes, 'page' => 2]))
            ->assertStatus(200)
            ->assertSee('Note 15')
            ->assertSee('Note 6')
            ->assertDontSee('Note 16')
            ->assertDontSee('Note 5')
            ->assertSee("id=\"field-{$notes->id}-rows\"", escape: false)
            ->assertSee("id=\"field-{$notes->id}-more\"", escape: false)
            ->assertSee('page=3');

        $this->actingAs($user)
            ->get(route('contacts.fieldRows.index', ['vault' => $contact->vault, 'contact' => $contact, 'field' => $notes, 'page' => 3]))
            ->assertStatus(200)
            ->assertSee('Note 1');
    }

    #[Test]
    public function it_does_not_find_a_field_of_another_account(): void
    {
        $user = User::factory()->create();
        [$notes, $contact] = $this->contactWithNotes($user, 1);
        $stranger = User::factory()->create();
        [$strangerNotes] = $this->contactWithNotes($stranger, 1);

        $this->actingAs($user)
            ->get(route('contacts.fieldRows.index', ['vault' => $contact->vault, 'contact' => $contact, 'field' => $strangerNotes]))
            ->assertNotFound();
    }

    #[Test]
    public function it_does_not_find_rows_on_a_field_that_repeats_nothing(): void
    {
        $user = User::factory()->create();
        [$notes, $contact] = $this->contactWithNotes($user, 1);
        $child = $notes->children->first();

        $this->actingAs($user)
            ->get(route('contacts.fieldRows.index', ['vault' => $contact->vault, 'contact' => $contact, 'field' => $child]))
            ->assertNotFound();
    }

    /** @return array{0: Field, 1: Contact} */
    private function contactWithNotes(User $user, int $count): array
    {
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $version->pages()->delete();

        $page = StructureVersionPage::factory()->create([
            'structure_version_id' => $version->id,
            'account_id' => $version->account_id,
            'key' => 'general',
            'label' => 'General',
        ]);

        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Notes']);
        $sectionVersion = SectionVersion::factory()->create(['section_id' => $section->id]);

        $notes = Field::factory()->create([
            'section_version_id' => $sectionVersion->id,
            'label' => 'Notes',
            'type' => FieldTypeEnum::Repeater,
            'is_repeatable' => true,
        ]);

        $note = Field::factory()->create([
            'section_version_id' => $sectionVersion->id,
            'parent_field_id' => $notes->id,
            'type' => FieldTypeEnum::Textarea,
            'label' => 'Note',
        ]);

        StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'structure_version_page_id' => $page->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $version->update(['status' => VersionStatusEnum::Published]);

        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $user->account_id,
            'structure_version_id' => $version->id,
        ]);

        foreach (range(1, $count) as $number) {
            $row = FieldValueRow::factory()->create([
                'valuable_id' => $contact->id,
                'field_id' => $notes->id,
                'position' => $number,
            ]);

            $row->forceFill(['created_at' => CarbonImmutable::parse('2026-01-01')->addDays($number)])->save();

            FieldValue::factory()->create([
                'valuable_id' => $contact->id,
                'field_id' => $note->id,
                'field_value_row_id' => $row->id,
                'value_string' => null,
                'value_text' => "Note {$number}",
            ]);
        }

        return [$notes->refresh(), $contact];
    }
}
