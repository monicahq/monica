<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->renameColumn('stripe_plan', 'stripe_price');
        });
        Schema::table('subscription_items', function (Blueprint $table) {
            $table->renameColumn('stripe_plan', 'stripe_price');
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->renameColumn('card_brand', 'pm_type');
            $table->renameColumn('card_last_four', 'pm_last_four');
        });

        Schema::table('subscription_items', function (Blueprint $table) {
            $table->string('stripe_product')->nullable()->after('stripe_id');
            $table->integer('quantity')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subscription_items', function (Blueprint $table) {
            $table->dropColumn('stripe_product');
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->renameColumn('pm_type', 'card_brand');
            $table->renameColumn('pm_last_four', 'card_last_four');
        });

        Schema::table('subscription_items', function (Blueprint $table) {
            $table->renameColumn('stripe_price', 'stripe_plan');
        });
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->renameColumn('stripe_price', 'stripe_plan');
        });
    }
};
