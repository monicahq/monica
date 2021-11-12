<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUuids extends Migration
{
    private $tables = [
        'accounts',
        'users',
        'activities',
        'activity_types',
        'activity_type_categories',
        'contact_field_types',
        'photos',
        'life_events',
        'life_event_categories',
        'life_event_types',
        'addressbooks',
        'addressbook_subscriptions',
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->uuid('uuid')->after('id')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        foreach ($this->tables as $table) {
            if (Schema::hasColumn($table, 'uuid')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropColumn('uuid');
                });
            }
        }
    }
}
