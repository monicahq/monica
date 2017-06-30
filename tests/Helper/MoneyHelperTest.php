<?php

namespace Tests\Helper;

use App\Currency;
use App\User;
use App\Helpers\MoneyHelper;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class MoneyHelperTest extends TestCase
{
    use DatabaseTransactions;

    public function testFormatReturnsAmountWithCurrencySymbol()
    {
        $currency = new Currency();
        $currency->symbol = "€";

        $this->assertEquals('€500', MoneyHelper::format(500, $currency));
    }

    public function testFormatUsesCurrencySettingIfDefined()
    {
        $currency = Currency::where('iso', 'GBP')->first();
        $user = factory(User::class)->create([
            'currency_id' => $currency->id
        ]);
        $this->actingAs($user);

        $this->assertEquals('£75', MoneyHelper::format(75));
    }

    public function testFormatReturnsAmountWithoutSymbolIfCurrencyIsUndefined()
    {
        $this->assertEquals('500', MoneyHelper::format(500));
    }

    public function testFormatReturnsZeroIfAmountIsNull()
    {
        $this->assertEquals('0', MoneyHelper::format(null));
    }
}
