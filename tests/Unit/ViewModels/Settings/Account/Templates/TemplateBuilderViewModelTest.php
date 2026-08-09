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
