<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateStripeColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // add index on accounts.stripe_id
        // cut accounts.card_last_four to 4 chars
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('card_last_four', 4)->change();
            $table->index(['stripe_id']);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('stripe_status')->after('stripe_id');
            $table->index(['account_id', 'stripe_status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('stripe_status');
            $table->dropIndex(['account_id', 'stripe_status']);
        });
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropIndex(['stripe_id']);
        });
    }
}
