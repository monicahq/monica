<?php

use Illuminate\Database\Seeder;

class ActivityTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('activity_types')->truncate();
        DB::table('activity_type_groups')->truncate();

        // SIMPLE ACTIVITIES
        DB::table('activity_type_groups')->insert([
            'key' => 'simple_activities',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('activity_types')->insert([
            'key' => 'just_hung_out',
            'location_type' => 'outside',
            'icon' => 'hang_out',
            'activity_type_group_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('activity_types')->insert([
            'key' => 'watched_movie_at_home',
            'location_type' => 'my_place',
            'icon' => 'movie_home',
            'activity_type_group_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('activity_types')->insert([
            'key' => 'talked_at_home',
            'location_type' => 'my_place',
            'icon' => 'talk_home',
            'activity_type_group_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // SPORT
        DB::table('activity_type_groups')->insert([
            'key' => 'sport',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('activity_types')->insert([
            'key' => 'did_sport_activities_together',
            'location_type' => 'outside',
            'icon' => 'sport',
            'activity_type_group_id' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // FOOD
        DB::table('activity_type_groups')->insert([
            'key' => 'food',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('activity_types')->insert([
            'key' => 'ate_at_his_place',
            'location_type' => 'his_place',
            'icon' => 'ate_his_place',
            'activity_type_group_id' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('activity_types')->insert([
            'key' => 'went_bar',
            'location_type' => 'outside',
            'icon' => 'bar',
            'activity_type_group_id' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('activity_types')->insert([
            'key' => 'ate_at_home',
            'location_type' => 'my_place',
            'icon' => 'ate_home',
            'activity_type_group_id' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('activity_types')->insert([
            'key' => 'picknicked',
            'location_type' => 'outside',
            'icon' => 'picknicked',
            'activity_type_group_id' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('activity_types')->insert([
            'key' => 'ate_restaurant',
            'location_type' => 'outside',
            'icon' => 'restaurant',
            'activity_type_group_id' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // CULTURAL
        DB::table('activity_type_groups')->insert([
            'key' => 'cultural_activities',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('activity_types')->insert([
            'key' => 'went_theater',
            'location_type' => 'outside',
            'icon' => 'theater',
            'activity_type_group_id' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('activity_types')->insert([
            'key' => 'went_concert',
            'location_type' => 'outside',
            'icon' => 'concert',
            'activity_type_group_id' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('activity_types')->insert([
            'key' => 'went_play',
            'location_type' => 'outside',
            'icon' => 'play',
            'activity_type_group_id' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('activity_types')->insert([
            'key' => 'went_museum',
            'location_type' => 'outside',
            'icon' => 'museum',
            'activity_type_group_id' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
