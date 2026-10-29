<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\Templates;

use App\Enums\VersionStatusEnum;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use App\ViewModels\Settings\Account\Templates\TemplatesViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TemplatesViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_splits_what_somebody_built_from_what_was_supplied(): void
    {
        $user = User::factory()->create();
        Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Pet']);
        Template::factory()->supplied()->create(['account_id' => $user->account_id]);

        $viewModel = new TemplatesViewModel(user: $user);

        $this->assertCount(1, $viewModel->ownTemplates());
        $this->assertCount(1, $viewModel->suppliedTemplates());
        $this->assertEquals('Pet', $viewModel->ownTemplates()[0]['name']);
    }

    #[Test]
    public function it_leaves_out_the_templates_of_another_account(): void
    {
        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        Template::factory()->create(['account_id' => $dwight->account_id]);

        $viewModel = new TemplatesViewModel(user: $michael);

        $this->assertCount(0, $viewModel->ownTemplates());
    }

    #[Test]
    public function it_narrows_the_list_by_name_or_slug(): void
    {
        $user = User::factory()->create();
        Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Pet', 'slug' => 'pet']);
        Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Company', 'slug' => 'company']);

        $this->assertCount(1, (new TemplatesViewModel(user: $user, query: 'pet'))->ownTemplates());
        $this->assertCount(1, (new TemplatesViewModel(user: $user, query: 'compan'))->ownTemplates());
        $this->assertCount(2, (new TemplatesViewModel(user: $user))->ownTemplates());
    }

    #[Test]
    public function it_narrows_the_list_to_the_ones_with_a_draft(): void
    {
        $user = User::factory()->create();
        $withDraft = Template::factory()->create(['account_id' => $user->account_id]);
        StructureVersion::factory()->create(['structure_id' => $withDraft->structure->id]);
        $published = Template::factory()->create(['account_id' => $user->account_id]);
        StructureVersion::factory()->published()->create(['structure_id' => $published->structure->id]);

        $viewModel = new TemplatesViewModel(user: $user, filter: TemplatesViewModel::FILTER_DRAFTS);

        $this->assertCount(1, $viewModel->ownTemplates());
    }

    #[Test]
    public function it_narrows_the_list_to_the_published_ones(): void
    {
        $user = User::factory()->create();
        $published = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->published()->create(['structure_id' => $published->structure->id]);
        $published->structure->update(['published_version_id' => $version->id]);
        Template::factory()->create(['account_id' => $user->account_id]);

        $viewModel = new TemplatesViewModel(user: $user, filter: TemplatesViewModel::FILTER_PUBLISHED);

        $this->assertCount(1, $viewModel->ownTemplates());
    }

    #[Test]
    public function it_says_how_much_of_the_list_is_showing(): void
    {
        $user = User::factory()->create();
        Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Pet']);
        Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Company']);

        $this->assertEquals('2 templates', (new TemplatesViewModel(user: $user))->summary());
        $this->assertEquals('1 of 2 templates', (new TemplatesViewModel(user: $user, query: 'pet'))->summary());
    }

    #[Test]
    public function it_says_which_version_is_showing_and_what_state_it_is_in(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $first = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);
        $template->structure->update(['published_version_id' => $first->id]);

        $row = (new TemplatesViewModel(user: $user))->ownTemplates()[0];

        $this->assertEquals('v1', $row['versionLabel']);
        $this->assertEquals('published', $row['versionSubLabel']);
    }

    #[Test]
    public function it_says_what_a_draft_was_based_on(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $first = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);
        StructureVersion::factory()->create([
            'structure_id' => $template->structure->id,
            'version_number' => 2,
            'previous_version_id' => $first->id,
        ]);

        $row = (new TemplatesViewModel(user: $user))->ownTemplates()[0];

        $this->assertEquals('v2', $row['versionLabel']);
        $this->assertEquals('draft, based on v1', $row['versionSubLabel']);
        $this->assertTrue($row['hasDraft']);
    }

    #[Test]
    public function it_says_a_template_with_no_version_has_never_been_published(): void
    {
        $user = User::factory()->create();
        Template::factory()->create(['account_id' => $user->account_id]);

        $row = (new TemplatesViewModel(user: $user))->ownTemplates()[0];

        $this->assertEquals('—', $row['versionLabel']);
        $this->assertEquals('never published', $row['versionSubLabel']);
    }

    #[Test]
    public function it_marks_the_filter_in_force(): void
    {
        $user = User::factory()->create();

        $filters = (new TemplatesViewModel(user: $user, filter: TemplatesViewModel::FILTER_DRAFTS))->filters();
        $current = array_values(array_filter($filters, fn (array $chip): bool => $chip['isCurrent']));

        $this->assertCount(1, $current);
        $this->assertEquals(TemplatesViewModel::FILTER_DRAFTS, $current[0]['key']);
    }

    #[Test]
    public function it_falls_back_to_showing_everything_when_the_filter_makes_no_sense(): void
    {
        $user = User::factory()->create();

        $this->assertEquals(TemplatesViewModel::FILTER_ALL, (new TemplatesViewModel(user: $user, filter: 'nonsense'))->filter());
    }

    #[Test]
    public function it_keeps_the_order_somebody_arranged_and_does_not_alphabetise_it(): void
    {
        $this->app->setLocale('fr_FR');

        $user = User::factory()->create();

        foreach (['Zoe', 'Élodie', 'Erin'] as $position => $name) {
            Template::factory()->create([
                'account_id' => $user->account_id,
                'name' => $name,
                'position' => $position + 1,
            ]);
        }

        $names = array_column(new TemplatesViewModel(user: $user)->ownTemplates(), 'name');

        $this->assertSame(['Zoe', 'Élodie', 'Erin'], $names);
    }

    #[Test]
    public function it_counts_the_sections_of_the_version_it_shows(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        StructureVersionSection::factory()->create(['structure_version_id' => $version->id]);
        $version->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);
        $template->structure->update(['published_version_id' => $version->id]);

        $row = (new TemplatesViewModel(user: $user))->ownTemplates()[0];

        $this->assertEquals('1 section', $row['sectionLabel']);
    }
}
