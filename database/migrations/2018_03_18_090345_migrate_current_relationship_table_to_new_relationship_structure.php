<?php

use App\Account;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MigrateCurrentRelationshipTableToNewRelationshipStructure extends Migration
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
                $relationship_type_id = $account->getRelationshipTypeByType('partner');

// @TODO le probleme : pour une relation bilaterale, il cree 4 enregistrements et non deux. Bug à fixer.

                $relationships = DB::table('relationships')->where('account_id', '=', $account->id)->get();
                foreach ($relationships as $relationship) {
                    DB::table('temp_relationships_table')->insertGetId([
                        'account_id' => $account->id,
                        'contact_id_main' => $relationship->contact_id,
                        'contact_id_secondary' => $relationship->with_contact_id,
                        'relationship_type_id' => $relationship_type_id,
                        'relationship_type_name' => 'partner',
                    ]);

                    DB::table('temp_relationships_table')->insertGetId([
                        'account_id' => $account->id,
                        'contact_id_main' => $relationship->with_contact_id,
                        'contact_id_secondary' => $relationship->contact_id,
                        'relationship_type_id' => $relationship_type_id,
                        'relationship_type_name' => 'partner',
                    ]);
                }
            }
        });

        Schema::dropIfExists('relationships');

        Schema::rename('temp_relationships_table', 'relationships');
    }
}
