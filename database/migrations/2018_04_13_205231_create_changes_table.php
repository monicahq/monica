<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateChangesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('changelogs', function (Blueprint $table) {
            $table->increments('id');
            $table->mediumText('description');
            $table->timestamps();
        });

        Schema::create('changelog_user', function (Blueprint $table) {
            $table->integer('changelog_id');
            $table->integer('user_id');
            $table->boolean('read')->default(0);
            $table->boolean('upvote')->default(0);
            $table->timestamps();
        });
    }
}
