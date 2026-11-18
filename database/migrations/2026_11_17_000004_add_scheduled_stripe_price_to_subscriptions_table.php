<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The column was first added by editing create_subscriptions_table, so only databases created after that edit have it.
        if (Schema::hasColumn('subscriptions', 'scheduled_stripe_price')) {
            return;
        }

        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->string('scheduled_stripe_price')->nullable()->after('stripe_price')->comment('price Stripe moves the subscription to at its next renewal, null when no change is scheduled');
        });
    }
};
