<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDefaultActivityTypeGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // create new default activity type groups row
        Schema::create('default_activity_type_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('translation_key');
            $table->timestamps();
        });

        Schema::create('default_activity_types', function ($table) {
            $table->increments('id');
            $table->integer('default_activity_type_category_id');
            $table->string('translation_key');
            $table->timestamps();
        });

        // SIMPLE ACTIVITIES
        DB::table('default_activity_type_categories')->insert([
            'key' => 'simple_activities',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('default_activity_types')->insert([
            'key' => 'just_hung_out',
            'location_type' => 'outside',
            'icon' => 'hang_out',
            'default_activity_type_category_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('default_activity_types')->insert([
            'key' => 'watched_movie_at_home',
            'location_type' => 'my_place',
            'icon' => 'movie_home',
            'default_activity_type_category_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('default_activity_types')->insert([
            'key' => 'talked_at_home',
            'location_type' => 'my_place',
            'icon' => 'talk_home',
            'default_activity_type_category_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // SPORT
        DB::table('default_activity_type_categories')->insert([
            'key' => 'sport',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('default_activity_types')->insert([
            'key' => 'did_sport_activities_together',
            'location_type' => 'outside',
            'icon' => 'sport',
            'default_activity_type_category_id' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // FOOD
        DB::table('default_activity_type_categories')->insert([
            'key' => 'food',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('default_activity_types')->insert([
            'key' => 'ate_at_his_place',
            'location_type' => 'his_place',
            'icon' => 'ate_his_place',
            'default_activity_type_category_id' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('default_activity_types')->insert([
            'key' => 'went_bar',
            'location_type' => 'outside',
            'icon' => 'bar',
            'default_activity_type_category_id' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('default_activity_types')->insert([
            'key' => 'ate_at_home',
            'location_type' => 'my_place',
            'icon' => 'ate_home',
            'default_activity_type_category_id' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('default_activity_types')->insert([
            'key' => 'picknicked',
            'location_type' => 'outside',
            'icon' => 'picknicked',
            'default_activity_type_category_id' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('default_activity_types')->insert([
            'key' => 'ate_restaurant',
            'location_type' => 'outside',
            'icon' => 'restaurant',
            'default_activity_type_category_id' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // CULTURAL
        DB::table('default_activity_type_categories')->insert([
            'key' => 'cultural_activities',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('default_activity_types')->insert([
            'key' => 'went_theater',
            'location_type' => 'outside',
            'icon' => 'theater',
            'default_activity_type_category_id' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('default_activity_types')->insert([
            'key' => 'went_concert',
            'location_type' => 'outside',
            'icon' => 'concert',
            'default_activity_type_category_id' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('default_activity_types')->insert([
            'key' => 'went_play',
            'location_type' => 'outside',
            'icon' => 'play',
            'default_activity_type_category_id' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('default_activity_types')->insert([
            'key' => 'went_museum',
            'location_type' => 'outside',
            'icon' => 'museum',
            'default_activity_type_category_id' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // in order to migrate all the activity types id, it'll be easier to
        // create a temp column to associate activities with activity types
        // through a label instead of an id, as the id will be different for each
        // account.
        Schema::table('activities', function (Blueprint $table) {
            $table->string('activity_type_label');
        });

        DB::table('activities')->orderBy('id')->chunk(100, function ($activities) {
            foreach ($activities as $activity) {
              $activity->activity_type_label = DB::table('activity_types')
                                                ->where('id', $activity->activity_type_id)
                                                ->first()
                                                ->key;
              $activity->save();
            }
        });

        // Creating temp tables as the new ones will have different columns
        // than the originals
        Schema::drop('activity_type_groups');
        Schema::drop('activity_types');

        Schema::create('activity_type_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->string('name')->nullable();
            $table->string('translation_key')->nullable();
            $table->timestamps();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });

        Schema::create('activity_types', function ($table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('activity_type_category_id');
            $table->string('name')->nullable();
            $table->string('translation_key')->nullable();
            $table->timestamps();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('activity_type_category_id')->references('id')->on('activity_type_categories')->onDelete('cascade');
        });

        $defaultActivityTypeCategories = DB::table('default_activity_type_categories')->get();

        DB::table('accounts')->orderBy('id')->chunk(100, function ($accounts) {
            foreach ($accounts as $account) {
                foreach ($defaultActivityTypeCategories as $defaultActivityTypeCategory) {
                    $activityTypeCategoryId = DB::table('activity_type_categories')->insertGetId([
                        'account_id' => $account->id,
                        'translation_key' => $defaultActivityTypeCategory->translation_key,
                    ]);

                    $defaultActivityTypes = DB::table('default_activity_types')
                                                ->where('default_activity_type_category_id', $defaultActivityTypeCategory->id)
                                                ->get();

                    foreach ($defaultActivityTypes as $defaultActivityType) {
                      DB::table('activity_types')->insert([
                          'account_id' => $account->id,
                          'activity_type_category_id' => $activityTypeCategoryId,
                          'translation_key' => $defaultActivityType->translation_key,
                      ]);
                    }
                }
            }
        });

        // final step
        DB::table('activities')->orderBy('id')->chunk(100, function ($activities) {
            foreach ($activities as $activity) {
                $activityLabel = $activity->activity_type_label;

                $activityType = DB::table('activity_types')->where('account_id', $activity->account_id)
                                                            ->where('translation_key', $activity->activity_type_label)
                                                            ->first();

                $activity->activity_type_id = $activityType->id;
                $activity->save();
            }
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn('activity_type_label');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('default_activity_type_categories');
        Schema::dropIfExists('default_activity_types');
    }
}
