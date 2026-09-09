<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stripe_orders', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('account_id')->comment('account that ordered, the billable model Cashier is told to use');
            $table->string('type')->comment('name the order is addressed by, always default since an account orders one thing');
            $table->string('stripe_id')->unique()->comment('checkout session identifier at Stripe, which is what makes a repeated return recognisable');
            $table->string('stripe_status')->comment('state as Stripe names it, unpaid then paid, refunded or disputed once money goes back');
            $table->string('stripe_price')->comment('price the order was placed against, which is what names the offer bought');
            $table->timestamps();

            $table->index(['account_id', 'stripe_status']);

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
        });
    }
};
