<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdatePossessionTransaction;
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

class UpdatePossessionTransactionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_changes_what_the_transaction_says(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possession = Possession::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id, 'name' => 'Dundie award']);
        $transaction = PossessionTransaction::factory()->create(['possession_id' => $possession->id, 'amount' => '40.00', 'shipping' => '60.00']);

        new UpdatePossessionTransaction(
            user: $user,
            transaction: $transaction,
            type: PossessionTransactionTypeEnum::Purchase,
            occurredOn: CarbonImmutable::parse('2005-03-14'),
            counterparty: 'Dundies Inc.',
            amount: '40',
            shipping: '6',
            currency: CurrencyEnum::UsDollar,
        )->execute();

        $this->assertDatabaseHas('possession_transactions', [
            'id' => $transaction->id,
            'counterparty' => 'Dundies Inc.',
            'amount' => '40.00',
            'shipping' => '6.00',
        ]);
        $this->assertEquals('46.00', $transaction->refresh()->total());

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionTransactionUpdate
                && $job->parameters === ['name' => 'Dundie award'],
        );
    }

    #[Test]
    public function it_refuses_a_figure_without_its_currency(): void
    {
        $user = User::factory()->create();
        $possession = Possession::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);
        $transaction = PossessionTransaction::factory()->create(['possession_id' => $possession->id]);

        $this->expectException(ValidationException::class);

        new UpdatePossessionTransaction(
            user: $user,
            transaction: $transaction,
            type: PossessionTransactionTypeEnum::Fee,
            fees: '85',
        )->execute();
    }

    #[Test]
    public function it_refuses_a_transaction_of_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new UpdatePossessionTransaction(
            user: User::factory()->create(),
            transaction: PossessionTransaction::factory()->create(),
            type: PossessionTransactionTypeEnum::Purchase,
        )->execute();
    }
}
