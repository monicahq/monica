<?php

namespace Tests\Unit\Services\Auth;

use Tests\TestCase;
use App\Models\User\User;
use App\Models\Account\Account;
use Illuminate\Support\Facades\DB;
use App\Exceptions\MissingParameterException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\Auth\Population\PopulateContactFieldTypesTable;

class PopulateContactFieldTypesTableTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $request = [
            'account_id' => 1,
        ];

        $this->expectException(\Exception::class);
        (new PopulateContactFieldTypesTable)->execute($request);

        $request = [
            'migrate_existing_data' => false,
        ];

        $this->expectException(MissingParameterException::class);
        (new PopulateContactFieldTypesTable)->execute($request);
    }

    public function test_it_populate_contact_field_types_tables()
    {
        $account = factory(Account::class)->create([]);
        $user = factory(User::class)->create([
            'account_id' => $account->id,
        ]);

        $number = DB::table('contact_field_types')
            ->where('account_id', $account->id)
            ->count();

        DB::table('default_contact_field_types')
            ->where('name', 'Phone')
            ->update(['migrated' => 0]);

        $request = [
            'account_id' => $account->id,
            'migrate_existing_data' => false,
        ];

        (new PopulateContactFieldTypesTable)->execute($request);

        $this->assertEquals(
            $number + 1,
            DB::table('contact_field_types')->where('account_id', $account->id)->count()
        );
    }

    public function test_it_only_populates_partially()
    {
        $account = factory(Account::class)->create([]);
        $user = factory(User::class)->create([
            'account_id' => $account->id,
        ]);

        $numberOfDefault = DB::table('default_contact_field_types')
            ->count();

        DB::table('default_contact_field_types')
            ->update(['migrated' => 0]);

        $numberOfContactFieldTypesAssociatedWithAccount = DB::table('contact_field_types')
            ->where('account_id', $account->id)
            ->count();

        $request = [
            'account_id' => $account->id,
            'migrate_existing_data' => true,
        ];

        (new PopulateContactFieldTypesTable)->execute($request);

        $this->assertEquals(
            $numberOfContactFieldTypesAssociatedWithAccount + $numberOfDefault,
            DB::table('contact_field_types')->where('account_id', $account->id)->get()->count()
        );
    }
}
