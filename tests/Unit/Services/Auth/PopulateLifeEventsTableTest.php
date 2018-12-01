<?php
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


namespace Tests\Unit\Services\Auth;

use Tests\TestCase;
use App\Models\User\User;
use App\Models\Account\Account;
use Illuminate\Support\Facades\DB;
use App\Exceptions\MissingParameterException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\Auth\Population\PopulateLifeEventsTable;

class PopulateLifeEventsTableTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $request = [
            'account_id' => 1,
        ];

        $this->expectException(\Exception::class);

        $populateLifeEventService = new PopulateLifeEventsTable;
        $populateLifeEventService->execute($request);

        $request = [
            'migrate_existing_data' => false,
        ];

        $this->expectException(MissingParameterException::class);

        $populateLifeEventService = new PopulateLifeEventsTable;
        $populateLifeEventService->execute($request);
    }

    public function test_it_populate_life_event_tables()
    {
        $account = factory(Account::class)->create([]);
        $user = factory(User::class)->create([
            'account_id' => $account->id,
        ]);

        DB::table('default_life_event_categories')
            ->where('translation_key', 'work_education')
            ->update(['migrated' => 0]);

        $request = [
            'account_id' => $account->id,
            'migrate_existing_data' => 1,
        ];

        $populateLifeEventService = new PopulateLifeEventsTable;
        $populateLifeEventService->execute($request);

        $this->assertEquals(
            5,
            DB::table('life_event_categories')->where('account_id', $account->id)->get()->count()
        );

        $this->assertEquals(
            43,
            DB::table('life_event_types')->where('account_id', $account->id)->get()->count()
        );

        // make sure tables have been set to migrated = 1
        $this->assertDatabaseMissing('default_life_event_categories', [
            'migrated' => 0,
        ]);

        $this->assertDatabaseMissing('default_life_event_types', [
            'migrated' => 0,
        ]);
    }

    public function test_it_refuses_to_populate_table_if_account_doesnt_have_locale()
    {
        $account = factory(Account::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'migrate_existing_data' => 0,
        ];

        $populateLifeEventService = new PopulateLifeEventsTable;
        $this->assertFalse($populateLifeEventService->execute($request));
    }

    public function test_it_only_populates_life_event_tables_partially()
    {
        $account = factory(Account::class)->create([]);
        $user = factory(User::class)->create([
            'account_id' => $account->id,
        ]);

        DB::table('default_life_event_categories')
            ->update(['migrated' => 0]);

        DB::table('default_life_event_categories')
            ->where('translation_key', 'work_education')
            ->update(['migrated' => 1]);

        // we will only migrate the ones that haven't been populated yet
        $request = [
            'account_id' => $account->id,
            'migrate_existing_data' => 0,
        ];

        $populateLifeEventService = new PopulateLifeEventsTable;
        $populateLifeEventService->execute($request);

        $this->assertEquals(
            4,
            DB::table('life_event_categories')->where('account_id', $account->id)->get()->count()
        );

        $this->assertEquals(
            36,
            DB::table('life_event_types')->where('account_id', $account->id)->get()->count()
        );
    }
}
