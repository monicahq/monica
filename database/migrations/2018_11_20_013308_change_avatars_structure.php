<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeAvatarsStructure extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('avatar_source')->after('food_preferencies')->default('adorable');
            $table->string('avatar_gravatar_url', 250)->after('avatar_source')->nullable();
            $table->string('avatar_adorable_url', 250)->after('avatar_gravatar_url')->nullable();
            $table->integer('avatar_photo_id')->after('avatar_gravatar_url')->nullable();
        });
    }
}
