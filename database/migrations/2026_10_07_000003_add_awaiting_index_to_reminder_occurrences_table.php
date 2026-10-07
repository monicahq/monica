<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reminder_occurrences', function (Blueprint $table): void {
            $table->index(['vault_id', 'answer', 'cancelled_at', 'local_date'], 'reminder_occurrences_awaiting_index');
        });
    }
};
