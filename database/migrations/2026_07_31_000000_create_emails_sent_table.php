<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('emails_sent', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('account_id')->comment('account the email was sent on behalf of');
            $table->unsignedBigInteger('user_id')->nullable()->comment('user who received the email, null when the system sent it to someone who has no account');
            $table->string('uuid')->nullable()->comment('identifier given to the email by Resend');
            $table->string('email_type')->comment('type of email sent');
            $table->string('email_address')->comment('address the email was sent to');
            $table->text('subject')->nullable()->comment('subject line of the email');
            $table->text('body')->nullable()->comment('body of the email, with the links stripped');
            $table->datetime('sent_at')->nullable()->comment('when the email was sent');
            $table->datetime('delivered_at')->nullable()->comment('when the email was delivered');
            $table->datetime('bounced_at')->nullable()->comment('when the email bounced, or when the provider never managed to send it');
            $table->datetime('complained_at')->nullable()->comment('when the recipient marked the email as spam');
            $table->timestamps();

            $table->index('uuid');

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }
};
