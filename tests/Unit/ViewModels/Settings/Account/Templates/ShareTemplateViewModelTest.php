<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\Templates;

use App\Enums\VersionStatusEnum;
use App\Models\Field;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionSection;
use App\Models\User;
use App\ViewModels\Settings\Account\Templates\ShareTemplateViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShareTemplateViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_names_the_file_after_the_template_and_the_version(): void
    {
        $user = User::factory()->create();
        $viewModel = new ShareTemplateViewModel(user: $user, template: $this->publishedTemplate($user));

        $this->assertEquals('dunder-mifflin-contact-v1.json', $viewModel->fileName());
    }

    #[Test]
    public function it_counts_what_is_in_the_file(): void
    {
        $user = User::factory()->create();
        $viewModel = new ShareTemplateViewModel(user: $user, template: $this->publishedTemplate($user));

        $this->assertEquals(1, $viewModel->sectionCount());
        $this->assertEquals(2, $viewModel->fieldCount());
        $this->assertEquals(0, $viewModel->valueListCount());
        $this->assertEquals('1 section, 2 fields, 0 value lists', $viewModel->structureLabel());
    }

    #[Test]
    public function it_shows_the_same_text_it_hands_over(): void
    {
        $user = User::factory()->create();
        $viewModel = new ShareTemplateViewModel(user: $user, template: $this->publishedTemplate($user));

        $decoded = json_decode($viewModel->json(), true);

        $this->assertEquals('template', $decoded['monica']['format']);
        $this->assertEquals('Dunder Mifflin contact', $decoded['template']['name']);
    }

    #[Test]
    public function it_says_how_many_records_are_on_the_version(): void
    {
        $user = User::factory()->create();
        $template = $this->publishedTemplate($user);

        $template->publishedVersion->record_count = 42;
        $template->publishedVersion->save();

        $viewModel = new ShareTemplateViewModel(user: $user, template: $template->fresh());

        $this->assertEquals('42 records', $viewModel->recordCountLabel());
    }

    #[Test]
    public function it_lists_what_is_in_the_file(): void
    {
        $user = User::factory()->create();
        $viewModel = new ShareTemplateViewModel(user: $user, template: $this->publishedTemplate($user));

        $labels = array_column($viewModel->inFile(), 'label');

        $this->assertEquals(['Structure', 'Version', 'Format', 'Size'], $labels);
    }

    private function publishedTemplate(User $user): Template
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
        ]);

        $sectionVersion = SectionVersion::factory()->create(['section_id' => $section->id]);

        Field::factory()->create(['section_version_id' => $sectionVersion->id, 'label' => 'Nickname', 'position' => 1]);
        Field::factory()->create(['section_version_id' => $sectionVersion->id, 'label' => 'Branch', 'position' => 2]);

        TemplateVersionSection::factory()->create([
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

        return $template->fresh();
    }
}
