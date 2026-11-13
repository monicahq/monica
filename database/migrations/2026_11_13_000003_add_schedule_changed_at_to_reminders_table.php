<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reminders', function (Blueprint $table): void {
            $table->timestamp('schedule_changed_at')->nullable()->after('schedule_revision')->comment('when the date, cadence, rhythm, time or zone last changed, which is where a date without a year starts counting from, null until the schedule first changes');
        });
    }
};
