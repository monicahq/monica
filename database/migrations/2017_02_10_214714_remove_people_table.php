<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemovePeopleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::drop('peoples');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('peoples', function (Blueprint $table) {
            $table->increments('id');
            $table->string('api_id');
            $table->integer('account_id');
            $table->enum('type', ['entity', 'contact']);
            $table->integer('object_id');
            $table->string('sortable_name')->nullable()->after('object_id');
            $table->string('has_kids')->default('false')->after('object_id')->nullable();
            $table->integer('number_of_kids')->after('has_kids')->nullable();
            $table->dateTime('last_talked_to')->nullable();
            $table->dateTime('viewed_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }
}
