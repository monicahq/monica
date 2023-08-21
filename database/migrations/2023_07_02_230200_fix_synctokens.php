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
        if (Schema::hasTable('synctokens')) {
            Schema::table('synctokens', function (Blueprint $table) {
                $table->unsignedBigInteger('id')->change();
            });
            Schema::rename('synctokens', 'sync_tokens');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        if (Schema::hasTable('sync_tokens')) {
            Schema::table('sync_tokens', function (Blueprint $table) {
                $table->unsignedInteger('id')->change();
            });
            Schema::rename('sync_tokens', 'synctokens');
        }
    }
};
