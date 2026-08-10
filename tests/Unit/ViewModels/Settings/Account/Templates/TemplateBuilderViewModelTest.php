<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\Templates;

use App\Enums\FieldTypeEnum;
use App\Enums\VersionStatusEnum;
use App\Models\Field;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionPage;
use App\Models\TemplateVersionSection;
use App\Models\User;
use App\ViewModels\Settings\Account\Templates\TemplateBuilderViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TemplateBuilderViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_opens_the_draft_rather_than_the_published_version(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $published = TemplateVersion::factory()->published()->create(['template_id' => $template->id]);
        $template->update(['published_version_id' => $published->id]);
        $draft = TemplateVersion::factory()->create(['template_id' => $template->id, 'version_number' => 2]);

        $viewModel = new TemplateBuilderViewModel(user: $user, template: $template->fresh());

        $this->assertTrue($viewModel->version()->is($draft));
        $this->assertTrue($viewModel->isEditable());
    }

    #[Test]
    public function it_opens_a_published_template_read_only(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $published = TemplateVersion::factory()->published()->create(['template_id' => $template->id]);
        $template->update(['published_version_id' => $published->id]);

        $viewModel = new TemplateBuilderViewModel(user: $user, template: $template->fresh());

        $this->assertFalse($viewModel->isEditable());
        $this->assertEquals(__('Published'), $viewModel->statusLabel());
    }

    #[Test]
    public function it_says_what_a_draft_was_based_on(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $first = TemplateVersion::factory()->published()->create(['template_id' => $template->id]);
        TemplateVersion::factory()->create([
            'template_id' => $template->id,
            'version_number' => 2,
            'previous_version_id' => $first->id,
        ]);

        $viewModel = new TemplateBuilderViewModel(user: $user, template: $template->fresh());

        $this->assertEquals('v2 · based on v1', $viewModel->versionLabel());
    }

    #[Test]
    public function it_lists_the_sections_with_their_fields(): void
    {
        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);
        Field::factory()->create([
            'section_version_id' => $row->section_version_id,
            'label' => 'Weight',
            'help_text' => 'In kilograms',
            'type' => FieldTypeEnum::Number,
        ]);

        $sections = new TemplateBuilderViewModel(user: $user, template: $template->fresh())->sections();

        $this->assertCount(1, $sections);
        $this->assertEquals('1 field', $sections[0]['fieldLabel']);
        $this->assertEquals('Weight', $sections[0]['fields'][0]['label']);
        $this->assertEquals('In kilograms', $sections[0]['fields'][0]['help']);
        $this->assertEquals('Number', $sections[0]['fields'][0]['typeLabel']);
    }

    #[Test]
    public function it_shows_the_heading_this_template_gave_the_section(): void
    {
        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);
        $row->update(['title_override' => 'Where they live']);

        $sections = new TemplateBuilderViewModel(user: $user, template: $template->fresh())->sections();

        $this->assertEquals('Where they live', $sections[0]['name']);
    }

    /**
     * The warning on the section is what makes the copy on write something
     * somebody has read about rather than something that happens to them.
     */
    #[Test]
    public function it_warns_that_a_shared_section_will_be_copied(): void
    {
        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);

        $alone = new TemplateBuilderViewModel(user: $user, template: $template->fresh())->sections();
        $this->assertFalse($alone[0]['willBeCopied']);

        $row->sectionVersion->update(['usage_count' => 2]);

        $shared = new TemplateBuilderViewModel(user: $user, template: $template->fresh())->sections();
        $this->assertTrue($shared[0]['willBeCopied']);
    }

    #[Test]
    public function it_opens_the_field_that_was_asked_for(): void
    {
        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);
        $field = Field::factory()->create(['section_version_id' => $row->section_version_id, 'label' => 'Weight']);

        $viewModel = new TemplateBuilderViewModel(user: $user, template: $template->fresh(), selectedFieldId: $field->id);

        $this->assertEquals('Weight', $viewModel->selectedField()['label']);
        $this->assertEquals($row->id, $viewModel->selectedField()['rowId']);
        $this->assertTrue($viewModel->sections()[0]['fields'][0]['isSelected']);
    }

    /**
     * A field id somebody typed into the URL must not open a field from another
     * template, and must not open one from another account at all.
     */
    #[Test]
    public function it_ignores_a_field_that_is_not_on_this_template(): void
    {
        $user = User::factory()->create();
        [$template] = $this->templateCarryingASection($user);
        $elsewhere = Field::factory()->create(['account_id' => $user->account_id]);

        $viewModel = new TemplateBuilderViewModel(user: $user, template: $template->fresh(), selectedFieldId: $elsewhere->id);

        $this->assertNull($viewModel->selectedField());
    }

    #[Test]
    public function it_ignores_a_field_from_another_account(): void
    {
        $michael = User::factory()->create();
        [$template] = $this->templateCarryingASection($michael);
        $theirs = Field::factory()->create();

        $viewModel = new TemplateBuilderViewModel(user: $michael, template: $template->fresh(), selectedFieldId: $theirs->id);

        $this->assertNull($viewModel->selectedField());
    }

    #[Test]
    public function it_says_nothing_is_relying_on_a_template_that_was_never_published(): void
    {
        $user = User::factory()->create();
        [$template] = $this->templateCarryingASection($user);

        $viewModel = new TemplateBuilderViewModel(user: $user, template: $template->fresh());

        $this->assertStringContainsString('Nothing is using this template yet', $viewModel->publishingNote());
    }

    #[Test]
    public function it_lists_the_pages_of_the_version_with_the_fields_on_each(): void
    {
        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);
        $version = $template->versions()->sole();
        TemplateVersionPage::factory()->create(['template_version_id' => $version->id, 'label' => 'Life events']);
        Field::factory()->count(2)->create(['section_version_id' => $row->section_version_id]);

        $pages = new TemplateBuilderViewModel(user: $user, template: $template->fresh())->pages();

        $this->assertCount(2, $pages);
        $this->assertEquals(['General', 'Life events'], array_column($pages, 'label'));
        $this->assertEquals([2, 0], array_column($pages, 'fieldCount'));
        $this->assertTrue($pages[0]['isSelected']);
        $this->assertFalse($pages[1]['isSelected']);
    }

    #[Test]
    public function it_opens_the_page_that_was_asked_for(): void
    {
        $user = User::factory()->create();
        [$template] = $this->templateCarryingASection($user);
        $version = $template->versions()->sole();
        $events = TemplateVersionPage::factory()->create(['template_version_id' => $version->id, 'label' => 'Life events']);

        $viewModel = new TemplateBuilderViewModel(
            user: $user,
            template: $template->fresh(),
            selectedPageKey: $events->key,
        );

        $this->assertTrue($viewModel->selectedPage()->is($events));
        $this->assertEquals([], $viewModel->sections());
    }

    /**
     * A tab that has gone is not worth an error page of its own.
     */
    #[Test]
    public function it_falls_back_to_the_first_page_when_the_one_asked_for_is_not_on_this_version(): void
    {
        $user = User::factory()->create();
        [$template] = $this->templateCarryingASection($user);
        $elsewhere = TemplateVersionPage::factory()->create();

        $viewModel = new TemplateBuilderViewModel(
            user: $user,
            template: $template->fresh(),
            selectedPageKey: $elsewhere->key,
        );

        $this->assertEquals('General', $viewModel->selectedPage()->label);
        $this->assertCount(1, $viewModel->sections());
    }

    #[Test]
    public function it_shows_only_the_sections_of_the_page_that_is_open(): void
    {
        $user = User::factory()->create();
        [$template] = $this->templateCarryingASection($user);
        $version = $template->versions()->sole();
        $events = TemplateVersionPage::factory()->create(['template_version_id' => $version->id, 'label' => 'Life events']);
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Jobs']);
        TemplateVersionSection::factory()->create([
            'template_version_id' => $version->id,
            'template_version_page_id' => $events->id,
            'section_version_id' => SectionVersion::factory()->create(['section_id' => $section->id])->id,
        ]);

        $onGeneral = new TemplateBuilderViewModel(user: $user, template: $template->fresh())->sections();
        $onEvents = new TemplateBuilderViewModel(user: $user, template: $template->fresh(), selectedPageKey: $events->key)->sections();

        $this->assertEquals(['Address'], array_column($onGeneral, 'name'));
        $this->assertEquals(['Jobs'], array_column($onEvents, 'name'));
    }

    /**
     * The page is named only when it is not the tab somebody is looking at.
     */
    #[Test]
    public function it_names_the_page_of_a_field_that_is_open_from_another_tab(): void
    {
        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);
        $version = $template->versions()->sole();
        $events = TemplateVersionPage::factory()->create(['template_version_id' => $version->id, 'label' => 'Life events']);
        $field = Field::factory()->create([
            'section_version_id' => $row->section_version_id,
            'account_id' => $user->account_id,
            'label' => 'Street',
            'type' => FieldTypeEnum::Text,
        ]);

        $fromItsOwnPage = new TemplateBuilderViewModel(user: $user, template: $template->fresh(), selectedFieldId: $field->id);
        $fromAnother = new TemplateBuilderViewModel(
            user: $user,
            template: $template->fresh(),
            selectedFieldId: $field->id,
            selectedPageKey: $events->key,
        );

        $this->assertEquals('Address', $fromItsOwnPage->selectedField()['sectionName']);
        $this->assertEquals('General › Address', $fromAnother->selectedField()['sectionName']);
    }

    #[Test]
    public function it_refuses_to_offer_the_removal_of_the_last_page(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        TemplateVersion::factory()->create(['template_id' => $template->id]);

        $viewModel = new TemplateBuilderViewModel(user: $user, template: $template->fresh());

        $this->assertFalse($viewModel->canRemovePage());
    }

    #[Test]
    public function it_refuses_to_offer_the_removal_of_a_page_still_holding_sections(): void
    {
        $user = User::factory()->create();
        [$template] = $this->templateCarryingASection($user);
        $version = $template->versions()->sole();
        $events = TemplateVersionPage::factory()->create(['template_version_id' => $version->id]);

        $onGeneral = new TemplateBuilderViewModel(user: $user, template: $template->fresh());
        $onEvents = new TemplateBuilderViewModel(user: $user, template: $template->fresh(), selectedPageKey: $events->key);

        $this->assertFalse($onGeneral->canRemovePage());
        $this->assertTrue($onEvents->canRemovePage());
    }

    #[Test]
    public function it_offers_the_other_pages_a_section_could_move_to(): void
    {
        $user = User::factory()->create();
        [$template] = $this->templateCarryingASection($user);
        $version = $template->versions()->sole();
        $events = TemplateVersionPage::factory()->create(['template_version_id' => $version->id, 'label' => 'Life events']);

        $others = new TemplateBuilderViewModel(user: $user, template: $template->fresh())->otherPages();

        $this->assertEquals([['id' => $events->id, 'uuid' => $events->uuid, 'label' => 'Life events']], $others);
    }

    /**
     * @return array{0: Template, 1: TemplateVersionSection}
     */
    private function templateCarryingASection(User $user): array
    {
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id, 'status' => VersionStatusEnum::Draft]);
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Address']);
        $sectionVersion = SectionVersion::factory()->used(1)->create(['section_id' => $section->id]);
        $row = TemplateVersionSection::factory()->create([
            'template_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        return [$template, $row];
    }
}
