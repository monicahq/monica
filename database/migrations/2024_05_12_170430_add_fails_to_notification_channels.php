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
        Schema::table('user_notification_channels', function (Blueprint $table) {
            $table->integer('fails')->default(0)->after('active');
        });

        Schema::table('user_notification_sent', function (Blueprint $table) {
            $table->longText('error')->nullable()->after('payload');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_notification_channels', function (Blueprint $table) {
            $table->dropColumn('fails');
        });

        Schema::table('user_notification_sent', function (Blueprint $table) {
            $table->dropColumn('error');
        });
    }
};
