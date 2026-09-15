<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Shared;

use App\Enums\FieldTypeEnum;
use App\Enums\VaultColourEnum;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\ShareLink;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Shared\SharedContactViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SharedContactViewModelTest extends TestCase
{
    use RefreshDatabase;

    private const string TOKEN = 'ScottsTotsScottsTotsScottsTotsScottsTotsScottsTotsScottsTots1234';

    #[Test]
    public function it_draws_the_sections_the_link_lets_through(): void
    {
        $contact = $this->sharedContact();
        $link = $this->linkOnto($contact);

        $sections = $this->viewModel($link, $contact)->sections();

        $this->assertSame(['Facts', 'Doctors'], array_column($sections, 'name'));
        $this->assertSame([['label' => 'Lives', 'value' => 'Scranton']], $sections[0]['fields']);
    }

    #[Test]
    public function it_carries_the_mark_of_each_section_it_draws(): void
    {
        $contact = $this->sharedContact();
        $link = $this->linkOnto($contact);
        Section::query()->where('name', 'Facts')->update(['icon' => 'health', 'colour' => '#4f8a49']);

        $sections = $this->viewModel($link, $contact)->sections();

        $this->assertSame('health', $sections[0]['icon']);
        $this->assertSame(VaultColourEnum::Green->tint(), $sections[0]['tint']);
    }

    #[Test]
    public function it_falls_back_to_the_neutral_mark_for_a_section_carrying_none(): void
    {
        $contact = $this->sharedContact();
        $link = $this->linkOnto($contact);

        $sections = $this->viewModel($link, $contact)->sections();

        $this->assertSame(Section::DEFAULT_ICON, $sections[0]['icon']);
        $this->assertSame(VaultColourEnum::neutralTint(), $sections[0]['tint']);
    }

    #[Test]
    public function it_never_reads_a_section_the_link_holds_back(): void
    {
        $contact = $this->sharedContact();
        $link = $this->linkOnto($contact, ['excluded_section_ids' => [$this->sectionNamed($contact, 'Doctors')->id]]);

        $this->assertSame(['Facts'], array_column($this->viewModel($link, $contact)->sections(), 'name'));
    }

    #[Test]
    public function a_field_marked_sensitive_stays_hidden_inside_a_shared_section(): void
    {
        $contact = $this->sharedContact();
        $link = $this->linkOnto($contact);

        $sections = $this->viewModel($link, $contact, 'work')->sections();

        $this->assertSame([['label' => 'Employer', 'value' => 'Dunder Mifflin']], $sections[0]['fields']);
    }

    #[Test]
    public function a_field_marked_sensitive_appears_when_the_link_says_so(): void
    {
        $contact = $this->sharedContact();
        $link = $this->linkOnto($contact, ['includes_sensitive_fields' => true]);

        $labels = array_column($this->viewModel($link, $contact, 'work')->sections()[0]['fields'], 'label');

        $this->assertContains('Social security number', $labels);
    }

    #[Test]
    public function it_offers_a_tab_for_each_page_that_has_something_on_it(): void
    {
        $contact = $this->sharedContact();
        $link = $this->linkOnto($contact);

        $this->assertSame(['overview', 'work'], array_column($this->viewModel($link, $contact)->tabs(), 'key'));
    }

    #[Test]
    public function a_page_whose_every_section_is_held_back_is_not_a_tab(): void
    {
        $contact = $this->sharedContact();
        $link = $this->linkOnto($contact, ['excluded_section_ids' => [$this->sectionNamed($contact, 'Where they worked')->id]]);

        $this->assertSame([], $this->viewModel($link, $contact)->tabs());
    }

    #[Test]
    public function one_page_left_means_no_tab_list(): void
    {
        $contact = $this->sharedContact();
        $link = $this->linkOnto($contact, [
            'excluded_section_ids' => [
                $this->sectionNamed($contact, 'Facts')->id,
                $this->sectionNamed($contact, 'Doctors')->id,
            ],
        ]);

        $viewModel = $this->viewModel($link, $contact);

        $this->assertSame([], $viewModel->tabs());
        $this->assertSame(['Where they worked'], array_column($viewModel->sections(), 'name'));
    }

    #[Test]
    public function an_address_naming_a_page_that_is_held_back_falls_back_to_one_that_is_not(): void
    {
        $contact = $this->sharedContact();
        $link = $this->linkOnto($contact, ['excluded_section_ids' => [$this->sectionNamed($contact, 'Where they worked')->id]]);

        $this->assertSame(['Facts', 'Doctors'], array_column($this->viewModel($link, $contact, 'work')->sections(), 'name'));
    }

    #[Test]
    public function a_section_whose_every_field_was_dropped_is_not_drawn(): void
    {
        $contact = $this->sharedContact();
        FieldValue::query()->delete();
        $link = $this->linkOnto($contact);

        $this->assertSame([], $this->viewModel($link, $contact)->sections());
    }

    #[Test]
    public function it_says_who_shared_it_and_on_what_terms(): void
    {
        $contact = $this->sharedContact();
        $user = $contact->account->user;
        $link = $this->linkOnto($contact, [
            'created_by_user_id' => $user->id,
            'expires_at' => now()->addDays(30),
        ]);

        $viewModel = $this->viewModel($link, $contact);

        $this->assertSame("Shared with you by {$user->name}", $viewModel->sharedBy());
        $this->assertStringContainsString('Read only · ends', $viewModel->terms());
        $this->assertStringContainsString(now()->addDays(30)->isoFormat('LL'), $viewModel->terms());
    }

    #[Test]
    public function a_link_that_never_ends_says_only_that_it_is_read_only(): void
    {
        $contact = $this->sharedContact();
        $link = $this->linkOnto($contact, ['expires_at' => null, 'created_by_user_id' => null]);

        $viewModel = $this->viewModel($link, $contact);

        $this->assertSame('Read only', $viewModel->terms());
        $this->assertSame('Shared with you', $viewModel->sharedBy());
        $this->assertStringNotContainsString('tell', $viewModel->closingLine());
    }

    #[Test]
    public function it_says_whom_to_tell_when_something_is_wrong(): void
    {
        $contact = $this->sharedContact();
        $user = $contact->account->user;
        $link = $this->linkOnto($contact, ['created_by_user_id' => $user->id]);

        $this->assertStringContainsString('tell', $this->viewModel($link, $contact)->closingLine());
    }

    #[Test]
    public function it_puts_the_highlighted_answers_under_the_name(): void
    {
        $contact = $this->sharedContact();
        Field::query()->where('label', 'Lives')->update(['is_highlighted' => true]);
        $link = $this->linkOnto($contact);

        $this->assertSame(
            [['label' => 'Lives', 'value' => 'Scranton']],
            $this->viewModel($link->refresh(), $contact->refresh())->headerFacts(),
        );
    }

    #[Test]
    public function it_leaves_a_highlighted_answer_out_of_the_header_when_its_section_is_held_back(): void
    {
        $contact = $this->sharedContact();
        Field::query()->where('label', 'Lives')->update(['is_highlighted' => true]);
        $link = $this->linkOnto($contact, ['excluded_section_ids' => [$this->sectionNamed($contact, 'Facts')->id]]);

        $this->assertSame([], $this->viewModel($link->refresh(), $contact->refresh())->headerFacts());
    }

    private function viewModel(ShareLink $link, Contact $contact, ?string $page = null): SharedContactViewModel
    {
        return new SharedContactViewModel(
            shareLink: $link,
            contact: $contact,
            token: self::TOKEN,
            selectedPageKey: $page,
        );
    }

    /** @param  array<string, mixed>  $attributes */
    private function linkOnto(Contact $contact, array $attributes = []): ShareLink
    {
        return ShareLink::factory()->for($contact, 'shareable')->create([
            'token' => hash('sha256', self::TOKEN),
            ...$attributes,
        ]);
    }

    private function sharedContact(): Contact
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);

        $this->page($version, 'Overview', [
            'Facts' => [['Lives', false]],
            'Doctors' => [['Doctor', false]],
        ]);
        $this->page($version, 'Work', [
            'Where they worked' => [['Employer', false], ['Social security number', true]],
        ]);

        $contact = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'structure_version_id' => $version->refresh()->id,
            'first_name' => 'Michael',
            'last_name' => 'Scott',
        ]);

        $this->answer($contact, 'Lives', 'Scranton');
        $this->answer($contact, 'Doctor', 'Dr Ferrand');
        $this->answer($contact, 'Employer', 'Dunder Mifflin');
        $this->answer($contact, 'Social security number', '123-45-6789');

        return $contact;
    }

    /** @param  array<string, array<int, array{0: string, 1: bool}>>  $sections */
    private function page(StructureVersion $version, string $label, array $sections): void
    {
        $page = StructureVersionPage::factory()->create([
            'structure_version_id' => $version->id,
            'account_id' => $version->account_id,
            'key' => Str::slug($label),
            'label' => $label,
        ]);

        $position = 1;

        foreach ($sections as $sectionName => $fields) {
            $section = Section::factory()->create(['account_id' => $version->account_id, 'name' => $sectionName]);
            $sectionVersion = SectionVersion::factory()->create(['section_id' => $section->id]);

            foreach ($fields as [$fieldLabel, $isSensitive]) {
                Field::factory()->create([
                    'section_version_id' => $sectionVersion->id,
                    'label' => $fieldLabel,
                    'type' => FieldTypeEnum::Text,
                    'is_sensitive' => $isSensitive,
                ]);
            }

            StructureVersionSection::factory()->create([
                'structure_version_id' => $version->id,
                'structure_version_page_id' => $page->id,
                'section_version_id' => $sectionVersion->id,
                'position' => $position++,
            ]);
        }
    }

    private function answer(Contact $contact, string $label, string $value): void
    {
        $field = Field::query()->where('label', $label)->firstOrFail();

        FieldValue::factory()->create([
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'field_id' => $field->id,
            'field_key' => $field->key,
            'value_string' => $value,
        ]);
    }

    private function sectionNamed(Contact $contact, string $name): StructureVersionSection
    {
        return $contact->structureVersion->assembly
            ->first(fn (StructureVersionSection $row): bool => $row->sectionVersion->section->name === $name);
    }
}
