<?php

use App\Models\Account\Account;
use Illuminate\Support\Facades\DB;
use App\Models\Relationship\RelationshipType;
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
        $defaultRelationshipTypeGroupId = DB::table('default_relationship_type_groups')
            ->where(['name' => 'family'])
            ->value('id');

        DB::table('default_relationship_types')->insert([
            'name' => 'stepparent',
            'name_reverse_relationship' => 'stepchild',
            'relationship_type_group_id' => $defaultRelationshipTypeGroupId,
        ]);
        DB::table('default_relationship_types')->insert([
            'name' => 'stepchild',
            'name_reverse_relationship' => 'stepparent',
            'relationship_type_group_id' => $defaultRelationshipTypeGroupId,
        ]);

        $defaultRelationshipTypes = DB::table('default_relationship_types')
            ->where('migrated', 0)
            ->get();

        // Add the default relationship type to the account relationship types
        Account::chunk(500, function ($accounts) use ($defaultRelationshipTypes) {
            foreach ($accounts as $account) {
                /* @var Account $account */

                $relationshipTypeGroupId = DB::table('relationship_type_groups')
                    ->where([
                        'account_id' => $account->id,
                        'name' => 'family',
                    ])
                    ->value('id');

                if ($relationshipTypeGroupId) {
                    foreach ($defaultRelationshipTypes as $defaultRelationshipType) {
                        RelationshipType::create([
                            'account_id' => $account->id,
                            'name' => $defaultRelationshipType->name,
                            'name_reverse_relationship' => $defaultRelationshipType->name_reverse_relationship,
                            'relationship_type_group_id' => $relationshipTypeGroupId,
                            'delible' => $defaultRelationshipType->delible,
                        ]);
                    }
                }
            }
        });

        DB::table('default_relationship_types')
            ->update(['migrated' => 1]);
    }
}
