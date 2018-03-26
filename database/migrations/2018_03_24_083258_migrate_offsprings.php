<?php

use App\Account;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

class MigrateOffsprings extends Migration
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
                $relationshipChildTypeId = $account->getRelationshipTypeByType('child')->id;
                $relationshipParentTypeId = $account->getRelationshipTypeByType('parent')->id;
                $offsprings = DB::table('offsprings')->where('account_id', $account->id)->get();

                foreach ($offsprings as $offspring) {
                    DB::table('relationships')->insert([
                        [
                            'account_id' => $account->id,
                            'contact_id_main' => $offspring->is_the_child_of,
                            'contact_id_secondary' => $offspring->contact_id,
                            'relationship_type_id' => $relationshipChildTypeId,
                            'relationship_type_name' => 'child',
                        ],
                        [
                            'account_id' => $account->id,
                            'contact_id_main' => $offspring->contact_id,
                            'contact_id_secondary' => $offspring->is_the_child_of,
                            'relationship_type_id' => $relationshipParentTypeId,
                            'relationship_type_name' => 'parent',
                        ],
                    ]);
                }
            }
        });

        Schema::dropIfExists('offsprings');
        Schema::dropIfExists('progenitors');
    }
}
