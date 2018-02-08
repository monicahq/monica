<?php

use App\Traits\MigrationHelper;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMoreStatistics extends Migration
{
    use MigrationHelper;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('statistics', function (Blueprint $table) {
            $this->default($table->integer('number_of_activities'), 0)->after('number_of_kids');
            $this->default($table->integer('number_of_addresses'), 0)->after('number_of_activities');
            $this->default($table->integer('number_of_api_calls'), 0)->after('number_of_addresses');
            $this->default($table->integer('number_of_calls'), 0)->after('number_of_api_calls');
            $this->default($table->integer('number_of_contact_fields'), 0)->after('number_of_calls');
            $this->default($table->integer('number_of_contact_field_types'), 0)->after('number_of_contact_fields');
            $this->default($table->integer('number_of_debts'), 0)->after('number_of_contact_field_types');
            $this->default($table->integer('number_of_entries'), 0)->after('number_of_debts');
            $this->default($table->integer('number_of_gifts'), 0)->after('number_of_entries');
            $this->default($table->integer('number_of_oauth_access_tokens'), 0)->after('number_of_notes');
            $this->default($table->integer('number_of_oauth_clients'), 0)->after('number_of_oauth_access_tokens');
            $this->default($table->integer('number_of_offsprings'), 0)->after('number_of_oauth_clients');
            $this->default($table->integer('number_of_progenitors'), 0)->after('number_of_offsprings');
            $this->default($table->integer('number_of_relationships'), 0)->after('number_of_progenitors');
            $this->default($table->integer('number_of_subscriptions'), 0)->after('number_of_relationships');
        });
    }
}
