<?php

use App\Account;
use App\RelationshipType;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class PopulateDefaultRelationshipTypeTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Create all default relationship type groups and relationship types

        // Love type
        $id = DB::table('default_relationship_type_groups')->insertGetId([
            'name' => 'love',
        ]);

        DB::table('default_relationship_types')->insertGetId([
            'name' => 'partner',
            'name_reverse_relationship' => 'partner',
            'relationship_type_group_id' => $id,
            'migrated' => true,
        ]);

        DB::table('default_relationship_types')->insertGetId([
            'name' => 'spouse',
            'name_reverse_relationship' => 'spouse',
            'relationship_type_group_id' => $id,
        ]);

        DB::table('default_relationship_types')->insertGetId([
            'name' => 'date',
            'name_reverse_relationship' => 'date',
            'relationship_type_group_id' => $id,
        ]);

        DB::table('default_relationship_types')->insertGetId([
            'name' => 'lover',
            'name_reverse_relationship' => 'lover',
            'relationship_type_group_id' => $id,
        ]);

        DB::table('default_relationship_types')->insertGetId([
            'name' => 'inlovewith',
            'name_reverse_relationship' => 'inlovewith',
            'relationship_type_group_id' => $id,
        ]);

        // Family type
        $id = DB::table('default_relationship_type_groups')->insertGetId([
            'name' => 'family',
        ]);

        DB::table('default_relationship_types')->insertGetId([
            'name' => 'parent',
            'name_reverse_relationship' => 'child',
            'relationship_type_group_id' => $id,
        ]);

        DB::table('default_relationship_types')->insertGetId([
            'name' => 'child',
            'name_reverse_relationship' => 'parent',
            'relationship_type_group_id' => $id,
        ]);

        DB::table('default_relationship_types')->insertGetId([
            'name' => 'sibling',
            'name_reverse_relationship' => 'sibling',
            'relationship_type_group_id' => $id,
        ]);

        DB::table('default_relationship_types')->insertGetId([
            'name' => 'grandparent',
            'name_reverse_relationship' => 'grandchild',
            'relationship_type_group_id' => $id,
        ]);

        DB::table('default_relationship_types')->insertGetId([
            'name' => 'grandchild',
            'name_reverse_relationship' => 'grandparent',
            'relationship_type_group_id' => $id,
        ]);

        DB::table('default_relationship_types')->insertGetId([
            'name' => 'uncle',
            'name_reverse_relationship' => 'nephew',
            'relationship_type_group_id' => $id,
        ]);

        DB::table('default_relationship_types')->insertGetId([
            'name' => 'nephew',
            'name_reverse_relationship' => 'uncle',
            'relationship_type_group_id' => $id,
        ]);

        DB::table('default_relationship_types')->insertGetId([
            'name' => 'cousin',
            'name_reverse_relationship' => 'cousin',
            'relationship_type_group_id' => $id,
        ]);

        DB::table('default_relationship_types')->insertGetId([
            'name' => 'godfather',
            'name_reverse_relationship' => 'godson',
            'relationship_type_group_id' => $id,
        ]);

        DB::table('default_relationship_types')->insertGetId([
            'name' => 'godson',
            'name_reverse_relationship' => 'godfather',
            'relationship_type_group_id' => $id,
        ]);

        // Friend
        $id = DB::table('default_relationship_type_groups')->insertGetId([
            'name' => 'friend',
        ]);

        DB::table('default_relationship_types')->insertGetId([
            'name' => 'friend',
            'name_reverse_relationship' => 'friend',
            'relationship_type_group_id' => $id,
        ]);

        // Work
        $id = DB::table('default_relationship_type_groups')->insertGetId([
            'name' => 'work',
        ]);

        DB::table('default_relationship_types')->insertGetId([
            'name' => 'colleague',
            'name_reverse_relationship' => 'colleague',
            'relationship_type_group_id' => $id,
        ]);

        DB::table('default_relationship_types')->insertGetId([
            'name' => 'boss',
            'name_reverse_relationship' => 'boss',
            'relationship_type_group_id' => $id,
        ]);
    }
}
