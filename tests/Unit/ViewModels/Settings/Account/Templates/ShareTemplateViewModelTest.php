<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\Templates;

use App\Enums\VersionStatusEnum;
use App\Models\Field;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use App\Models\Template;
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

    private function publishedTemplate(User $user): Template
    {
        $template = Template::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin contact',
            'slug' => 'dunder-mifflin-contact',
        ]);

        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);

        $section = Section::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Personal details',
        ]);

        $sectionVersion = SectionVersion::factory()->create(['section_id' => $section->id]);

        Field::factory()->create(['section_version_id' => $sectionVersion->id, 'label' => 'Nickname', 'position' => 1]);
        Field::factory()->create(['section_version_id' => $sectionVersion->id, 'label' => 'Branch', 'position' => 2]);

        StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
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

        $template->structure->update(['published_version_id' => $version->id]);

        return $template->fresh();
    }
}
