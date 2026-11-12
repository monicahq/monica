<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreatePossessionTransaction;
use App\Enums\CurrencyEnum;
use App\Enums\PossessionTransactionTypeEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Possession;
use App\Models\PossessionTransaction;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreatePossessionTransactionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_records_a_purchase_with_its_figures(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $possession = Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Dundie award']);

        $transaction = new CreatePossessionTransaction(
            user: $user,
            possession: $possession,
            type: PossessionTransactionTypeEnum::Purchase,
            occurredOn: CarbonImmutable::parse('2005-03-14'),
            counterparty: ' <b>Dundies Inc.</b> ',
            amount: '40',
            tax: '3.2',
            shipping: '6',
            currency: CurrencyEnum::UsDollar,
            reference: 'Invoice 4021',
            sourceUrl: 'https://dundies.example.com/orders/4021',
            note: 'For Kevin, the hottest in the office award.',
        )->execute();

        $this->assertInstanceOf(PossessionTransaction::class, $transaction);
        $this->assertDatabaseHas('possession_transactions', [
            'id' => $transaction->id,
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'possession_id' => $possession->id,
            'type' => 'purchase',
            'counterparty' => 'Dundies Inc.',
            'amount' => '40.00',
            'tax' => '3.20',
            'fees' => null,
            'shipping' => '6.00',
            'entered_total' => null,
            'currency' => 'usd',
            'reference' => 'Invoice 4021',
            'source_url' => 'https://dundies.example.com/orders/4021',
        ]);
        $this->assertEquals('2005-03-14', $transaction->occurred_on->toDateString());

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionTransactionCreation
                && $job->parameters === ['name' => 'Dundie award'],
        );
    }

    #[Test]
    public function it_records_an_inheritance_without_a_date_or_a_figure(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possession = Possession::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);

        $transaction = new CreatePossessionTransaction(
            user: $user,
            possession: $possession,
            type: PossessionTransactionTypeEnum::Inheritance,
            counterparty: 'Estate of Mose Schrute',
        )->execute();

        $this->assertNull($transaction->occurred_on);
        $this->assertNull($transaction->currency);
        $this->assertNull($transaction->total());
    }

    #[Test]
    public function it_refuses_an_amount_below_zero(): void
    {
        $user = User::factory()->create();
        $possession = Possession::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);

        $this->expectException(ValidationException::class);

        new CreatePossessionTransaction(
            user: $user,
            possession: $possession,
            type: PossessionTransactionTypeEnum::Refund,
            amount: '-5',
            currency: CurrencyEnum::UsDollar,
        )->execute();
    }

    #[Test]
    public function it_refuses_an_amount_without_its_currency(): void
    {
        $user = User::factory()->create();
        $possession = Possession::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);

        try {
            new CreatePossessionTransaction(
                user: $user,
                possession: $possession,
                type: PossessionTransactionTypeEnum::Fee,
                fees: '85',
            )->execute();

            $this->fail('A fee without a currency was recorded.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('currency', $e->errors());
        }
    }

    #[Test]
    public function it_refuses_a_possession_of_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new CreatePossessionTransaction(
            user: User::factory()->create(),
            possession: Possession::factory()->create(),
            type: PossessionTransactionTypeEnum::Purchase,
        )->execute();
    }
}
