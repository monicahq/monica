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
        if (! Schema::hasColumn('addressbook_subscriptions', 'current_logid')) {
            Schema::table('addressbook_subscriptions', function (Blueprint $table) {
                $table->unsignedBigInteger('current_logid')->after('last_batch')->nullable();
            });
        }
    }
};
