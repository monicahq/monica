<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() != 'sqlite') {
            Schema::table('addressbook_subscriptions', function (Blueprint $table) {
                $table->dropForeign('addressbook_subscriptions_sync_token_id_foreign');
            });
        }

        Schema::table('sync_tokens', function (Blueprint $table) {
            $table->unsignedBigInteger('id', true)->change();
        });

        if (DB::connection()->getDriverName() !== 'sqlite') {
            Schema::table('addressbook_subscriptions', function (Blueprint $table) {
                $table->foreign('sync_token_id')->references('id')->on('sync_tokens')->nullOnDelete();
            });
        }
    }
};
