<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUuids extends Migration
{
    /**
     * @var array
     */
    private $tables = [
        'accounts',
        'users',
        'activities',
        'activity_types',
        'activity_type_categories',
        'contact_field_types',
        'photos',
        'documents',
        'life_events',
        'life_event_categories',
        'life_event_types',
        'addresses',
        'addressbooks',
        'addressbook_subscriptions',
        'entries',
        'days',
        'calls',
        'contact_fields',
        'conversations',
        'debts',
        'gifts',
        'messages',
        'notes',
        'pets',
        'reminders',
        'relationships',
        'genders',
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach ($this->tables as $name) {
            if (! Schema::hasColumn($name, 'uuid')) {
                Schema::table($name, function (Blueprint $table) use ($name) {
                    $table->uuid('uuid')->after('id')->nullable();
                    if ($name !== 'accounts') {
                        if (Schema::hasColumn('table', 'column')) {
                        }
                        $table->index(['account_id', 'uuid']);
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        foreach ($this->tables as $name) {
            if (Schema::hasColumn($name, 'uuid')) {
                Schema::table($name, function (Blueprint $table) use ($name) {
                    if ($name !== 'accounts') {
                        try {
                            //$table->dropIndex(['account_id', 'uuid']);
                        } catch (\Exception $e) {
                            //
                        }
                    }
                    $table->dropColumn('uuid');
                });
            }
        }
    }
}
