<?php

declare(strict_types=1);

use App\Models\Account;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->foreignIdFor(Account::class)->comment('account the preferences belong to')->constrained()->cascadeOnDelete();
            $table->boolean('quiet_hours_are_enabled')->default(false)->comment('whether quiet hours apply at all');
            $table->time('quiet_hours_start')->nullable()->comment('local time from which nothing may be delivered');
            $table->time('quiet_hours_end')->nullable()->comment('local time from which delivery may resume');
            $table->string('delivery_mode', 32)->default('immediate')->comment('whether each notification goes out on its own or is folded into a summary, a NotificationDeliveryModeEnum value');
            $table->time('digest_time')->nullable()->comment('local time the daily summary is sent');
            $table->json('digest_weekdays')->nullable()->comment('days the summary is sent, so weekends can be spared');
            $table->string('timezone', 64)->comment('zone quiet hours and the digest are read in');
            $table->json('type_overrides')->nullable()->comment('per type exceptions, so one kind of notification can bypass or be silenced');
            $table->timestamps();

            $table->unique('account_id');
        });
    }
};
