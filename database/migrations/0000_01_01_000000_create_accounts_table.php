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
            $table->string('plan', 20)->comment('plan the account subscribed to');
            $table->boolean('is_self_hosted')->default(false)->comment('whether the account runs on a self hosted instance instead of the cloud one');
            $table->string('billing_email')->nullable()->comment('email address the invoices are sent to');
            $table->datetime('trial_ends_at')->nullable()->comment('when the trial ends');
            $table->timestamps();
        });
    }
};
