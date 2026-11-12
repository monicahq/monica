<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreatePossessionInsuranceRecord;
use App\Enums\CurrencyEnum;
use App\Enums\PossessionInsuranceStatusEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Possession;
use App\Models\PossessionInsuranceRecord;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreatePossessionInsuranceRecordTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_records_a_policy_period(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $possession = Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Dundie award']);

        $record = new CreatePossessionInsuranceRecord(
            user: $user,
            possession: $possession,
            insurer: ' <b>Sabre Insurance</b> ',
            status: PossessionInsuranceStatusEnum::Active,
            insuredValue: '500',
            currency: CurrencyEnum::UsDollar,
            policyNumber: 'SAB-2026-0042',
            coverage: 'Theft, fire and accidental damage',
            excess: '50',
            startsOn: CarbonImmutable::parse('2026-01-01'),
            endsOn: CarbonImmutable::parse('2027-01-01'),
            isListedIndividually: true,
            contactName: 'Gabe Lewis',
            contactEmail: 'gabe@sabre.example.com',
            contactPhone: '570 555 0142',
            note: 'Not covered when Michael lends it to Dwight.',
        )->execute();

        $this->assertInstanceOf(PossessionInsuranceRecord::class, $record);
        $this->assertDatabaseHas('possession_insurance_records', [
            'id' => $record->id,
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'possession_id' => $possession->id,
            'insurer' => 'Sabre Insurance',
            'status' => 'active',
            'policy_number' => 'SAB-2026-0042',
            'insured_value' => '500.00',
            'excess' => '50.00',
            'currency' => 'usd',
            'is_listed_individually' => true,
            'contact_name' => 'Gabe Lewis',
            'contact_email' => 'gabe@sabre.example.com',
            'contact_phone' => '570 555 0142',
        ]);
        $this->assertEquals('2027-01-01', $record->ends_on->toDateString());

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionInsuranceRecordCreation
                && $job->parameters === ['name' => 'Dundie award'],
        );
    }

    #[Test]
    public function it_refuses_a_negative_insured_value(): void
    {
        $user = User::factory()->create();
        $possession = Possession::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);

        $this->expectException(ValidationException::class);

        new CreatePossessionInsuranceRecord(
            user: $user,
            possession: $possession,
            insurer: 'Sabre Insurance',
            status: PossessionInsuranceStatusEnum::Active,
            insuredValue: '-1',
            currency: CurrencyEnum::UsDollar,
        )->execute();
    }

    #[Test]
    public function it_refuses_a_period_ending_before_it_starts(): void
    {
        $user = User::factory()->create();
        $possession = Possession::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);

        try {
            new CreatePossessionInsuranceRecord(
                user: $user,
                possession: $possession,
                insurer: 'Sabre Insurance',
                status: PossessionInsuranceStatusEnum::Active,
                insuredValue: '500',
                currency: CurrencyEnum::UsDollar,
                startsOn: CarbonImmutable::parse('2027-01-01'),
                endsOn: CarbonImmutable::parse('2026-01-01'),
            )->execute();

            $this->fail('A period ending before it starts was recorded.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('ends_on', $exception->errors());
        }
    }

    #[Test]
    public function it_refuses_an_insurer_left_blank_by_its_markup(): void
    {
        $user = User::factory()->create();
        $possession = Possession::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);

        try {
            new CreatePossessionInsuranceRecord(
                user: $user,
                possession: $possession,
                insurer: '<b></b>',
                status: PossessionInsuranceStatusEnum::Active,
                insuredValue: '500',
                currency: CurrencyEnum::UsDollar,
            )->execute();

            $this->fail('A record without an insurer was recorded.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('insurer', $exception->errors());
        }
    }

    #[Test]
    public function it_refuses_a_possession_of_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new CreatePossessionInsuranceRecord(
            user: User::factory()->create(),
            possession: Possession::factory()->create(),
            insurer: 'Sabre Insurance',
            status: PossessionInsuranceStatusEnum::Active,
            insuredValue: '500',
            currency: CurrencyEnum::UsDollar,
        )->execute();
    }
}
