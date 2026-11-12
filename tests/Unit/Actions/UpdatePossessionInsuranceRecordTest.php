<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdatePossessionInsuranceRecord;
use App\Enums\CurrencyEnum;
use App\Enums\PossessionInsuranceStatusEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Possession;
use App\Models\PossessionInsuranceRecord;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdatePossessionInsuranceRecordTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_changes_the_record_and_leaves_the_others_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possession = Possession::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id, 'name' => 'Watercolour of the office']);
        $record = PossessionInsuranceRecord::factory()->create(['possession_id' => $possession->id, 'policy_number' => 'SAB-0001']);
        $earlier = PossessionInsuranceRecord::factory()->create(['possession_id' => $possession->id, 'status' => PossessionInsuranceStatusEnum::Expired, 'policy_number' => 'UTI-1999']);

        new UpdatePossessionInsuranceRecord(
            user: $user,
            record: $record,
            insurer: 'Sabre Insurance',
            status: PossessionInsuranceStatusEnum::Pending,
            insuredValue: '750.5',
            currency: CurrencyEnum::Euro,
            policyNumber: 'SAB-2026-0042',
        )->execute();

        $this->assertDatabaseHas('possession_insurance_records', [
            'id' => $record->id,
            'status' => 'pending',
            'policy_number' => 'SAB-2026-0042',
            'insured_value' => '750.50',
            'currency' => 'eur',
            'excess' => null,
        ]);
        $this->assertDatabaseHas('possession_insurance_records', ['id' => $earlier->id, 'policy_number' => 'UTI-1999']);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionInsuranceRecordUpdate
                && $job->parameters === ['name' => 'Watercolour of the office'],
        );
    }

    #[Test]
    public function it_refuses_a_record_of_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new UpdatePossessionInsuranceRecord(
            user: User::factory()->create(),
            record: PossessionInsuranceRecord::factory()->create(),
            insurer: 'Sabre Insurance',
            status: PossessionInsuranceStatusEnum::Active,
            insuredValue: '500',
            currency: CurrencyEnum::UsDollar,
        )->execute();
    }
}
