<?php

declare(strict_types=1);

use App\Models\Account;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->foreignIdFor(Account::class)->comment('account the subscription belongs to, the billable model Cashier is told to use')->constrained()->cascadeOnDelete();
            $table->string('type')->comment('name Cashier addresses the subscription by, always default here since an account holds at most one');
            $table->string('stripe_id')->unique()->comment('subscription identifier at Stripe');
            $table->string('stripe_status')->comment('state as Stripe names it, active, past_due or canceled among others');
            $table->string('stripe_price')->nullable()->comment('price the subscription bills, which is what names the offer held');
            $table->string('scheduled_stripe_price')->nullable()->comment('price Stripe moves the subscription to at its next renewal, null when no change is scheduled');
            $table->integer('quantity')->nullable()->comment('how many of that price, always one here');
            $table->timestamp('trial_ends_at')->nullable()->comment('when a trial ends, never set since no offer has one');
            $table->timestamp('ends_at')->nullable()->comment('when a cancelled subscription stops, null while it renews');
            $table->timestamps();

            $table->index(['account_id', 'stripe_status']);
        });
    }
};
