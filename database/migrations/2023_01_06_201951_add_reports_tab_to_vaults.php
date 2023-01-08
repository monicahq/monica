<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vaults', function (Blueprint $table) {
            $table->boolean('show_reports_tab')->default(true)->after('show_companies_tab');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vaults', function (Blueprint $table) {
            $table->dropColumn('show_reports_tab');
        });
    }
};
