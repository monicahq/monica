<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stripe_events', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->string('stripe_id')->unique()->comment('id of the Stripe event, evt_..., so an event Stripe sends again is acted on once');
            $table->string('type')->comment('type of the Stripe event, such as customer.subscription.deleted');
            $table->timestamps();
        });
    }
};
