<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->boolean('can_be_deleted')->default(true)->after('name_translation_key');
        });
    }

    public function down()
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->dropColumn('can_be_deleted');
        });
    }
};
