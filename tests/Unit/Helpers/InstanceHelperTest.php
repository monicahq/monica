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
use App\Helpers\InstanceHelper;
use App\Models\Account\Account;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class InstanceHelperTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_gets_the_number_of_paid_subscribers()
    {
        $account = factory(Account::class)->create(['stripe_id' => 'id292839']);
        $account = factory(Account::class)->create();
        $account = factory(Account::class)->create(['stripe_id' => 'id2sdf92839']);

        $this->assertEquals(
            2,
            InstanceHelper::getNumberOfPaidSubscribers()
        );
    }

    public function test_it_fetches_the_monthly_plan_information()
    {
        config(['monica.paid_plan_monthly_friendly_name' => 'Monthly']);
        config(['monica.paid_plan_monthly_id' => 'monthly']);
        config(['monica.paid_plan_monthly_price' => 1000]);

        $this->assertEquals(
            'monthly',
            InstanceHelper::getPlanInformationFromConfig('monthly')['type']
        );

        $this->assertEquals(
            'Monthly',
            InstanceHelper::getPlanInformationFromConfig('monthly')['name']
        );

        $this->assertEquals(
            'monthly',
            InstanceHelper::getPlanInformationFromConfig('monthly')['id']
        );

        $this->assertEquals(
            1000,
            InstanceHelper::getPlanInformationFromConfig('monthly')['price']
        );

        $this->assertEquals(
            10,
            InstanceHelper::getPlanInformationFromConfig('monthly')['friendlyPrice']
        );
    }

    public function test_it_fetches_the_annually_plan_information()
    {
        config(['monica.paid_plan_annual_friendly_name' => 'Annual']);
        config(['monica.paid_plan_annual_id' => 'annual']);
        config(['monica.paid_plan_annual_price' => 1000]);

        $this->assertEquals(
            'annual',
            InstanceHelper::getPlanInformationFromConfig('annual')['type']
        );

        $this->assertEquals(
            'Annual',
            InstanceHelper::getPlanInformationFromConfig('annual')['name']
        );

        $this->assertEquals(
            'annual',
            InstanceHelper::getPlanInformationFromConfig('annual')['id']
        );

        $this->assertEquals(
            1000,
            InstanceHelper::getPlanInformationFromConfig('annual')['price']
        );

        $this->assertEquals(
            10,
            InstanceHelper::getPlanInformationFromConfig('annual')['friendlyPrice']
        );
    }

    public function test_it_returns_null_when_fetching_an_unknown_plan_information()
    {
        $account = new Account;

        $this->assertNull(
            InstanceHelper::getPlanInformationFromConfig('unknown_plan')
        );
    }

    public function test_it_gets_latest_changelog_entries()
    {
        $json = public_path('changelog.json');
        $changelogs = json_decode(file_get_contents($json), true)['entries'];
        $count = count($changelogs);

        $this->assertCount(
            $count,
            InstanceHelper::getChangelogEntries()
        );

        $this->assertCount(
            3,
            InstanceHelper::getChangelogEntries(3)
        );
    }
}
