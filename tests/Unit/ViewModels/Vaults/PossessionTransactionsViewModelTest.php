<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\CurrencyEnum;
use App\Enums\NumberFormatEnum;
use App\Enums\PossessionTransactionTypeEnum;
use App\Models\Possession;
use App\Models\PossessionTransaction;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\PossessionTransactionsViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionTransactionsViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_summary_reads_the_acquisition_and_the_costs_since(): void
    {
        [$user, $possession] = $this->scranton();
        $this->record($possession, PossessionTransactionTypeEnum::Purchase, '2005-03-14', ['amount' => '40.00', 'tax' => '3.20', 'shipping' => '6.00'], counterparty: 'Dundies Inc.');
        $this->record($possession, PossessionTransactionTypeEnum::Fee, '2006-01-03', ['fees' => '85.00']);
        $this->record($possession, PossessionTransactionTypeEnum::Shipping, '2007-05-01', ['shipping' => '12.50']);
        $this->record($possession, PossessionTransactionTypeEnum::Refund, '2007-06-01', ['amount' => '2.50']);
        $this->record($possession, PossessionTransactionTypeEnum::Tax, '2008-02-02', ['tax' => '9.00'], CurrencyEnum::Euro);
        $this->record($possession, PossessionTransactionTypeEnum::Fee, '2001-01-01', ['fees' => '100.00']);

        $summary = new PossessionTransactionsViewModel(user: $user, possession: $possession)->summary();

        $this->assertStringContainsString('Dundies Inc.', $summary['how']);
        $this->assertSame('$49.20', $summary['price']);
        $this->assertSame('$95', $summary['costs']);
    }

    #[Test]
    public function it_lists_the_newest_first_with_the_undated_last_and_counts_each_filter(): void
    {
        [$user, $possession] = $this->scranton();
        $this->record($possession, PossessionTransactionTypeEnum::Inheritance, null, []);
        $this->record($possession, PossessionTransactionTypeEnum::Shipping, '2019-11-05', ['shipping' => '42.00']);
        $this->record($possession, PossessionTransactionTypeEnum::Sale, '2024-06-12', ['amount' => '900.00']);

        $viewModel = new PossessionTransactionsViewModel(user: $user, possession: $possession);
        $transactions = $viewModel->transactions();

        $this->assertTrue($transactions[2]['isAcquisition']);
        $this->assertSame(['ownership' => 2, 'all' => 3], $viewModel->counts());
    }

    #[Test]
    public function a_new_transaction_starts_in_the_currency_last_used(): void
    {
        [$user, $possession] = $this->scranton();
        $possession->update(['declared_value' => '50.00', 'declared_value_currency' => 'gbp']);

        $this->assertSame(CurrencyEnum::PoundSterling, new PossessionTransactionsViewModel(user: $user, possession: $possession)->preselectedCurrency());

        $this->record($possession, PossessionTransactionTypeEnum::Purchase, '2005-03-14', ['amount' => '40.00'], CurrencyEnum::SwissFranc);

        $this->assertSame(CurrencyEnum::SwissFranc, new PossessionTransactionsViewModel(user: $user, possession: $possession->fresh())->preselectedCurrency());
    }

    #[Test]
    public function editing_fills_the_form_and_leaves_the_transaction_out_of_the_list(): void
    {
        [$user, $possession] = $this->scranton();
        $this->record($possession, PossessionTransactionTypeEnum::Purchase, '2005-03-14', ['amount' => '40.00'], counterparty: 'Dundies Inc.');
        $this->record($possession, PossessionTransactionTypeEnum::Fee, '2006-01-03', ['fees' => '85.00']);
        $purchase = $possession->transactions()->where('type', 'purchase')->firstOrFail();

        $viewModel = new PossessionTransactionsViewModel(user: $user, possession: $possession, editing: $purchase);

        $this->assertTrue($viewModel->form()['isEditing']);
        $this->assertSame('Dundies Inc.', $viewModel->form()['values']['counterparty']);
        $this->assertSame('40.00', $viewModel->form()['values']['amount']);
        $this->assertCount(1, $viewModel->transactions());
    }

    /** @param  array<string, string>  $figures */
    private function record(Possession $possession, PossessionTransactionTypeEnum $type, ?string $date, array $figures, CurrencyEnum $currency = CurrencyEnum::UsDollar, ?string $counterparty = null): void
    {
        PossessionTransaction::factory()->create([
            'possession_id' => $possession->id,
            'type' => $type,
            'occurred_on' => $date,
            'counterparty' => $counterparty,
            'amount' => null,
            ...$figures,
            'currency' => $figures === [] ? null : $currency,
        ]);
    }

    /** @return array{0: User, 1: Possession} */
    private function scranton(): array
    {
        app()->setLocale('en');
        $user = User::factory()->create(['number_format' => NumberFormatEnum::CommaAndDot]);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return [$user, Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Dundie award'])];
    }
}
