<?php

namespace Tests\Unit\Services\Auth;

use Tests\TestCase;
use App\Models\User\User;
use App\Models\Account\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\Auth\Population\PopulateModulesTable;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PopulateModulesTableTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $request = [
            'account_id' => 1,
        ];

        $this->expectException(\Exception::class);

        $populateModulesService = new PopulateModulesTable;
        $populateModulesService->execute($request);

        $request = [
            'migrate_existing_data' => false,
        ];

        $this->expectException(ValidationException::class);

        app(PopulateModulesTable::class)->execute($request);
    }

    /** @test */
    public function it_populate_modules_tables()
    {
        $account = factory(Account::class)->create([]);
        $user = factory(User::class)->create([
            'account_id' => $account->id,
        ]);

        DB::table('default_contact_modules')
            ->where('key', 'work_education')
            ->update(['migrated' => 0]);

        $request = [
            'account_id' => $account->id,
            'migrate_existing_data' => 1,
        ];

        app(PopulateModulesTable::class)->execute($request);

        // by defauult there is 18 columns in the default table.
        // therefore, we need 18 entries for the new account.
        $this->assertEquals(
            18,
            DB::table('modules')->where('account_id', $account->id)->get()->count()
        );

        // make sure tables have been set to migrated = 1
        $this->assertDatabaseMissing('default_contact_modules', [
            'migrated' => 0,
        ]);
    }

    /** @test */
    public function it_only_populates_module_tables_partially()
    {
        $account = factory(Account::class)->create([]);
        $user = factory(User::class)->create([
            'account_id' => $account->id,
        ]);

        DB::table('default_contact_modules')
            ->update(['migrated' => 0]);

        DB::table('default_contact_modules')
            ->where('key', 'love_relationships')
            ->update(['migrated' => 1]);

        // we will only migrate the ones that haven't been populated yet
        $request = [
            'account_id' => $account->id,
            'migrate_existing_data' => 0,
        ];

        app(PopulateModulesTable::class)->execute($request);

        // by defauult there is 18 columns in the default table.
        // therefore, we need 17 entries for the new account.
        $this->assertEquals(
            17,
            DB::table('modules')->where('account_id', $account->id)->get()->count()
        );
    }
}
