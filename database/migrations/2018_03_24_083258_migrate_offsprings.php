<?php

use App\Models\Account\Account;
use Illuminate\Support\Facades\DB;
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
                            'contact_is' => $offspring->is_the_child_of,
                            'relationship_type_name' => 'child',
                            'of_contact' => $offspring->contact_id,
                            'relationship_type_id' => $relationshipChildTypeId,
                        ],
                        [
                            'account_id' => $account->id,
                            'contact_is' => $offspring->contact_id,
                            'relationship_type_name' => 'parent',
                            'of_contact' => $offspring->is_the_child_of,
                            'relationship_type_id' => $relationshipParentTypeId,
                        ],
                    ]);
                }
            }
        });

        Schema::dropIfExists('offsprings');
        Schema::dropIfExists('progenitors');
    }
}
