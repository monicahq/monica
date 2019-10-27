<?php

use App\Models\Account\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class PopulateRelationshipTypeTablesWithDefaultValues extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Account::chunk(200, function ($accounts) {
            foreach ($accounts as $account) {
                $account->populateRelationshipTypeGroupsTable();
                $account->populateRelationshipTypesTable();
            }
        });

        DB::table('default_relationship_types')
            ->update(['migrated' => 1]);

        DB::table('default_relationship_type_groups')
            ->update(['migrated' => 1]);
    }
}
