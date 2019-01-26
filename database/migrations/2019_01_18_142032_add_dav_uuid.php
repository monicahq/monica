<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDavUuid extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->index(['account_id', 'uuid']);
        });
        Schema::table('special_dates', function (Blueprint $table) {
            $table->uuid('uuid')->after('contact_id')->nullable();
            $table->index(['account_id', 'uuid']);
        });
        Schema::table('tasks', function (Blueprint $table) {
            $table->uuid('uuid')->after('contact_id')->nullable();
            $table->index(['account_id', 'uuid']);
        });
        Schema::table('synctoken', function (Blueprint $table) {
            $table->string('name')->after('user_id')->default('contacts');
            $table->index(['account_id', 'user_id', 'name']);
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
            $table->dropIndex(['account_id', 'uuid']);
        });
        Schema::table('special_dates', function (Blueprint $table) {
            $table->dropIndex(['account_id', 'uuid']);
            $table->dropColumn('uuid');
        });
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['account_id', 'uuid']);
            $table->dropColumn('uuid');
        });
        Schema::table('synctoken', function (Blueprint $table) {
            $table->dropIndex(['account_id', 'user_id', 'name']);
            $table->dropColumn('name');
        });
    }
}
