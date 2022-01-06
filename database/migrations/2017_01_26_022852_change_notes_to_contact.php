<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeNotesToContact extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->dropColumn(
                'people_id'
            );
        });

        Schema::table('notes', function (Blueprint $table) {
            $table->integer('contact_id')->after('account_id');
        });
    }
}
