<?php

namespace Tests\Unit\Services\Contact\Conversation;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\User\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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

        $this->expectException(\Exception::class);

        $populateLifeEventService = new PopulateLifeEvents;
        $populateLifeEventService->execute($request);
    }

    public function test_it_populate_life_event_tables()
    {
        $account = factory(Account::class)->create([]);
        $user = factory(User::class)->create([
            'account_id' => $account->id,
        ]);

        $request = [
            'account_id' => $account->id,
            'migrate_existing_data' => 1,
        ];

        $populateLifeEventService = new PopulateLifeEventsTable;
        $populateLifeEventService->execute($request);

        $this->assertEquals(
            5,
            DB::table('life_event_categories')->get()->count()
        );

        $this->assertEquals(
            45,
            DB::table('life_event_types')->get()->count()
        );
    }

    public function test_it_populate_partial_life_event_tables()
    {
        $account = factory(Account::class)->create([]);
        $user = factory(User::class)->create([
            'account_id' => $account->id,
        ]);

        DB::table('default_life_event_categories')
            ->where('translation_key', 'settings.personalization_life_event_category_work_education')
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
            DB::table('life_event_categories')->get()->count()
        );

        $this->assertEquals(
            38,
            DB::table('life_event_types')->get()->count()
        );
    }
}
