/**
  *  This file is part of Monica.
  *
  *  Monica is free software: you can redistribute it and/or modify
  *  it under the terms of the GNU Affero General Public License as published by
  *  the Free Software Foundation, either version 3 of the License, or
  *  (at your option) any later version.
  *
  *  Monica is distributed in the hope that it will be useful,
  *  but WITHOUT ANY WARRANTY; without even the implied warranty of
  *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  *  GNU Affero General Public License for more details.
  *
  *  You should have received a copy of the GNU Affero General Public License
  *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
  **/


namespace Tests\Unit\Helpers;

use Tests\TestCase;
use App\Models\User\User;
use App\Helpers\MoneyHelper;
use App\Models\Settings\Currency;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class MoneyHelperTest extends TestCase
{
    use DatabaseTransactions;

    public function testFormatReturnsAmountWithCurrencySymbol()
    {
        $currency = new Currency();
        $currency->iso = 'EUR';

        $this->assertEquals('€500.00', MoneyHelper::format(500, $currency));
        $this->assertEquals('€5,038.29', MoneyHelper::format(5038.29, $currency));
    }

    public function testFormatUsesCurrencySettingIfDefined()
    {
        $currency = Currency::where('iso', 'GBP')->first();
        $user = factory(User::class)->create([
            'currency_id' => $currency->id,
        ]);
        $this->actingAs($user);

        $this->assertEquals('£75.00', MoneyHelper::format(75));
        $this->assertEquals('£2,734.12', MoneyHelper::format(2734.12));
    }

    public function testFormatReturnsAmountWithoutSymbolIfCurrencyIsUndefined()
    {
        $this->assertEquals('500', MoneyHelper::format(500));
        $this->assertEquals('5,000', MoneyHelper::format(5000));
    }

    public function testFormatReturnsZeroIfAmountIsNull()
    {
        $this->assertEquals('0', MoneyHelper::format(null));
    }

    public function test_it_covers_brazilian_currency()
    {
        $currency = Currency::where('iso', 'BRL')->first();

        $user = factory(User::class)->create([
            'currency_id' => $currency->id,
        ]);
        $this->actingAs($user);

        $this->assertEquals('R$12,345.67', MoneyHelper::format(12345.67));
    }
}
