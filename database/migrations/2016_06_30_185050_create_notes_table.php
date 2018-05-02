<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->integer('people_id');
            $table->enum('type', ['activity', 'phone_call', 'note', 'gift_idea']);
            $table->string('title')->nullable();
            $table->mediumText('body');
            $table->integer('activity_type_id')->nullable();
            $table->dateTime('activity_date')->nullable();
            $table->string('sticky')->default('false');
            $table->string('remind_for_next_call_or_email')->default('false');
            $table->integer('author_id');
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
        Schema::drop('notes');
    }
}
