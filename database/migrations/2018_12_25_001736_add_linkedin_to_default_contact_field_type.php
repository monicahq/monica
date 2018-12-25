<?php

use App\Models\Account\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;
use App\Services\Auth\Population\PopulateContactFieldTypesTable;

class AddLinkedinToDefaultContactFieldType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('default_contact_field_types')->insertGetId([
            'name' => 'LinkedIn',
            'fontawesome_icon' => 'fa fa-linkedin-square',
        ]);

        Account::chunk(200, function ($accounts) {
            foreach ($accounts as $account) {
                (new PopulateContactFieldTypesTable)->execute([
                    'account_id' => $account->id,
                    'migrate_existing_data' => false,
                ]);
            }
        });

        DB::table('default_contact_field_types')
            ->update(['migrated' => 1]);
    }
}
