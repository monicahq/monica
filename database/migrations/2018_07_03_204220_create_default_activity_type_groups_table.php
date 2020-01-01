<?php

use Illuminate\Support\Facades\DB;
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
            $table->string('location_type');
            $table->timestamps();
        });

        // SIMPLE ACTIVITIES
        DB::table('default_activity_type_categories')->insert([
            'translation_key' => 'simple_activities',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('default_activity_types')->insert([
            'translation_key' => 'just_hung_out',
            'location_type' => 'outside',
            'default_activity_type_category_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('default_activity_types')->insert([
            'translation_key' => 'watched_movie_at_home',
            'location_type' => 'my_place',
            'default_activity_type_category_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('default_activity_types')->insert([
            'translation_key' => 'talked_at_home',
            'location_type' => 'my_place',
            'default_activity_type_category_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // SPORT
        DB::table('default_activity_type_categories')->insert([
            'translation_key' => 'sport',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('default_activity_types')->insert([
            'translation_key' => 'did_sport_activities_together',
            'location_type' => 'outside',
            'default_activity_type_category_id' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // FOOD
        DB::table('default_activity_type_categories')->insert([
            'translation_key' => 'food',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('default_activity_types')->insert([
            'translation_key' => 'ate_at_his_place',
            'location_type' => 'his_place',
            'default_activity_type_category_id' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('default_activity_types')->insert([
            'translation_key' => 'went_bar',
            'location_type' => 'outside',
            'default_activity_type_category_id' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('default_activity_types')->insert([
            'translation_key' => 'ate_at_home',
            'location_type' => 'my_place',
            'default_activity_type_category_id' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('default_activity_types')->insert([
            'translation_key' => 'picknicked',
            'location_type' => 'outside',
            'default_activity_type_category_id' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('default_activity_types')->insert([
            'translation_key' => 'ate_restaurant',
            'location_type' => 'outside',
            'default_activity_type_category_id' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // CULTURAL
        DB::table('default_activity_type_categories')->insert([
            'translation_key' => 'cultural_activities',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('default_activity_types')->insert([
            'translation_key' => 'went_theater',
            'location_type' => 'outside',
            'default_activity_type_category_id' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('default_activity_types')->insert([
            'translation_key' => 'went_concert',
            'location_type' => 'outside',
            'default_activity_type_category_id' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('default_activity_types')->insert([
            'translation_key' => 'went_play',
            'location_type' => 'outside',
            'default_activity_type_category_id' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('default_activity_types')->insert([
            'translation_key' => 'went_museum',
            'location_type' => 'outside',
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

        DB::table('activities')
            ->where('activity_type_id', 0)
            ->update(['activity_type_id' => null]);

        DB::table('activities')->whereNotNull('activity_type_id')->orderBy('id')->chunk(100, function ($activities) {
            foreach ($activities as $activity) {
                $activityType = DB::table('activity_types')
                                            ->where('id', $activity->activity_type_id)
                                            ->first();

                DB::table('activities')
                    ->where('id', $activity->id)
                    ->update(['activity_type_label' => $activityType->key]);
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
            $table->string('location_type')->nullable();
            $table->timestamps();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('activity_type_category_id')->references('id')->on('activity_type_categories')->onDelete('cascade');
        });

        $defaultActivityTypeCategories = DB::table('default_activity_type_categories')->get();

        DB::table('accounts')->orderBy('id')->chunk(100, function ($accounts) use ($defaultActivityTypeCategories) {
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
        DB::table('activities')->orderBy('id')->where('activity_type_label', '!=', '')->chunk(100, function ($activities) {
            foreach ($activities as $activity) {
                $activityLabel = $activity->activity_type_label;

                $activityType = DB::table('activity_types')->where('account_id', $activity->account_id)
                                                            ->where('translation_key', $activity->activity_type_label)
                                                            ->first();

                DB::table('activities')
                    ->where('id', $activity->id)
                    ->update(['activity_type_id' => $activityType->id]);
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
        Schema::dropIfExists('activity_type_categories');
    }
}
