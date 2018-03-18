<?php

use App\Account;
use App\RelationshipType;
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
        Schema::create('default_relationship_type_groups', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->string('name');
            $table->boolean('delible')->default(0);
            $table->boolean('migrated')->default(0);
            $table->timestamps();
        });

        Schema::create('default_relationship_types', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->string('name');
            $table->string('name_reverse_relationship');
            $table->integer('relationship_type_group_id');
            $table->boolean('delible')->default(0);
            $table->boolean('migrated')->default(0);
            $table->timestamps();
        });

        // Create all default relationship type groups and relationship types
        Account::chunk(200, function ($accounts) {
            foreach ($accounts as $account) {
                // Love type
                $id = DB::table('default_relationship_type_groups')->insertGetId([
                    'account_id' => $account->id,
                    'name' => 'love',
                ]);

                DB::table('default_relationship_types')->insertGetId([
                    'account_id' => $account->id,
                    'name' => 'partner',
                    'name_reverse_relationship' => 'partner',
                    'relationship_type_group_id' => $id,
                    'migrated' => true,
                ]);

                DB::table('default_relationship_types')->insertGetId([
                    'account_id' => $account->id,
                    'name' => 'spouse',
                    'name_reverse_relationship' => 'spouse',
                    'relationship_type_group_id' => $id,
                ]);

                DB::table('default_relationship_types')->insertGetId([
                    'account_id' => $account->id,
                    'name' => 'date',
                    'name_reverse_relationship' => 'date',
                    'relationship_type_group_id' => $id,
                ]);

                DB::table('default_relationship_types')->insertGetId([
                    'account_id' => $account->id,
                    'name' => 'lover',
                    'name_reverse_relationship' => 'lover',
                    'relationship_type_group_id' => $id,
                ]);

                DB::table('default_relationship_types')->insertGetId([
                    'account_id' => $account->id,
                    'name' => 'inlovewith',
                    'name_reverse_relationship' => 'inlovewith',
                    'relationship_type_group_id' => $id,
                ]);

                // Family type
                $id = DB::table('default_relationship_type_groups')->insertGetId([
                    'account_id' => $account->id,
                    'name' => 'family',
                ]);

                DB::table('default_relationship_types')->insertGetId([
                    'account_id' => $account->id,
                    'name' => 'parent',
                    'name_reverse_relationship' => 'child',
                    'relationship_type_group_id' => $id,
                ]);

                DB::table('default_relationship_types')->insertGetId([
                    'account_id' => $account->id,
                    'name' => 'child',
                    'name_reverse_relationship' => 'parent',
                    'relationship_type_group_id' => $id,
                ]);

                DB::table('default_relationship_types')->insertGetId([
                    'account_id' => $account->id,
                    'name' => 'sibling',
                    'name_reverse_relationship' => 'sibling',
                    'relationship_type_group_id' => $id,
                ]);

                DB::table('default_relationship_types')->insertGetId([
                    'account_id' => $account->id,
                    'name' => 'grandparent',
                    'name_reverse_relationship' => 'grandchild',
                    'relationship_type_group_id' => $id,
                ]);

                DB::table('default_relationship_types')->insertGetId([
                    'account_id' => $account->id,
                    'name' => 'grandchild',
                    'name_reverse_relationship' => 'grandparent',
                    'relationship_type_group_id' => $id,
                ]);

                DB::table('default_relationship_types')->insertGetId([
                    'account_id' => $account->id,
                    'name' => 'uncle',
                    'name_reverse_relationship' => 'nephew',
                    'relationship_type_group_id' => $id,
                ]);

                DB::table('default_relationship_types')->insertGetId([
                    'account_id' => $account->id,
                    'name' => 'nephew',
                    'name_reverse_relationship' => 'uncle',
                    'relationship_type_group_id' => $id,
                ]);

                DB::table('default_relationship_types')->insertGetId([
                    'account_id' => $account->id,
                    'name' => 'cousin',
                    'name_reverse_relationship' => 'cousin',
                    'relationship_type_group_id' => $id,
                ]);

                DB::table('default_relationship_types')->insertGetId([
                    'account_id' => $account->id,
                    'name' => 'godfather',
                    'name_reverse_relationship' => 'godson',
                    'relationship_type_group_id' => $id,
                ]);

                DB::table('default_relationship_types')->insertGetId([
                    'account_id' => $account->id,
                    'name' => 'godson',
                    'name_reverse_relationship' => 'godfather',
                    'relationship_type_group_id' => $id,
                ]);

                // Friend
                $id = DB::table('default_relationship_type_groups')->insertGetId([
                    'account_id' => $account->id,
                    'name' => 'friend',
                ]);

                DB::table('default_relationship_types')->insertGetId([
                    'account_id' => $account->id,
                    'name' => 'friend',
                    'name_reverse_relationship' => 'friend',
                    'relationship_type_group_id' => $id,
                ]);

                // Work
                $id = DB::table('default_relationship_type_groups')->insertGetId([
                    'account_id' => $account->id,
                    'name' => 'work',
                ]);

                DB::table('default_relationship_types')->insertGetId([
                    'account_id' => $account->id,
                    'name' => 'colleague',
                    'name_reverse_relationship' => 'colleague',
                    'relationship_type_group_id' => $id,
                ]);

                DB::table('default_relationship_types')->insertGetId([
                    'account_id' => $account->id,
                    'name' => 'boss',
                    'name_reverse_relationship' => 'boss',
                    'relationship_type_group_id' => $id,
                ]);
            }
        });

        // Create new table structure
        Schema::create('relationship_types', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->string('name');
            $table->string('name_reverse_relationship');
            $table->integer('relationship_type_group_id');
            $table->boolean('delible')->default(0);
            $table->timestamps();
        });

        Schema::create('relationship_type_groups', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->string('name');
            $table->boolean('delible')->default(0);
            $table->timestamps();
        });

        Schema::create('custom_relationships', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->integer('relationship_type_id');
            $table->integer('contact_id_main');
            $table->string('relationship_type_name');
            $table->integer('contact_id_secondary');
            $table->timestamps();
        });

        // Populating relationship type fields table
        Account::chunk(200, function ($accounts) {
            foreach ($accounts as $account) {
                $account->populateRelationshipTypesTable();
            }
        });

        // Migrating existing tables to the new Relationship structure
        Account::chunk(200, function ($accounts) {
            foreach ($accounts as $account) {
                $relationship_type_id = $account->getRelationshipTypeByType('partner');

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
        });

        Schema::dropIfExists('relationships');

        Schema::rename('custom_relationships', 'relationships');
    }
}
