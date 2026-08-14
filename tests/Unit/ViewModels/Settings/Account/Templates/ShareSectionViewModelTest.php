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
use App\ViewModels\Settings\Account\Templates\ShareSectionViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShareSectionViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_names_the_file_after_the_section(): void
    {
        $user = User::factory()->create();
        $viewModel = $this->viewModel($user);

        $this->assertEquals('personal-details.json', $viewModel->fileName());
    }

    #[Test]
    public function it_reads_by_the_title_this_template_gives_the_section(): void
    {
        $user = User::factory()->create();
        $row = $this->publishedRow($user);

        $row->title_override = 'About Dwight';
        $row->saveQuietly();

        $viewModel = new ShareSectionViewModel(user: $user, template: $row->templateVersion->template, row: $row->fresh());

        $this->assertEquals('About Dwight', $viewModel->name());
    }

    #[Test]
    public function it_lists_the_fields_that_are_going_out(): void
    {
        $user = User::factory()->create();
        $viewModel = $this->viewModel($user);

        $this->assertEquals(
            [['label' => 'Nickname', 'type' => 'Short text'], ['label' => 'Branch', 'type' => 'Short text']],
            $viewModel->fields(),
        );
    }

    #[Test]
    public function it_counts_what_is_in_the_file(): void
    {
        $user = User::factory()->create();
        $viewModel = $this->viewModel($user);

        $this->assertEquals(2, $viewModel->fieldCount());
        $this->assertEquals('1 section, 2 fields, 0 lists of choices', $viewModel->structureLabel());
    }

    #[Test]
    public function it_says_which_template_the_section_came_out_of(): void
    {
        $user = User::factory()->create();
        $viewModel = $this->viewModel($user);

        $this->assertEquals('Dunder Mifflin contact, v1', $viewModel->fromLabel());
    }

    #[Test]
    public function it_shows_the_same_text_it_hands_over(): void
    {
        $user = User::factory()->create();
        $viewModel = $this->viewModel($user);

        $decoded = json_decode($viewModel->json(), true);

        $this->assertEquals('section', $decoded['monica']['format']);
        $this->assertEquals('Personal details', $decoded['section']['name']);
    }

    private function viewModel(User $user): ShareSectionViewModel
    {
        $row = $this->publishedRow($user);

        return new ShareSectionViewModel(
            user: $user,
            template: $row->templateVersion->template,
            row: $row,
        );
    }

    private function publishedRow(User $user): TemplateVersionSection
    {
        $template = Template::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin contact',
            'slug' => 'dunder-mifflin-contact',
        ]);

        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);

        $section = Section::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Personal details',
            'slug' => 'personal-details',
        ]);

        $sectionVersion = SectionVersion::factory()->create(['section_id' => $section->id]);

        Field::factory()->create([
            'section_version_id' => $sectionVersion->id,
            'label' => 'Nickname',
            'type' => FieldTypeEnum::Text,
            'position' => 1,
        ]);

        Field::factory()->create([
            'section_version_id' => $sectionVersion->id,
            'label' => 'Branch',
            'type' => FieldTypeEnum::Text,
            'position' => 2,
        ]);

        $row = TemplateVersionSection::factory()->create([
            'template_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $sectionVersion->status = VersionStatusEnum::Published;
        $sectionVersion->published_at = now();
        $sectionVersion->save();

        $section->published_version_id = $sectionVersion->id;
        $section->save();

        $version->status = VersionStatusEnum::Published;
        $version->published_at = now();
        $version->save();

        $template->published_version_id = $version->id;
        $template->save();

        return $row->fresh();
    }
}
