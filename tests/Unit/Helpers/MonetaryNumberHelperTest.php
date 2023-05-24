<?php

namespace Tests\Unit\Helpers;

use App\Helpers\MonetaryNumberHelper;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class MonetaryNumberHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_formatted_value_default(): void
    {
        $number = 1234567;
        $user = User::factory()->create([
            'number_format' => User::NUMBER_FORMAT_TYPE_LOCALE_DEFAULT,
        ]);

        App::setLocale('en');
        $this->assertEquals('12,345.67', MonetaryNumberHelper::formatValue($user, $number, 'EUR'));
        $this->assertEquals('12,345.67', MonetaryNumberHelper::formatValue($user, $number, 'USD'));
        $this->assertEquals('12,345.67', MonetaryNumberHelper::formatValue($user, $number));

        App::setLocale('fr');
        $this->assertEquals('12 345,67', MonetaryNumberHelper::formatValue($user, $number, 'EUR'));
        $this->assertEquals('12 345,67', MonetaryNumberHelper::formatValue($user, $number, 'USD'));
        $this->assertEquals('12 345,67', MonetaryNumberHelper::formatValue($user, $number));
    }

    /** @test */
    public function it_gets_the_formatted_value_comma_dot(): void
    {
        $number = 1234567;
        $user = User::factory()->create([
            'number_format' => User::NUMBER_FORMAT_TYPE_COMMA_THOUSANDS_DOT_DECIMAL,
        ]);

        $this->assertEquals('12,345.67', MonetaryNumberHelper::formatValue($user, $number, 'EUR'));
        $this->assertEquals('12,345.67', MonetaryNumberHelper::formatValue($user, $number, 'USD'));
        $this->assertEquals('12,345.67', MonetaryNumberHelper::formatValue($user, $number));
    }

    /** @test */
    public function it_gets_the_formatted_with_currency_comma_dot(): void
    {
        $number = 1234567;
        $user = User::factory()->create([
            'number_format' => User::NUMBER_FORMAT_TYPE_COMMA_THOUSANDS_DOT_DECIMAL,
        ]);

        App::setLocale('en');
        $this->assertEquals('€12,345.67', MonetaryNumberHelper::format($user, $number, 'EUR'));
        $this->assertEquals('$12,345.67', MonetaryNumberHelper::format($user, $number, 'USD'));
        $this->assertEquals('12,345.67', MonetaryNumberHelper::format($user, $number));

        App::setLocale('fr');
        $this->assertEquals('12,345.67 €', MonetaryNumberHelper::format($user, $number, 'EUR'));
        $this->assertEquals('12,345.67 $US', MonetaryNumberHelper::format($user, $number, 'USD'));
        $this->assertEquals('12,345.67', MonetaryNumberHelper::format($user, $number));
    }

    /** @test */
    public function it_gets_the_formatted_value_space_comma(): void
    {
        $number = 1234567;
        $user = User::factory()->create([
            'number_format' => User::NUMBER_FORMAT_TYPE_SPACE_THOUSANDS_COMMA_DECIMAL,
        ]);

        App::setLocale('en');
        $this->assertEquals('12 345,67', MonetaryNumberHelper::formatValue($user, $number, 'EUR'));
        $this->assertEquals('12 345,67', MonetaryNumberHelper::formatValue($user, $number, 'USD'));
        $this->assertEquals('12 345,67', MonetaryNumberHelper::formatValue($user, $number));

        App::setLocale('fr');
        $this->assertEquals('12 345,67', MonetaryNumberHelper::formatValue($user, $number, 'EUR'));
        $this->assertEquals('12 345,67', MonetaryNumberHelper::formatValue($user, $number, 'USD'));
        $this->assertEquals('12 345,67', MonetaryNumberHelper::formatValue($user, $number));
    }

    /** @test */
    public function it_gets_the_formatted_with_currency_space_comma(): void
    {
        $number = 1234567;
        $user = User::factory()->create([
            'number_format' => User::NUMBER_FORMAT_TYPE_SPACE_THOUSANDS_COMMA_DECIMAL,
        ]);

        App::setLocale('en');
        $this->assertEquals('€12 345,67', MonetaryNumberHelper::format($user, $number, 'EUR'));
        $this->assertEquals('$12 345,67', MonetaryNumberHelper::format($user, $number, 'USD'));
        $this->assertEquals('12 345,67', MonetaryNumberHelper::format($user, $number));

        App::setLocale('fr');
        $this->assertEquals('12 345,67 €', MonetaryNumberHelper::format($user, $number, 'EUR'));
        $this->assertEquals('12 345,67 $US', MonetaryNumberHelper::format($user, $number, 'USD'));
        $this->assertEquals('12 345,67', MonetaryNumberHelper::format($user, $number));
    }

    /** @test */
    public function it_gets_the_formatted_value_dot_comma(): void
    {
        $number = 1234567;
        $user = User::factory()->create([
            'number_format' => User::NUMBER_FORMAT_TYPE_DOT_THOUSANDS_COMMA_DECIMAL,
        ]);

        App::setLocale('en');
        $this->assertEquals('12.345,67', MonetaryNumberHelper::formatValue($user, $number, 'EUR'));
        $this->assertEquals('12.345,67', MonetaryNumberHelper::formatValue($user, $number, 'USD'));
        $this->assertEquals('12.345,67', MonetaryNumberHelper::formatValue($user, $number));

        App::setLocale('fr');
        $this->assertEquals('12.345,67', MonetaryNumberHelper::formatValue($user, $number, 'EUR'));
        $this->assertEquals('12.345,67', MonetaryNumberHelper::formatValue($user, $number, 'USD'));
        $this->assertEquals('12.345,67', MonetaryNumberHelper::formatValue($user, $number));
    }

    /** @test */
    public function it_gets_the_formatted_with_currency_dot_comma(): void
    {
        $number = 1234567;
        $user = User::factory()->create([
            'number_format' => User::NUMBER_FORMAT_TYPE_DOT_THOUSANDS_COMMA_DECIMAL,
        ]);

        App::setLocale('en');
        $this->assertEquals('€12.345,67', MonetaryNumberHelper::format($user, $number, 'EUR'));
        $this->assertEquals('$12.345,67', MonetaryNumberHelper::format($user, $number, 'USD'));
        $this->assertEquals('12.345,67', MonetaryNumberHelper::format($user, $number));

        App::setLocale('fr');
        $this->assertEquals('12.345,67 €', MonetaryNumberHelper::format($user, $number, 'EUR'));
        $this->assertEquals('12.345,67 $US', MonetaryNumberHelper::format($user, $number, 'USD'));
        $this->assertEquals('12.345,67', MonetaryNumberHelper::format($user, $number));
    }

    /** @test */
    public function it_gets_the_formatted_value_exchange(): void
    {
        $number = 1234567;
        $user = User::factory()->create([
            'number_format' => User::NUMBER_FORMAT_TYPE_NO_SPACE_DOT_DECIMAL,
        ]);

        $this->assertEquals('12345.67', MonetaryNumberHelper::formatValue($user, $number, 'EUR'));
        $this->assertEquals('12345.67', MonetaryNumberHelper::formatValue($user, $number, 'USD'));
        $this->assertEquals('12345.67', MonetaryNumberHelper::formatValue($user, $number));
    }

    /** @test */
    public function it_returns_the_amount_with_the_currency_symbol_default()
    {
        $user = User::factory()->create();
        $currency = 'EUR';

        $this->assertEquals('500', MonetaryNumberHelper::formatValue($user, 50000, $currency));
        $this->assertEquals('5,038.29', MonetaryNumberHelper::formatValue($user, 503829, $currency));
        $this->assertEquals('€500.00', MonetaryNumberHelper::format($user, 50000, $currency));
        $this->assertEquals('€5,038.29', MonetaryNumberHelper::format($user, 503829, $currency));
        $this->assertEquals(500, MonetaryNumberHelper::inputValue(50000, $currency));
        $this->assertEquals(5038.29, MonetaryNumberHelper::inputValue(503829, $currency));
    }

    /** @test */
    public function it_returns_the_amount_with_the_currency_symbol_in_the_right_locale()
    {
        $user = User::factory()->create();
        App::setLocale('fr');

        $currency = 'EUR';

        $this->assertEquals('500', MonetaryNumberHelper::formatValue($user, 50000, $currency));
        $this->assertEquals('5 038,29', MonetaryNumberHelper::formatValue($user, 503829, $currency));
        $this->assertEquals('500,00 €', MonetaryNumberHelper::format($user, 50000, $currency));
        $this->assertEquals('5 038,29 €', MonetaryNumberHelper::format($user, 503829, $currency));
        $this->assertEquals(500, MonetaryNumberHelper::inputValue(50000, $currency));
        $this->assertEquals(5038.29, MonetaryNumberHelper::inputValue(503829, $currency));
    }

    /** @test */
    public function it_returns_the_amount_with_the_currency_symbol_with_the_right_punctuation()
    {
        $user = User::factory()->create();
        $currency = 'JPY'; // minorUnit value is zero "0"

        $this->assertEquals('500', MonetaryNumberHelper::formatValue($user, 500, $currency));
        $this->assertEquals('5,038', MonetaryNumberHelper::formatValue($user, 5038, $currency));
        $this->assertEquals('¥500', MonetaryNumberHelper::format($user, 500, $currency));
        $this->assertEquals('¥5,038', MonetaryNumberHelper::format($user, 5038, $currency));
        $this->assertEquals(500, MonetaryNumberHelper::inputValue(500, $currency));
        $this->assertEquals(5038, MonetaryNumberHelper::inputValue(5038, $currency));
    }

    /** @test */
    public function it_formats_the_currency_with_the_right_locale()
    {
        $user = User::factory()->create();
        $currency = 'GBP';

        $this->assertEquals('75', MonetaryNumberHelper::formatValue($user, 7500, $currency));
        $this->assertEquals('2,734.12', MonetaryNumberHelper::formatValue($user, 273412, $currency));
        $this->assertEquals('£75.00', MonetaryNumberHelper::format($user, 7500, $currency));
        $this->assertEquals('£2,734.12', MonetaryNumberHelper::format($user, 273412, $currency));
        $this->assertEquals(75, MonetaryNumberHelper::inputValue(7500, $currency));
        $this->assertEquals(2734.12, MonetaryNumberHelper::inputValue(273412, $currency));
    }

    /** @test */
    public function it_returns_the_amount_without_the_currency_symbol_if_not_provided()
    {
        $user = User::factory()->create();
        $this->assertEquals('75', MonetaryNumberHelper::formatValue($user, 7500));
        $this->assertEquals('2,734.12', MonetaryNumberHelper::formatValue($user, 273412));
        $this->assertEquals('5', MonetaryNumberHelper::format($user, 500));
        $this->assertEquals('50', MonetaryNumberHelper::format($user, 5000));
        $this->assertEquals(75, MonetaryNumberHelper::inputValue(7500));
        $this->assertEquals(2734.12, MonetaryNumberHelper::inputValue(273412));
    }

    /** @test */
    public function it_covers_brazilian_currency()
    {
        $user = User::factory()->create();
        $currency = 'BRL';

        $this->assertEquals('R$12,345.67', MonetaryNumberHelper::format($user, 1234567, $currency));
        $this->assertEquals('12,345.67', MonetaryNumberHelper::formatValue($user, 1234567, $currency));
        $this->assertEquals(12345.67, MonetaryNumberHelper::inputValue(1234567, $currency));
    }

    /** @test */
    public function it_parse_an_input_value()
    {
        $currency = 'EUR';

        $this->assertEquals(50000, MonetaryNumberHelper::parseInput('500.00', $currency));
        $this->assertEquals(503829, MonetaryNumberHelper::parseInput('5038.29', $currency));
    }
}
