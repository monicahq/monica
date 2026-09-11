<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_channels', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('account_id')->comment('account the channel belongs to');
            $table->string('channel', 32)->comment('which channel this row is about, a NotificationChannelEnum value');
            $table->boolean('is_enabled')->default(false)->comment('whether the account wants anything to go out on it');
            $table->string('destination')->nullable()->comment('the room, the conversation or the number things are sent to');
            $table->string('verification_token_hash', 64)->nullable()->unique()->comment('the hash of what a channel that hands us its own destination quotes back, so the token itself is never kept');
            $table->timestamp('proven_at')->nullable()->comment('when the destination was shown to be the account\'s own');
            $table->string('proof_code_hash')->nullable()->comment('the code that was sent, hashed, since it is only ever compared');
            $table->timestamp('proof_expires_at')->nullable()->comment('after which the code no longer counts');
            $table->unsignedInteger('proof_attempts')->default(0)->comment('wrong guesses since the code was sent');
            $table->unsignedInteger('proof_sent_count')->default(0)->comment('how many codes have been sent for the current destination');
            $table->timestamp('proof_last_sent_at')->nullable()->comment('when the last code went out');
            $table->unsignedInteger('consecutive_failures')->default(0)->comment('refusals in a row, reset by any success');
            $table->string('disabled_reason')->nullable()->comment('why the product switched the channel off, rather than the account');
            $table->timestamps();

            $table->unique(['account_id', 'channel']);

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
        });
    }
};
