<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\CurrencyEnum;
use App\Enums\PossessionProvenanceDatePrecisionEnum;
use App\Enums\PossessionTimelineSectionEnum;
use App\Enums\PossessionTransactionTypeEnum;
use App\Models\Contact;
use App\Models\Location;
use App\Models\Possession;
use App\Models\PossessionLoan;
use App\Models\PossessionLocationStay;
use App\Models\PossessionMaintenanceRecord;
use App\Models\PossessionProvenanceEvent;
use App\Models\PossessionTransaction;
use App\Models\PossessionValuation;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\PossessionTimelineViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionTimelineViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_words_each_entry_the_way_its_section_does(): void
    {
        [$user, $costume] = $this->armadillo();
        $dwight = Contact::factory()->create(['account_id' => $costume->account_id, 'vault_id' => $costume->vault_id, 'first_name' => 'Dwight', 'last_name' => 'Schrute']);
        PossessionTransaction::factory()->create(['possession_id' => $costume->id, 'type' => PossessionTransactionTypeEnum::Purchase, 'counterparty' => 'Party City', 'occurred_on' => '2025-06-28', 'amount' => '45.00', 'currency' => CurrencyEnum::UsDollar]);
        PossessionValuation::factory()->create(['possession_id' => $costume->id, 'valued_on' => '2025-07-01', 'amount' => '45.00', 'currency' => CurrencyEnum::UsDollar]);
        PossessionValuation::factory()->create(['possession_id' => $costume->id, 'valued_on' => '2026-10-26', 'amount' => '70.00', 'currency' => CurrencyEnum::UsDollar]);
        PossessionLoan::factory()->returned()->create(['possession_id' => $costume->id, 'contact_id' => $dwight->id, 'started_on' => '2026-10-12', 'returned_on' => '2026-11-02']);
        PossessionMaintenanceRecord::factory()->create(['possession_id' => $costume->id, 'title' => 'Cleaning', 'performed_on' => '2026-09-14', 'cost' => '18.00', 'currency' => CurrencyEnum::UsDollar]);
        PossessionProvenanceEvent::factory()->create(['possession_id' => $costume->id, 'title' => 'Previously owned by Jan Levinson', 'date_precision' => PossessionProvenanceDatePrecisionEnum::Circa, 'occurred_on' => '2024-01-01']);

        $all = collect($this->viewModel($user, $costume)->groups())->flatMap(fn (array $group): array => $group['entries']);
        $entries = $all->keyBy('title');

        $this->assertSame('Acquisition', $entries['Purchase']['badge']);
        $this->assertSame('Seller: Party City', $entries['Purchase']['detail']);
        $current = $all->first(fn (array $entry): bool => $entry['badge'] === 'Current value');
        $this->assertSame('Valuation', $current['noun']);
        $this->assertStringStartsWith('Up ', $current['amountNote']);
        $this->assertArrayHasKey('Lent to Dwight Schrute', $entries->all());
        $this->assertArrayHasKey('Returned by Dwight Schrute', $entries->all());
        $this->assertSame('Paid', $entries['Cleaning']['amountNote']);
        $this->assertSame('About 2024', $entries['Previously owned by Jan Levinson']['date']);
        $this->assertStringStartsWith(route('possessions.loans.index', [$costume->vault, $costume]).'#loan-', $entries['Lent to Dwight Schrute']['url']);
        $this->assertSame(route('possessions.maintenanceRecords.index', [$costume->vault, $costume]), $entries['Cleaning']['url']);
    }

    #[Test]
    public function it_says_where_the_possession_was_kept_then_where_it_moved(): void
    {
        [$user, $costume] = $this->armadillo();
        $closet = Location::factory()->create(['account_id' => $costume->account_id, 'name' => 'Hall closet']);
        $storage = Location::factory()->create(['account_id' => $costume->account_id, 'name' => 'Storage space']);
        PossessionLocationStay::factory()->create(['possession_id' => $costume->id, 'location_id' => $closet->id, 'arrived_on' => '2025-06-28', 'left_on' => '2026-11-17']);
        PossessionLocationStay::factory()->create(['possession_id' => $costume->id, 'location_id' => $storage->id, 'arrived_on' => '2026-11-17', 'left_on' => null]);

        $entries = collect($this->viewModel($user, $costume)->groups())->flatMap(fn (array $group): array => $group['entries']);

        $this->assertSame(['Moved to Storage space', 'Kept in Hall closet'], $entries->pluck('title')->all());
        $this->assertSame(['Here now', null], $entries->pluck('badge')->all());
    }

    #[Test]
    public function it_groups_the_entries_by_year_and_puts_the_undated_last(): void
    {
        [$user, $costume] = $this->armadillo();
        PossessionMaintenanceRecord::factory()->create(['possession_id' => $costume->id, 'performed_on' => '2026-09-14']);
        PossessionMaintenanceRecord::factory()->create(['possession_id' => $costume->id, 'performed_on' => '2026-03-01']);
        PossessionMaintenanceRecord::factory()->create(['possession_id' => $costume->id, 'performed_on' => '2025-12-20']);
        PossessionTransaction::factory()->create(['possession_id' => $costume->id, 'occurred_on' => null]);

        $groups = $this->viewModel($user, $costume)->groups();

        $this->assertSame(['2026', '2025', 'Date unknown'], array_column($groups, 'title'));
        $this->assertSame(['2 entries', '1 entry', '1 entry'], array_column($groups, 'count'));
    }

    #[Test]
    public function it_offers_a_chip_per_section_holding_entries_and_narrows_to_the_chosen_one(): void
    {
        [$user, $costume] = $this->armadillo();
        PossessionMaintenanceRecord::factory()->count(2)->create(['possession_id' => $costume->id]);
        PossessionTransaction::factory()->create(['possession_id' => $costume->id]);

        $viewModel = $this->viewModel($user, $costume, PossessionTimelineSectionEnum::Maintenance);
        $chips = $viewModel->chips();

        $this->assertSame(['All', 'Transactions', 'Maintenance'], array_column($chips, 'label'));
        $this->assertSame([3, 1, 2], array_column($chips, 'count'));
        $this->assertSame([false, false, true], array_column($chips, 'isCurrent'));
        $this->assertSame(route('possessions.timeline.index', [$costume->vault, $costume, 'maintenance']), $chips[2]['url']);
        $this->assertCount(2, collect($viewModel->groups())->flatMap(fn (array $group): array => $group['entries']));
    }

    #[Test]
    public function it_says_so_when_nothing_is_recorded(): void
    {
        [$user, $costume] = $this->armadillo();

        $viewModel = $this->viewModel($user, $costume);

        $this->assertSame([], $viewModel->groups());
        $this->assertSame([], $viewModel->chips());
        $this->assertSame('Nothing recorded in any section yet.', $viewModel->emptyLine());
    }

    /** @return array{0: User, 1: Possession} */
    private function armadillo(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return [$user, Possession::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'name' => 'Holiday Armadillo costume'])];
    }

    private function viewModel(User $user, Possession $possession, ?PossessionTimelineSectionEnum $section = null): PossessionTimelineViewModel
    {
        return new PossessionTimelineViewModel(user: $user, possession: $possession->fresh(), section: $section);
    }
}
