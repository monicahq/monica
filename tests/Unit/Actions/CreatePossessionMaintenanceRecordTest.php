<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreatePossessionMaintenanceRecord;
use App\Enums\CurrencyEnum;
use App\Enums\PossessionMaintenanceTypeEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Account;
use App\Models\Possession;
use App\Models\PossessionCondition;
use App\Models\PossessionMaintenanceRecord;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreatePossessionMaintenanceRecordTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_records_a_job(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $possession = Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Dundie award']);
        $worn = PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Worn']);
        $likeNew = PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Like new']);

        $record = new CreatePossessionMaintenanceRecord(
            user: $user,
            possession: $possession,
            type: PossessionMaintenanceTypeEnum::Restoration,
            title: ' <b>Re-plating</b> ',
            performedOn: CarbonImmutable::parse('2026-03-12'),
            performedBy: 'Scranton Trophy and Engraving',
            cost: '40',
            currency: CurrencyEnum::UsDollar,
            conditionBefore: $worn,
            conditionAfter: $likeNew,
            nextDueOn: CarbonImmutable::parse('2027-03-12'),
            description: 'The gold had worn off the base.',
        )->execute();

        $this->assertInstanceOf(PossessionMaintenanceRecord::class, $record);
        $this->assertDatabaseHas('possession_maintenance_records', [
            'id' => $record->id,
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'possession_id' => $possession->id,
            'type' => 'restoration',
            'title' => 'Re-plating',
            'performed_by' => 'Scranton Trophy and Engraving',
            'cost' => '40.00',
            'currency' => 'usd',
            'condition_before_id' => $worn->id,
            'condition_after_id' => $likeNew->id,
            'description' => 'The gold had worn off the base.',
        ]);
        $this->assertEquals('2027-03-12', $record->next_due_on->toDateString());

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionMaintenanceRecordCreation
                && $job->parameters === ['name' => 'Dundie award'],
        );
    }

    #[Test]
    public function it_refuses_a_negative_cost(): void
    {
        $user = User::factory()->create();
        $possession = Possession::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);

        $this->expectException(ValidationException::class);

        new CreatePossessionMaintenanceRecord(
            user: $user,
            possession: $possession,
            type: PossessionMaintenanceTypeEnum::Repair,
            title: 'New gasket',
            performedOn: CarbonImmutable::parse('2026-03-12'),
            cost: '-1',
            currency: CurrencyEnum::UsDollar,
        )->execute();
    }

    #[Test]
    public function it_refuses_a_cost_without_a_currency(): void
    {
        $user = User::factory()->create();
        $possession = Possession::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);

        try {
            new CreatePossessionMaintenanceRecord(
                user: $user,
                possession: $possession,
                type: PossessionMaintenanceTypeEnum::Repair,
                title: 'New gasket',
                performedOn: CarbonImmutable::parse('2026-03-12'),
                cost: '40',
            )->execute();

            $this->fail('A cost without a currency was recorded.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('currency', $exception->errors());
        }
    }

    #[Test]
    public function it_refuses_a_job_performed_in_the_future(): void
    {
        $user = User::factory()->create();
        $possession = Possession::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);

        try {
            new CreatePossessionMaintenanceRecord(
                user: $user,
                possession: $possession,
                type: PossessionMaintenanceTypeEnum::Inspection,
                title: 'Annual check',
                performedOn: CarbonImmutable::now($user->timezone)->addDays(2),
            )->execute();

            $this->fail('A job performed in the future was recorded.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('performed_on', $exception->errors());
        }
    }

    #[Test]
    public function it_refuses_a_next_due_date_before_the_job(): void
    {
        $user = User::factory()->create();
        $possession = Possession::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);

        try {
            new CreatePossessionMaintenanceRecord(
                user: $user,
                possession: $possession,
                type: PossessionMaintenanceTypeEnum::Inspection,
                title: 'Annual check',
                performedOn: CarbonImmutable::parse('2026-03-12'),
                nextDueOn: CarbonImmutable::parse('2026-01-01'),
            )->execute();

            $this->fail('A next due date before the job was recorded.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('next_due_on', $exception->errors());
        }
    }

    #[Test]
    public function it_refuses_a_possession_of_another_account(): void
    {
        $user = User::factory()->create();
        $possession = Possession::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new CreatePossessionMaintenanceRecord(
            user: $user,
            possession: $possession,
            type: PossessionMaintenanceTypeEnum::Repair,
            title: 'New gasket',
            performedOn: CarbonImmutable::parse('2026-03-12'),
        )->execute();
    }

    #[Test]
    public function it_refuses_a_condition_of_another_account(): void
    {
        $user = User::factory()->create();
        $possession = Possession::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);
        $condition = PossessionCondition::factory()->create(['account_id' => Account::factory()->create()->id]);

        $this->expectException(ModelNotFoundException::class);

        new CreatePossessionMaintenanceRecord(
            user: $user,
            possession: $possession,
            type: PossessionMaintenanceTypeEnum::Repair,
            title: 'New gasket',
            performedOn: CarbonImmutable::parse('2026-03-12'),
            conditionAfter: $condition,
        )->execute();
    }
}
