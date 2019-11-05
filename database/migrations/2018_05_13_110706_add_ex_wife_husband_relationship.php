<?php

use App\Models\Account\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class AddExWifeHusbandRelationship extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $id = DB::table('default_relationship_type_groups')
            ->where([
                'name' => 'love',
            ])
            ->value('id');

        DB::table('default_relationship_types')->insert([
            'name' => 'ex_husband',
            'name_reverse_relationship' => 'ex_husband',
            'relationship_type_group_id' => $id,
        ]);

        // Add the default relationship type to the account relationship types
        Account::chunk(200, function ($accounts) {
            foreach ($accounts as $account) {
                /* @var Account $account */
                $account->populateRelationshipTypesTable(true);
            }
        });

        DB::table('default_relationship_types')
            ->update(['migrated' => 1]);
    }
}
