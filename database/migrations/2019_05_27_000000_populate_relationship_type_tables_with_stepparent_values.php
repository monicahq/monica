<?php

use App\Models\Account\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class PopulateRelationshipTypeTablesWithStepparentValues extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add some indexes
        Schema::table('relationship_type_groups', function (Blueprint $table) {
            $table->index(['name']);
        });
        Schema::table('default_relationship_types', function (Blueprint $table) {
            $table->index(['migrated']);
        });

        $defaultRelationshipTypeGroupId = DB::table('default_relationship_type_groups')
            ->where([
                'name' => 'family',
            ])
            ->value('id');

        DB::table('default_relationship_types')->insert([
            'name' => 'stepparent',
            'name_reverse_relationship' => 'stepchild',
            'relationship_type_group_id' => $defaultRelationshipTypeGroupId,
        ],
        [
            'name' => 'stepchild',
            'name_reverse_relationship' => 'stepparent',
            'relationship_type_group_id' => $defaultRelationshipTypeGroupId,
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
