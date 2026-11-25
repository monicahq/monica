<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Helpers\RecordKind;
use App\Models\Entity;
use App\Models\Vault;
use App\ViewModels\Vaults\RecordStatisticsViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Statistics\AnimalsVault;
use Tests\TestCase;

class RecordStatisticsViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_names_the_page_after_the_kind_and_states_its_scope(): void
    {
        $fixture = AnimalsVault::build();

        $viewModel = $this->viewModel($fixture);

        $this->assertSame('Animals statistics', $viewModel->title());
        $this->assertSame('Family vault · Non-archived Animals', $viewModel->scope());
        $this->assertSame([
            ['label' => 'Animals', 'value' => 18],
            ['label' => 'Templates in use', 'value' => 2],
            ['label' => 'On older versions', 'value' => 3],
            ['label' => 'Archived Animals', 'value' => 2],
        ], $viewModel->overview());
    }

    #[Test]
    public function it_leaves_older_versions_out_when_no_record_is_on_one(): void
    {
        $fixture = AnimalsVault::build();
        Entity::query()->where('structure_version_id', $fixture->petV2->id)->update(['structure_version_id' => $fixture->petV3->id]);

        $labels = array_column($this->viewModel($fixture)->overview(), 'label');

        $this->assertNotContains('On older versions', $labels);
    }

    #[Test]
    public function it_links_each_version_to_the_list_filtered_on_it(): void
    {
        $fixture = AnimalsVault::build();

        $pet = $this->viewModel($fixture)->templates()[0];

        $this->assertSame('14 Animals · 78%', $pet['summary']);
        $this->assertSame(['v3 · 11 records', 'v2 · 3 records'], array_column($pet['versions'], 'label'));
        $this->assertSame(
            route('entities.index', ['vault' => $fixture->vault, 'entityType' => $fixture->animals, 'template' => $fixture->pet->uuid, 'version' => 2]),
            $pet['versions'][1]['url'],
        );
        $this->assertSame('View 14 Animals', $pet['viewLabel']);
    }

    #[Test]
    public function it_shows_the_default_template_in_field_completion_and_says_when_nothing_uses_one(): void
    {
        $fixture = AnimalsVault::build();
        $emptyVault = Vault::factory()->create(['account_id' => $fixture->user->account_id]);

        $viewModel = $this->viewModel($fixture);
        $empty = new RecordStatisticsViewModel(user: $fixture->user, kind: RecordKind::forEntityType(vault: $emptyVault, entityType: $fixture->animals));

        $this->assertSame('Pet', collect($viewModel->completionTemplates())->firstWhere('selected', true)['label']);
        $this->assertSame('5 of 11 · 45%', $viewModel->completionSections()[0]['fields'][0]['figures']);
        $this->assertSame('Available from v3', $viewModel->completionSections()[0]['fields'][0]['note']);
        $this->assertTrue($empty->isEmpty());
        $this->assertTrue($empty->completionIsEmpty());
        $this->assertSame('No Animals yet', $empty->emptyTitle());
    }

    #[Test]
    public function it_offers_a_change_only_where_records_and_a_destination_exist(): void
    {
        $fixture = AnimalsVault::build();

        [$pet, $horse, $bird] = $this->viewModel($fixture)->templates();

        $this->assertSame(['Change template…', 'Update to v3…'], array_column($pet['actions'], 'label'));
        $this->assertSame(['Change template…'], array_column($horse['actions'], 'label'));
        $this->assertSame([], $bird['actions']);
    }

    #[Test]
    public function it_keeps_the_update_when_no_other_template_can_take_the_records(): void
    {
        $fixture = AnimalsVault::build();
        $fixture->horse->update(['is_active' => false]);
        $fixture->bird->update(['is_active' => false]);

        $pet = $this->viewModel($fixture)->templates()[0];

        $this->assertSame(['Update to v3…'], array_column($pet['actions'], 'label'));
    }

    private function viewModel(AnimalsVault $fixture): RecordStatisticsViewModel
    {
        return new RecordStatisticsViewModel(
            user: $fixture->user,
            kind: RecordKind::forEntityType(vault: $fixture->vault, entityType: $fixture->animals),
        );
    }
}
