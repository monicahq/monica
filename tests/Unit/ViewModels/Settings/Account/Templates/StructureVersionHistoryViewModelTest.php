<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\Templates;

use App\Enums\VersionStatusEnum;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use App\ViewModels\Settings\Account\Templates\StructureVersionHistoryViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StructureVersionHistoryViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_every_version_newest_first(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $first = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id, 'version_number' => 1]);
        $second = StructureVersion::factory()->published()->create([
            'structure_id' => $template->structure->id,
            'version_number' => 2,
            'previous_version_id' => $first->id,
        ]);
        StructureVersion::factory()->create([
            'structure_id' => $template->structure->id,
            'version_number' => 3,
            'previous_version_id' => $second->id,
        ]);

        $entries = new StructureVersionHistoryViewModel(user: $user, template: $template)->entries();

        $this->assertSame(['v3', 'v2', 'v1'], array_map(fn ($entry): string => $entry->label(), $entries));
    }

    #[Test]
    public function it_lists_a_retired_version_too(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        StructureVersion::factory()->retired()->create(['structure_id' => $template->structure->id, 'version_number' => 1]);
        StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id, 'version_number' => 2]);

        $entries = new StructureVersionHistoryViewModel(user: $user, template: $template)->entries();

        $this->assertCount(2, $entries);
    }

    #[Test]
    public function it_says_when_a_template_has_no_version(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);

        $viewModel = new StructureVersionHistoryViewModel(user: $user, template: $template);

        $this->assertFalse($viewModel->hasVersions());
        $this->assertSame([], $viewModel->entries());
    }

    #[Test]
    public function it_reads_the_section_version_the_structure_version_placed_rather_than_the_section(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Address']);
        $placed = SectionVersion::factory()->create(['section_id' => $section->id, 'version_number' => 3]);
        SectionVersion::factory()->create(['section_id' => $section->id, 'version_number' => 4]);
        StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'section_version_id' => $placed->id,
        ]);
        $this->publish($version);

        $entries = new StructureVersionHistoryViewModel(user: $user, template: $template)->entries();

        $this->assertSame('s3', $entries[0]->pages()[0]['sections'][0]['version']);
    }

    #[Test]
    public function it_points_back_at_the_builder(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Basic contact']);

        $viewModel = new StructureVersionHistoryViewModel(user: $user, template: $template);

        $this->assertSame(route('settings.templateBuilder.index', $template), $viewModel->backUrl());
    }

    #[Test]
    public function it_reads_the_whole_tree_without_a_query_per_version(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);

        foreach (range(1, 3) as $number) {
            $version = StructureVersion::factory()->create([
                'structure_id' => $template->structure->id,
                'version_number' => $number,
            ]);
            StructureVersionSection::factory()->create(['structure_version_id' => $version->id]);
            $this->publish($version);
        }

        $viewModel = new StructureVersionHistoryViewModel(user: $user, template: $template);

        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        foreach ($viewModel->entries() as $entry) {
            $entry->counts();
            $entry->pages();
        }

        $this->assertLessThanOrEqual(6, $queries);
    }

    private function publish(StructureVersion $version): void
    {
        $version->update([
            'status' => VersionStatusEnum::Published,
            'published_at' => now(),
        ]);
    }
}
