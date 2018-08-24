<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLegacyFreePlan extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->boolean('legacy_free_plan_unlimited_contacts')->default(false)->after('trial_ends_at');
        });

        DB::table('accounts')->update(['legacy_free_plan_unlimited_contacts' => true]);
    }
}
