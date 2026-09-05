<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid()->unique()->comment('unguessable identifier of the notification, used wherever it is addressed from outside');
            $table->unsignedBigInteger('account_id')->comment('account the notification belongs to');
            $table->unsignedBigInteger('user_id')->comment('user it is for');
            $table->string('type', 64)->comment('what it is about, a NotificationTypeEnum value');
            $table->string('channel', 32)->comment('how it was delivered, a NotificationChannelEnum value');
            $table->string('notifiable_type')->nullable()->comment('class of what it is about, null on a digest, which summarises rather than being about one thing');
            $table->unsignedBigInteger('notifiable_id')->nullable()->comment('id of that object');
            $table->string('title')->comment('the line the user reads first');
            $table->text('body')->comment('the rest of what it says');
            $table->json('payload')->nullable()->comment('data the interface needs to render it and to offer its actions');
            $table->string('locale', 5)->comment('language it was rendered in, so a language bug is visible after the fact');
            $table->timestamp('scheduled_for')->comment('when it was due, copied from the deadline and never recomputed');
            $table->timestamp('held_until')->comment('when it may go out, equal to scheduled_for when nothing holds it, so the sweep is one comparison');
            $table->timestamp('sent_at')->nullable()->comment('when it was handed to the channel');
            $table->timestamp('delivered_at')->nullable()->comment('when the channel confirmed delivery, null when it cannot confirm');
            $table->timestamp('failed_at')->nullable()->comment('when delivery was abandoned');
            $table->string('failure_reason')->nullable()->comment('why nothing was sent');
            $table->unsignedInteger('attempts')->default(0)->comment('how many times the channel was tried, so a bounded retry is visible on the row');
            $table->timestamp('read_at')->nullable()->comment('when the user read it, for the in-app channel');
            $table->unsignedBigInteger('digest_id')->nullable()->comment('summary it was folded into, null when it went out on its own');
            $table->timestamps();

            // The instant is in the key because putting a deadline off moves it, and somebody who
            // asked to be told later has to be told a second time.
            $table->unique(['notifiable_type', 'notifiable_id', 'channel', 'user_id', 'scheduled_for'], 'notifications_told_once_unique');

            $table->index(['held_until', 'sent_at']);
            $table->index(['user_id', 'read_at']);
            $table->index('digest_id');

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('digest_id')->references('id')->on('notifications')->nullOnDelete();
        });
    }
};
