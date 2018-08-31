<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLifeEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('default_life_event_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('translation_key');
            $table->boolean('migrated')->default(0);
            $table->timestamps();
        });

        Schema::create('default_life_event_types', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('default_life_event_category_id');
            $table->string('translation_key');
            $table->boolean('migrated')->default(0);
            $table->timestamps();
            $table->foreign('default_life_event_category_id')->references('id')->on('default_life_event_categories')->onDelete('cascade');
        });

        // Core monica data means that there are some special actions that we do
        // with the data. For those core data, we will warn the user if he
        // deletes them that we won't be able to do special actions with it.
        // Example: let's say user indicates that the contact expects a baby.
        // The system can see that this is linked to a specific action and we
        // could write a special action for it to be reminded in X months to
        // see if the baby is born, for instance.
        Schema::create('life_event_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->string('name');
            $table->boolean('core_monica_data')->default(0);
            $table->timestamps();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });

        Schema::create('life_event_types', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('life_event_category_id');
            $table->string('name');
            $table->boolean('core_monica_data')->default(0);
            $table->timestamps();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('life_event_category_id')->references('id')->on('life_event_categories')->onDelete('cascade');
        });

        Schema::create('life_events', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('contact_id');
            $table->unsignedInteger('life_event_type_id');
            $table->string('name')->nullable();
            $table->mediumText('note')->nullable();
            $table->dateTime('happened_at');
            $table->timestamps();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('life_event_type_id')->references('id')->on('life_event_types')->onDelete('cascade');
        });

        Schema::create('contact_life_event', function (Blueprint $table) {
            $table->unsignedInteger('contact_id');
            $table->unsignedInteger('life_event_id');
            $table->foreign('life_event_id')->references('id')->on('life_events')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
        });

        // POPULATE DEFAULT TABLES
        // WORK AND EDUCATION
        $defaultCategoryId = DB::table('default_life_event_categories')->insertGetId([
            'translation_key' => 'settings.personalization_life_event_category_work_education',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('default_life_event_types')->insert([
            'translation_key' => 'settings.personalization_life_event_type_new_job',
            'default_life_event_category_id' => $defaultCategoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('default_life_event_types')->insert([
            'translation_key' => 'settings.personalization_life_event_type_retirement',
            'default_life_event_category_id' => $defaultCategoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('default_life_event_types')->insert([
            'translation_key' => 'settings.personalization_life_event_type_new_school',
            'default_life_event_category_id' => $defaultCategoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('default_life_event_types')->insert([
            'translation_key' => 'settings.personalization_life_event_type_study_abroad',
            'default_life_event_category_id' => $defaultCategoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('default_life_event_types')->insert([
            'translation_key' => 'settings.personalization_life_event_type_volunteer_work',
            'default_life_event_category_id' => $defaultCategoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('default_life_event_types')->insert([
            'translation_key' => 'settings.personalization_life_event_type_published_book_or_paper',
            'default_life_event_category_id' => $defaultCategoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('default_life_event_types')->insert([
            'translation_key' => 'settings.personalization_life_event_type_military_service',
            'default_life_event_category_id' => $defaultCategoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // HOME LIVING
        $defaultCategoryId = DB::table('default_life_event_categories')->insertGetId([
            'translation_key' => 'settings.personalization_life_event_category_family_relationships',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('default_life_event_types')->insert([
            'translation_key' => 'settings.personalization_life_event_type_first_met',
            'default_life_event_category_id' => $defaultCategoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('default_life_event_types')->insert([
            'translation_key' => 'settings.personalization_life_event_type_new_relationship',
            'default_life_event_category_id' => $defaultCategoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('default_life_event_types')->insert([
            'translation_key' => 'settings.personalization_life_event_type_engagement',
            'default_life_event_category_id' => $defaultCategoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('default_life_event_types')->insert([
            'translation_key' => 'settings.personalization_life_event_type_marriage',
            'default_life_event_category_id' => $defaultCategoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('default_life_event_types')->insert([
            'translation_key' => 'settings.personalization_life_event_type_anniversary',
            'default_life_event_category_id' => $defaultCategoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('default_life_event_types')->insert([
            'translation_key' => 'settings.personalization_life_event_type_expecting_a_baby',
            'default_life_event_category_id' => $defaultCategoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('default_life_event_types')->insert([
            'translation_key' => 'settings.personalization_life_event_type_new_child',
            'default_life_event_category_id' => $defaultCategoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('default_life_event_types')->insert([
            'translation_key' => 'settings.personalization_life_event_type_new_family_member',
            'default_life_event_category_id' => $defaultCategoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('default_life_event_types')->insert([
            'translation_key' => 'settings.personalization_life_event_type_new_pet',
            'default_life_event_category_id' => $defaultCategoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('default_life_event_types')->insert([
            'translation_key' => 'settings.personalization_life_event_type_end_of_relationship',
            'default_life_event_category_id' => $defaultCategoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('default_life_event_types')->insert([
            'translation_key' => 'settings.personalization_life_event_type_loss_of_a_loved_one',
            'default_life_event_category_id' => $defaultCategoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // HOME & LIVING
        $defaultCategoryId = DB::table('default_life_event_categories')->insertGetId([
            'translation_key' => 'settings.personalization_life_event_category_home_living',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('default_life_event_types')->insert([
            'translation_key' => 'settings.personalization_life_event_type_moved',
            'default_life_event_category_id' => $defaultCategoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('default_life_event_types')->insert([
            'translation_key' => 'settings.personalization_life_event_type_bought_a_home',
            'default_life_event_category_id' => $defaultCategoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('default_life_event_types')->insert([
            'translation_key' => 'settings.personalization_life_event_type_home_improvement',
            'default_life_event_category_id' => $defaultCategoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('default_life_event_types')->insert([
            'translation_key' => 'settings.personalization_life_event_type_holidays',
            'default_life_event_category_id' => $defaultCategoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('default_life_event_types')->insert([
            'translation_key' => 'settings.personalization_life_event_type_new_vehicule',
            'default_life_event_category_id' => $defaultCategoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('default_life_event_types')->insert([
            'translation_key' => 'settings.personalization_life_event_type_new_roommate',
            'default_life_event_category_id' => $defaultCategoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // TRAVEL AND EXPERIENCES
        $defaultCategoryId = DB::table('default_life_event_categories')->insertGetId([
            'translation_key' => 'settings.personalization_life_event_category_travel_experiences',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('default_life_event_types')->insert([
            'translation_key' => 'settings.personalization_life_event_type_organ_donor',
            'default_life_event_category_id' => $defaultCategoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('default_life_event_types')->insert([
            'translation_key' => 'settings.personalization_life_event_type_overcame_an_illness',
            'default_life_event_category_id' => $defaultCategoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('default_life_event_types')->insert([
            'translation_key' => 'settings.personalization_life_event_type_quit_a_habit',
            'default_life_event_category_id' => $defaultCategoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('default_life_event_types')->insert([
            'translation_key' => 'settings.personalization_life_event_type_new_eating_habits',
            'default_life_event_category_id' => $defaultCategoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('default_life_event_types')->insert([
            'translation_key' => 'settings.personalization_life_event_type_weight_loss',
            'default_life_event_category_id' => $defaultCategoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('default_life_event_types')->insert([
            'translation_key' => 'settings.personalization_life_event_type_wear_glass_or_contact',
            'default_life_event_category_id' => $defaultCategoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('default_life_event_types')->insert([
            'translation_key' => 'settings.personalization_life_event_type_broken_bone',
            'default_life_event_category_id' => $defaultCategoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('default_life_event_types')->insert([
            'translation_key' => 'settings.personalization_life_event_type_removed_braces',
            'default_life_event_category_id' => $defaultCategoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('default_life_event_types')->insert([
            'translation_key' => 'settings.personalization_life_event_type_surgery',
            'default_life_event_category_id' => $defaultCategoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('default_life_event_types')->insert([
            'translation_key' => 'settings.personalization_life_event_type_dentist',
            'default_life_event_category_id' => $defaultCategoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // HEALTH AND WELLNESS
        $defaultCategoryId = DB::table('default_life_event_categories')->insertGetId([
            'translation_key' => 'settings.personalization_life_event_category_health_wellness',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('default_life_event_types')->insert([
            'translation_key' => 'settings.personalization_life_event_type_new_sport',
            'default_life_event_category_id' => $defaultCategoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('default_life_event_types')->insert([
            'translation_key' => 'settings.personalization_life_event_type_new_hobby',
            'default_life_event_category_id' => $defaultCategoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('default_life_event_types')->insert([
            'translation_key' => 'settings.personalization_life_event_type_new_instrument',
            'default_life_event_category_id' => $defaultCategoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('default_life_event_types')->insert([
            'translation_key' => 'settings.personalization_life_event_type_new_language',
            'default_life_event_category_id' => $defaultCategoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('default_life_event_types')->insert([
            'translation_key' => 'settings.personalization_life_event_type_tatoo_or_piercing',
            'default_life_event_category_id' => $defaultCategoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('default_life_event_types')->insert([
            'translation_key' => 'settings.personalization_life_event_type_new_license',
            'default_life_event_category_id' => $defaultCategoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('default_life_event_types')->insert([
            'translation_key' => 'settings.personalization_life_event_type_travel',
            'default_life_event_category_id' => $defaultCategoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('default_life_event_types')->insert([
            'translation_key' => 'settings.personalization_life_event_type_achievement_or_award',
            'default_life_event_category_id' => $defaultCategoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('default_life_event_types')->insert([
            'translation_key' => 'settings.personalization_life_event_type_changed_beliefs',
            'default_life_event_category_id' => $defaultCategoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('default_life_event_types')->insert([
            'translation_key' => 'settings.personalization_life_event_type_first_word',
            'default_life_event_category_id' => $defaultCategoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('default_life_event_types')->insert([
            'translation_key' => 'settings.personalization_life_event_type_first_kiss',
            'default_life_event_category_id' => $defaultCategoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
