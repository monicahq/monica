<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\CurrencyEnum;
use App\Enums\PossessionMaintenanceTypeEnum;
use App\Models\Possession;
use App\Models\PossessionCondition;
use App\Models\PossessionMaintenanceRecord;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\PossessionMaintenanceViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionMaintenanceViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_describes_a_job_with_its_conditions_and_its_cost(): void
    {
        $this->travelTo('2026-10-27');

        [$user, $dundie] = $this->dundie();
        $worn = PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Worn']);
        $likeNew = PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Like new']);
        PossessionMaintenanceRecord::factory()->create([
            'possession_id' => $dundie->id,
            'type' => PossessionMaintenanceTypeEnum::Restoration,
            'title' => 'Re-plating',
            'performed_by' => 'Scranton Trophy and Engraving',
            'cost' => '40.00',
            'currency' => CurrencyEnum::UsDollar,
            'condition_before_id' => $worn->id,
            'condition_after_id' => $likeNew->id,
            'next_due_on' => '2027-03-12',
        ]);

        $record = new PossessionMaintenanceViewModel(user: $user, possession: $dundie)->records()[0];
        $parts = array_column($record['parts'], 'value', 'label');

        $this->assertSame('Restoration', $record['type']);
        $this->assertSame('$40', $record['cost']);
        $this->assertFalse($record['isOverdue']);
        $this->assertSame('Scranton Trophy and Engraving', $parts['Performed by']);
        $this->assertSame('Worn → Like new', $parts['Condition']);
        $this->assertSame('Delete the “Re-plating” record?', $record['deleteTitle']);
    }

    #[Test]
    public function it_marks_only_the_latest_job_overdue(): void
    {
        $this->travelTo('2026-10-27');

        [$user, $dundie] = $this->dundie();
        PossessionMaintenanceRecord::factory()->create(['possession_id' => $dundie->id, 'performed_on' => '2025-01-01', 'next_due_on' => '2025-06-01']);
        PossessionMaintenanceRecord::factory()->create(['possession_id' => $dundie->id, 'performed_on' => '2026-03-12', 'next_due_on' => '2026-09-01']);

        $records = new PossessionMaintenanceViewModel(user: $user, possession: $dundie)->records();

        $this->assertSame([true, false], array_column($records, 'isOverdue'));
    }

    #[Test]
    public function it_leaves_out_the_job_being_edited(): void
    {
        [$user, $dundie] = $this->dundie();
        $editing = PossessionMaintenanceRecord::factory()->create(['possession_id' => $dundie->id]);
        PossessionMaintenanceRecord::factory()->create(['possession_id' => $dundie->id]);

        $viewModel = new PossessionMaintenanceViewModel(user: $user, possession: $dundie, editing: $editing);

        $this->assertCount(1, $viewModel->records());
        $this->assertTrue($viewModel->form()['isEditing']);
    }

    #[Test]
    public function it_offers_the_conditions_of_the_account_from_best_to_worst(): void
    {
        [$user, $dundie] = $this->dundie();
        PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Worn', 'position' => 2]);
        PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Like new', 'position' => 1]);
        PossessionCondition::factory()->create(['name' => 'Ruined']);

        $conditions = new PossessionMaintenanceViewModel(user: $user, possession: $dundie)->conditions();

        $this->assertSame(['Like new', 'Worn'], array_column($conditions, 'label'));
    }

    /** @return array{0: User, 1: Possession} */
    private function dundie(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return [$user, Possession::factory()->create([
            'vault_id' => $vault->id,
            'name' => 'Dundie award',
            'declared_value_currency' => 'usd',
        ])];
    }
}
