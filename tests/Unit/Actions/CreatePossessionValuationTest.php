<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreatePossessionValuation;
use App\Enums\CurrencyEnum;
use App\Enums\PossessionValuationConfidenceEnum;
use App\Enums\PossessionValuationTypeEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Possession;
use App\Models\PossessionValuation;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreatePossessionValuationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_records_a_valuation(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $possession = Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Dundie award']);

        $valuation = new CreatePossessionValuation(
            user: $user,
            possession: $possession,
            type: PossessionValuationTypeEnum::Appraisal,
            valuedOn: CarbonImmutable::parse('2026-03-14'),
            amount: ' 650 ',
            currency: CurrencyEnum::UsDollar,
            confidence: PossessionValuationConfidenceEnum::Certain,
            valuer: ' <b>Scranton Jewelers</b> ',
            method: 'Inspection and comparable sales',
            referenceNumber: 'APP-2026-0212',
            sourceUrl: 'https://scrantonjewelers.example.com/appraisals/0212',
            note: 'Michael insists the gold paint adds to its worth.',
        )->execute();

        $this->assertInstanceOf(PossessionValuation::class, $valuation);
        $this->assertDatabaseHas('possession_valuations', [
            'id' => $valuation->id,
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'possession_id' => $possession->id,
            'type' => 'appraisal',
            'confidence' => 'certain',
            'amount' => '650.00',
            'currency' => 'usd',
            'valuer' => 'Scranton Jewelers',
            'method' => 'Inspection and comparable sales',
            'reference_number' => 'APP-2026-0212',
            'source_url' => 'https://scrantonjewelers.example.com/appraisals/0212',
        ]);
        $this->assertEquals('2026-03-14', $valuation->valued_on->toDateString());

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionValuationCreation
                && $job->parameters === ['name' => 'Dundie award'],
        );
    }

    #[Test]
    public function its_confidence_is_unknown_unless_chosen(): void
    {
        $user = User::factory()->create();
        $possession = Possession::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);

        $valuation = new CreatePossessionValuation(
            user: $user,
            possession: $possession,
            type: PossessionValuationTypeEnum::OwnEstimate,
            valuedOn: CarbonImmutable::parse('2026-03-14'),
            amount: '20',
            currency: CurrencyEnum::UsDollar,
        )->execute();

        $this->assertEquals(PossessionValuationConfidenceEnum::Unknown, $valuation->confidence);
    }

    #[Test]
    public function it_refuses_a_negative_amount(): void
    {
        $user = User::factory()->create();
        $possession = Possession::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);

        try {
            new CreatePossessionValuation(
                user: $user,
                possession: $possession,
                type: PossessionValuationTypeEnum::OwnEstimate,
                valuedOn: CarbonImmutable::parse('2026-03-14'),
                amount: '-1',
                currency: CurrencyEnum::UsDollar,
            )->execute();

            $this->fail('A negative amount was recorded.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('amount', $exception->errors());
        }
    }

    #[Test]
    public function it_refuses_a_malformed_source_link(): void
    {
        $user = User::factory()->create();
        $possession = Possession::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);

        try {
            new CreatePossessionValuation(
                user: $user,
                possession: $possession,
                type: PossessionValuationTypeEnum::ComparableListing,
                valuedOn: CarbonImmutable::parse('2026-03-14'),
                amount: '650',
                currency: CurrencyEnum::UsDollar,
                sourceUrl: 'dunder mifflin',
            )->execute();

            $this->fail('A malformed link was recorded.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('source_url', $exception->errors());
        }
    }

    #[Test]
    public function it_refuses_a_possession_of_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new CreatePossessionValuation(
            user: User::factory()->create(),
            possession: Possession::factory()->create(),
            type: PossessionValuationTypeEnum::OwnEstimate,
            valuedOn: CarbonImmutable::parse('2026-03-14'),
            amount: '650',
            currency: CurrencyEnum::UsDollar,
        )->execute();
    }
}
