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
            $table->boolean('is_self_hosted')->default(false)->comment('whether the account runs on a self hosted instance instead of the cloud one');
            $table->boolean('has_lifetime_access')->default(false)->comment('whether the one time purchase unlocking the full product was made');
            $table->datetime('purchased_at')->nullable()->comment('when the one time purchase was made');
            $table->string('billing_email')->nullable()->comment('email address the invoices are sent to');
            $table->unsignedBigInteger('storage_used_in_bytes')->default(0)->comment('sum of the size of every file the account holds');
            $table->unsignedBigInteger('storage_limit_in_bytes')->nullable()->comment('ceiling above which no new file is accepted, null when the account is not metered');
            $table->datetime('onboarding_completed_at')->nullable()->comment('when the account finished the first run');
            $table->timestamps();
        });
    }
};
