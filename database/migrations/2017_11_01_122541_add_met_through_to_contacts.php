<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMetThroughToContacts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('is_first_met_date_approximate');
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->integer('first_met_through_contact_id')->after('phone_number')->nullable();
        });
    }
}
