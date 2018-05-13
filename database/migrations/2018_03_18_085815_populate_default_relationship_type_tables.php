<?php

use Illuminate\Support\Facades\DB;
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

        DB::table('default_relationship_types')->insert([
        [
            'name' => 'partner',
            'name_reverse_relationship' => 'partner',
            'relationship_type_group_id' => $id,
        ],
        [
            'name' => 'spouse',
            'name_reverse_relationship' => 'spouse',
            'relationship_type_group_id' => $id,
        ],
        [
            'name' => 'date',
            'name_reverse_relationship' => 'date',
            'relationship_type_group_id' => $id,
        ],
        [
            'name' => 'lover',
            'name_reverse_relationship' => 'lover',
            'relationship_type_group_id' => $id,
        ],
        [
            'name' => 'inlovewith',
            'name_reverse_relationship' => 'lovedby',
            'relationship_type_group_id' => $id,
        ],
        [
            'name' => 'lovedby',
            'name_reverse_relationship' => 'inlovewith',
            'relationship_type_group_id' => $id,
        ], ]);

        DB::table('default_relationship_types')->insertGetId([
            'name' => 'ex',
            'name_reverse_relationship' => 'ex',
            'relationship_type_group_id' => $id,
        ]);

        // Family type
        $id = DB::table('default_relationship_type_groups')->insertGetId([
            'name' => 'family',
        ]);

        DB::table('default_relationship_types')->insert([
        [
            'name' => 'parent',
            'name_reverse_relationship' => 'child',
            'relationship_type_group_id' => $id,
        ],
        [
            'name' => 'child',
            'name_reverse_relationship' => 'parent',
            'relationship_type_group_id' => $id,
        ],
        [
            'name' => 'sibling',
            'name_reverse_relationship' => 'sibling',
            'relationship_type_group_id' => $id,
        ],
        [
            'name' => 'grandparent',
            'name_reverse_relationship' => 'grandchild',
            'relationship_type_group_id' => $id,
        ],
        [
            'name' => 'grandchild',
            'name_reverse_relationship' => 'grandparent',
            'relationship_type_group_id' => $id,
        ],
        [
            'name' => 'uncle',
            'name_reverse_relationship' => 'nephew',
            'relationship_type_group_id' => $id,
        ],
        [
            'name' => 'nephew',
            'name_reverse_relationship' => 'uncle',
            'relationship_type_group_id' => $id,
        ],
        [
            'name' => 'cousin',
            'name_reverse_relationship' => 'cousin',
            'relationship_type_group_id' => $id,
        ],
        [
            'name' => 'godfather',
            'name_reverse_relationship' => 'godson',
            'relationship_type_group_id' => $id,
        ],
        [
            'name' => 'godson',
            'name_reverse_relationship' => 'godfather',
            'relationship_type_group_id' => $id,
        ], ]);

        // Friend
        $id = DB::table('default_relationship_type_groups')->insertGetId([
            'name' => 'friend',
        ]);

        DB::table('default_relationship_types')->insert([
        [
            'name' => 'friend',
            'name_reverse_relationship' => 'friend',
            'relationship_type_group_id' => $id,
        ],
        [
            'name' => 'bestfriend',
            'name_reverse_relationship' => 'bestfriend',
            'relationship_type_group_id' => $id,
        ], ]);

        // Work
        $id = DB::table('default_relationship_type_groups')->insertGetId([
            'name' => 'work',
        ]);

        DB::table('default_relationship_types')->insert([
        [
            'name' => 'colleague',
            'name_reverse_relationship' => 'colleague',
            'relationship_type_group_id' => $id,
        ],
        [
            'name' => 'boss',
            'name_reverse_relationship' => 'subordinate',
            'relationship_type_group_id' => $id,
        ],
        [
            'name' => 'subordinate',
            'name_reverse_relationship' => 'boss',
            'relationship_type_group_id' => $id,
        ],
        [
            'name' => 'mentor',
            'name_reverse_relationship' => 'protege',
            'relationship_type_group_id' => $id,
        ],
        [
            'name' => 'protege',
            'name_reverse_relationship' => 'mentor',
            'relationship_type_group_id' => $id,
        ], ]);
    }
}
