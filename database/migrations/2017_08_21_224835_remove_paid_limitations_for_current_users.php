<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemovePaidLimitationsForCurrentUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->boolean('has_access_to_paid_version_for_free')->after('id')->default(false);
        });

        DB::table('accounts')
                ->update(['has_access_to_paid_version_for_free' => true]);
    }
}
