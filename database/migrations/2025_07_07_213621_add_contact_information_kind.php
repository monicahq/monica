<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contact_information', function (Blueprint $table) {
            $table->string('kind')->after('data')->nullable();
            $table->boolean('pref')->after('kind')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contact_information', function (Blueprint $table) {
            $table->dropColumn('kind');
            $table->dropColumn('pref');
        });
    }
};
