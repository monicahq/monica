<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNicknameToContacts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contacts', function ($table) {
            $table->dropColumn([
                'surname',
            ]);
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->string('nickname')->nullable()->after('last_name');
        });
    }
}
