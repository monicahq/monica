<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdatePossessionMaintenanceRecord;
use App\Enums\CurrencyEnum;
use App\Enums\PossessionMaintenanceTypeEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Possession;
use App\Models\PossessionCondition;
use App\Models\PossessionMaintenanceRecord;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdatePossessionMaintenanceRecordTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_changes_a_job(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possession = Possession::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id, 'name' => 'Watercolour of the office']);
        $worn = PossessionCondition::factory()->create(['account_id' => $user->account_id]);
        $record = PossessionMaintenanceRecord::factory()->create(['possession_id' => $possession->id, 'condition_before_id' => $worn->id, 'cost' => '30.00', 'currency' => CurrencyEnum::UsDollar]);

        new UpdatePossessionMaintenanceRecord(
            user: $user,
            record: $record,
            type: PossessionMaintenanceTypeEnum::Conservation,
            title: 'Reframing',
            performedOn: CarbonImmutable::parse('2026-04-02'),
            cost: '45',
            currency: CurrencyEnum::UsDollar,
        )->execute();

        $this->assertDatabaseHas('possession_maintenance_records', [
            'id' => $record->id,
            'type' => 'conservation',
            'title' => 'Reframing',
            'cost' => '45.00',
            'condition_before_id' => null,
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionMaintenanceRecordUpdate
                && $job->parameters === ['name' => 'Watercolour of the office'],
        );
    }

    #[Test]
    public function it_refuses_a_record_of_another_account(): void
    {
        $user = User::factory()->create();
        $record = PossessionMaintenanceRecord::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new UpdatePossessionMaintenanceRecord(
            user: $user,
            record: $record,
            type: PossessionMaintenanceTypeEnum::Repair,
            title: 'New gasket',
            performedOn: CarbonImmutable::parse('2026-03-12'),
        )->execute();
    }
}
