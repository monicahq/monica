<?php

namespace Tests\Unit\Helpers;

use Tests\TestCase;
use App\Models\User\User;
use App\Helpers\MoneyHelper;
use App\Models\Settings\Currency;
use Illuminate\Support\Facades\App;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class MoneyHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_returns_the_amount_with_the_currency_symbol()
    {
        $currency = new Currency();
        $currency->iso = 'EUR';

        $this->assertEquals('€500.00', MoneyHelper::format(50000, $currency));
        $this->assertEquals('€5,038.29', MoneyHelper::format(503829, $currency));
        $this->assertEquals('500.00', MoneyHelper::getValue(50000, $currency));
        $this->assertEquals('5038.29', MoneyHelper::getValue(503829, $currency));
        $this->assertEquals(500, MoneyHelper::exchangeValue(50000, $currency));
        $this->assertEquals(5038.29, MoneyHelper::exchangeValue(503829, $currency));
    }

    /** @test */
    public function it_returns_the_amount_with_the_currency_symbol_in_the_right_locale()
    {
        App::setLocale('fr');

        $currency = new Currency();
        $currency->iso = 'EUR';

        $this->assertEquals('500,00 €', MoneyHelper::format(50000, $currency));
        $this->assertEquals('5 038,29 €', MoneyHelper::format(503829, $currency));
        $this->assertEquals('500,00', MoneyHelper::getValue(50000, $currency));
        $this->assertEquals('5038,29', MoneyHelper::getValue(503829, $currency));
        $this->assertEquals(500, MoneyHelper::exchangeValue(50000, $currency));
        $this->assertEquals(5038.29, MoneyHelper::exchangeValue(503829, $currency));
    }

    /** @test */
    public function it_returns_the_amount_with_the_currency_symbol_with_the_right_punctuation()
    {
        $currency = new Currency();
        $currency->iso = 'JPY'; // minorUnit value is zero "0"

        $this->assertEquals('¥500', MoneyHelper::format(500, $currency));
        $this->assertEquals('¥5,038', MoneyHelper::format(5038, $currency));
        $this->assertEquals('500', MoneyHelper::getValue(500, $currency));
        $this->assertEquals('5038', MoneyHelper::getValue(5038, $currency));
        $this->assertEquals(500, MoneyHelper::exchangeValue(500, $currency));
        $this->assertEquals(5038, MoneyHelper::exchangeValue(5038, $currency));
    }

    /** @test */
    public function it_formats_the_currency_with_the_right_locale()
    {
        $currency = Currency::where('iso', 'GBP')->first();
        $user = factory(User::class)->create([
            'currency_id' => $currency->id,
        ]);
        $this->actingAs($user);

        $this->assertEquals('£75.00', MoneyHelper::format(7500, $currency));
        $this->assertEquals('£2,734.12', MoneyHelper::format(273412, $currency));
        $this->assertEquals('75.00', MoneyHelper::getValue(7500, $currency));
        $this->assertEquals('2734.12', MoneyHelper::getValue(273412, $currency));
        $this->assertEquals(75, MoneyHelper::exchangeValue(7500, $currency));
        $this->assertEquals(2734.12, MoneyHelper::exchangeValue(273412, $currency));
    }

    /** @test */
    public function it_returns_the_amount_without_the_currency_symbol_if_not_provided()
    {
        $this->assertEquals('500', MoneyHelper::format(500));
        $this->assertEquals('5,000', MoneyHelper::format(5000));
    }

    /** @test */
    public function it_returns_zero_if_amount_is_null()
    {
        $this->assertEquals('0', MoneyHelper::format(null));
    }

    /** @test */
    public function it_covers_brazilian_currency()
    {
        $currency = Currency::where('iso', 'BRL')->first();

        $user = factory(User::class)->create([
            'currency_id' => $currency->id,
        ]);
        $this->actingAs($user);

        $this->assertEquals('R$12,345.67', MoneyHelper::format(1234567, $currency));
        $this->assertEquals('12345.67', MoneyHelper::getValue(1234567, $currency));
        $this->assertEquals(12345.67, MoneyHelper::exchangeValue(1234567, $currency));
    }

    /** @test */
    public function it_parse_an_input_value()
    {
        $currency = new Currency();
        $currency->iso = 'EUR';

        $this->assertEquals(50000, MoneyHelper::parseInput('500.00', $currency));
        $this->assertEquals(503829, MoneyHelper::parseInput('5038.29', $currency));
    }
}
