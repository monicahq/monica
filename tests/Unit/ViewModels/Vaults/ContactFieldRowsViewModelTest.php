<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

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
use App\ViewModels\Vaults\ContactFieldRowsViewModel;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactFieldRowsViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_ten_notes_at_a_time_newest_first(): void
    {
        $user = User::factory()->create();
        $notes = $this->repeater($user, 'Notes', [FieldTypeEnum::Textarea]);
        $contact = $this->contact($user, $notes);

        foreach (range(1, 25) as $number) {
            $this->writeRow($contact, $notes, "Note {$number}", CarbonImmutable::parse('2026-01-01')->addDays($number));
        }

        $first = new ContactFieldRowsViewModel(user: $user, vault: $contact->vault, contact: $contact, field: $notes);

        $this->assertTrue($first->readsAsNotes());
        $this->assertCount(10, $first->noteEntries());
        $this->assertSame(['Note 25'], $first->noteEntries()[0]['shown']);
        $this->assertSame(['Note 16'], $first->noteEntries()[9]['shown']);
        $this->assertSame(25, $first->rows()->total());
        $this->assertStringContainsString(route('contacts.fieldRows.index', ['vault' => $contact->vault, 'contact' => $contact, 'field' => $notes]), (string) $first->nextPageUrl());
        $this->assertStringContainsString('page=2', (string) $first->nextPageUrl());
        $this->assertFalse($first->showsEnd());

        $last = new ContactFieldRowsViewModel(user: $user, vault: $contact->vault, contact: $contact, field: $notes, page: 3);

        $this->assertCount(5, $last->noteEntries());
        $this->assertSame(['Note 5'], $last->noteEntries()[0]['shown']);
        $this->assertNull($last->nextPageUrl());
        $this->assertTrue($last->showsEnd());
        $this->assertSame(5, $last->count());
    }

    #[Test]
    public function it_shows_ten_rows_of_any_other_repeater_in_the_order_somebody_set(): void
    {
        $user = User::factory()->create();
        $phones = $this->repeater($user, 'Phones', [FieldTypeEnum::Text, FieldTypeEnum::Text]);
        $contact = $this->contact($user, $phones);

        foreach (range(1, 12) as $number) {
            $this->writeRow($contact, $phones, "555-01{$number}", CarbonImmutable::parse('2026-01-01')->subDays($number));
        }

        $viewModel = new ContactFieldRowsViewModel(user: $user, vault: $contact->vault, contact: $contact, field: $phones);

        $this->assertFalse($viewModel->readsAsNotes());
        $this->assertCount(10, $viewModel->shapedRows());
        $this->assertSame('555-011', $viewModel->shapedRows()[0]['fields'][0]['value']);
        $this->assertSame('555-0110', $viewModel->shapedRows()[9]['fields'][0]['value']);
        $this->assertCount(2, $viewModel->shapedRows()[0]['fields']);
        $this->assertSame("field-{$phones->id}-rows", $viewModel->rowsId());
        $this->assertSame("field-{$phones->id}-more", $viewModel->footerId());
    }

    #[Test]
    public function it_leaves_out_the_rows_of_another_contact(): void
    {
        $user = User::factory()->create();
        $notes = $this->repeater($user, 'Notes', [FieldTypeEnum::Textarea]);
        $michael = $this->contact($user, $notes);
        $dwight = $this->contact($user, $notes);

        $this->writeRow($michael, $notes, 'World’s best boss.', CarbonImmutable::parse('2026-01-01'));
        $this->writeRow($dwight, $notes, 'Assistant to the regional manager.', CarbonImmutable::parse('2026-01-02'));

        $viewModel = new ContactFieldRowsViewModel(user: $user, vault: $michael->vault, contact: $michael, field: $notes);

        $this->assertCount(1, $viewModel->noteEntries());
        $this->assertSame(['World’s best boss.'], $viewModel->noteEntries()[0]['shown']);
    }

    #[Test]
    public function it_splits_a_note_into_paragraphs_only_at_blank_lines_whatever_its_characters(): void
    {
        $user = User::factory()->create();
        $notes = $this->repeater($user, 'Notes', [FieldTypeEnum::Textarea]);
        $contact = $this->contact($user, $notes);

        $this->writeRow($contact, $notes, "Zakupy ą\nmleko\n\nchleb", CarbonImmutable::parse('2026-01-01'));

        $viewModel = new ContactFieldRowsViewModel(user: $user, vault: $contact->vault, contact: $contact, field: $notes);

        $this->assertSame(["Zakupy ą\nmleko", 'chleb'], $viewModel->noteEntries()[0]['shown']);
    }

    /** @param  array<int, FieldTypeEnum>  $childTypes */
    private function repeater(User $user, string $label, array $childTypes): Field
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

        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => $label]);
        $sectionVersion = SectionVersion::factory()->create(['section_id' => $section->id]);

        $repeater = Field::factory()->create([
            'section_version_id' => $sectionVersion->id,
            'label' => $label,
            'type' => FieldTypeEnum::Repeater,
            'is_repeatable' => true,
        ]);

        foreach ($childTypes as $index => $type) {
            Field::factory()->create([
                'section_version_id' => $sectionVersion->id,
                'parent_field_id' => $repeater->id,
                'type' => $type,
                'label' => "{$label} {$index}",
                'position' => $index + 1,
            ]);
        }

        StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'structure_version_page_id' => $page->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $version->update(['status' => VersionStatusEnum::Published]);

        return $repeater->refresh();
    }

    private function contact(User $user, Field $repeater): Contact
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return Contact::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $user->account_id,
            'structure_version_id' => StructureVersionSection::query()->where('section_version_id', $repeater->section_version_id)->sole()->structure_version_id,
        ]);
    }

    private function writeRow(Contact $contact, Field $repeater, string $text, CarbonImmutable $at): FieldValueRow
    {
        $row = FieldValueRow::factory()->create([
            'valuable_id' => $contact->id,
            'field_id' => $repeater->id,
            'position' => FieldValueRow::query()->where('field_id', $repeater->id)->where('valuable_id', $contact->id)->count() + 1,
        ]);

        $row->forceFill(['created_at' => $at])->save();

        $child = $repeater->children->first();

        FieldValue::factory()->create([
            'valuable_id' => $contact->id,
            'field_id' => $child->id,
            'field_value_row_id' => $row->id,
            'value_string' => $child->type === FieldTypeEnum::Textarea ? null : $text,
            'value_text' => $child->type === FieldTypeEnum::Textarea ? $text : null,
        ]);

        return $row;
    }
}
