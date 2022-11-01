<?php

namespace Tests\Unit\Domains\Settings\ManageCurrencies\Web\ViewHelpers;

use App\Domains\Settings\ManageCurrencies\Web\ViewHelpers\CurrencyIndexViewHelper;
use App\Models\Account;
use App\Models\Currency;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CurrencyIndexViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $currency = Currency::factory()->create();
        $account = Account::factory()->create();
        $account->currencies()->attach($currency->id, ['active' => true]);
        $collection = CurrencyIndexViewHelper::data($account);
        $this->assertEquals(
            [
                0 => [
                    'id' => $currency->id,
                    'name' => 'CAD',
                    'selected' => null,
                ],
            ],
            $collection->toArray()
        );
    }

    /** @test */
    public function it_gets_the_data_needed_for_the_view_with_a_currency(): void
    {
        $currency = Currency::factory()->create();
        $otherCurrency = Currency::factory()->create();
        $account = Account::factory()->create();
        $account->currencies()->attach($currency->id, ['active' => true]);
        $account->currencies()->attach($otherCurrency->id, ['active' => true]);

        $collection = CurrencyIndexViewHelper::data($account, $otherCurrency->id);
        $this->assertEquals(
            [
                0 => [
                    'id' => $currency->id,
                    'name' => 'CAD',
                    'selected' => false,
                ],
                1 => [
                    'id' => $otherCurrency->id,
                    'name' => 'CAD',
                    'selected' => true,
                ],
            ],
            $collection->toArray()
        );
    }
}
