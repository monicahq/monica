<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRelationshipTypeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('relationship_types', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->string('name');
            $table->string('name_reverse_relationship');
            $table->boolean('delible')->default(1);
            $table->timestamps();
        });

        Schema::create('custom_relationships', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->string('relationship_type_id');
            $table->integer('contact_id_main');
            $table->string('relationship_type_name');
            $table->integer('contact_id_secondary');
            $table->timestamps();
        });

        $accounts = DB::table('accounts')->select('id')->get();
        foreach ($accounts as $account) {
            $relationship_type_id = DB::table('relationship_types')->insertGetId([
                'account_id' => $account->id,
                'name' => 'partner',
                'name_reverse_relationship' => 'partner',
                'delible' => 0,
            ]);

            $relationships = DB::table('relationships')->where('account_id', '=', $account->id)->get();
            foreach ($relationships as $relationship) {
                DB::table('custom_relationships')->insertGetId([
                    'account_id' => $account->id,
                    'contact_id_main' => $relationship->contact_id,
                    'contact_id_secondary' => $relationship->with_contact_id,
                    'relationship_type_id' => $relationship_type_id,
                    'relationship_type_name' => 'partner',
                ]);

                DB::table('custom_relationships')->insertGetId([
                    'account_id' => $account->id,
                    'contact_id_main' => $relationship->with_contact_id,
                    'contact_id_secondary' => $relationship->contact_id,
                    'relationship_type_id' => $relationship_type_id,
                    'relationship_type_name' => 'partner',
                ]);
            }
        }

        Schema::dropIfExists('relationships');

        Schema::rename('custom_relationships', 'relationships');
    }
}
