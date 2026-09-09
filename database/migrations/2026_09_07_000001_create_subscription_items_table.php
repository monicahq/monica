<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_items', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('subscription_id')->comment('subscription the line belongs to');
            $table->string('stripe_id')->unique()->comment('subscription item identifier at Stripe');
            $table->string('stripe_product')->comment('product the price belongs to');
            $table->string('stripe_price')->comment('price the line bills');
            $table->integer('quantity')->nullable()->comment('how many of that price, always one here');
            $table->string('meter_event_name')->nullable()->comment('meter the line reports usage to, unused since nothing here is metered by Stripe');
            $table->string('meter_id')->nullable()->comment('identifier of that meter');
            $table->timestamps();

            $table->index(['subscription_id', 'stripe_price']);

            $table->foreign('subscription_id')->references('id')->on('subscriptions')->cascadeOnDelete();
        });
    }
};
