<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->datetime('access_granted_at')->nullable()->comment('when an administrator granted full access, null when nobody did');
            $table->string('billing_email')->nullable()->comment('email address the invoices are sent to');
            $table->unsignedBigInteger('storage_used_in_bytes')->default(0)->comment('sum of the size of every file the account holds');
            $table->unsignedBigInteger('storage_limit_in_bytes')->nullable()->comment('ceiling above which no new file is accepted, null when the account is not metered');
            $table->string('stripe_id')->nullable()->index()->comment('customer identifier at Stripe, null until the account first reaches checkout');
            $table->string('pm_type')->nullable()->comment('brand of the card on file, as Stripe names it');
            $table->string('pm_last_four', 4)->nullable()->comment('last four digits of that card');
            $table->timestamp('trial_ends_at')->nullable()->comment('when a generic trial ends, never set since no offer has one');
            $table->datetime('onboarding_completed_at')->nullable()->comment('when the account finished the first run');
            $table->timestamps();
        });
    }
};
