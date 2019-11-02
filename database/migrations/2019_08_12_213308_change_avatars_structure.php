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
            $table->string('avatar_source')->after('food_preferences')->default('default');
            $table->string('avatar_gravatar_url', 250)->after('avatar_source')->nullable();
            $table->uuid('avatar_adorable_uuid')->after('avatar_gravatar_url')->nullable();
            $table->string('avatar_adorable_url', 250)->after('avatar_adorable_uuid')->nullable();
            $table->string('avatar_default_url', 250)->after('avatar_adorable_url')->nullable();
            $table->integer('avatar_photo_id')->after('avatar_default_url')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('avatar_source');
            $table->dropColumn('avatar_gravatar_url');
            $table->dropColumn('avatar_adorable_uuid');
            $table->dropColumn('avatar_adorable_url');
            $table->dropColumn('avatar_default_url');
            $table->dropColumn('avatar_photo_id');
        });
    }
}
