<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\PossessionConditions;

use App\Enums\VaultColourEnum;
use App\Models\Possession;
use App\Models\PossessionCondition;
use App\Models\PossessionMaintenanceRecord;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Settings\Account\PossessionConditions\PossessionConditionsViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionConditionsViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_conditions_of_the_account_in_its_order(): void
    {
        $user = User::factory()->create();
        PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Broken', 'position' => 3, 'colour' => VaultColourEnum::Slate->value]);
        PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Mint', 'position' => 1, 'colour' => VaultColourEnum::Teal->value, 'icon' => 'star']);
        PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Used', 'position' => 2]);
        PossessionCondition::factory()->create(['name' => 'Water damaged']);

        $conditions = new PossessionConditionsViewModel(user: $user)->conditions();

        $this->assertSame(['Mint', 'Used', 'Broken'], array_column($conditions, 'name'));
        $this->assertSame('star', $conditions[0]['icon']);
        $this->assertSame([true, false, false], array_column($conditions, 'isFirst'));
        $this->assertSame([false, false, true], array_column($conditions, 'isLast'));
    }

    #[Test]
    public function it_says_nothing_when_the_account_has_no_condition(): void
    {
        $viewModel = new PossessionConditionsViewModel(user: User::factory()->create());

        $this->assertSame('', $viewModel->countLine());
        $this->assertTrue($viewModel->isEmpty());
        $this->assertSame([], $viewModel->conditions());
    }

    #[Test]
    public function it_warns_how_many_maintenance_records_name_a_condition_before_it_is_deleted(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $possession = Possession::factory()->create(['vault_id' => $vault->id]);
        $worn = PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Worn', 'position' => 1]);
        PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Mint', 'position' => 2]);
        PossessionMaintenanceRecord::factory()->create(['possession_id' => $possession->id, 'condition_before_id' => $worn->id, 'condition_after_id' => $worn->id]);
        PossessionMaintenanceRecord::factory()->create(['possession_id' => $possession->id, 'condition_after_id' => $worn->id]);

        $conditions = new PossessionConditionsViewModel(user: $user)->conditions();

        $this->assertSame('2 maintenance records name this condition. They will read “Not recorded” there.', $conditions[0]['deleteWarning']);
        $this->assertSame('', $conditions[1]['deleteWarning']);
    }
}
