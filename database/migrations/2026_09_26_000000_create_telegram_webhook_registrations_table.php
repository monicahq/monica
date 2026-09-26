<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_webhook_registrations', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('user_id')->nullable()->comment('administrator who registered the bot, absent when the console did');
            $table->string('url')->comment('the incoming address Telegram was told to call');
            $table->string('token_fingerprint')->comment('one-way hash of the bot token in effect, never the token itself');
            $table->string('secret_fingerprint')->comment('one-way hash of the webhook secret in effect, never the secret itself');
            $table->dateTime('registered_at')->comment('when Telegram accepted the registration');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }
};
