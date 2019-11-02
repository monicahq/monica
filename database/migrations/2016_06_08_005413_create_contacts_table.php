<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->integer('entity_id')->nullable();
            $table->enum('status', ['adult', 'parent', 'kid']);
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('surname')->nullable();
            $table->enum('gender', ['male', 'female']);
            $table->enum('nature_of_relationship', ['friend', 'family', 'friend_of_friend', 'business'])->nullable();
            $table->enum('couple_status', ['married', 'engaged', 'complicated', 'dates', 'single'])->nullable();
            $table->string('is_birthdate_approximate')->default('false')->nullable();
            $table->dateTime('birthdate')->nullable();
            $table->string('warned_about_birthdate')->default('true');
            $table->string('email')->unique()->nullable();
            $table->string('phone_number')->nullable();
            $table->string('twitter_id')->nullable();
            $table->string('instagram_id')->nullable();
            $table->string('is_first_met_date_approximate')->default('false')->nullable();
            $table->dateTime('first_met')->nullable();
            $table->string('first_met_where')->nullable();
            $table->longText('first_met_additional_info')->nullable();
            $table->string('job')->nullable();
            $table->dateTime('last_talked_to')->nullable();
            $table->string('street')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code')->nullable();
            $table->integer('country_id')->nullable();
            $table->longText('food_preferencies')->nullable();
            $table->string('has_kids')->default('false')->nullable();
            $table->integer('first_parent_id')->nullable;
            $table->integer('second_parent_id')->nullable;
            $table->dateTime('viewed_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('kids', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->integer('contact_id_first_parent');
            $table->integer('contact_id_second_parent')->nullable();
            $table->string('was_part_of_entity_id');
            $table->timestamps();
        });

        Schema::create('entities', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->string('name');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('important_dates', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->enum('type', ['entity', 'contact']);
            $table->integer('contact_id');
            $table->dateTime('date_to_remember');
            $table->string('description');
            $table->timestamps();
        });

        Schema::create('gifts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->string('people_id');
            $table->enum('type_of_people', ['parent', 'kid']);
            $table->enum('nature', ['amazon', 'other']);
            $table->integer('amazon_gift_id')->nullable();
            $table->string('occasion');
            $table->string('giving_date')->nullable();
            $table->timestamps();
        });

        Schema::create('countries', function (Blueprint $table) {
            $table->increments('id');
            $table->string('iso');
            $table->string('country');
        });

        Schema::create('peoples', function (Blueprint $table) {
            $table->increments('id');
            $table->string('api_id');
            $table->integer('account_id');
            $table->enum('type', ['entity', 'contact']);
            $table->integer('object_id');
            $table->dateTime('viewed_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('reminders', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->integer('people_id');
            $table->string('title')->nullable();
            $table->longText('description')->nullable();
            $table->string('frequency_type');
            $table->integer('frequency_number')->nullable();
            $table->dateTime('last_triggered')->nullable();
            $table->dateTime('next_expected_date');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('contacts', 'kids', 'important_dates', 'entities', 'gifts', 'note_object', 'notes', 'countries', 'peoples', 'reminders');
    }
}
