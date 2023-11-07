<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveViewedAtFromContacts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('contacts', 'viewed_at')) {
            Schema::table('contacts', function (Blueprint $table) {
                $table->dropColumn('viewed_at');
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
        if (! Schema::hasColumn('contacts', 'viewed_at')) {
            Schema::table('contacts', function (Blueprint $table) {
                $table->dateTime('viewed_at')->nullable();
            });
        }
    }
}
