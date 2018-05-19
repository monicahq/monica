<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ActivityTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $now = now();
        DB::table('activity_types')->delete();
        DB::table('activity_type_groups')->delete();

        // SIMPLE ACTIVITIES
        $activityTypeGroupId = DB::table('activity_type_groups')->insertGetId([
            'key' => 'simple_activities',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('activity_types')->insert([
            'key' => 'just_hung_out',
            'location_type' => 'outside',
            'icon' => 'hang_out',
            'activity_type_group_id' => $activityTypeGroupId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('activity_types')->insert([
            'key' => 'watched_movie_at_home',
            'location_type' => 'my_place',
            'icon' => 'movie_home',
            'activity_type_group_id' => $activityTypeGroupId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('activity_types')->insert([
            'key' => 'talked_at_home',
            'location_type' => 'my_place',
            'icon' => 'talk_home',
            'activity_type_group_id' => $activityTypeGroupId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // SPORT
        $activityTypeGroupId = DB::table('activity_type_groups')->insertGetId([
            'key' => 'sport',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('activity_types')->insert([
            'key' => 'did_sport_activities_together',
            'location_type' => 'outside',
            'icon' => 'sport',
            'activity_type_group_id' => $activityTypeGroupId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // FOOD
        $activityTypeGroupId = DB::table('activity_type_groups')->insertGetId([
            'key' => 'food',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('activity_types')->insert([
            'key' => 'ate_at_his_place',
            'location_type' => 'his_place',
            'icon' => 'ate_his_place',
            'activity_type_group_id' => $activityTypeGroupId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('activity_types')->insert([
            'key' => 'went_bar',
            'location_type' => 'outside',
            'icon' => 'bar',
            'activity_type_group_id' => $activityTypeGroupId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('activity_types')->insert([
            'key' => 'ate_at_home',
            'location_type' => 'my_place',
            'icon' => 'ate_home',
            'activity_type_group_id' => $activityTypeGroupId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('activity_types')->insert([
            'key' => 'picknicked',
            'location_type' => 'outside',
            'icon' => 'picknicked',
            'activity_type_group_id' => $activityTypeGroupId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('activity_types')->insert([
            'key' => 'ate_restaurant',
            'location_type' => 'outside',
            'icon' => 'restaurant',
            'activity_type_group_id' => $activityTypeGroupId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // CULTURAL
        $activityTypeGroupId = DB::table('activity_type_groups')->insertGetId([
            'key' => 'cultural_activities',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('activity_types')->insert([
            'key' => 'went_theater',
            'location_type' => 'outside',
            'icon' => 'theater',
            'activity_type_group_id' => $activityTypeGroupId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('activity_types')->insert([
            'key' => 'went_concert',
            'location_type' => 'outside',
            'icon' => 'concert',
            'activity_type_group_id' => $activityTypeGroupId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('activity_types')->insert([
            'key' => 'went_play',
            'location_type' => 'outside',
            'icon' => 'play',
            'activity_type_group_id' => $activityTypeGroupId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('activity_types')->insert([
            'key' => 'went_museum',
            'location_type' => 'outside',
            'icon' => 'museum',
            'activity_type_group_id' => $activityTypeGroupId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
